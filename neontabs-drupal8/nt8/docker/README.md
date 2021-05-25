# NT8 Dockerfiles

Each folder under this directory has a seperate Docker file in it. To use one cd intot he correct folder and build the image.
Once built you can run it, extend it save it whatever. In each case you will need to build and then run it:

First pull the base image:

    docker pull tobybatch/nt8base

Now you can build and run an image, each Docker file has a seperate readme.

You could run this image with the following command but it's an empty framework:

    docker run -ti tobybatch/nt8base:latest

```
docker build --force-rm .
docker run -ti -p 8888:80 [IMAGE_ID]
```
## Docker build better explained

You will need to use IDs to manipulate the images and containers. An image is an initialised template for a container, a container is a running/initialised instance of an image.

## Building

Run a build using:

    docker build --force-rm .

This is the sample output, snipped in the middle.

```
tobias@tobias docker $ docker build --force-rm .
Sending build context to Docker daemon 4.608 kB
Step 1 : FROM tobybatch/nt8base
---> f4e1901bb837
Step 2 : MAINTAINER tobias@neontribe.co.uk
---> 53ad448e0762
...
[SNIP]
...
---> a1541d92d854
Removing intermediate container 7ccbaf09efeb
---> Running in 811951b1feb4
---> 93349b2ac76f
Removing intermediate container 811951b1feb4
Successfully built 93349b2ac76f
```

The last line has the image ID in it, in this case 93349b2ac76f, we'll need that to start the image.

## Running the Image

Start a container passing host port 8888 to port 80 in the container:

    docker run -ti -p 8888:80 93349b2ac76f

You can list available images using the ```ps``` command:

    docker ps -a

Resulting in:

```
tobias@tobias docker $ docker ps -a
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS                         PORTS                            NAMES
4ad40b8ad0cc        93349b2ac76f        "/usr/local/bin/drush"   13 seconds ago      Up 12 seconds                  8080/tcp, 0.0.0.0:8888->80/tcp   prickly_shirley
28d544ca42a1        140122355c2f        "/usr/local/bin/drush"   About an hour ago   Exited (0) 31 minutes ago                                       gigantic_franklin
917822d05917        c9f50ca5da0c        "/bin/bash"              2 hours ago         Exited (0) 37 minutes ago                                       dreamy_snyder
```

### Attaching a local directory

    docker run -ti -p 8888:80 -v $HOME/workspace/cottaging/nt8:/opt/EntyAte/web/modules/custom [CONTAINER_ID]

### Attaching a volume

## Attaching a process

You can get a bash prompt on a running server using:

    docker exec -it [CONTAINER_ID] /bin/bash

