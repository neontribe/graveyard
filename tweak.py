'''
Useful utility functions for loading configuration files.
'''

import os
import json

DEFAULT_CONFIG_EXTENSION = '.default.json'
CREATED_CONFIG_EXTENSION = '.json'

def load_config(path):
    # remove any suffixes, so they can be added programmatically as necessary
    if path.endswith(DEFAULT_CONFIG_EXTENSION):
        path = path[:len(DEFAULT_CONFIG_EXTENSION)]
    elif path.endswith(CREATED_CONFIG_EXTENSION):
        path = path[:len(CREATED_CONFIG_EXTENSION)]

    # attempt to read a user created config
    try:
        with open(path + CREATED_CONFIG_EXTENSION, 'r') as created_config_file:
            raw = created_config_file.read()
            data = json.loads(raw)
            return data
    except IOError as e:
        # the file did not exist, or could not be read
        # we will proceed to attempt to read the default
        pass
    except ValueError as e:
        # the JSON in the file was malformed
        raise ValueError('User created config could not be parsed: ' + str(e))

    # attempt to read the default config
    try:
        with open(path + DEFAULT_CONFIG_EXTENSION, 'r') as default_config_file:
            raw = default_config_file.read()
            data = json.loads(raw)
            return data
    except ValueError as e:
        # the JSON in the file was malformed
        raise ValueError('Default config could not be parsed: ' + str(e))

def get_script_directory():
    # return the directory of this script
    return os.path.dirname(os.path.realpath(__file__))
