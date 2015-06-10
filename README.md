## Start new mysql
    docker run --name BRANDCODE-mysql -v /tmp/mysql-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=strangehat -d mysql:latest 

## Start existing mysql
Start attached
    docker run -v /tmp/mysql-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=strangehat mysql:5
Add -d to detach
    docker run -d -v /tmp/mysql-data:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=strangehat mysql:6

## Get the UID of the running container
In this case the container is a mysql:5 instance
    docker ps |grep mysql:5|awk '{print $1}'

## Attach to running instance
    docker exec -i -t 665b4a1e17b6 bash

## Build new LAP from docker file
    docker build -f Dockerfile -t lamp . 

## Run new LAP and attach to the mysql
Fetch the name of the mysql instance:
    docker ps |grep mysql:5|awk '{print $NF}'
Run the LAP stack
    docker run --link reverent_pasteur:mysql -t --entrypoint=/bin/bash lamp:latest
