FROM ubuntu:14.04

MAINTAINER Toby Batch
# docker run --name BRANDCODE-mysql -v mysql-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=strangehat -d mysql:latest

RUN \
    apt-get update -y && \
    apt-get upgrade -y && \
    apt-get install -y imagemagick vim git htop mc ccze realpath jpegoptim optipng gifsicle yui-compressor php-pear php5-curl php5-imagick php5-common php5-gd php5-mcrypt php5-mysql php5-xsl php5-xmlrpc php5-intl php5-readline php5-xdebug php-apc php5-imagick curl php5-cli apache2 libapache2-mod-php5 && \
    git clone https://github.com/drush-ops/drush.git /usr/local/share/drush && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=bin --filename=composer && \
    composer --working-dir=/usr/local/share/drush update && \
    # echo mysql-server mysql-server/root_password password strangehat | sudo debconf-set-selections && \
    # echo mysql-server mysql-server/root_password_again password strangehat | sudo debconf-set-selections && \
    # sudo apt-get install -y mysql-server && \
    sed -i "s/80/8090/g" /etc/apache2/ports.conf && \
    sed -i "s/80/8090/g" /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite && \
    a2ensite 000-default.conf

ENTRYPOINT /bin/bash
