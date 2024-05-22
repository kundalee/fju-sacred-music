(function() {

  angular

    .module('du-admin')

    .controller('BulletinListCtrl', function() {

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

    .controller('BulletinFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');
      var bulletinId = $('input[name="bulletin_id"]', $theForm).val();

      $('.picture-file-help [data-toggle="remove"]').on('click', function(e) {
        e.preventDefault();
        var $rEle = $(this);
        DU.dialog.confirm('您確認要刪除圖檔嗎？', function(result) {
          if (result) {
            $.ajax({
              url: $theForm.attr('action'),
              type: 'GET',
              data: {
                action: 'cancel_picture',
                is_ajax: 1,
                id: $rEle.data('row-id')
              },
              cache: false,
              dataType: 'json',
              beforeSend: DU.ajax.beforeSend,
              complete: DU.ajax.complete,
              error: DU.ajax.error,
              success: function(data, textStatus) {
                $rEle.closest('.picture-file-help').remove();
              }
            });
          }
        });
      });

      require(['selectzone'], function() {

        var $relGoodsSource = $('[name="rel_bulletin_src_sel"]', $theForm);
        var $relGoodsTarget = $('[name="rel_bulletin_tgt_sel"]', $theForm);

        var selGoodsObj = new SelectZone(1, $relGoodsSource.get(0), $relGoodsTarget.get(0));
        selGoodsObj.fileName = $theForm.attr('action') + '?is_ajax=1';

        $relGoodsSource.on('dblclick', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.addItem(false, 'add_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });
        $relGoodsTarget.on('dblclick', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.dropItem(false, 'drop_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });

        $('.add-col-rel-bulletin', $theForm).on('click', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.addItem(true, 'add_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });
        $('.add-one-rel-bulletin', $theForm).on('click', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.addItem(false, 'add_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });
        $('.drop-one-rel-bulletin', $theForm).on('click', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.dropItem(false, 'drop_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });
        $('.drop-col-rel-bulletin', $theForm).on('click', function(e) {
          var isSingle = $('input[name="is_single"]:checked', $theForm).val();
          selGoodsObj.dropItem(true, 'drop_rel_bulletin', {
            bulletin_id: bulletinId,
            is_single: isSingle
          });
        });

        $('#search-rel-bulletin', $theForm).on('click', function(e) {
          selGoodsObj.loadOptions('get_bulletin_list', {
            keywords: $('input[name="rel_title"]').val(),
            category: $('select[name="rel_bulletin_cat_id"]').val(),
            bulletin_id: bulletinId
          });
        });

      });

      var langLabArr = $('.lang-tabs:first li').map(function() {
        return $.trim($(this).text())
      }).get() || [];

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

        var errArr = [];

        if ($('[name="title"]', this).val() == '') {
          errArr.push('標題未填寫!');
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