function ChartManager() {
    let $remoteInfo = $('#remote-info');
    let previewDataUrl = $remoteInfo.data('preview-url');

    function getPreviewData() {
        let form = $('form[name=create_network_form]');
        let formData = form.serialize();
        $.post(previewDataUrl, formData, function (response) {
            let responseObject = JSON.parse(response);
            drawChart(responseObject);
            getTrainingDataPreview(responseObject);

            let symbols = [];
            let $selectBox = $('#create_network_form_symbols');

            for (let i = 0; i < responseObject.sourceInputs.length; i++) {
                let $optionElement = $selectBox.find('option[data-symbol-name="' + responseObject['sourceInputs'][i]['symbolName']+'"]');
                symbols.push($optionElement.val());
            }
            $selectBox.val(symbols);
        })
    }

    function formatNumber(price)
    {
        if (price > 10) {
            return price.toFixed(6 - ( Math.log(parseInt(price))  / Math.LN10 ));
        } else {
            return price.toFixed(6);
        }
    }

    function getTrainingDataPreview(responseObject) {
        let $trainingPreviewContainer = $('#training-data-preview-container');

        let data = responseObject.trainingData.rawData;

        let html = '<p>Training-sets: ' + data.length + '</p><table id="preview-table" class="table"><th>Inputs</th><th>Outputs</th>';

        let $typeContainer = $('#create_network_form_outputConfig_type');
        let boolHits = 0;
        let boolMode = ($typeContainer.val() == 2 || $typeContainer.val() == 3);
        for (let x = 0; x < data.length; x++) {
            let inputs = data[x][0];
            let outputs = data[x][1];

            let inputStr = '';
            let outputStr = '';

            for (let i = 0; i < inputs.length; i ++) {
                inputStr += '<span class="preview-data-item">' + formatNumber(inputs[i]) + '</span>';
            }
            for (let i = 0; i < outputs.length; i++) {
                if (boolMode && i == 0 && outputs[1] == 1) {
                    boolHits++;
                }
                outputStr += '<span class="preview-data-item">' + formatNumber(outputs[i]) + '</span>';
            }
            html += '<tr><td>' + inputStr + '</td><td>' + outputStr + '</td></tr>';
        }
        html += '</table>';
        $trainingPreviewContainer.html(html);
        $('#input-data-shape-info').removeClass('hidden').text('Shape: [' + data[0][0].length + '][' + data[0][1].length + ']');

        let $boolInfoContainer = $('#bool-hit-info');
        if (boolMode) {
            $boolInfoContainer.html('(hits: ' + boolHits + ' / ' + data.length + ')');
            $boolInfoContainer.removeClass('hidden');
        } else {
            $boolInfoContainer.addClass('hidden');
        }

    }

    function drawOrderBooks(numberOfSymbols, orderBookData, symbolNames) {
        let orderBookChartData = [];
        for (let x = 0; x < numberOfSymbols; x++) {
            let lastOrderBookForSymbol = orderBookData[x][orderBookData[x].length - 1];
            let chartData = Array(lastOrderBookForSymbol.length);
            let accumlativeVolume = 0;

            for (let i = 0; i < lastOrderBookForSymbol.length; i += 2) {
                accumlativeVolume += lastOrderBookForSymbol[i + 1];
                if (i == lastOrderBookForSymbol.length / 2) {
                    accumlativeVolume = 0; //buy done, now sell
                }
                if (i < lastOrderBookForSymbol.length / 2) {
                    chartData[(lastOrderBookForSymbol.length / 2) - i - 1] = [lastOrderBookForSymbol[i], accumlativeVolume];
                } else {
                    chartData[i] = [lastOrderBookForSymbol[i], accumlativeVolume];
                }
            }

            let xValues = [],
                yValues = [];
            chartData.forEach((element) => {
                xValues.push(element[0]);
                yValues.push(element[1]);
            });

            orderBookChartData.push({
                x: xValues,
                y: yValues,
                type: 'scatter',
                name: symbolNames[x]
            });

        }

        Plotly.newPlot('order-book-preview', orderBookChartData, {
            title: 'Order Books (price as "% from last")'
        });
    }

    function drawChart(networkData) {
        let sourceInputs = networkData.sourceInputs;
        let numberOfSymbols = sourceInputs.length;
        let seperateInputs = $('#create_network_form_separateInputSymbols').is(':checked');

        let symbolNames = [];
        let dataTable = new google.visualization.DataTable();
        dataTable.addColumn('date', 'time');
        for (let x = 0; x < numberOfSymbols; x++) {
            let sourceInput = sourceInputs[x];
            let symbolName = $('#symbol-info-' + sourceInput.symbolId).data('symbol-name');
            symbolNames.push(symbolName);
            dataTable.addColumn('number', symbolName);
            dataTable.addColumn({type: 'string', role: 'tooltip', 'p': {'html': true}});
        }

        let orderBookData = [];
        let inputSteps = parseInt($('#create_network_form_inputSteps').val());
        let orderBookSteps = parseInt($('#create_network_form_orderBookSteps').val());
        let useOrderBook = $('#create_network_form_useOrderBooks').is(':checked');

        inputSteps = inputSteps > 3 ? 3 : inputSteps;

        for (let x = 0; x < networkData.trainingData.rawData.length; x++) {
            let rowData = [];
            if (sourceInputs[0].tradesData.length > x)
            {
                let dateTime = new Date(sourceInputs[0].tradesData[x]['time']);
                rowData.push(dateTime);
                for (let i = 0; i < numberOfSymbols; i++) {
                    if (useOrderBook) {
                        if (!orderBookData[i]) {
                            orderBookData[i] = [];
                        }
                        let start = inputSteps + (i * inputSteps + (i * ((orderBookSteps * 4) * inputSteps)));
                        orderBookData[i].push(networkData.trainingData.rawData[x][0].slice(
                            start,
                            start + (orderBookSteps * 4)
                        ));
                    } else {
                        orderBookSteps = 0
                    }

                    rowData.push(parseFloat(networkData.trainingData.rawData[x][0][(i * inputSteps) + (i * orderBookSteps * 4)]));
                    rowData.push(dateFormat(dateTime, 'mmm dd HH:MM') + '<br>Price: ' + sourceInputs[i].tradesData[x]['price']);

                    //https://www.youtube.com/watch?v=s3YyVK9j33o
                    if (seperateInputs) {
                        if (((x+1) % numberOfSymbols) != 0) {
                            x++;
                            i = -1;
                        } else {
                            i = numberOfSymbols;
                        }

                    }
                }
                dataTable.addRows([rowData]);
            }
        }

        let options = {
            'curveType': 'function',
            'height': 300,
            tooltip: {isHtml: true},
        };

        let chart = new google.visualization.LineChart($('#stock-chart-preview')[0]);
        chart.draw(dataTable, options);

        if (useOrderBook) {
            $('#order-book-preview').first().show();
            drawOrderBooks(numberOfSymbols, orderBookData, symbolNames);
        } else {
            $('#order-book-preview').first().hide();
        }
    }

    this.bind = function () {
        $('form[name=create_network_form]').on('change', function () {
            getPreviewData();
        });
        let $symbolSelect = $('#create_network_form_symbols');
        $symbolSelect.on('change', function() {
            let $selectedOptions = $(this).find('option:selected').clone();
            let $predictionTargetInput = $('#create_network_form_outputConfig_pricePredictionSymbol');
            let oldSelectedOption = $predictionTargetInput.find('option:selected');
            $predictionTargetInput.find('option').remove();
            $predictionTargetInput.append($selectedOptions);
            $predictionTargetInput.val(oldSelectedOption.val());
            if ($predictionTargetInput.filter('option:selected').length == 0) {
                $predictionTargetInput.val(
                    $($predictionTargetInput.find('option')[0]).val()
                );
            }
        });
        $symbolSelect.val(
            $($symbolSelect.find('option')[0]).val()
        );
        window.setTimeout(getPreviewData, 1000);

    }
}

function initCharManager()
{
    let chartManager = new ChartManager();
    chartManager.bind();
}

google.charts.load('current', {'packages': ['corechart'], 'callback': initCharManager});