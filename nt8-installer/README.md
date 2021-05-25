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

### Create the site with existing configuration (without content).
    echo "Clone Drupal Configuration Files"

    cd EntyAte
    mkdir -p config/sync
    cd $_
    git clone git@github.com:neontabs/nt8config.git .

    echo "Site install using configuration files."

    cd ../../web
    drush \
        -y site-install config_installer config_installer_sync_configure_form.sync_directory=../config/sync \
        --db-url=sqlite://sites/default/files/.ht.sqlite \
        --account-mail=${USER}@neontribe.co.uk \
        --account-name=superadmin \
        --site-mail=${USER}@neontribe.co.uk


### Create the site from scratch with no configuration.

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

    drush -y en nt8theme
    drush -y config-set system.theme default nt8theme
    drush -y en nt8property nt8propertyshortlist
    drush -y en nt8map nt8search
    drush -y en nt8propertyshortlist nt8landingpage
    drush -y nt8:ia
    drush -y cr

## Compass compile

    compass compile themes/contrib/nt8theme/

## Chown files to run as www-data user

    sudo chown -R www-data:www-data sites/default/files

## Or run using drush

    drush rs
