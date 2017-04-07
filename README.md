# Composer template for Drupal projects

## Install drupla 8 with nt8 ready to go:
```
composer \
    create-project neontribe/nt8-installer EntyAte \
    --stability dev \
    --no-interaction
```

## drush must be run from the web folder

    cd EntyAte/web

## Install the site using sqlite

```
drush \
    -y site-install \
    --db-url=sqlite://sites/default/files/.ht.sqlite \
    --account-mail=${USER}@neontribe.co.uk \
    --account-name=superadmin \
    --site-mail=${USER}@neontribe.co.uk \
    --site-name=EntyAte
```

Check the output for the random superadmin password

## Enable nt8 modules

````
drush en -y nt8_theme
drush -y config-set system.theme default nt8_theme
drush en -y nt8tabsio
drush en -y nt8property
drush en -y nt8search nt8map

## Chown files to run as www-data user

    sudo chown -R www-data:www-data sites/default/files

## Or run using drush

    drush rs
