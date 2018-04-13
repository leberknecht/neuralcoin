#!/bin/bash
docker-compose stop && docker-compose up -d && docker-compose stop nc_cron && sudo bin/docker-ip-helper.sh
docker-compose exec nc_rabbitmq rabbitmqctl purge_queue train-network
docker-compose exec nc_rabbitmq rabbitmqctl purge_queue assemble-training-data
docker-compose exec nc_rabbitmq rabbitmqctl purge_queue request-prediction
docker-compose exec nc_rabbitmq rabbitmqctl purge_queue trades

