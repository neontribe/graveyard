Ansible provision for Apache
============================

This playbook is for a (cottage) LAMP stack

Parameters for init:

    server_type: [desktop|test|live]
    ssl_passphrase: The passphrase for the private key

Files

Ansible will look in the 

    redirects.conf
    expire_rules.conf
    ssl.crt
    ssl.key
    chain.crt
    000.ssl.conf.tpl
    010.http.conf
    020.wildcard.conf.tpl



Sample playbook:

    - hosts: all
      vars:
          timezone: UTC

