(function() {

  angular

    .module('du-admin')

    .controller('FlowStatsCtrl', function() {

      var $theForm = $('form[name="search-form"]');
      $theForm.on('submit', function(e) {
        e.preventDefault();

        $.ajax({
          type: 'POST',
          url: $theForm.attr('action'),
          data: $theForm.serialize(),
          cache: false,
          beforeSend: DU.ajax.beforeSend,
          complete: DU.ajax.complete,
          error: DU.ajax.error,
          success: function(data, textStatus) {
            var tplHtml = angular.element(data);
            angular.element('[ui-view]').html(tplHtml);
            angular.element(document).injector().invoke(function($compile) {
              var scope = angular.element(tplHtml).scope();
              $compile(tplHtml)(scope);
              scope.$digest();
            });
          }
        });

      });

      require(['echarts'], function(echarts) {

        $('#chart-view, #chart-area, #chart-from, #chart-browser, #chart-system').css({height: '400px'}).on('load-chart', function(e) {

          // 初始化echarts實例
          var chart = echarts.init(this);
          // 使用制定的配置項和數據顯示圖表
          chart.setOption($(this).data('table'));
          $(window).on('resize chart-resize', function(e) {
            chart.resize();
          });

        }).trigger('load-chart');

        $('#stats-page-content-area [data-plugins="tools-tabs"] li').on('click', function(e) {
          $(window).trigger('chart-resize');
        });

      });

    });
})();