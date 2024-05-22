(function() {

  angular

    .module('du-admin')

    .controller('AdminRoleListCtrl', function() {

      require(['list-table'], function() {

        var $listDiv = $('#list-div');

        listTable.url = $listDiv.closest('form').attr('action');
        listTable.loadAjax = true;

        listTable.query = $listDiv.data('page-query') || 'query';
        listTable.recordCount = parseInt($listDiv.data('record-count'), 10);
        listTable.pageCount = parseInt($listDiv.data('page-count'), 10);
        listTable.filter = $listDiv.data('filter') || {};

      });

    })

    .controller('AdminRoleFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');

      $('input[name="chkGroup"]', $theForm).on('click', function(e) {
        var $tEle = $(this).closest('.panel-heading');
        var $aEle = $('input:checkbox[name="action_code[]"]', $tEle.next());
        $aEle.prop('checked', this.checked);
      });

      var $allCodeEle = $('input[name="action_code[]"]');
      $allCodeEle.on('click', function(e) {
        var $cEle = $(this);
        var relevance = ($cEle.data('relevance').toString() || '').split(',');
        if (this.checked) {
          $.each(relevance, function(i, n) {
            $allCodeEle.filter('[value="' + n + '"]').prop('checked', true);
          });
        } else {
          $allCodeEle
            .not($cEle)
            .filter(function() {
              var $sEle = $(this);
              return $.inArray($cEle.val(), ($sEle.data('relevance').toString() || '').split(',')) > -1;
            })
            .prop('checked', false);
        }
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
        var errArr = [];
        if ($('input[name="role_name"]', this).val() == '') {
          errArr.push('名稱不能為空白!');
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