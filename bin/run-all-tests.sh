#!/usr/bin/env bash
bin/console doc:data:drop --force --env test
bin/console doc:data:create --env test
bin/console doc:mig:mig --no-interaction --env test
bin/console doc:fix:load --no-interaction --env test
vendor/bin/phpunit --stop-on-error --stop-on-failure
