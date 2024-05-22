(function() {

  angular

    .module('du-admin')

    .controller('WebTemplateListCtrl', function() {

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

      var $theForm = $('form[name="list-form"]');
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

    })
    .controller('WebTemplateFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');

      var langLabArr = $('.lang-tabs:first li').map(function() {
        return $.trim($(this).text())
      }).get() || [];

      $('.picture-file-help [data-toggle="remove"]').on('click', function(e) {
        e.preventDefault();
        var $rEle = $(this);
        DU.dialog.confirm('您確認要刪除圖檔嗎？', function(result) {
          if (result) {
            $.ajax({
              type: 'POST',
              url: $theForm.attr('action'),
              data: $.extend({
                is_ajax: '1',
                action: 'del'
              }, $rEle.data('args') || {}),
              cache: false,
              dataType: 'json',
              beforeSend: DU.ajax.ajaxBeforeSend,
              complete: DU.ajax.ajaxComplete,
              error: DU.ajax.ajaxError,
              success: function(data, textStatus) {
                $rEle.closest('.picture-file-help').remove();
              }
            });
          }
        });
      });

      /**
       * 檢查表單輸入的數據
       */
      $theForm.on('submit', function(e) {

        e.preventDefault();

        // 去掉字符串起始和結尾的空格
        $('input[type="text"], textarea', this).val(function() {
          return $.trim(this.value);
        });

        // 解決 CKeditor 在 ajax 無法更新內容的問題
        if (typeof CKEDITOR !== 'undefined') {
          for (instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
          }
        }

        var isInsert = $('input[name="action"]', this).val() == 'insert';
        var errArr = [], errStr = '', langErr = [];

        if ($('[name="tpl_name"]', this).val() == '') {
          errArr.push('名稱未填寫!');
        }
        if (errArr.length > 0) {
          DU.dialog.alert(errArr);
          return false;
        }

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