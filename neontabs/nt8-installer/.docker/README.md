NT8 Installer Docker
====================

Default/latest image
--------------------

This docker builds a base ubuntu xenial and adds PHP and SQLite to it.  It install composer and clones the nt8-installer into /opt/nt8.  It then runs composer install on the nt8 installer and copies in a helper script.  The entry point is a bash prompt, this copy of NT8 is set up, it has no database.

The helper script is ```/usr/local/bin/checkout.sh``` it parses an environment variable and attemopts to checkout specified repos to provided branches.  e.g.

    CHECKOUT="ntnt8property:AB-123 nt8landingpage:CD:456" ./checkout.sh

Will attempt to checkout branch AB-123 in /opt/nt8/web/modules/custom/ntnt8property and branch CD:456 in /opt/nt8/web/modules/custom/nt8landingpage.  To pass this into a docker use the ```-e``` flag.

e.g.

    docker -ti -e CHECKOUT="ntnt8property:AB-123 nt8landingpage:CD:456" IMAGEID

tobybatch/nt8installer:demo
---------------------------

Extends the base image and runs a site install.  The site will be available on http://localhost:8888

    docker run -ti -p 8888:8888 tobybatch/nt8installer:demo

It does support running custom branches.

    docker run -ti -p 8888:8888 -e CHECKOUT="ntnt8property:AB-123 nt8landingpage:CD:456" tobybatch/nt8installer:demo

tobybatch/nt8installer:tester
-----------------------------

Extends the base image and runs php unit tetst.  All tests are run unless a group is specified.

    docker run -ti -e PHPUNIT_GROUP=ntnt8property tobybatch/nt8installer:tester

It also supports the checkout script:

    docker run -ti -e PHPUNIT_GROUP=ntnt8property -e CHECKOUT="ntnt8property:AB-123 nt8landingpage:CD:456" tobybatch/nt8installer:tester

