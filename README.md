# Composer template for Drupal projects

## Quick start
```
composer \
    create-project neontribe/nt8-installer some-dir \
    --stability dev \
    --no-interaction
    
drush \
    -y site-install \
    --db-url=sqlite://sites/default/files/.ht.sqlite \
    --account-mail=tobias@neontribe.co.uk \
    --account-name=tobias \
    --account-pass=b191wkm \
    --site-mail=tobias@neontribe.co.uk \
    --site-name=EntyAte
sudo chown www-data:www-data sites/default/files/.ht.sqlite*
```
