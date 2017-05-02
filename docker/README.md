# NT8 Docker

These docker files will build different versions of the nt8 stack.

## Raw NT8

This is a basic nt8 project without DB or other set up.

    docker pull tobybatch/nt8base
    docker run -ti tobybatch/nt8base:latest

Beware docker files are NOT persistent.  If you want to keep your changes you'll need to mount some folder, see below.

## Fully provisioned stack

This docker sets up and runs the drupal stack.  After running this command you will need the image id and superadmin password:

<code>
<span style='color: #bdbdbd'>tobias@tobias docker $</span> docker build --force-rm .

Sending build context to Docker daemon 4.608 kB

Step 1 : FROM tobybatch/nt8base

---> f4e1901bb837

Step 2 : MAINTAINER tobias@neontribe.co.uk

---> 53ad448e0762

Step 3 : WORKDIR /opt/EntyAte/web

---> Using cache

---> cda964a3157c

Step 4 : RUN drush     -r /opt/EntyAte/web     -y site-install     --db-url=sqlite://sites/default/files/.ht.sqlite     --account-mail=root@localhost     --account-name=superadmin     --site-mail=root@localhost     --site-name=EntyAte

---> Running in 89a2d0fdb3e1

You are about to CREATE the 'sites/default/files/.ht.sqlite' database. Do you want to continue? (y/n): y

Starting Drupal installation. This takes a while. Consider using the        [ok]

--notify global option.

Installation complete.  User name: superadmin  User password:               [ok]

<strong style='color: red'>Prn746vUWC</strong>

Congratulations, you installed Drupal!                                  [status]

---> 86b5354369c0

...

[SNIP]

...

---> a1541d92d854

Removing intermediate container 7ccbaf09efeb

Step 12 : LABEL "mydrupal" ""

---> Running in 811951b1feb4

---> 93349b2ac76f

Removing intermediate container 811951b1feb4

Successfully built <strong style='color: red'>93349b2ac76f</strong>

<span style='color: #bdbdbd'>tobias@tobias docker $</span> docker run -ti -p 8888:80 <strong style='color: red'>93349b2ac76f</strong>

</code>

To connect to this running docker.

<code>
<span style='color: #bdbdbd'>tobias@tobias docker $</span> docker ps -a
<pre>
CONTAINER ID        IMAGE               COMMAND                  CREATED             STATUS                         PORTS                            NAMES

<strong style='color: red'>29d40b8ad0cc</strong>        93349b2ac76f        "/usr/local/bin/drush"   13 seconds ago      Up 12 seconds                  8080/tcp, 0.0.0.0:8888->80/tcp   prickly_shirley

28d544ca42a1        140122355c2f        "/usr/local/bin/drush"   About an hour ago   Exited (0) 31 minutes ago                                       gigantic_franklin

917822d05917        c9f50ca5da0c        "/bin/bash"              2 hours ago         Exited (0) 37 minutes ago                                       dreamy_snyder

</pre>
<span style='color: #bdbdbd'>tobias@tobias docker $</span> docker exec -it <strong style='color: red'>29d40b8ad0cc</strong> /bin/bash
</code>

To save DB/config changes mount the files folder inside the docker:

    docker run -ti -p 8888:80 -v $HOME/workspace/cottaging/project/files:/opt/EntyAte/web/sites/default/files [CONTAINER_ID]

To allow you to work on the files insode the container  mount the files inside the docker:

    docker run -ti -p 8888:80 -v $HOME/workspace/cottaging/nt8:/opt/EntyAte/web/modules/custom [CONTAINER_ID]

To do both

    docker run -ti -p 8888:80 -v $HOME/workspace/cottaging/project/files:/opt/EntyAte/web/sites/default/files -v $HOME/workspace/cottaging/nt8:/opt/EntyAte/web/modules/custom [CONTAINER_ID]
