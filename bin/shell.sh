#!/bin/bash
if [[ "$@" == "" ]]; then
    docker-compose run --rm nc_phpfpm /bin/bash
else
    ARGS="$@"
    docker-compose run --rm nc_phpfpm /bin/bash -c "${ARGS}"
fi
