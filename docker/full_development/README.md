# NT8 Full Development

This will create a docker that will persist DB changes betweeb restarts, and allows you to mount your local files in the container.

    docker build --force-rm .
    docker run -ti -p 8888:80 -v `pwd`/files:/opt/EntyAte/web/sites/default/files -v $HOME/workspace/cottaging/nt8:/opt/EntyAte/web/modules/custom [IMAGE_ID]

    docker exec -it [CONTAINER_ID] /bin/bash
