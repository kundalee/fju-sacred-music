(function() {

  angular

    .module('du-admin')

    .controller('SearchengineStatsCtrl', function() {

      require(
        [
          'goog!visualization,1,packages:[corechart],language:zh',
        ],
        function() {

          var _wp = $('#stats-page-content-area').width();
          $('#chart-view').on('load.chart', function(e) {
            google.visualization.drawChart({
              chartType: 'ColumnChart',
              dataTable: $(this).data('table'),
              options: {
                height: (_wp > 480 ? 400 : 800),
                legend: {
                  position: 'bottom'
                },
                vAxis: {
                  format: '#'
                },
                orientation: (_wp > 480 ? 'horizontal' : 'vertical'),
                pointSize: 5
              },
              containerId: 'chart-view'
            });
          }).trigger('load.chart');

          $(window).on('resize', function(e) {
            _wp = $('#stats-page-content-area').width();
            $('div.tab-pane:visible [data-toggle="chart"]').trigger('load.chart');
          }).trigger('resize');
        }
      );

    });

})();