function ChartManager() {
    let dataUrl = '/trading-tools/symbol-data/{symbol}/{exchange}/{offsetSeconds}';
    let that = this;

    this.getData = function(symbolName, link) {
        let targetUrl = dataUrl;
        let offsetSeconds = 60 * 20;
        targetUrl = targetUrl.replace('{offsetSeconds}', offsetSeconds.toString());
        targetUrl = targetUrl.replace('{exchange}', $('#exchange-name').val());
        targetUrl = targetUrl.replace('{symbol}', 'BTC' + symbolName);
        $.post(targetUrl, null, function (response) {
            let chartData = JSON.parse(response);
            drawChart(chartData);
        });
        $('#current-symbol').attr('value', symbolName).attr('href', link);
        $('#current-symbol-name').html(
                '<a target="_blank" href="' + link + '">' + symbolName + '</a>'
            ).removeClass('hidden');
    };

    this.formatNumber = function(number)
    {
        if (number > 10) {
            return number.toFixed(6 - ( Math.log(parseInt(number))  / Math.LN10 ));
        } else {
            return number.toFixed(6);
        }
    };

    function drawChart(chartData) {
        let $stockPreviewContainer = $('#symbol-chart')[0];

        let dataTable = new google.visualization.DataTable();

        dataTable.addColumn('date', 'time');
        dataTable.addColumn('number', 'price');

        for (let x = 0; x < chartData.length; x++) {
            let rowData = [];

            let dateTime = new Date(chartData[x]['time']);
            rowData.push(dateTime);
            rowData.push(chartData[x]['price']);
            dataTable.addRows([rowData]);
        }

        let options = {
            'height': 300
        };

        let chart = new google.visualization.LineChart($stockPreviewContainer);
        chart.draw(dataTable, options);
    }

    this.bind = function () {
        $('body').on('click', 'a.high-raise-symbol', function(e){
            e.preventDefault();
            let domElement = $(this);
            that.getData(domElement.html(), domElement.attr('href'));
        });
    }
}

let chartManager = new ChartManager();

function initCharManager()
{
    chartManager.bind();
}

google.charts.load('current', {'packages': ['corechart'], 'callback': initCharManager});

let $networkSelect = $('select[name=network-options]').first();
$networkSelect.on('change', function(event){
    $('.network-info-container').hide();
    $('#' + $(this).val() + '-info').removeClass('hidden').show();

});

$networkSelect.trigger('change');

$('#run-network-button').on('click', function() {
    let currentSymbol = $('#current-symbol').val();
    let predictionUrl = $networkSelect.find('option:selected').data('prediction-url');
    if (!currentSymbol || !predictionUrl || predictionUrl === '') {
        return;
    }

    predictionUrl += '?symbol=BTC' + currentSymbol;

    $.post(predictionUrl, null, function (response) {
        let chartData = JSON.parse(response);
        let yesProbability = chartManager.formatNumber(chartData.outputData[1] * 100);
        let noProbability = chartManager.formatNumber(chartData.outputData[0] * 100);
        $('#prediction-no').html(noProbability.toString() + ' %');
        $('#prediction-yes').html(yesProbability.toString() + ' %');
        $('#prediction-no-percentage').css('width', Math.ceil(noProbability).toString() + '%');
        $('#prediction-yes-percentage').css('width', Math.ceil(yesProbability).toString() + '%');
        document.title = currentSymbol + ': yes: ' +  yesProbability + '%' +
            ' - No: ' +  noProbability;
    });
});

function refreshPrediction() {
    let currentSymbol = $('#current-symbol');
    if (currentSymbol) {
        let symbolName = currentSymbol.val();
        if (!symbolName || symbolName === '') {
            return;
        }
        $('#run-network-button').trigger('click');
        chartManager.getData(symbolName, currentSymbol.attr('href'));
    }

    setTimeout(refreshPrediction, 15000);
}

refreshPrediction();