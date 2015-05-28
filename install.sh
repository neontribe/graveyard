#!/bin/bash
set +x

echo 'Enter brand code (in lower case)'
while [ -z "$BC" ]; do
    read BC
done

git clone https://github.com/neontribe-ansible/cottage-PAS ansible-$BC
cd ansible-$BC
git submodule init
git submodule update

echo vagrant up
