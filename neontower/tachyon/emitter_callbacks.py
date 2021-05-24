class EmitterCallbacks(object):
    def __init__(self, emitter):
        self._emitter = emitter

    def emitter(self, data):
        self._emitter(data)

    ##############
    ### Runner ###
    ##############

    '''
    Called when task in playbook fails.
    '''
    def on_failed(self, host, res, ignore_errors=False):
        return_dict = {'event': 'failed', 'host': host, 'res': res, 'ignore_errors': ignore_errors}
        self.emitter(return_dict)

    '''
    Called when task in playbook is successful.
    '''
    def on_ok(self, host, res):
        return_dict = {'event': 'ok', 'host': host, 'res': res}
        self.emitter(return_dict)

    '''
    Called when task in playbook is skipped due to some flag being set telling the task not to run hence it is skipped.
    **NOTE** This does NOT signify an error simple the task has not been performed.
    '''
    def on_skipped(self, host, item=None):
        return_dict = {'event': 'skipped', 'host': host, 'item': item}
        self.emitter(return_dict)

    '''
    Called when the host of the task that it is attemping to run on is not available.
    '''
    def on_unreachable(self, host, res):
        return_dict = {'event': 'unreachable', 'host': host, 'res': res}
        self.emitter(return_dict)

    '''
    Called when there are no hosts left to perform the actions on.
    **NOTE** This DOES signify an error.
    '''
    def on_no_hosts(self):
        return_dict = {'event': 'no_hosts'}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_async_poll(self, host, res, jid, clock):
        pass

    '''
    TODO.
    '''
    def on_async_ok(self, host, res, jid):
        pass

    '''
    TODO.
    '''
    def on_async_failed(self, host, res, jid):
        pass

    ################
    ### PlayBook ###
    ################

    '''
    Called when the running of the playbook begins.
    '''
    def on_start(self):
        return_dict = {'event': 'start'}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_notify(self, host, handler):
        return_dict = {'event': 'notify', 'host': host, 'handler': handler}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_no_hosts_matched(self):
        return_dict = {'event': 'no_hosts_matched'}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_no_hosts_remaining(self):
        return_dict = {'event': 'no_hosts_remaining'}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_task_start(self, name, is_conditional):
        return_dict = {'event': 'task_start', 'name': name, 'conditional': is_conditional}
        self.emitter(return_dict)


    '''
    Called when the user is meant to be prompted for an input.
    **NOTE** This will NOT be used in our code since we are doing the playbook calls from the website the user won't have a chance to input things.
    '''
    def on_vars_prompt(self, varname, private=True, prompt=None, encrypt=None, confirm=False, salt_size=None, salt=None, default=None):
        return_dict = {'event': 'prompt', 'varname': varname, 'private': private, 'prompt': prompt, 'encrypt': encrypt, 'confirm': confirm, 'salt_size': salt_size, 'salt': salt, 'default': default}
        self.emitter(return_dict)


    '''
    TODO.
    '''
    def on_setup(self):
        return_dict = {'event': 'setup'}
        self.emitter(return_dict)


    '''
    TODO.
    '''
    def on_import_for_host(self, host, imported_file):
        return_dict = {'event': 'import_for_host', 'host': host, 'imported_file': imported_file}
        self.emitter(return_dict)


    '''
    TODO.
    '''
    def on_not_import_for_host(self, host, missing_file):
        return_dict = {'event': 'not_import_for_host', 'host': host, 'missing_file': missing_file}
        self.emitter(return_dict)



    '''
    Called when the running of a Play begins.

    Parameters:
    * name      str     The name of the Play
    '''
    def on_play_start(self, name):
        return_dict = {'event': 'play_start', 'name': name}
        self.emitter(return_dict)

    '''
    TODO.
    '''
    def on_stats(self, stats):
        return_dict = {'event': 'stats', 'stats': stats}
        self.emitter(return_dict)

    '''
    A custom callback, added for usage within tachyon and /not/ called by the
    Ansible API. This callback is called after all attempts at running have
    ceased.
    '''
    def on_complete(self):
        return_dict = {'event': 'complete'}
        self.emitter(return_dict)
