Cottage Vagrant installer
=========================

Quick start
-----------

This will fail if you haven't fulfilled the dependencies below:

    $ export BC=BRANDCODE # << This should be the brand code you are cloning
    $ wget -O - -q https://raw.githubusercontent.com/neontribe-ansible/cottage-PAS/master/install.sh | bash

If this fails (and it does from time to time) run

    $ vagrant provision

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

Live deployment
---------------

    $ sudo apt-get install software-properties-common
    $ echo deb http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main | sudo tee --append /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list
    $ echo deb http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main | sudo tee --append /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list
    $ echo deb-src http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main | sudo tee --append /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list
    $ sudo apt-get update
    $ sudo apt-get install ansible
    $ apt-get install git
    $ git clone https://github.com/neontribe-ansible/cottage-PAS.git
    $ cd cottage-PAS/
    $ git submodule init
    $ git submodule update
    $ sed -i 's/CHANGEME/SOME_NEW_PASSWORD/g' playbook.wild.yml
    $ ansible-playbook -vv -i "localhost," -c local /root/cottage-PAS/playbook.wild.yml --extra-vars "sudo=yes hostname=heritagehideaways.com server_type=live cottage_brand_code=hr admin_email=info@neontribe.co.uk servername=heritagehideaways.com doc_root=/var/www/latest ssl_passphrase=password"
