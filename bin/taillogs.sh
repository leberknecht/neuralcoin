#!/bin/bash
ncdocker logs --tail 30 -f | egrep -v "nginx|nc_db|scraper|rabbitmq|redis|websocket|broker"

