'''
Useful utility functions for loading configuration files.
'''

import os
import json

CREATED_CACHE_EXTENSION = '.json'
CREATED_CONFIG_EXTENSION = '.json'
DEFAULT_CONFIG_EXTENSION = '.default.json'

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


def load_cache(path, default, refresh=False):
    # TODO: don't just swallow errors

    # append path if it is not already a suffix
    if not path.endswith(CREATED_CACHE_EXTENSION):
        path += CREATED_CACHE_EXTENSION

    if not refresh:
        # attempt to read a user created config
        try:
            with open(path, 'r') as cache_file:
                raw = cache_file.read()
                data = json.loads(raw)['data']
                return data
        except IOError as e:
            # the file did not exist, or could not be read
            # we will proceed to attempt to use the default value
            pass
        except ValueError as e:
            # the JSON in the file was malformed
            # we will proceed to attempt to use the default value
            pass

    # attempt to get a current value
    value = default()

    # cache the value
    try:
        with open(path, 'w') as cache_file:
            raw = json.dumps({ 'data': value })
            cache_file.write(raw)
    except IOError as e:
        # the cache file could not be written to
        # this is not a fatal error
        pass

    return value


def get_script_directory():
    # return the directory of this script
    return os.path.dirname(os.path.realpath(__file__))


def get_project_directory():
    # reserved for potential future usage, if this file is moved to a different module
    return get_script_directory()


def get_config_directory():
    # return the directory of config files
    return get_project_directory() + '/config'


def get_cache_directory():
    # return the directory of config files
    return get_project_directory() + '/cache'
