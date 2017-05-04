# Composer template for Drupal projects

## Install drupal 8 with nt8 ready to go:
```
composer \
    create-project neontribe/nt8-installer EntyAte \
    --stability dev \
    --no-interaction
```

## Run using a VM

### Change to the folder where the vagrant file is:

    cd EntyAte

### The run Vagrant up:

    vagrant up

## Run in the local machine

### Create the site

    drush \
        -y site-install \
        --db-url=sqlite://sites/default/files/.ht.sqlite \
        --account-mail=${USER}@neontribe.co.uk \
        --account-name=superadmin \
        --site-mail=${USER}@neontribe.co.uk \
        --site-name=EntyAte

or for a SQL DB

    drush \
        -y site-install \
        --db-url=mysql://EntyAte:EntyAte@localhost/EntyAte \
        --account-mail=${USER}@neontribe.co.uk \
        --account-name=superadmin \
        --site-mail=${USER}@neontribe.co.uk \
        --site-name=EntyAte

Check the output for the random superadmin password

## Enable nt8 modules

    drush -y en nt8_theme
    drush -y config-set system.theme default nt8_theme
    drush -y en nt8property
    drush -y en nt8map nt8search
    drush -y nt8-import-all

## Compass compile

    compass compile themes/contrib/nt8theme/

## Chown files to run as www-data user

    sudo chown -R www-data:www-data sites/default/files

## Or run using drush

    drush rs
