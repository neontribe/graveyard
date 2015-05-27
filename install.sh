#!/bin/bash

CWD=`dirname $0`
BR=$1

while [ -z "$BR" ]; do
    read BR
done

git clone https://github.com/neontribe-ansible/cottage $CWD/$BR
cd $CWD/$BR
echo git submodule init
echo git submodule update

echo vagrant up
