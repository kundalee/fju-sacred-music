(function() {

  angular

    .module('du-admin')

    .controller('FocusListCtrl', function() {

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

    .controller('FocusFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');
      var songId = $('input[name="song_id"]', $theForm).val();

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

      require(['mCustomScrollbar', 'div-option', 'serializeObject'], function() {

        $('#define-song-items').mCustomScrollbar({
          theme: 'light-thick',
          scrollbarPosition: 'outside',
          autoExpandScrollbar: true,
          mouseWheel: {
            preventDefault: true
          },
          advanced: {
            autoExpandHorizontalScroll: true
          }
        });
        $('#search-song-items').mCustomScrollbar({
          theme: 'light-thick',
          scrollbarPosition: 'outside',
          autoExpandScrollbar: true,
          mouseWheel: {
            preventDefault: true
          },
          advanced: {
            autoExpandHorizontalScroll: true
          }
        });

        divOption.url = $theForm.attr('action');
        divOption.params.focus_id = $('[name="song_id"]', $theForm).val();

        divOption.callback = function(res) {

          if (res.reload_define_song) {
            $.ajax({
              type: 'POST',
              dataType: 'json',
              url: divOption.url,
              data: $.extend(divOption.params, {
                action: 'load_define_song',
              }),
              cache: false,
              success: function(data, textStatus) {
                if (data.error == 0) {
                  divOption.callback(data);
                }
              }
            });
            return;
          }

          if (res.load_define_song) {
            //  .scroll-content
            $('#define-song-items .mCSB_container').html(res.content);
            return;
          }

          if (res.find_define_song) {
            $('#search-song-items .mCSB_container').html(res.content);
            return;
          }
        };
        divOption.callback({reload_define_song: true});

        /* 搜尋歌曲 */
        $('#search-song-button').on('click', function(e) {
          $.ajax({
            type: 'POST',
            dataType: 'json',
            url: divOption.url,
            data: $.extend(divOption.params, {
              action: 'search_song',
            }, $('.ajax-song-search').serializeObject()),
            cache: false,
            success: function(data, textStatus) {
              if (data.error == 0) {
                divOption.callback(data);
              }
            }
          });
        });

        $('#join-checked-true-button').on('click', function(e) {
          e.preventDefault();
          $('input[name^="join_song"]').prop('checked', true);
        });
        $('#join-checked-false-button').on('click', function(e) {
          e.preventDefault();
          $('input[name^="join_song"]').prop('checked', false);
        });
        $('#join-define-song').bind('click', function() {
          var errArr = [];

          if ($('input[name^="join_song"]:checked').size() == 0) {
            errArr.push('未勾選要加入的歌曲');
          }

          if (errArr.length > 0) {
            DU.dialog.alert(errArr);
          } else {

            $.ajax({
              type: 'POST',
              dataType: 'json',
              url: divOption.url,
              data: $.extend(divOption.params, {
                action: 'add_define_song',
              }, $('.ajax-add-define-song').serializeObject()),
              cache: false,
              success: function(data, textStatus) {
                if (data.error == 0) {
                  divOption.callback(data);
                }
              }
            });
          }
        });

        $('#update-checked-true-button').on('click', function(e) {
          e.preventDefault();
          $('input[name^="update_song"]').prop('checked', true);
        });
        $('#update-checked-false-button').on('click', function(e) {
          e.preventDefault();
          $('input[name^="update_song"]').prop('checked', false);
        });

        $('#drop-define-song').on('click', function(e) {
          if ($('input[name^="update_song"]:checked').size() == 0) {
            DU.dialog.alert('未勾選要刪除的歌曲');
          } else {
            $.ajax({
              type: 'POST',
              dataType: 'json',
              url: divOption.url,
              data: $.extend(divOption.params, {
                action: 'drop_define_song',
              }, $('.ajax-update-define-song').serializeObject()),
              cache: false,
              success: function(data, textStatus) {
                if (data.error == 0) {
                  divOption.callback(data);
                }
              }
            });
          }
        });

        $('#define-song-items').on('click mouseover mouseout', '[data-cell-edit]', function(e) {
          var $editObj = $(this);
          if (e.type == 'mouseover') {
            $editObj.attr('title', '點擊修改內容').css({borderBottom: '1px solid #000000'});
          } else if (e.type == 'mouseout') {
            $editObj.css({borderBottom: 'none'});
          } else if (e.type == 'click') {

            var _focusId = $editObj.closest('tr').data('item-id');

            /* 保存原始的內容 */
            var orgVal = $editObj.text();

            /* 創建一個輸入框 */
            var $txtObj = $(document.createElement('INPUT'));
            $txtObj.val((orgVal == 'N/A') ? '' : orgVal);
            $txtObj.css({textAlign: 'right'});

            /* 隱藏對像中的內容，並將輸入框加入到對像中 */
            $editObj.html('');
            $editObj.after($txtObj);
            $txtObj.addClass('form-control input-sm').focus();

            $txtObj.on('keypress', function(evt) { /* 編輯區輸入事件處理函數 */
              if (evt.keyCode == 13 && editType != 'textarea') {
                this.blur();
                return false;
              }
              if (evt.keyCode == 27) {
                this.parentNode.innerHTML = org;
              }
            }).on('blur', function(evt) { /* 編輯區失去焦點的處理函數 */
              var txtVal = $txtObj.val();
              if (txtVal.length == 0 || txtVal != orgVal) {
                $.ajax({
                  type: 'POST',
                  url: $theForm.attr('action'),
                  cache: false,
                  data: {
                    action: 'relation_edit_' + $editObj.data('cell-edit'),
                    val: $txtObj.val(),
                    focus_id: _focusId,
                    song_id: songId
                  },
                  dataType: 'json',
                  // beforeSend: _self.ajaxBeforeSend,
                  // complete: _self.ajaxComplete,
                  // error: _self.ajaxError,
                  success: function(data, textStatus) {
                    if (data.message) {
                      DU.dialog.alert(data.message);
                    }
                    $txtObj.remove();
                    if (data.error == 0) {
                      if (txtVal == '') {
                        $editObj.text('N/A');
                      } else if (txtVal == data.content) {
                        $editObj.html(txtVal.replace(/\n/g, '<br>'));
                      } else {
                        $editObj.html(data.content.toString().replace(/\n/g, '<br>'));
                      }
                    } else {
                      $editObj.html(org);
                    }
                  }
                });
              } else {
                $txtObj.remove();
                $editObj.html(org);
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