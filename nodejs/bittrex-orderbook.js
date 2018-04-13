//https://bittrex.com/api/v1.1/public/getorderbook?market=BTC-LTC&type=both

const MessagePusher = require('./MessagePusher');

const https = require('https');

var symbols = [
    'BTC-LTC',
    'BTC-BCC',
    'BTC-XRP',
    'BTC-XVG',
    'BTC-RDD',
    'BTC-NXT',
    'BTC-CVC',
    'BTC-HMQ',
    'BTC-DOGE',
    'BTC-ETH',
    'BTC-DGB',
    'BTC-SC',
    'BTC-NAV,',
    'BTC-VTC',
];


function fetchOrderBooks() {
    symbols.forEach(function (symbol) {
        console.log('fetching order book for ' + symbol);

        https.get('https://bittrex.com/api/v1.1/public/getorderbook?market=' + symbol + '&type=both', (resp) => {
            let data = '';

            resp.on('data', (chunk) => {
                data += chunk;
            });

            resp.on('end', () => {
                console.log('order book data received');
                let response = JSON.parse(data);
                MessagePusher.sendOrderBook('bittrex', symbol, response.result);
                console.log('order book send to queue for symbol: ' + symbol);
            });
        }).on("error", (err) => {
            console.log("Error: " + err.message);
        });
    });
}

fetchOrderBooks();
setInterval(fetchOrderBooks, 60000);

console.log('done');
