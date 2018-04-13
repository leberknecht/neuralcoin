const MessagePusher = require('./MessagePusher');
const process = require('process');
let  bittrex = require('./node.bittrex.api.js');
let  yaml = require('js-yaml');
let  fs = require('fs');

let  configParametersPath = __dirname + '/../app/config/parameters.yml';
let  configParameters = yaml.safeLoad(fs.readFileSync(configParametersPath, 'utf8')).parameters;
console.log('bittrex api key: ' + configParameters.bittrex_api_key);

function handleSymbolDelta(marketsDelta, lastValues) {
    let  pairname = marketsDelta.MarketName.replace('-', '');
    let  currentVolume = marketsDelta.Volume;
    let  currentPrice = marketsDelta.Last;
    let  currentTime = marketsDelta.TimeStamp;
    if (lastValues[pairname]) {
        if (lastValues[pairname]['lastVolume'] != currentVolume) {
            let volumeDiff = currentVolume - lastValues[pairname]['lastVolume'];
            if (volumeDiff < 0) {
                volumeDiff *= -1;
            }
            lastValues[pairname]['lastVolume'] = currentVolume;
            lastValues[pairname]['lastPrice'] = currentPrice;
            MessagePusher.send('bittrex', pairname, volumeDiff, currentPrice);
        }
    } else {
        lastValues[pairname] = {lastVolume: currentVolume, lastPrice: currentPrice, time: currentTime};
    }
}

function handleSummaryUpdate(data, lastValues) {
    data.A.forEach(function (data_for) {
        data_for.Deltas.forEach(function (marketsDelta) {
            handleSymbolDelta(marketsDelta, lastValues);
        });
    });
}

function listenCallback(lastValues) {
    bittrex.websockets.listen(function (data, client) {
        if (data.M === 'updateSummaryState') {
            handleSummaryUpdate(data, lastValues);
        }
    });
}

function readFeed()
{
    bittrex.options({
        'apikey' : configParameters.bittrex_api_key,
        'apisecret' : configParameters.bittrex_api_secret
    });

    let  lastValues = {};

    bittrex.websockets.client(function(wsclient){
        wsclient.serviceHandlers.onerror = function() {
          console.log('connection to bittrex failed, quitting.');
          process.exit(3);
        };
        wsclient.serviceHandlers.connected = function () {
            console.log('Connected !');
            wsclient.call('CoreHub', 'SubscribeToSummaryDeltas').done(function(err, result) {
                if (err) {
                    return console.error(err);
                }
                if (result === true) {
                    console.log('Subscribed to tickers');
                    listenCallback(lastValues);
                }
            });
        };
    });
}

try {
    readFeed();
} catch (err) {
    process.exit(2);
}
