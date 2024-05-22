(function() {

  angular

    .module('du-admin')

    .controller('AdminLogsCtrl', function() {

      require(['list-table'], function() {

        var $listDiv = $('#list-div');

        listTable.url = $listDiv.closest('form').attr('action');
        listTable.loadAjax = true;
        listTable.recordCount = parseInt($listDiv.data('record-count'), 10);
        listTable.pageCount = parseInt($listDiv.data('page-count'), 10);
        listTable.filter = $listDiv.data('filter') || {};

        $('form[name="search-form"]').on('submit', function(e) {
          e.preventDefault();
          $(':checkbox, select[multiple]', this).each(function(index, domEle) {
            var fieldName = ($(domEle).attr('name').toString() || '').replace(/\[.*\]/, '');
            if (listTable.filter.hasOwnProperty(fieldName)) {
              eval('delete listTable.filter["' + fieldName + '"]');
            }
          });
          $.extend(true, listTable.filter, $(this).serializeObject());
          listTable.filter.page = 1;
          listTable.loadList();
        });

      });

      $('form[name="list-form"], form[name="drop-form"]').on('submit', function(e) {
        e.preventDefault();
        var $theForm = $(this);
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

    });

})();