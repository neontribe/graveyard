import os
import json

from flask import Flask
from flask import jsonify
from flask import request
from flask import Response
from flask import render_template

import tweak
from tachyon import bridge

HTTP_NOT_ACCEPTABLE = 406
HTTP_UNPROCESSABLE_ENTITY = 422

app = Flask(__name__)
server_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'server']))
ansible_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'ansible']))
playbooks_schemas = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'playbooks']))
playbooks_config = tweak.load_config(os.path.sep.join([tweak.get_config_directory(), 'playbooksConfig']))

@app.route('/', methods=['GET'])
def index():
    return render_template('index.html')

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


if __name__ == '__main__':
    app.run(server_config['host'], server_config['port'], server_config['threading'])
