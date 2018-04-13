let tickerHost = $('#feed-ticker-container').data('ticker-host');
let webSocket = new WebSocket(tickerHost);
let pause = false;
let highChangeMode = false;

function log10(val) {
    return Math.log(val) / Math.LN10;
}

let lastPrices = [];

function ensureKeys(exchange, symbol)
{
    if (undefined === lastPrices[exchange])    {
        lastPrices[exchange] = [];
    }
    if (undefined === lastPrices[exchange][symbol])    {
        lastPrices[exchange][symbol] = 0.0;
    }
}

webSocket.onmessage = function (event) {
    if (pause) {
        return;
    }

    let feedData = JSON.parse(event.data);
    let $newRow = $('.hidden.latest-quotes-row:first').clone();
    $newRow.removeClass('hidden');
    $newRow.addClass('ticker-row');
    $newRow.find('.exchange-cell').text(feedData.exchange);
    let baseCurrency = feedData.symbol.match(/^(BTC|USDT|ETH|XMR|BNB|ETC)/)[0];
    let symbolName = feedData.symbol.replace(/^(BTC|USDT|ETH|XMR|BNB|ETC)/, "");
    $newRow.find('.symbol-cell a').text(symbolName);

    let urls = {
        'bittrex': 'https://bittrex.com/Market/Index?MarketName='+ baseCurrency + '-' + symbolName,
        'poloniex': 'https://poloniex.com/exchange#'+ baseCurrency + '_' + symbolName,
        'binance': 'https://www.binance.com/trade.html?symbol=' + symbolName + '_' + baseCurrency
    };
    
    $newRow.find('.symbol-cell a').attr(
        'href',
        urls[feedData.exchange]
    );

    let price = parseFloat(feedData.price);

    ensureKeys(feedData.exchange, feedData.symbol);
    let lastPrice = lastPrices[feedData.exchange][feedData.symbol];

    let highChange = false;
    let threshold = 2;
    let thresholdConfig = $('#high-raise-threshold');
    if (thresholdConfig) {
        threshold = parseInt(thresholdConfig.val());
    }
    if (lastPrice && price / lastPrice > 1 + (threshold / 100)) {
        highChange = true;
    }
    if (lastPrice && price / lastPrice < 1 - (threshold / 100)) {
        highChange = true;
    }

    lastPrices[feedData.exchange][feedData.symbol] = price;

    if (highChangeMode && !highChange) {
        return;
    }

    if (highChangeMode && highChange) {
        let change = ((100 / lastPrice) * price) - 100;
        $newRow.find('.price-cell').text(change.toFixed(2));
    } else if (price > 10) {
        $newRow.find('.price-cell').text(price.toFixed(6 - ( log10(parseInt(price)))));
    } else {
        $newRow.find('.price-cell').text(price.toFixed(6 ));
    }


    $('#feed-maker-row').after($newRow);
    let $table = $('#latest-quotes-table');

    while(($table.find('tr.ticker-row')).length > 9) {
        $table.find('tr.ticker-row:last').remove();
    }
};

$('#ticker-pause-button').on('click', function(){
    if ($(this).data('status') == 'unpaused') {
        pause = true;
        $(this).data('status', 'paused');
        $(this).removeClass('glyphicon-pause');
        $(this).addClass('glyphicon-play');
    } else {
        pause = false;
        $(this).data('status', 'unpaused');
        $(this).addClass('glyphicon-pause');
        $(this).removeClass('glyphicon-play');
    }
});


$('#ticker-highchangemode-button').on('click', function(){
    if ($(this).data('status') == '0') {
        highChangeMode = true;
        $('.ticker-row').remove();
        $(this).data('status', '1');
        $(this).addClass('glyphicon-red');
    } else {
        highChangeMode = false;
        $(this).data('status', '0');
        $(this).removeClass('glyphicon-red');
    }
});