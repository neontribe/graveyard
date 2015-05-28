#!/bin/bash +x

BR=$1

echo 'Enter brand code (in lower case)'
while [ -z "$BR" ]; do
    read BR
done

git clone https://github.com/neontribe-ansible/cottage-PAS ansible-$BR
cd ansible-$BR
git submodule init
git submodule update

echo vagrant up
