#!/usr/bin/env bash
SF_ENV="$@"
echo "resetting db for env: ${SF_ENV}"
bin/console doc:data:drop --force --env ${SF_ENV}
bin/console doc:data:create --env ${SF_ENV}
bin/console doc:mig:mig --no-interaction --env ${SF_ENV}
bin/console doc:fix:load --no-interaction --env ${SF_ENV}