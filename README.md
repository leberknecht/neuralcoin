Neuralcoin
==========

About 
-----
We are scraping crypto exchanges, fire Tensorflow and create predictions.
The predictions made by this software will NOT be correct! 

This project wants to predict prices of crypto currencies and will NOT work. :) This is more a tech 
playground to try out new things. Anyways: We are scraping poloniex, bittrex, bitstamp and bitfinex. 
Polo and bittrex are having way more trading pairs, so for playing around with the NN, these exchanges 
are a good way to go. 


![How it looks like in action](https://github.com/leberknecht/neuralcoin/raw/master/web/images/running-example.png)

Scrapers are running on NodeJS and publish the scraped trades to a RabbitMQ queue. A symfony 3 application
sits in the middle and consumes from rabbit. The trades are persisted to a MySQL and forwarded to the
users on the frontend over WebSockets. We create a network config on the PHP level, then we put the config
intoa JSON message and transfer it over a queue to a Python consumer that generates to Tensorflow network files.
Training-Data is generated on the PHP layer as well (which is questionable, see the To-Do section), stored on a 
mount (we support local-filesystem and S3, see the `oneup_flysystem` section on `app/config/config.yml`) and 
a training-message is transferred via Queue. Another Python consumer processes the training Messages. We have a
nice option "generate Image" on the create-network view. If set, we will generate a SVG with the network topology
after each successful training 
ATTENTION! This is mostly to visualize the topology changes on a longer time-scope 
(we put the images of the training runs into a slideshow, so you can see the weights changing on the connections)
and will probably "crash" your application if you enable this feature on a "not-small" network (which, in this context,
means "anything with more than 50 inputs", as the resulting images are very big). By the way, as this is somehow 
a nice feature, i have extracted the part for the image generation to a separate 
repo: https://github.com/leberknecht/tflearn-topology-visualizer


Finding nice usable parameters for your network is not an easy task, keep in mind: The "Time-Scope" setting affects
1:1 the amount of training-data you will have available in each training run. But: if you set if to something like 
e.g. a week, your network wont be usable on the dashboard (as we request predictions there in a 15-seconds interval,
and alone assembling the training-data for a one-week scope will require longer). 
A more-helpful documentation will be added soonish.

Regarding Order-Books: The order-books are currently only for the following symbols on bittrex:

* BTC-LTC
* BTC-BCC
* BTC-XRP
* BTC-XVG
* BTC-RDD
* BTC-NXT
* BTC-CVC
* BTC-HMQ
* BTC-DOGE
* BTC-ETH
* BTC-DGB
* BTC-SC
* BTC-NAV
* BTC-VTC

Prerequistis
------------
You need:
* latest docker and docker-compose version
* gulp + browser-sync will help during local development

To avoid conflicts between host and container NPM modules, please use node ~v9.9.0

Install
-----
Linux:
```bash
cp docker-compose.yml.dist docker-compose.yml
docker-compose build
docker-compose run --rm --user www-data nc_phpfpm composer install --prefer-dist
docker-compose run --rm --user www-data nc_scraper_bittrex yarn install
docker-compose run --rm --user www-data nc_scraper_bittrex bower install
bin/shell.sh bin/console doctrine:database:create
bin/shell.sh bin/console doctrine:schema:up --force
docker-compose up -d
#if you want entries in /etc/hosts for your containers
sudo bin/docker-ip-helper.sh
gulp
```

Mac:
Mostly the same, but use 
```bash
docker-machine start neuralcoin
eval $(docker-machine env neuralcoin)
bin/docker-ip-helper.sh $(docker-machine ip neuralcoin)
```
to bring up the stack and set corresponding entries in `/etc/hosts`

Then open <http://neuralcoin.local/status>
You should see some trade-activity in the ticker on the right. 
If so, we are almost there. Just let the setup run for 10 minutes or so, then fire the command 

    bin/shell.sh bin/console neuralcoin:update-symbol-exchanges
    
This will update the DB and configure which symbols are available on what exchange. 
Now you should be good to go :)

Mac-Users: if you experience performance issues, try to change docker-compose.yml mounts to cached 
driver (requires latest docker...wow, guess this line will be untrue pretty soon...meh.)
Replace `- ./:/code` mounts with `- ./:/code:cached` 

Good luck and happy predicting :)

Troubleshooting
-----
If you dont see any trades in the ticker on `/status`, maybe the rabbit queues are not being processed. 
You should be able to login to rabbit management interface on <http://nc_rabbitmq:15672>

The Bittrex API is very unstable lately. If the scraper fails to connect, just restart the container until
the connection is successful.

To-Do
---
* The frontend code is in bad shape :/ Parts are in ReactJS (on the dashboard), parts are..not (on the
create-network view). There are no tests for it and the code- and file-structure is semi nice. 
* The directory structure in general is unnecessary complex and should be cleaned up
* we assemble the training-data in PHP, and i'm pretty sure that could be done way more elegant in Python,
but that would require to process the network-configuration in Python as well, so this is a bigger to-do, and
brings up the question how the whole thing would look like with Django-or-something in the middle instead of
symfony (which i mostly chose because...well, there was this idea of selling this thing, which would have 
required not just a User+Auth layer but a big bunch of other "traditional web-related bindings", which Symfony 
can do very well).