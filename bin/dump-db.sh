#!/usr/bin/env bash
set -e
ssh ubuntu@neuralcoin.io mysqldump -uroot -proot -hnc_db neuralcoin > /tmp/db.sql
mysql -uroot -proot -hnc_db -e 'DROP database neuralcoin;'
mysql -uroot -proot -hnc_db -e 'CREATE database neuralcoin;'
mysql -uroot -proot -hnc_db -D neuralcoin < /tmp/db.sql
