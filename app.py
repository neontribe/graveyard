import os

from flask import Flask

import tweak

app = Flask(__name__)
server_config = tweak.load_config(os.path.sep.join([tweak.get_script_directory(), 'config', 'server']))

if __name__ == '__main__':
    app.run(server_config['host'], server_config['port'], server_config['threading'])
