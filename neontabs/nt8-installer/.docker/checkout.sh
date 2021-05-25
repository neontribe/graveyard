#!/bin/bash

for namevalue in ${CHECKOUT}; do

    IFS=':' read -a pair <<< "$namevalue"
    BRANCH_NAME=${pair[0]}
    REPO_NAME=${pair[1]}

    if [ ! -z "$BRANCH_NAME" ] && [ ! -z "$REPO_NAME" ]; then
        git -C /opt/nt8/web/modules/custom/${REPO_NAME}/ checkout ${BRANCH_NAME}
    fi

done
