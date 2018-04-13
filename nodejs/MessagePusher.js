let amqp = require('amqplib/callback_api');
const yaml = require('js-yaml');
const fs = require('fs');
let queueChannel = null;
let queue = 'trades';
let orderBookQueue = 'order-books';

let configParametersPath = __dirname + '/../app/config/parameters.yml';
let configParameters = yaml.safeLoad(fs.readFileSync(configParametersPath, 'utf8')).parameters;
console.log('using parameters file: ' + configParametersPath);
console.log('rabbit-host: ' + configParameters.rabbitmq_host);
console.log('rabbit-user: ' + configParameters.rabbitmq_user);

function openConnection()
{
    if (!queueChannel) {
        let uri = 'amqp://' + configParameters.rabbitmq_user
            + ':'
            + configParameters.rabbitmq_password
            + '@'
            + configParameters.rabbitmq_host;

        amqp.connect(uri, function(err, conn) {
            if (conn) {
                conn.createChannel(function(err, ch) {
                    queueChannel = ch;
                });
            }
        });
        console.log('connection to rabbit server established on: ' + configParameters.rabbitmq_host);
    }
}

function sendMessage(queue, message)
{
    if (!queueChannel) {
        openConnection();
    }
    if (queueChannel) {
        queueChannel.assertQueue(queue, {durable: false});
        queueChannel.sendToQueue(queue, new Buffer(JSON.stringify(message)));
    }
}

module.exports = {
    send: function(exchange, channel, volume, price) {
        let msg = {
            exchange: exchange,
            symbol: channel,
            volume: volume,
            price: price,
            time: new Date().toISOString()
        };
        sendMessage(queue, msg);
    },

    sendOrderBook: (exchange, symbol, data) => {
        let msg = {
            exchange: exchange,
            symbol: symbol,
            data: data,
            date: new Date().toISOString()
        };
        sendMessage(orderBookQueue, msg);
    }
};

openConnection();