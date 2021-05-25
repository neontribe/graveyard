#!/bin/bash

checkout.sh

if [ ! -z "${PHPUNIT_GROUP}" ]; then
    /opt/nt8/vendor/bin/phpunit --group ${PHPUNIT_GROUP} --configuration /opt/nt8/web/core/phpunit.xml.dist
else
    /opt/nt8/vendor/bin/phpunit --configuration /opt/nt8/web/core/phpunit.xml.dist
fi
