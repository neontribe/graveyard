import os
import json

from flask import Flask
from flask import jsonify
from flask import request
from flask import url_for
from flask import redirect
from flask import Response
from flask import render_template

import tweak
from tachyon import bridge

HTTP_NOT_ACCEPTABLE = 406
HTTP_UNPROCESSABLE_ENTITY = 422

app = Flask(__name__)

# TODO: definitively decide what should be cached and what should be loaded
#       every time
server_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'server']))
ansible_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'ansible']))
playbooks_schemas = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'playbooks']))
playbooks_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'playbooksConfig']))

filetree_cache = tweak.load_cache(os.path.sep.join([tweak.get_cache_directory(), 'filetree']))


@app.route('/', methods=['GET'])
def index():
    return redirect(url_for('choose_task'))

@app.route('/choose_task', methods=['GET'])
def choose_task():
    data = {}

    if 'error' in request.args:
        data['error'] = request.args['error']

    data['hosts'] = sorted(bridge.get_host_names(ansible_config['inventory_path']))
    data['playbooks'] = playbooks_schemas['playbooks']

    return render_template('choose_task.html', **data)

@app.route('/setup_task', methods=['POST'])
def setup_task():
    if not 'host' in request.form:
        return redirect(url_for('choose_task', error='The \'host\' parameter is required'))
    host = request.form['host']
    if not host in bridge.get_host_names(ansible_config['inventory_path']):
        return redirect(url_for('choose_task', error='Host \'' + host + '\' does not exist'))

    if not 'playbook' in request.form:
        return redirect(url_for('choose_task', error='The \'playbook\' parameter is required'))
    playbook_name = request.form['playbook']
    for potential_schema in playbooks_schemas['playbooks']:
        if playbook_name == potential_schema['name']:
            playbook_schema = potential_schema
            break
    else:
        return redirect(url_for('choose_task', error='Playbook \'' + playbook_name + '\' does not exist'))

    data = { 'host': host, 'playbook': playbook_schema }

    # TODO: this is the worst way of checking types in a template engine ever,
    #       there must be a better way
    data['is_string'] = lambda obj : type(obj) in [str, unicode]
    data['is_boolean'] = lambda obj : type(obj) == bool
    data['is_list'] = lambda obj : type(obj) == list
    data['is_dictionary'] = lambda obj : type(obj) == dict

    data['helpers'] = {}
    data['helpers']['get_filetree_info'] = get_filetree_info

    return render_template('setup_task.html', **data)

@app.route('/run_playbook', methods=['GET'])
def run_playbook():
    # check the id of the PlayBook schema is present
    if not 'name' in request.args:
        return jsonify(error='The \'name\' parameter is required'), HTTP_UNPROCESSABLE_ENTITY
    name = request.args['name']

    # find the PlayBook schema by the provided name
    playbook_schema = None
    for potential_schema in playbooks_schemas['playbooks']:
        if potential_schema['name'] == name:
            playbook_schema = potential_schema
            break
    else:
        return jsonify(error='There is no PlayBook of the name \'' + name + '\''), HTTP_UNPROCESSABLE_ENTITY

    # parse parameters to populate extra_vars
    extra_vars = {}
    for field in playbook_schema['fields']:
        if not field['name'] in request.args:
            return jsonify(error='Missing required parameter for PlayBook \'' + field['name'] + '\''), HTTP_UNPROCESSABLE_ENTITY
        value = request.args[field['name']]
        return_types = { 'string': str, 'boolean': bool, 'integer': int }
        return_type_caster = return_types[field['returnType']]
        try:
            casted = return_type_caster(value)
        except ValueError as e:
            return jsonify(error='\'' + value + '\' is not a valid \'' + field['returnType'] + '\''), HTTP_UNPROCESSABLE_ENTITY
        extra_vars[field['name']] = casted

    # add PlayBook's configurable variables
    for config_object in playbook_schema['configNodes']:
        extra_vars[config_object['argName']] = playbooks_config[config_object['node']]

    # add PlayBook's constants
    for constant_object in playbook_schema['constants']:
        extra_vars[constant_object['argName']] = config_object['value']

    # work out the values to call tachyon with
    playbook_path = os.path.sep.join([ansible_config['ntdr_pas_path'], 'playbooks', playbook_schema['yaml']])
    inventory_path = ansible_config['inventory_path']

    # check that the provided server both exists and is valid
    if not 'host' in request.args:
        return jsonify(error='The \'host\' parameter is required'), HTTP_UNPROCESSABLE_ENTITY
    potential_hosts = bridge.get_host_names(ansible_config['inventory_path'])
    limit = request.args['host']
    if not limit in potential_hosts:
        return jsonify(error='The host \'' + limit + '\' is not known'), HTTP_UNPROCESSABLE_ENTITY

    # check that the client accepts server-side-events
    if request.headers.get('accept') == 'text/event-stream':
        def events():
            # yield events as they arrive
            for event in bridge.run_playbook(playbook_path, inventory_path, [ limit ], extra_vars):
                yield 'data: ' + json.dumps(event) + '\n'
        # give Flask the event data generator
        return Response(events(), content_type='text/event-stream')
    else:
        # let the client know that we don't dig their Accept header
        return (
            jsonify(error="The response is not of content type text/event-stream and, hence, this must be rejected"),
            HTTP_NOT_ACCEPTABLE
        )
@app.route('/get-filetree', methods=['GET'])
def get_filetree():

    if 'refresh' in request.args:
        if request.args['refresh'].lower() == 'true':
            server_codes = bridge.get_host_names(ansible_config['inventory_path'])
            event_generator = bridge.run_task(
                os.path.sep.join([ansible_config['ntdr_pas_path'], 'playbooks','library','ntdr_get_filetree.py']),
                ansible_config['inventory_path'],
                server_codes,
                { 'path': '/var/www' }
            )

            cached_info = {}
            for event in iter(event_generator):
                print event
                entry = {'meta':{'status':event['event']}}
                if entry['meta']['status'] == 'ok':
                    entry['data'] = event['res']['stat']['files']
                else:
                    entry['data'] = {}

                if event['event'] != 'complete':
                    cached_info[event['host']] = entry


            global filetree_cache
            filetree_cache = cached_info

            filetree_cache_file = open(os.path.sep.join([tweak.get_cache_directory(), 'filetree.cache']),'w')
            filetree_cache_file.write(json.dumps(cached_info))
            filetree_cache_file.close()
            return json.dumps(cached_info)

        else:
            return jsonify(filetree_cache)
    else:
        return jsonify(filetree_cache)

def get_filetree_info(hostname, flat=True):
    if flat:
        if hostname in filetree_cache:
            if filetree_cache[hostname]['data'] != {}:
                return sorted([ x['name'] for x in filetree_cache[hostname]['data']['flat']])
            else:
                return []
        else:
            return 'no such hostname'

    else:
        if hostname in filetree_cache:
            if filetree_cache[hostname]['data'] != {}:
                return filetree_cache[hostname]['data']['path']
            else:
                return []
        else:
            return 'no such hostname'





if __name__ == '__main__':
    app.run(server_config['host'], server_config['port'], server_config['threading'])
