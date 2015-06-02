FROM ubuntu:14.04

MAINTAINER Toby Batch

# EXPOSE 80
COPY playbook.yml /root/playbook.yml
COPY roles /root/roles
RUN \
  echo deb http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main >> /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list && \
  echo deb-src http://ppa.launchpad.net/ansible/ansible/ubuntu trusty main >> /etc/apt/sources.list.d/ansible-ubuntu-ansible-utopic.list && \
  apt-get update -y && \
  apt-get upgrade -y && \
  apt-get install -y software-properties-common && \
  apt-get install -y --force-yes ansible && \
  sudo ansible-playbook -vv -i "localhost," -c local /root/playbook.yml --extra-vars "sudo=yes hostname=ly server_type=desktop cottage_alias_name=neonly drupal.dbname=live_ly drupal.dbuser=live_ly drupal_dbpass=live_ly cottage_brand_code=ly admin_email=info@neontribe.co.uk servername=testserver cottage_target=/vagrant/drupal doc_root=/vagrant/drupal ssl_passphrase=password ssl.key=ssl-cert-snakeoil.key ssl.crt=ssl-cert-snakeoil.pem ssl.chain=chain.crt key: ssl-cert-snakeoil.key neon_user=root remote_host=192.168.21.44 remote_user=ansible"
