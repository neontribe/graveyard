Ansible provision for base system
=================================

Parameters for init:

    php_ppa: [Version]
    sys_packages: [package list]
    timezone: [UTC, Europe/London, etc...]
    hostname: [Hostname]

Sample playbook:

    - hosts: all
      vars:
          php_ppa: php5-5.6
          sys_packages: ["imagemagick","vim","git","htop","ccze","realpath","jpegoptim","optipng","gifsicle","yui-compressor"]
          timezone: UTC
          hostname: devserver01

