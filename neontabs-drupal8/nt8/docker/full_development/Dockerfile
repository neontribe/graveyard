FROM tobybatch/nt8base
MAINTAINER tobias@neontribe.co.uk

COPY startup.sh /usr/local/bin/startup.sh

# This sets the default path when connectin to the container.
WORKDIR    /opt/EntyAte/web

# This is what we run when we do a "docker run -ti -p 8888:80 [IMAGE_ID]"
ENTRYPOINT ["/usr/local/bin/startup.sh"]

# Connect to the running image
# docker exec -it [CONTAINER_ID] /bin/bash
