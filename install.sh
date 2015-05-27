#!/bin/bash

CWD=`dirname $0`
BR=$1

if [ -z "$BR" ]; then
    echo Usage: $0 BRANDCODE
    exit 1
fi

cd $CWD
git submodule init
git submodule update

vagrant up
