FROM ubuntu:xenial
MAINTAINER tobias@neontribe.co.uk

RUN apt update && apt -y install git ruby-compass php7.0 php7.0-curl php7.0-dev php7.0-gd php7.0-intl php7.0-json php7.0-mbstring php7.0-mcrypt php7.0-readline php7.0-sqlite3 php7.0-tidy php7.0-xml php-apcu php-imagick php-tidy php-xdebug php-xml libapache2-mod-php7.0 php7.0-mysql sqlite3

# Just for debug/testing
RUN apt -y install vim tmux ccze htop

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === '669656bab3166a7aff8a7506b8cb2d1c292f042046c5a994c43155c0be6190fa0355160742ab2e1c88d40d5be660b410') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN ln -s /composer.phar /usr/local/bin/composer

RUN git clone https://github.com/neontabs/nt8-installer.git /opt/nt8
WORKDIR /opt/nt8
RUN composer install --dev --no-interaction
RUN ln -s /opt/nt8/vendor/drush/drush/drush /usr/local/bin/drush

COPY checkout.sh /usr/local/bin/checkout.sh
RUN chmod 777 /usr/local/bin/checkout.sh

WORKDIR /opt/nt8/web

EXPOSE 8888
ENTRYPOINT ["/bin/bash"]
