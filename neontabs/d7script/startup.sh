#!/bin/bash

export PASS=`pwgen -s -1 14`
echo $PASS > /tmp/mysql_root_pass
echo "=> Starting MySQL"
/usr/bin/mysqld_safe --datadir="/var/lib/mysql" &
while [ ! -e /var/run/mysqld/mysqld.sock ]; do
  sleep 1
done

/usr/bin/mysqladmin -u root password $PASS
echo "***************************************"
echo "* Mysql Root Password: $PASS *"
echo "***************************************"

echo "Creating user & DB"
mysql -u root -p$PASS -e "CREATE USER 'drupal'@'localhost' IDENTIFIED BY 'drupal';"
mysql -u root -p$PASS -e "GRANT ALL PRIVILEGES ON * . * TO 'drupal'@'localhost';"
mysql -u root -p$PASS -e "CREATE DATABASE drupal CHARACTER SET utf8 COLLATE utf8_general_ci"

if [ ! -e $TARGET/.installed ]; then
  echo "Installing site SQL"
  drush -r $TARGET sqlc < $TARGET/sites/default/sql/dump.sql
  touch $TARGET/.installed
fi

drush -r $TARGET cc all
drush -r $TARGET rs 0.0.0.0:$PORT
