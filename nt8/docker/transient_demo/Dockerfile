FROM tobybatch/nt8base
MAINTAINER tobias@neontribe.co.uk

# This sets the default path when connectin to the container.
WORKDIR    /opt/EntyAte/web

# Provisioning.  If this got any more comlicated I'd use ansible.
# RUN composer --working-dir=/opt/EntyAte update # This takies ages...
RUN drush \
    -r /opt/EntyAte/web \
    -y site-install \
    --db-url=sqlite://sites/default/files/.ht.sqlite \
    --account-mail=root@localhost \
    --account-name=superadmin \
    --site-mail=root@localhost \
    --site-name=EntyAte
RUN drush -r /opt/EntyAte/web en -y nt8theme
RUN drush -r /opt/EntyAte/web -y config-set system.theme default nt8theme
RUN drush -r /opt/EntyAte/web en -y nt8property
RUN drush -r /opt/EntyAte/web en -y nt8map nt8search
RUN compass compile /opt/EntyAte/web/themes/contrib/nt8theme

RUN drush -r /opt/EntyAte/web -y nt8-import-all

# VOLUME        ["/opt/calibre/library"]

# This is what we run when we do a "docker run -ti -p 8888:80 [IMAGE_ID]"
ENTRYPOINT ["/usr/local/bin/drush", "rs", "0.0.0.0:80"]

# Connect to the running image
# docker exec -it [CONTAINER_ID] /bin/bash
