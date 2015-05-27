Cottage Vagrant installer
=========================

Quick start
-----------

This will fail if you haven't fulfilled the dependencies below:

    $ wget -O - -q https://raw.githubusercontent.com/neontribe-ansible/ly/master/install.sh | bash

This will ask for the brandcode, enter it in lower case.


Dependancies
------------

### Virtualbox

At the time of writting this the version in the 14.04 ubuntu repositories is sufficient:

    # sudo apt-get install virtualbox

This will install a version 4.3.26

### Vagrant

Vagrant need to be version 1.7.2 or higher, 14.04 ships with 1.4.3-1:

    $ wget https://dl.bintray.com/mitchellh/vagrant/vagrant_1.7.2_x86_64.deb
    $ sudo dpkg -i vagrant_1.7.2_x86_64.deb

If that fails on dependencies:

    $ sudo apt-get install -f

### Ansible

Ansible should ab at verion 1.8 or higher, 14.04 ships with 1.5.4:

    $ sudo apt-get install software-properties-common
    $ echo deb http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main | sudo tee --append /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list
    $ echo deb-src http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main | sudo tee --append /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list
    $ sudo apt-get update
    $ sudo apt-get install ansible



    git submodule add git@github.com:neontribe-ansible/cottage.git roles/cottage
    git submodule add git@github.com:neontribe-ansible/cottage-apache.git roles/cottage-apache
    git submodule add git@github.com:neontribe-ansible/mysql.git roles/mysql
    git submodule add git@github.com:neontribe-ansible/php5.git roles/php5
    git submodule add git@github.com:neontribe-ansible/ansible-drush.git roles/ansible-drush
    git submodule add git@github.com:neontribe-ansible/shelltools.git roles/shelltools
    git submodule add git@github.com:neontribe-ansible/clamav.git roles/clamav
    git submodule add git@github.com:neontribe-ansible/rkhunter.git roles/rkhunter
    git submodule add git@github.com:neontribe-ansible/ntpd.git roles/ntpd
    git submodule add git@github.com:neontribe-ansible/firewall.git roles/firewall
    git submodule add git@github.com:neontribe-ansible/ssh.git roles/ssh
    git submodule add git@github.com:neontribe-ansible/cottage-cron.git roles/cottage-cron
    git submodule add git@github.com:neontribe-ansible/ansible-postfix.git roles/ansible-postfix
    git submodule add git@github.com:neontribe-ansible/init.git roles/init
    git submodule add git@github.com:neontribe-ansible/bashrc.git roles/bashrc
