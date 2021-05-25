#!/bin/bash

set -x

SCRIPTDIR=$(dirname $0)
PROJECTRID=$(realpath $SCRIPTDIR)
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
if [ -z "$DBHOST" ]; then DBHOST=mysql; fi
if [ -z "$DBFILE" ]; then DBFILE=sql/dump.sql; fi

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
echo DBFILE=$DBFILE
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

echo "Drush make"
rm -rf $TARGET
drush make $MAKEFILE $TARGET

echo "Installing settings"
cd $TARGET/sites
rm -rf default
cp -r $PROJECTRID default
cp default/default.local_settings.php default/local_settings.php
sed -i "s/DBNAME/$DBNAME/g" default/local_settings.php
sed -i "s/DBPASS/$DBPASS/g" default/local_settings.php
sed -i "s/DBUSER/$DBUSER/g" default/local_settings.php
sed -i "s/DBHOST/$DBHOST/g" default/local_settings.php
sed -i "s/DBPORT/$DBPORT/g" default/local_settings.php

echo "Compass compile"
for x in default/themes/*; do
    compass compile $x
done
