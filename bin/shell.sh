#!/bin/bash
if [[ "$@" == "" ]]; then
    docker-compose exec nc_phpfpm /bin/bash
else
    ARGS="$@"
    docker-compose exec nc_phpfpm /bin/bash -c "${ARGS}"
fi
