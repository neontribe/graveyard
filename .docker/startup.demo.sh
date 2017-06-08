#!/bin/bash

checkout.sh

composer update -d /opt/nt8

drush \
    -r /opt/nt8/web \
    -y site-install \
    --db-url=sqlite://sites/default/files/.ht.sqlite \
    --account-mail=cottages@neontribe.co.uk \
    --account-name=superadmin \
    --site-mail=cottages@neontribe.co.uk \
    --site-name=EntyAte
drush -r /opt/nt8/web -y en nt8theme
drush -r /opt/nt8/web -y config-set system.theme default nt8theme
drush -r /opt/nt8/web -y en nt8property
drush -r /opt/nt8/web -y en nt8map nt8search
drush -r /opt/nt8/web -y nt8-import-all
compass compile /opt/nt8/web/themes/contrib/nt8theme/
RUNSERVER_BASE_URL='http://localhost:8888' drush -r /opt/nt8/web rs 0.0.0.0:8888
