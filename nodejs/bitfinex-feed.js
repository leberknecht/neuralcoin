const MessagePusher = require('./MessagePusher');
const WebSocket = require('ws');
const w = new WebSocket("wss://api.bitfinex.com/ws/2");
const fs = require('fs');
const process = require('process');

let subscriptions = [];
w.onmessage = function(msg) {
    let messageObject = JSON.parse(msg.data);
    if (messageObject.event && messageObject.event == 'subscribed') {
        subscriptions[messageObject.chanId] = messageObject.symbol;
        if (subscriptions[messageObject.chanId][0] == 't') {
            subscriptions[messageObject.chanId] = subscriptions[messageObject.chanId].substring(1);
        }
    }
    if (Array.isArray(messageObject) && messageObject.length == 3 && messageObject[1] == 'te') {
        let symbol = subscriptions[messageObject[0]];
        MessagePusher.send('bitfinex', symbol, messageObject[2][2],  messageObject[2][3])

    }
};

w.onclose = function(ev)
{
    fs.writeFile("scraper-errors.log", "error occured, bittrex, reason: " + ev.reason);
    process.exit(1);
};

w.onopen = function() {
    msg = {
        event: 'subscribe',
        channel: 'trades',
        symbol: 'BTCUSD'
    };
    w.send(JSON.stringify(msg));
    msg = {
        event: 'subscribe',
        channel: 'trades',
        symbol: 'BTCEUR'
    };
    w.send(JSON.stringify(msg));
};
