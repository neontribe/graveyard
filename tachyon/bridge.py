'''This class is designed to be a simplification of Ansible's notably
undocumented API. It also has some explanation of the API used, in the hope of
making days of trawling through Ansible's source code to work out what the heck
was going on worthwhile.

The term "Ansible" is a reference to a communication medium in a Sci-Fi novel
that can use faster-than-light methods to send messages. Tachyons are
unproven theoratical particles that can supposedly exceed the speed of light. So
aren't we clever.
'''

import os
from threading import Thread

from ansible.runner import Runner
from ansible.playbook import PlayBook
from ansible.inventory import Inventory
from ansible import callbacks # don't change the order of this import, somehow that breaks it

from tachyon.queue_callbacks import QueueCallbacks

def _run_yielding_events(ansible_object):
    '''Executes an Ansible PlayBook or Runner and - assuming it uses a
    QueueCallbacks object - yields the events that are created on the calling
    of the callbacks.
    '''

    # used for asynchronously running the Ansible object
    def execute():
        ansible_object.run()
        ansible_object.callbacks.on_complete() # produce our own event to let us know it has finished

    execution_thread = Thread(target=execute)
    execution_thread.start()

    # read from the QueueCallbacks' Queue, getting events as they are put
    for callback_data in iter(ansible_object.callbacks.queue.get, None):
        yield callback_data
        if callback_data['event'] == 'complete':
            break


def run_playbook(playbook_path, inventory_path, limits, extra_vars):
    '''Instantiate and run a PlayBook object with Ansible's API, yielding
    events in the process.

    Parameters:
    * playbook_path     str     The path of the YML PlayBook file.
    * inventory_path    str     The path of the inventory file.
    * limits            str[]   The servers to limit the tasks to (can be None to do all servers).
    * extra_vars        dict    String keys and values of variables to pass into the PlayBook.

    Example Usage:
      event_generator = tachyon.run_playbook(
          '/home/harryd/ntdr-pas/playbooks/pull-full-copy.yml',
          '/home/harryd/ntdr-pas/playbooks/inventory/cottage-servers',
          ['zz_live'],
          { 'source': '/var/www', 'local': '/var/tmp' }
      )

      for event in iter(event_generator):
          print(event)
    '''

    callbacks_object = QueueCallbacks()

    # this is required as an argument, although not currently used
    # it may be useful if further data is required in the future
    stats = callbacks.AggregateStats()

    subset = ':'.join(limits) if limits != None else None

    # see https://github.com/ansible/ansible/blob/74afd2438754a2c94e37c3bfe2804c43b7f6c7f3/lib/ansible/playbook/__init__.py#L55
    # for other parameters and info not made obvious by the code. Unfortunately,
    # as you probably know, Ansible does not have much documentation on this.
    playbook = PlayBook(
        playbook            =   playbook_path,
        host_list           =   inventory_path,
        subset              =   subset,
        extra_vars          =   extra_vars,
        callbacks           =   callbacks_object,
        runner_callbacks    =   callbacks_object,
        forks               =   1, # increasing this has lead to problems with storing events
        stats               =   stats
    )

    for callback_data in iter(_run_yielding_events(playbook)):
        yield callback_data


def run_task(module_path, inventory_path, limits, extra_vars):
    '''Instantiate and run a Runner object with Ansible's API (effectively
    running a task), yielding events in the process.

    Parameters:
    * module_path       str     The path of the .py task file.
    * inventory_path    str     The path of the inventory file.
    * limits            str[]   The servers to limit the task to (can be None to do all servers)
    * extra_vars        dict    String keys and values of variables to pass into the task.

    Example Usage:
      event_generator = tachyon.run_task(
          '/home/harryd/ntdr-pas/playbooks/library/ntdr_get_filetree.py',
          '/home/harryd/ntdr-pas/playbooks/inventory/cottage-servers',
          ['zz_live'],
          { 'path': '/var/www' }
      )

      for event in iter(event_generator):
          print(event)
    '''

    callbacks_object = QueueCallbacks()

    # the Ansible API wants the module's file name and directory passed in
    # separately, for some reason. Something to do with the scope of where it
    # looks for the file, me thinks.
    module_path_parts = module_path.split(os.path.sep)
    module_name = module_path_parts[-1]
    module_parent_directory = None
    if len(module_path_parts) > 1:
        module_parent_directory = os.path.sep.join(module_path_parts[:-1])

    subset = ':'.join(limits) if limits != None else None

    #
    runner = Runner(
        module_name         =   module_name,
        module_path         =   module_parent_directory,
        inventory           =   Inventory(inventory_path),
        subset              =   subset,
        module_args         =   extra_vars,
        callbacks           =   callbacks_object,
        forks               =   1 # increasing this has lead to problems with storing events
    )

    for callback_data in iter(_run_yielding_events(runner)):
        yield callback_data
