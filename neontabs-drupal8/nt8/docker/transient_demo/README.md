# NT8 Transient Demo

This will create a docker that when started will demo the nt8 stack. Data in the container is not persisted so the container resets each time you start/stop it.

In short:

    docker build --force-rm .
    docker run -ti -p 8888:80 [IMAGE_ID]

Attach a terminal if needed:

    docker exec -it [CONTAINER_ID] /bin/bash
