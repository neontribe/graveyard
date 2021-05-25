#!/bin/bash

if [ ! -e "/opt/EntyAte/web/sites/default/files/.ht.sqlite" ]; then
    drush \
        -r /opt/EntyAte/web \
        -y site-install \
        --db-url=sqlite://sites/default/files/.ht.sqlite \
        --account-mail=root@localhost \
        --account-name=superadmin \
        --site-mail=root@localhost \
        --site-name=EntyAte
    drush -r /opt/EntyAte/web en -y nt8theme
    drush -r /opt/EntyAte/web -y config-set system.theme default nt8theme
    drush -r /opt/EntyAte/web en -y nt8property
    drush -r /opt/EntyAte/web en -y nt8map nt8search
    compass compile /opt/EntyAte/web/themes/contrib/nt8theme
    drush -r /opt/EntyAte/web -y nt8-import-all
fi

/usr/local/bin/drush -r /opt/EntyAte/web rs 0.0.0.0:80
/bin/bash
