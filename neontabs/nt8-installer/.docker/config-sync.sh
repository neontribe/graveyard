#!/bin/bash

echo ${BRAND_SYNC_REPO}


mkdir -p config/sync
cd $_

if [ ! -z "${BRAND_SYNC_REPO}" ]
then
  git clone ${BRAND_SYNC_REPO} .
else
  echo "No Configuration Repo Provided. Exiting."
  exit 1
fi





