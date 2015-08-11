import os

from flask import Flask
from flask import render_template

import tweak

app = Flask(__name__)
server_config = tweak.load_config(os.path.sep.join([tweak.get_script_directory(), 'config', 'server']))

@app.route('/', methods=['GET'])
def index():
    return render_template('index.html')

if __name__ == '__main__':
    app.run(server_config['host'], server_config['port'], server_config['threading'])
