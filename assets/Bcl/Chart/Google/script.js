BclChartGoogle = {
    init : function() {
        $('.bcl-chart-google').each(function(idx, elm) {
            let self = this;
            let elementId = $(elm).attr('id');
            let chartType = $(elm).data('type');
            let chartCols = $(elm).data('columns');            
            let chartRows = $(elm).data('rows');                       
            let chartOptions = $(elm).data('options');            
            google.charts.load('current', {'packages':['corechart']});
            google.charts.setOnLoadCallback(function() {                
                BclChartGoogle.drawChart(
                    elementId,
                    chartType,
                    chartCols,
                    chartRows,
                    chartOptions
                );
            });
        });
    },
    drawChart : function drawChart(elementId, chartType, columns, rows, options) 
    {                                        
        let dataset = new google.visualization.DataTable();        
        for (let col in columns) {            
            dataset.addColumn(columns[col], col);
        }        
        dataset.addRows(rows);
        let container = document.getElementById(elementId);
        let chart = new google.visualization[chartType](container);
        chart.draw(dataset, options);
    }    
};

$(document).ready(function() {
    BclChartGoogle.init();
});    
