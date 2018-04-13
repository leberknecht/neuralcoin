Neuralcoin
==========

We are scraping crypto exchanges, fire Tensorflow and create predictions.
The predictions made by this software will NOT be correct! 


![How it looks like in action](https://github.com/leberknecht/neuralcoin/raw/master/web/images/running-example.png)


Prerequistis
------------
You need:
* latest docker and docker-compose version
* gulp will help during local development

Install
-----
Linux:
```bash
cp docker-compose.yml.dist docker-compose.yml
docker-compose run --rm nc_phpfpm composer install --prefer-dist
docker-compose build
bin/docker-console doctrine:database:create
bin/console-docker doctrine:schema:up --force
docker-compose up -d
#if you want entries in /etc/hosts for your containers
sudo bin/docker-ip-helper.sh
bower install
gulp
```

Mac: 
```bash
cp docker-compose.yml.dist docker-compose.yml
docker-machine start neuralcoin
eval $(docker-machine env neuralcoin)
docker-compose run --rm nc_phpfpm composer install --prefer-dist
docker-compose up -d
bin/docker-ip-helper.sh $(docker-machine ip neuralcoin)
bin/docker-console doctrine:database:create
bin/console-docker doctrine:schema:up --force
gulp
```

The open <http://neuralcoin.local/status> (htaccess is testing/testing)
You should see some trade-activity in the ticker on the right.
If so, we are almost there. Just let the setup run for 10 minutes or so, then fire the command 

    bin/shell.sh bin/console neuralcoin:update-symbol-exchanges
    
This will update the DB and configure which symbols are available on what exchange. 
Now you should be good to go :)

Mac-Users: if you experience performance issues, try to change docker-compose.yml mounts to cached driver (requires latest docker...wow, guess this line will be untrue pretty soon...meh.)
Replace `- ./:/code` mounts with `- ./:/code:cached` 

Good luck and happy predicting :)

Troubleshooting
-----
Be aware! You dont need to run `npm install` for the node containers to work, they are already prepared. _If_ you run `npm install` on your host (maybe because you want to debug the scripts from the ide of something), and you run a different node version than the containers, this might result in "the scrapers dont work anymore".

If you dont see any trades in the ticker on `/status`, maybe the rabbit queues are not beeing processed. You should be able to login to rabbit management interface on <http://nc_rabbitmq:15672>


About 
-----
This project wants to predict prices of crypto currencies and will NOT work. :) This is more a tech playground to try out new things. Anyways: We are scraping poloniex, bittrex, bitstamp and bitfinex. But to be honest, actually polo and bittrex are interesting (as they have way more traffic and way more trading pairs). 