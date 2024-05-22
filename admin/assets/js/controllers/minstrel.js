(function() {

  angular

    .module('du-admin')

    .controller('MinstrelListCtrl', function() {

      var storageUnits = ['B', 'KB', 'MB', 'GB', 'TB'];
      var storageHex = 1024;
      var format = function(num, hex, units, dec, forTable) {

        num = num || 0;
        dec = dec || 0;
        forTable = forTable || false;
        var level = 0;

        // 详细报表中表格数据的最小单位为 KB 和 万次
        if (forTable) {
          num /= hex;
          level++;
        }
        while (num >= hex) {
          num /= hex;
          level++;
        }

        if (level === 0) {
          dec = 0;
        }

        return {
          base: num.toFixed(dec),
          unit: units[level],
          format: function(sep) {
            sep = sep || '';
            return this.base + sep + this.unit;
          }
        };
      };

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

        $('a[href="#upload-excel"]').on('click', function(e) {
          e.preventDefault();

          var tpl = '' +

          '<div class="well">' +
            '<h4 class="lighter">' +
              '<i class="far fa-hand-point-right icon-animated-hand-pointer blue"></i>' +
              '<a href="minstrel.php?action=import_example" data-toggle="modal" class="pink"> 下載匯入檔案 </a>' +
            '</h4>' +
            '<ul class="list-unstyled spaced">' +
              '<li>' +
                '<i class="far fa-bell bigger-110 red"></i>' +
                ' 請使用我們所提供的檔案，並依據我們提供的規則進行編輯資料。' +
              '</li>' +
              '<li>' +
                '<i class="fas fa-wrench bigger-110 green"></i>' +
                ' 將編輯好的資料上傳到伺服器，程式端會依據您輸入的內容進行批次建立資料。' +
              '</li>' +
            '</ul>' +
          '</div>' +

          '<div class="file-upload dropzone well dz-clickable">' +
            '<div class="dz-default dz-message">' +
              '<div class="space-10"></div>' +
              '<i class="fas fa-cloud-upload-alt blue fa-5x"></i>' +
              '<div class="space-2"></div>' +
              '<span class="bigger-110 bolder">' +
                '拖曳檔案至此' +
              '</span>' +
              '<div class="space-10"></div>' +
              '<div class="btn-group-lg">' +
                '<a href="#" data-action="upload" class="btn btn-primary btn-outline">' +
                  '<i class="fas fa-plus fa-fw"></i>' +
                  '&nbsp;選擇檔案' +
                '</a>' +
              '</div>' +
              '<div class="space-10"></div>' +
            '</div>' +
          '</div>' +
          '<div class="file-list"></div>';

          DU.dialog.show({
            type: 'type-success',
            size: 'size-normal',
            title: '歌曲',
            message: tpl,
            onshow: function(dialogRef) {
              dialogRef.getModalBody().css('padding', 0);
            },
            onshown: function(dialogRef) {

              var $domEle = dialogRef.getModalBody();

              require(
                [
                  'plupload',
                  'toastr'
                ],
                function(plupload, toastr) {

                  var toastrTemplate = function() {
                    toastr.options = {
                      closeButton: true,
                      debug: false,
                      newestOnTop: true,
                      progressBar: true,
                      positionClass: 'toast-bottom-right',
                      preventDuplicates: false,
                      showDuration: 300,
                      hideDuration: 1000,
                      timeOut: 6000,
                      extendedTimeOut: 6000,
                      showEasing: 'swing',
                      hideEasing: 'linear',
                      showMethod: 'fadeIn',
                      hideMethod: 'fadeOut'
                    };
                  };

                  var $uploadEle = $domEle.find('.file-upload');
                  var uploader = new plupload.Uploader({
                    runtimes: 'html5,flash,silverlight,html4',
                    browse_button: $uploadEle.find('[data-action="upload"]').get(0),
                    container: $uploadEle.get(0),
                    drop_element: $uploadEle.get(0),
                    dragdrop: true,
                    url: $theForm.attr('action'),
                    multipart_params: {
                      action: 'upload_import'
                    },
                    filters: {
                      mime_types: [{'title':'Excel 檔案','extensions':'xlsx,xls'}],
                      max_file_size: '20MB', // 最大只能上傳 2MB 的文件
                    },
                    flash_swf_url: 'assets/Moxie.swf',
                    silverlight_xap_url: 'assets/Moxie.xap',
                    unique_names: true,
                    chunk_size: '1mb'
                  });

                  uploader.bind('Init', function(up, params) {
                    // 取消上傳
                    $domEle.on('click', 'a.delete[data-upload-model="queue"]', function(e) {
                      e.preventDefault();
                      var fileId = $(this).data('file-id');
                      uploader.removeFile(fileId);
                      $('#' + fileId).remove();
                    });
                  });

                  uploader.bind('FilesAdded', function(up, files) {

                    $.each(files, function(index, file) {
                      var tpl = '' +
                      '<div id="' + file.id + '" class="row js-build">' +
                        '<div class="col-xs-8">' +
                          '<table>' +
                            '<tr>' +
                              '<td><a href="#" data-upload-model="queue" data-file-id="' + file.id + '" class="delete"><i class="fa fa-times"></i></a></td>' +
                              '<td><span class="name">' + file.name + '</span></td>' +
                            '</tr>' +
                          '</table>' +
                        '</div>' +
                        '<div class="col-xs-4 text-right">' + plupload.formatSize(file.size) + '</div>' +
                        '<div class="col-xs-12">' +
                          '<div class="progress">' +
                            '<div class="progress-bar progress-bar-info">' +
                              '<span></span>' +
                            '</div>' +
                          '</div>' +
                        '</div>' +
                      '</div>';

                      $domEle.find('.file-list').append(tpl);

                      uploader.start();

                    });

                    up.refresh(); // Reposition Flash/Silverlight
                  });

                  uploader.bind('BeforeUpload', function(up, file) {
                    up.settings.multipart_params.old_name = file.name;
                  });

                  uploader.bind('UploadProgress', function(up, file) {
                    var speed = up.total.bytesPerSec;
                    var size = format(file.loaded, storageHex, storageUnits, 2);
                    speed = format(speed, storageHex, storageUnits, 2);
                    $('#' + file.id + ' .progress-bar')
                      .css({width: file.percent + '%'})
                      .find('span')
                      .html('已上傳： ' + size.base + size.unit + ' 上傳速度： ' + speed.base + speed.unit + '/s');
                  });

                  uploader.bind('Error', function(up, err) {
                    toastrTemplate();
                    toastr['error'](
                      '<div>Error: ' + err.code + '<br>Message: ' + err.message + (err.file ? '<br>File: ' + err.file.name : '') + '</div>'
                    );
                    up.refresh(); // Reposition Flash/Silverlight
                  });

                  uploader.bind('FileUploaded', function(up, file, data) {
                    uploader.removeFile(file);
                    try {
                      var response = $.parseJSON(data.response);
                      var $cFileDiv = $('#' + file.id);
                      toastrTemplate();
                      if (response.error == 0) {
                        $cFileDiv.remove();
                        toastr['info'](
                          '檔案:' + file.name + ' 已上傳完成',
                          '系統訊息'
                        );
                      } else {
                        var $cPBar = $cFileDiv.find('.progress-bar');
                        $cPBar
                          .removeClass('progress-bar-info')
                          .children('span')
                          .text(response.message);
                        if (response.file_url) {
                          $cFileDiv
                            .find('span.name')
                            .wrap('<a target="_blank" href="' + response.file_url + '"></a>');
                          $cPBar.addClass('progress-bar-warning');
                          toastr['warning'](
                            response.message,
                            '系統訊息'
                          );
                        } else {
                          $cPBar.addClass('progress-bar-danger');
                          toastr['error'](
                            response.message,
                            '系統訊息'
                          );
                        }
                      }
                    } catch (e) {
                      dialogRef.close();
                      DU.dialog.show({
                        size: BootstrapDialog.SIZE_WIDE,
                        title: false,
                        message: data.response
                      });
                    }
                  });

                  uploader.bind('UploadComplete', function(up, files) {
                    listTable.filter.page = 1;
                    listTable.loadList();
                  });

                  uploader.init();

                  $uploadEle.on('dragenter dragover', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $uploadEle.css({borderColor: '#9585bf', opacity: 0.6});
                  }).on('drop dragleave', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    $uploadEle.css({borderColor: '#e3e3e3', opacity: 1});
                  });
                }
              );
            }
          });
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

    .controller('MinstrelFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');
      var minstrelId = $('input[name="minstrel_id"]', $theForm).val();

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