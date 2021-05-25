Ansible provision for bashrc
============================

Parameters for bashrc:

    server_type: live|test|desktop

Sample playbook

    - hosts: all
      vars:
        server_type: desktop
