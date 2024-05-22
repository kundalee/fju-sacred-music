(function() {

  angular

    .module('du-admin')

    .controller('BulletinCatListCtrl', function() {

      require(['list-table'], function() {

        var $listDiv = $('#list-div');
        listTable.url = $listDiv.closest('form').attr('action');
        listTable.loadAjax = true;
      });

    })

    .controller('ListTriggerHandlerCtrl', function() {

      require(['treegrid'], function() {

        $('.table-tree').treegrid({
          saveState: true,
          expanderExpandedClass: 'fas fa-minus',
          expanderCollapsedClass: 'fas fa-plus'
        });

      });

    })

    .controller('BulletinCatSortableCtrl', function() {

      require(['nestable'], function() {

        var $domEle = $('.js-nestable');

        var sortable = $domEle.nestable({
          maxDepth: $domEle.data('max-depth')
        });

        $('#save-sortable').on('click', function(e) {

          e.preventDefault();
          var $theForm = $('form[name="list-form"]');
          $.ajax({
            type: 'POST',
            url: $theForm.attr('action'),
            data: {
              action: 'save_node',
              node: sortable.nestable('serialize')
            },
            cache: false,
            dataType: 'json',
            beforeSend: DU.ajax.beforeSend,
            complete: DU.ajax.complete,
            error: DU.ajax.error,
            success: function(data, textStatus) {
              if (data.message) {
                DU.dialog.alert(data.message);
              }
            }
          })
        });

      });

    })

    .controller('BulletinCatFormCtrl', function() {

      var langLabArr = $('.lang-tabs:first li').map(function() {
        return $.trim($(this).text())
      }).get() || [];

      var $theForm = $('form[name="the-form"]');

      /**
       * 檢查表單輸入的數據
       */
      $theForm.on('submit', function(e) {

        e.preventDefault();

        // 去掉字符串起始和結尾的空格
        $('input[type="text"], textarea', this).val(function() {
          return $.trim(this.value);
        });

        var errArr = [], errStr = '';

        var $catName = $('[name="cat_name"]', this);
        if ($catName.val() == '') {
          errArr.push('分類名稱不能為空!');
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

    })

    .controller('BulletinCatMoveCtrl', function() {

      var $theForm = $('form[name="the-form"]');

      /**
       * 檢查表單輸入的數據
       */
      $theForm.on('submit', function(e) {

        e.preventDefault();

        var errArr = [];
        var sourceId = $('select[name="cat_id"]', this).val();
        var targetId = $('select[name="target_cat_id"]', this).val();

        if (sourceId == 0) {
          errArr.push('來源分類未選擇');
        } else if (sourceId == targetId) {
          errArr.push('來源分類與目標分類相同');
        }
        if (targetId == 0) {
          errArr.push('目標分類未選擇');
        }

        if (errArr.length > 0) {
          if (errArr.length == 1) {
            DU.dialog.alert(errArr[0]);
          } else {
            errStr = '';
            for (var i = 0; i < errArr.length; i++) {
              errStr += (i + 1) + '. ' + errArr[i] + "\n";
            }
            DU.dialog.alert(errStr.replace(/\n/g, '<br>'));
          }
          return false;
        }

        DU.dialog.confirm('您確定要執行此操作？', function(result) {
          if (result) {
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
          }
        });

      });

    });

})();