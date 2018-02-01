#!/bin/bash

SCRIPTDIR=$(dirname $0)
PROJECTRID=$(realpath $SCRIPTDIR/..)
WEBUSER=www-data

if [ -z "$BRANDCODE" ]; then
  echo "Brandcode is not set. Quitting"
  exit 1
fi

if [ -z "$TARGET" ]; then
  echo "Set target folder for deployment:"
  read TARGET
  PAUSE=yes
fi

MAKEFILE=$PROJECTRID/$BRANDCODE.make
NOW=$(date +"%y%m%d_%H%M")

if [ -z "$DBNAME" ]; then DBNAME=$BRANDCODE"_"$NOW; fi
if [ -z "$DBUSER" ]; then DBUSER=$BRANDCODE"_"$NOW; fi
if [ -z "$DBPASS" ]; then DBPASS=$BRANDCODE"_"$NOW; fi
if [ -z "$DBHOST" ]; then DBHOST=localhost; fi

if [ -z "$MYSQLROOTPASS" ]; then
  echo "I need the root password the sql server to create the DB"
  echo Mysql root password for $DBHOST
  read MYSQLROOTPASS
  if [ -z "$MYSQLROOTPASS" ]; then
    echo No mysql root password cannot proceed.  Use export MYSQLROOTPASS=foo before running composer.
    exit 1
  fi
fi

echo SCRIPTDIR=$SCRIPTDIR
echo PROJECTRID=$PROJECTRID
echo
echo WEBUSER=$WEBUSER
echo MAKEFILE=$MAKEFILE
echo
echo BRANDCODE=$BRANDCODE
echo
echo DBNAME=$DBNAME
echo DBUSER=$DBUSER
echo DBPASS=$DBPASS
echo DBHOST=$DBHOST
echo
echo TARGET=$TARGET

if [ ! -z "$PAUSE" ]; then
  echo
  echo "Proceed [y/N]?"
  read a
  if [ "$a" != "Y" ] && [ "$a" != "y" ]; then
    exit 1
  fi
fi

mysql \
  -u root \
  -p$MYSQLROOTPASS \
  -h $DBHOST \
  -s \
  -e "CREATE USER '$DBUSER'@'%' IDENTIFIED BY '$DBPASS';"
mysql \
  -u root \
  -p$MYSQLROOTPASS \
  -h $DBHOST \
  -s \
  -e "GRANT USAGE ON * . * TO '$DBUSER'@'%' IDENTIFIED BY '$DBPASS' WITH MAX_QUERIES_PER_HOUR 0 MAX_CONNECTIONS_PER_HOUR 0 MAX_UPDATES_PER_HOUR 0 MAX_USER_CONNECTIONS 0 ;"
mysql \
  -u root \
  -p$MYSQLROOTPASS \
  -h $DBHOST \
  -s \
  -e "CREATE DATABASE IF NOT EXISTS \`$DBNAME\` ;"
mysql \
  -u root \
  -p$MYSQLROOTPASS \
  -h $DBHOST \
  -s \
  -e "GRANT ALL PRIVILEGES ON \`$DBNAME\` . * TO '$DBUSER'@'%';"

drush make $MAKEFILE $TARGET
cd $TARGET/sites
rm -rf default
cp -r $PROJECTRID default
cp default/default.local_settings.php default/local_settings.php
sed -i "s/DBNAME/$DBNAME/g" default/local_settings.php
sed -i "s/DBPASS/$DBPASS/g" default/local_settings.php
sed -i "s/DBUSER/$DBUSER/g" default/local_settings.php
sed -i "s/DBHOST/$DBHOST/g" default/local_settings.php
compass compile default/themes/ntcm_theme
drush sqlc < default/sql/dump.sql
drush cc all

echo
echo cd $TARGET && echo drush rs 0.0.0.0:8888
