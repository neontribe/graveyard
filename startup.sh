#!/bin/bash

if [ ! -e $TARGET/.installed ]; then
  drush -r $TARGET sqlc < $TARGET/sites/default/sql/dump.sql
  touch $TARGET/.installed
fi

drush -r $TARGET cc all
drush -r $TARGET rs 0.0.0.0:80
