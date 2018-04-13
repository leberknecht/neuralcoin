function PredictionChartManager() {

    function drawChart(networkData) {
        var $container = $('#prediction-chart-container')[0];
        var dataTable = new google.visualization.DataTable();

        dataTable.addColumn('date', 'time');
        dataTable.addColumn('number', 'successRate');

        var totalLength = networkData.length;
        for (var x = 0; x < totalLength; x++) {
            var setInfo = networkData[x];
            dataTable.addRows([[new Date(setInfo.date), parseInt(setInfo.percentage)]]);
        }

        var options = {
            'title': 'correct direction hits per day',
            'chartArea': {
                left:10,
                top:20,
                width:"90%",
                height:"100%"
            },
            'legend': {'position': 'bottom'}
        };

        var chart = new google.visualization.LineChart($container);
        chart.draw(dataTable, options);
    }

    this.bind = function()
    {
        data = $('#prediction-data').data('chart-data');
        drawChart(data);
    }
}


function initCharManager()
{
    var predictionChartManager = new PredictionChartManager();
    predictionChartManager.bind();
}

google.charts.load('current', {'packages': ['corechart'], 'callback': initCharManager});