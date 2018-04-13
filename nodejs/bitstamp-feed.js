const MessagePusher = require('./MessagePusher');
const yaml = require('js-yaml');
const fs   = require('fs');
let MockBrowser = require('mock-browser').mocks.MockBrowser;
let mock = new MockBrowser();
window = MockBrowser.createWindow();
document = mock.getDocument();
const Pusher = require('pusher-js');

let configParametersPath = __dirname + '/../app/config/parameters.yml';
let configParameters = yaml.safeLoad(fs.readFileSync(configParametersPath, 'utf8')).parameters;
let pusher = new Pusher(configParameters.pusher_id);
console.log('using parameters file: ' + configParametersPath);
console.log('pusher-id: ' + configParameters.pusher_id);

let tradesChannel = pusher.subscribe('live_trades');
let tradesChannelEur = pusher.subscribe('live_trades_btceur');

tradesChannel.bind('trade', function (data) {
    MessagePusher.send('bitstamp', 'BTCUSD', data.amount, data.price);
});

tradesChannelEur.bind('trade', function (data) {
    MessagePusher.send('bitstamp', 'BTCEUR', data.amount, data.price);
});