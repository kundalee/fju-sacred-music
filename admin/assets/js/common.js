require(['du-core'], function() {

  $(document).ready(function(e) {

    DU.dialog.init();

    var $win = $(window), $doc = $(document), $body = $('body'), $form = $('form');

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

    /**
     * 產生隨機密碼
     *
     * @param     string    generated    密碼產生欄位名稱
     * @param     string    pwd          密碼欄位名稱
     * @param     string    pwdConf      密碼確認欄位名稱
     * @param     string    length       密碼長度
     *
     * @return    void
     */
    var genRandPwd = function(generated, pwd, pwdConf, length) {

      if (generated == undefined || pwd == undefined || pwdConf == undefined) {
        return;
      }

      length = length || 12;

      var $thisForm = $('[name="' + generated + '"]').closest('form');
      var $generated = $('[name="' + generated + '"]', $thisForm);
      var $pwd = $('[name="' + pwd + '"]', $thisForm);
      var $pwdConf = $('[name="' + pwdConf + '"]', $thisForm);
      var charList = 'abcdefhjmnpqrstuvwxyz23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
      var randPwd = '';

      for (i = 0; i < length; i++) {
        randPwd += charList.charAt(Math.floor(Math.random() * 53));
      }

      $pwd
        .add($pwdConf)
        .on(
          'keyup',
          function(e) {
            if (e.keyCode > 47 && e.keyCode < 91 || e.keyCode > 95 && e.keyCode < 106) {
              if ($generated.val().length > 0) {
                if ($(this).get(0) == $pwd.get(0)) {
                  $pwdConf.val('');
                } else {
                  $pwd.val('');
                }
              }

              $generated.val('');
            }
          }
        )
        .add($generated)
        .val(randPwd);

      return true
    };

    $body
      // 初始化動作
      .on('setup.DU', function(e) {
        var $_self = $(this);

        e.stopPropagation();

        // 所有子元素進行初始化動作
        $.each($._data($_self.get(0), 'events').init || [], function(index, domEle) {
          var $domEle = $_self.find(domEle.selector);
          if (domEle.namespace === 'DU' && domEle.selector != undefined && domEle.type === 'init') {
            if ($domEle.data('load-init') === undefined) {
              $domEle.trigger(domEle.origType + '.' + domEle.namespace);
            }
          }
        });
      })
      .on('click', 'a[href="#"]', function(e) {
        e.preventDefault();
      });

    if (!$body.hasClass('login-layout')) {

      $body.find('> .px-nav').pxNav();

      $body.find('> .px-footer').pxFooter();

      var sendMailDialog, sm = null;
      var startSendMail = function() {
        $.ajax({
          type: 'GET',
          url: 'index.php',
          data: {
            action: 'send_mail',
            is_ajax: 1,
          },
          cache: false,
          dataType: 'json',
          success: function(data, textStatus) {
            if (typeof(data.count) == 'undefined') {
              data.count = 0;
              data.message = '';
            }
            if (data.count == 0) {
              window.clearInterval(sm);
              sendMailDialog.getButton('dialog-start-send-mail').stopSpin();
            }
            if (typeof(data.goon) != 'undefined') {
              startSendMail();
            }

            sendMailDialog.getModalBody().html(data.message);
            sendMailDialog.getButton('dialog-start-send-mail').hide();
            sendMailDialog.getButton('dialog-close-send-mail').show();

            $('a[href="#send-mail-queue"] .infobox-data-number').text(data.count);
          }
        });
      };

      $body
        .on(
          {
            'click': function(e) {
              e.preventDefault();
              require(
                [
                  'bootstrap-dialog'
                ],
                function(BootstrapDialog) {

                  sendMailDialog = new BootstrapDialog.show({
                    title: '待發送佇列',
                    message: '正在處理郵件發送佇列',
                    onshow: function(dialogRef) {
                      dialogRef.getButton('dialog-close-send-mail').hide();
                    },
                    onhide: function(dialog) {
                      window.clearInterval(sm);
                    },
                    buttons: [
                      {
                        id: 'dialog-start-send-mail',
                        label: ' 開始發送',
                        icon: 'fas fa-play-circle fa-fw',
                        cssClass: 'btn-default',
                        action: function(dialog) {
                          this.disable().find('.fa-play-circle').removeClass('fa-play-circle').addClass('fa-spinner fa-spin');
                          dialog.getModalBody().show();
                          sm = window.setInterval(startSendMail, 5000);
                        }
                      },
                      {
                        id: 'dialog-close-send-mail',
                        label: '關閉',
                        icon: 'fas fa-circle-notch fa-fw',
                        cssClass: 'btn-success',
                        action: function(dialog) {
                          dialog.close();
                        }
                      }
                    ]
                  });
                  sendMailDialog.getModalBody().hide();
                }
              );
            }
          },
          'a[href="#send-mail-queue"]'
        )
        .on(
          {
            // 產生隨機密碼
            'click': function(e) {
              var $domEle = $(this);
              genRandPwd(
                $domEle.data('pwd-tips'),
                $domEle.data('pwd'),
                $domEle.data('pwd-conf'),
                $domEle.data('pwd-len')
              );
            }
          },
          '.generatePwd'
        )
        .on(
          {
            'click': function(e) {
              e.preventDefault();
              var tpl = '' +
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
                size: 'size-wide',
                title: false,
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
                        url: 'common.php',
                        multipart_params: {
                          action: 'upload',
                          handle: 'batch'
                        },
                        // filters: {
                        //   mime_types: [],
                        //   max_file_size: 0, // 最大只能上傳 2MB 的文件
                        // },
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
                            '<div class="col-xs-7">' +
                              '<table>' +
                                '<tr>' +
                                  '<td><a href="#" data-upload-model="queue" data-file-id="' + file.id + '" class="delete"><i class="fas fa-times"></i></a></td>' +
                                  '<td><span class="name">' + file.name + '</span></td>' +
                                '</tr>' +
                              '</table>' +
                            '</div>' +
                            '<div class="col-xs-5 text-right">' + plupload.formatSize(file.size) + '</div>' +
                            '<div class="col-xs-12">' +
                              '<div class="progress">' +
                                '<div class="progress-bar progress-bar-info progress-bar-striped">' +
                                  '<span></span>' +
                                '</div>' +
                              '</div>' +
                            '</div>' +
                          '</div>';

                          $domEle.find('.file-list').append(tpl);

                          up.start();
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
                        var response = $.parseJSON(data.response);
                        var $cFileProgress = $('#' + file.id);
                        if (response.error == 0) {
                          $cFileProgress.remove();
                        } else {
                          $cFileProgress
                            .find('.progress-bar')
                            .removeClass('progress-bar-info')
                            .addClass('progress-bar-danger')
                            .children('span')
                            .text(response.message);

                          toastrTemplate();
                          toastr['error'](
                            response.message,
                            '系統訊息'
                          );
                        }
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
            }
          },
          'a[href="#batch-file-upload"]'
        )
        .on(
          {
            'performed': function(e) {
              var $domEle = $(this);
              var $uploadEle = $domEle.next();
              if ($domEle.data('single-upload') == 'image') {
                $domEle.on('click', function(e) {
                  $domEle.addClass('hidden');
                  $uploadEle.removeClass('hidden');
                });
                $uploadEle.find('.remove').on('click', function(e) {
                  e.preventDefault();
                  $domEle.removeClass('hidden');
                  $uploadEle.addClass('hidden');
                });
              } else {
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

                  var params = $uploadEle.data('upload-params') || {};
                  $.extend(true, params, {is_ajax: 1});
                  var uploader = new plupload.Uploader({
                    runtimes: 'html5,flash,silverlight,html4',
                    browse_button: $uploadEle.find('[data-action="upload"]').get(0),
                    container: $uploadEle.find('.ace-file-container').get(0) || $uploadEle.get(0),
                    drop_element: $uploadEle.find('.ace-file-container').get(0) || $uploadEle.get(0),
                    dragdrop: true,
                    url: $uploadEle.data('upload-action-url') || window.location.pathname,
                    multipart_params: params,
                    filters: {
                      mime_types: $uploadEle.data('upload-mime-types') || [],
                      max_file_size: $uploadEle.data('upload-max-file-size') || 0, // 最大只能上傳 2MB 的文件
                    },
                    flash_swf_url: 'assets/Moxie.swf',
                    silverlight_xap_url: 'assets/Moxie.xap',
                    unique_names: true,
                    multi_selection: false,
                    chunk_size: '1mb'
                  });

                  uploader.bind('Init', function(up, params) {
                    // 取消上傳
                    $domEle.closest('form').on('click', 'a.delete[data-upload-model="queue"]', function(e) {
                      e.preventDefault();
                      var fileId = $(this).data('file-id');
                      uploader.removeFile(fileId);
                      $('#' + fileId).remove();
                    });
                  });

                  uploader.bind('FilesAdded', function(up, files) {

                    if (up.files.length > 1) {
                      $.each(files, function(i, file) {
                        up.removeFile(file.id);
                      });
                      toastrTemplate();
                      toastr['error'](
                        '一次只能上傳 1個檔案',
                        '系統訊息'
                      );
                      return false;
                    }

                    if ($domEle.data('single-upload') == 'image') {
                      up.start();
                    } else {
                      $.each(files, function(index, file) {
                        var tpl = '' +
                        '<div id="' + file.id + '" class="row js-build">' +
                          '<div class="col-xs-7">' +
                            '<table>' +
                              '<tr>' +
                                '<td><a href="#" data-upload-model="queue" data-file-id="' + file.id + '" class="delete"><i class="fas fa-times"></i></a></td>' +
                                '<td><span class="name">' + file.name + '</span></td>' +
                              '</tr>' +
                            '</table>' +
                          '</div>' +
                          '<div class="col-xs-5 text-right">' + plupload.formatSize(file.size) + '</div>' +
                          '<div class="col-xs-12">' +
                            '<div class="progress">' +
                              '<div class="progress-bar progress-bar-info progress-bar-striped">' +
                                '<span></span>' +
                              '</div>' +
                            '</div>' +
                          '</div>' +
                        '</div>';

                        $domEle.closest('form').find('.file-list').append(tpl);
                        up.start();
                      });
                    }
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
                    toastrTemplate();
                    uploader.removeFile(file);
                    var response = $.parseJSON(data.response);
                    var $cFileProgress = $('#' + file.id);
                    if (response.error == 0) {
                      $cFileProgress.remove();
                      toastr['success'](
                        '上傳完成',
                        '系統訊息'
                      );
                      if ($domEle.data('single-upload') == 'image') {
                        var picture = new Image();
                        picture.src = response.picture_thumb;
                        picture.onload = function() {
                          $domEle.attr('src', response.picture_thumb).removeClass('hidden');
                          $uploadEle.addClass('hidden');
                        };
                      }
                    } else {
                      $cFileProgress
                        .find('.progress-bar')
                        .removeClass('progress-bar-info')
                        .addClass('progress-bar-danger')
                        .children('span')
                        .text(response.message);
                      toastr['error'](
                        response.message,
                        '系統訊息'
                      );
                    }

                  });
                  uploader.init();
                }
              );
            }
          },
          '[data-single-upload]'
        )
        .on(
          {
            'performed': function(e) {
              var $domEle = $(this);
              require(
                [
                  'ui',
                  'ui.touch-punch',
                  'magnific-popup'
                ],
                function() {

                  var $layoutEle = $domEle.find('[data-sortable-layout]');

                  $layoutEle.sortable({
                    handle: '.panel-heading',
                    opacity: 0.8,
                    revert: true,
                    forceHelperSize: true,
                    placeholder: 'gallery-panel panel-container-col',
                    forcePlaceholderSize: true,
                    tolerance: 'pointer',
                    containment: 'form',
                    start: function(event, ui) {
                      $(ui.placeholder).html('<div></div>').find('> div').css({
                        'border': '2px dashed #D9D9D9',
                        'min-height': ui.item.find('> .panel').height(),
                        'margin-bottom': '20px'
                      });
                      $('.gallery-panel.panel-container-col').css({
                        height: ui.item.height()
                      });
                    },
                    stop: function(event, ui) {
                      $domEle.find('.panel-container-col').removeAttr('style');
                    }
                  });

                  switch (($layoutEle.data('sortable-layout') || 'image')) {

                    case 'image':

                      $layoutEle.magnificPopup({
                        delegate: 'a.widget-products-image',
                        type: 'image',
                        closeBtnInside: false,
                        mainClass: 'mfp-with-zoom mfp-img-mobile',
                        image: {
                          verticalFit: true
                        },
                        gallery: {
                          enabled: true
                        },
                        zoom: {
                          enabled: true,
                          duration: 300
                        }
                      });
                      break;
                  }

                  $layoutEle.on('click', '[name="chk_file_del[]"]', function(e) {
                    var disabled = !$domEle.find('[name="chk_file_del[]"]:checked').size();
                    $domEle.find('.del-all-file').prop('disabled', disabled);
                  }).on('click', '[data-toggle="remove"]', function(e) {
                    e.preventDefault();
                    var $rEle = $(this);
                    DU.dialog.confirm('您確認要刪除圖檔嗎？', function(result) {
                      if (result) {
                        $.ajax({
                          type: 'GET',
                          url: $domEle.data('remove-url'),
                          data: {
                            is_ajax: 1,
                            id: $rEle.data('item-id')
                          },
                          cache: false,
                          dataType: 'json',
                          beforeSend: DU.ajax.beforeSend,
                          complete: DU.ajax.complete,
                          error: DU.ajax.error,
                          success: function(data, textStatus) {
                            $rEle.closest('.panel-container-col').remove();
                            var disabled = !$domEle.find('[name="chk_file_del[]"]:checked').size();
                            $domEle.find('.del-all-file').prop('disabled', disabled);
                          }
                        });
                      }
                    });
                  }).on('change', '[name^="is_def"]:checked', function(e) {
                    var $cEle = $(this);
                    $layoutEle.find($cEle.data('handler-effect')).addClass('hidden');
                    $cEle.closest('.panel-container-col').find($cEle.data('handler-effect')).removeClass('hidden');
                  }).on('click', '[data-toggle="edit"]', function(e) {
                    e.preventDefault();
                    var $rEle = $(this);

                    DU.dialog.show({
                      draggable: true,
                      size: 'size-wide',
                      title: '編輯圖案',
                      message: '資料載入中...',
                      onshow: function(dialogRef) {
                        dialogRef.getModalFooter().hide();
                      },
                      onshown: function(dialogRef) {
                        $.ajax({
                          type: 'GET',
                          url: $domEle.data('edit-url'),
                          data: {
                            is_ajax: 1,
                            id: $rEle.data('item-id')
                          },
                          cache: false,
                          dataType: 'json',
                          beforeSend: DU.ajax.beforeSend,
                          complete: DU.ajax.complete,
                          error: DU.ajax.error,
                          success: function(data, textStatus) {
                            dialogRef.getModalFooter().show();
                            dialogRef.getModalBody().html(data.content);
                            dialogRef.getModalBody().find('[data-plugins]').trigger('init.DU');
                            dialogRef.getModalBody().find('[data-single-upload], [data-file-upload]').trigger('performed');
                          }
                        });
                      },
                      buttons: [{
                        label: '確定',
                        action: function(dialogRef) {
                          var $picEditForm = dialogRef.getModalBody().find('form');
                          // 重新取得編輯器元素值
                          $('[data-plugins="ckeditor"]', $picEditForm).each(function(i, editor) {
                            var $editor = $(editor);
                            $editor.val($editor.getCkeditor());
                          });

                          $.ajax({
                            type: 'POST',
                            url: $domEle.data('update-url'),
                            data: $picEditForm.serialize(),
                            cache: false,
                            dataType: 'json',
                            beforeSend: DU.ajax.beforeSend,
                            complete: DU.ajax.complete,
                            error: DU.ajax.error,
                            success: function(data, textStatus) {
                              var $picEle = $rEle.closest('.panel-container-col');
                              $picEle.find('.panel-title strong').text(data.picture_name);
                              $picEle.find('.panel-body .widget-products-image').attr('href', data.picture_original || data.picture_large);
                              $picEle.find('.panel-body .img-responsive').attr('src', data.picture_thumb);
                              if (data.is_def == 1) {
                                $domEle.find('.default-picture-tack').addClass('hidden');
                                $picEle.find('.default-picture-tack').removeClass('hidden');
                                $picEle.find('[name^="is_def"]').prop('checked', true);
                              }

                              dialogRef.close();
                            }
                          });
                        }
                      }]
                    });

                  });
                }
              );

              $domEle.find('.del-all-file').on('click', function(e) {
                e.preventDefault();

                DU.dialog.confirm('您確定要刪除所選取的項目嗎？', function(result) {
                  if (result) {
                    $.ajax({
                      type: 'POST',
                      url: $domEle.data('batch-remove-url') + '&is_ajax=1',
                      data: $domEle.find('[name="chk_file_del[]"]:checked').serialize(),
                      cache: false,
                      dataType: 'json',
                      beforeSend: DU.ajax.beforeSend,
                      complete: DU.ajax.complete,
                      error: DU.ajax.error,
                      success: function(data, textStatus) {
                        $domEle.find('[name="chk_file_del[]"]:checked').each(function() {
                          $(this).closest('.panel-container-col').remove();
                        });
                      }
                    });
                  }
                });
              });

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
                  var params = $uploadEle.data('upload-params') || {};
                  var uploader = new plupload.Uploader({
                    runtimes: 'html5,flash,silverlight,html4',
                    browse_button: $uploadEle.find('[data-action="upload"]').get(0),
                    container: $uploadEle.get(0),
                    drop_element: $uploadEle.get(0),
                    dragdrop: true,
                    url: $uploadEle.data('upload-action-url') || window.location.pathname,
                    multipart_params: params,
                    filters: {
                      mime_types: $uploadEle.data('upload-mime-types') || [],
                      max_file_size: $uploadEle.data('upload-max-file-size') || 0, // 最大只能上傳 2MB 的文件
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
                        '<div class="col-xs-7">' +
                          '<table>' +
                            '<tr>' +
                              '<td><a href="#" data-upload-model="queue" data-file-id="' + file.id + '" class="delete"><i class="fas fa-times"></i></a></td>' +
                              '<td><span class="name">' + file.name + '</span></td>' +
                            '</tr>' +
                          '</table>' +
                        '</div>' +
                        '<div class="col-xs-5 text-right">' + plupload.formatSize(file.size) + '</div>' +
                        '<div class="col-xs-12">' +
                          '<div class="progress">' +
                            '<div class="progress-bar progress-bar-info progress-bar-striped">' +
                              '<span></span>' +
                            '</div>' +
                          '</div>' +
                        '</div>' +
                      '</div>';

                      $domEle.find('.file-list').append(tpl);

                      up.start();
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
                    var response = $.parseJSON(data.response);
                    var $cFileProgress = $('#' + file.id);
                    if (response.error == 0) {
                      $cFileProgress.remove();
                      $domEle.find('[data-sortable-layout]').append(response.tpl);
                    } else {
                      $cFileProgress
                        .find('.progress-bar')
                        .removeClass('progress-bar-info')
                        .addClass('progress-bar-danger')
                        .children('span')
                        .text(response.message);

                      toastrTemplate();
                      toastr['error'](
                        response.message,
                        '系統訊息'
                      );
                    }
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
          },
          '[data-multi-upload]'
        )
        .on(
          {
            'performed': function(e) {
              var $domEle = $(this);
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

                  var def = {
                    showUploadButton: false,
                    doneProgress: false
                  };

                  var opts = null;
                  try {
                     opts = $.parseJSON(($domEle.data('element-options') || '').replace(/'/g, '"') || null);
                  } catch(e) {
                     opts = $domEle.data('element-options');
                  }
                  var settings = $.extend(def, opts);

                  var $resEle = $('[name="' + $domEle.data('file-upload') + '"]');
                  var $fileList = $domEle.closest('.row').find('.file-list');

                  var params = $domEle.data('upload-params') || {};
                  var uploader = new plupload.Uploader({
                    runtimes: 'html5,flash,silverlight,html4',
                    browse_button: $domEle.get(0),
                    container: $domEle.parent().get(0),
                    url: 'common.php?action=upload',
                    multipart_params: params,
                    filters: {
                      mime_types: $domEle.data('upload-mime-types') || [],
                      max_file_size: $domEle.data('upload-max-file-size') || 0, // 最大只能上傳 2MB 的文件
                    },
                    flash_swf_url: 'assets/Moxie.swf',
                    silverlight_xap_url: 'assets/Moxie.xap',
                    unique_names: true,
                    multi_selection: false,
                    chunk_size: '1mb'
                  });

                  uploader.bind('Init', function(up, params) {
                    // 取消上傳
                    $fileList.on('click', 'a.delete[data-upload-model="queue"]', function(e) {
                      e.preventDefault();
                      var fileId = $(this).data('file-id');
                      uploader.removeFile(fileId);
                      $('#' + fileId).remove();

                      $domEle.show();
                      $resEle.prop('readonly', false);
                    });
                  });

                  uploader.bind('FilesAdded', function(up, files) {

                    if (up.files.length > 1) {
                      $.each(files, function(i, file) {
                        up.removeFile(file.id);
                      });
                      toastrTemplate();
                      toastr['error'](
                        '一次只能上傳 1個檔案',
                        '系統訊息'
                      );
                      return false;
                    }

                    if (settings.showUploadButton == false) {
                      $domEle.hide();
                    }

                    $resEle.prop('readonly', true);

                    $fileList.empty();
                    $.each(files, function(index, file) {
                      var tpl = '' +
                      '<div id="' + file.id + '" class="row">' +
                        '<div class="col-xs-7">' +
                          '<table>' +
                            '<tr>' +
                              '<td><a href="#" data-upload-model="queue" data-file-id="' + file.id + '" class="delete"><i class="fas fa-times"></i></a></td>' +
                              '<td><span class="name">' + file.name + '</span></td>' +
                            '</tr>' +
                          '</table>' +
                        '</div>' +
                        '<div class="col-xs-5 text-right">' + plupload.formatSize(file.size) + '</div>' +
                        '<div class="col-xs-12">' +
                          '<div class="progress">' +
                            '<div class="progress-bar progress-bar-info progress-bar-striped">' +
                              '<span></span>' +
                            '</div>' +
                          '</div>' +
                        '</div>' +
                      '</div>';

                      $fileList.append(tpl);
                      up.start();
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
                    var response = $.parseJSON(data.response);
                    var $cFileProgress = $('#' + file.id);

                    toastrTemplate();
                    if (response.error == 0) {

                      if (settings.doneProgress == false) {
                        $cFileProgress.remove();
                      } else {
                        $cFileProgress.find('td:first').html('<a href="#" class="delete"><i class="fas fa-check"></i></a>');
                      }

                      $domEle.show();
                      $resEle.val(response.file_url).prop('readonly', false);

                      toastr['success'](
                        '上傳完成',
                        '系統訊息'
                      );

                    } else {

                      $cFileProgress
                        .find('.progress-bar')
                        .removeClass('progress-bar-info')
                        .addClass('progress-bar-danger')
                        .children('span')
                        .text(response.message);

                      toastr['error'](
                        response.message,
                        '系統訊息'
                      );
                    }
                  });

                  uploader.init();
                }
              );
            }
          },
          '[data-file-upload]'
        )
        .on(
          {
            'init.DU': function(e) {
              require(['picture']);
            }
          },
          '[data-edit-picture]'
        )
        .on(
          {
            // 初始化
            'init.DU': function(e) {
              var $domEle = $(this);
              $domEle.on('click', function(e) {
                e.preventDefault();
                var $iconEle = $(this).find('> i');
                var $panelEle = $(this).closest('.panel');
                var $mainEle = $panelEle.find('> .panel-main');
                if ($panelEle.hasClass('collapsed')) {
                  $panelEle.removeClass('collapsed');
                  $iconEle.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                  $mainEle.collapse('show');
                } else {
                  $iconEle.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                  $mainEle.collapse('hide');
                }
              }).each(function() {
                var $panelEle = $(this).closest('.panel');
                var $mainEle = $panelEle.find('> .panel-main');
                $mainEle.on('hidden.bs.collapse', function() {
                  $panelEle.addClass('collapsed');
                });
              });
            }
          },
          '.panel a[data-action="collapse"]'
        )
        .on(
          {
            // 摧毀編輯器
            'destroy.DU': function(e) {
              var $lAEditor = $(this);
              var textareaName = $lAEditor.attr('name');
              if (typeof CKEDITOR !== 'undefined' && CKEDITOR.instances.hasOwnProperty(textareaName)) {
                eval('CKEDITOR.instances["' + textareaName + '"].destroy();');
                $lAEditor.removeData('ckeditor');
              }
            },
            // 重建編輯器
            'reset.DU rebuild.DU': function(e) {
              var $lAEditor = $(this);
              $lAEditor.trigger('destroy').trigger('build.DU');
            },
            // 初始化編輯器
            'init.DU build.DU': function(e) {
              var $lAEditor = $(this);
              if ($lAEditor.data('ckeditor') == undefined) {

                $lAEditor.hide();
                DU.toogleLoader(true);

                window.CKEDITOR_BASEPATH = window.location.pathname + 'assets/js/plugins/CKEditor/';
                window.CKFINDER_BASEPATH = window.location.pathname + 'includes/CKFinder/';

                require(
                  [
                    'editor'
                  ],
                  function() {
                    try {

                      /**
                       * 讀取 HTML 樣版
                       *
                       * @param     {string}    $tplCode    樣版代碼
                       *
                       * @return    {string}
                       */
                      CKEDITOR.loadHtmlTpl = function($tplCode) {
                        var html = '';

                        if ($tplCode != '') {
                          $.ajax({
                            url: 'common.php',
                            data: {
                              'action': 'get_html_tpl',
                              'code': $tplCode
                            },
                            async: false,
                            dataType: 'json',
                            success: function(data) {
                              html = data.content;
                            }
                          });
                        }

                        return html;
                      };

                      CKEDITOR.dtd['a']['div'] = 1; // 允許 a 標籤包裹 div

                      var settings = $.extend(
                        {
                          entities: false,
                          toolbar: 'Normal'
                        },
                        $lAEditor.data('settings') || {}
                      );

                      if (settings.templates !== undefined) {
                        settings.templates_files = [];
                        $.each(settings.templates.split(','), function(i, v) {
                          settings.templates_files.push(CKEDITOR.getUrl('templates/' + v + '.js'));
                        });
                      } else {
                        settings.removePlugins = 'templates';
                      }

                      CKFinder.setupCKEditor(
                        CKEDITOR.replace($lAEditor.get(0), settings),
                        window.CKFINDER_BASEPATH
                      );

                      $lAEditor.data('ckeditor', {'initialized': true});

                    } catch (ex) {
                      $lAEditor.show();
                      console.log('編輯器載入失敗:' + ex);
                    }
                    DU.toogleLoader(false);
                  }
                );
              }
            }
          },
          '[data-plugins="ckeditor"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('bs.popover')) {
                $.isFunction($domEle.data('bs.popover').destroy) && $domEle.data('bs.popover').destroy();
              }
              $domEle.removeData('popover');
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('popover') == undefined) {

                try {
                  var def = {
                    'container': 'body'
                  };
                  var opts = null;

                  try {
                     opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                  } catch(e) {
                     opts = $domEle.data('options');
                  }

                  $domEle.popover($.extend(true, {}, def, opts));
                  $domEle.data('popover', {'initialized': true});

                } catch (ex) {
                  console.log('popover 載入失敗:' + ex);
                }
              }
            }
          },
          '[data-plugins="popover"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              $domEle.removeData(['magnific-popup', 'magnificPopup']);
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.is('[href]') && ($domEle.attr('href') || '').toString().match(/^#/) !== null) {
                return false;
              }

              if ($domEle.data('magnific-popup') == undefined) {
                require(
                  [
                    'magnific-popup'
                  ],
                  function() {
                    try {
                      var def = {
                        type: 'image',
                        closeBtnInside: false,
                        mainClass: 'mfp-with-zoom mfp-img-mobile',
                        image: {
                          verticalFit: true,
                          titleSrc: function(item) {
                            return item.el.data('title') || item.el.attr('title');
                          }
                        },
                        zoom: {
                          enabled: true,
                          duration: 300,
                          easing: 'ease-in-out',
                          opener: function(element) {
                            if (element.find('img').size() > 0) {
                              return element.find('img');
                            } else if (element.find('i').size() > 0) {
                              return element.find('i')
                            } else {
                              return element;
                            }
                          }
                        }
                      };
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.magnificPopup($.extend(true, {}, def, opts));

                      $domEle.data('magnific-popup', {initialized: true});
                    } catch (ex) {
                      console.log('magnific-popup 載入失敗:' + ex);
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="magnific-popup"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              $domEle.unwrap().nextAll().remove();
              $domEle.removeData(['minicolors', 'minicolorsInitialized', 'minicolorsSettings', 'minicolors-initialized']);
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('minicolors') == undefined) {
                require(
                  [
                    'minicolors'
                  ],
                  function() {
                    try {
                      var def = {
                        control: 'hue',
                        position: 'top left',
                        changeDelay: 200,
                        letterCase: 'uppercase',
                        theme: 'bootstrap'
                      };
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.minicolors($.extend(true, {}, def, opts));

                      $domEle.data('minicolors', {initialized: true});
                    } catch (ex) {
                      console.log('minicolors 載入失敗:' + ex);
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="minicolors"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.closest('.bootstrap-touchspin').size() > 0) {
                $domEle.unwrap('.bootstrap-touchspin');
                $domEle.siblings('[class*="bootstrap-touchspin"], .input-group-btn-vertical').remove();
              }
              $domEle.removeData(['touchspin', 'alreadyinitialized', 'initvalue', 'spinnerid']);
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('touchspin') === undefined) {
                $domEle.hide();

                require(
                  [
                    // 'assets/css/plugin-fix.css',
                    'numeric',
                    'bootstrap-touchspin'
                  ],
                  function() {
                    try {
                      var def = {
                        verticalbuttons: true,
                        buttonup_class: 'btn btn-sm btn-default',
                        buttondown_class: 'btn btn-sm btn-default',
                        verticalup: '<i class="icon-only fas fa-chevron-up"></i>',
                        verticaldown: '<i class="icon-only fas fa-chevron-down"></i>'
                      };
                      var opts = null;

                      if ($domEle.attr('min') !== undefined) {
                        $domEle.data('min', $domEle.attr('min'));
                      }
                      if ($domEle.attr('max') != undefined) {
                        $domEle.data('max', $domEle.attr('max'));
                      }
                      if ($domEle.attr('step') != undefined) {
                        $domEle.data('step', $domEle.attr('step'));
                      }

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.TouchSpin($.extend(true, {}, def, opts)).numeric({decimal: false, negative: false});

                      $domEle.data('touchspin', {initialized: true});
                    } catch (ex) {
                      console.log('touchspin 載入失敗:' + ex);
                    }

                    $domEle.show();
                  }
                );
              }
            }
          },
          '[data-plugins="touchspin"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('ion-range-slider')) {
                $.isFunction($domEle.data('ion-range-slider').destroy) && $domEle.data('ion-range-slider').destroy();
              } else {
                $domEle.prev('[class^="irs"]').remove();;
              }

              $domEle.show();
              $domEle.removeData('ion-range-slider');
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('ion-range-slider') == undefined) {
                $domEle.hide();

                require(
                  [
                    'ion.rangeSlider'
                  ],
                  function() {
                    try {
                      var def = {};
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.ionRangeSlider($.extend(true, {}, def, opts));

                      $domEle.data('ion-range-slider', {initialized: true});
                    } catch (ex) {
                      $domEle.show();
                      console.log('ion Range Slider 載入失敗:' + ex);
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="ion-range-slider"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              $domEle.removeClass('selectized').next('.selectize-control').remove();
              $domEle.removeData(['selectize-text']);
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('selectize-text') == undefined) {
                $domEle.prop('disabled', true);

                require(
                  [
                    'selectize'
                  ],
                  function() {
                    try {
                      var def = {
                        plugins: ['remove_button', 'drag_drop', 'restore_on_backspace'],
                        delimiter: ',',
                        persist: false,
                        render: {
                          option_create: function(data, escape) {
                            return '<div class="create">新增 <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                          }
                        },
                        create: function(input) {
                          return {
                            value: input,
                            text: input
                          }
                        }
                      };
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.selectize($.extend(true, {}, def, opts));

                      $domEle.data('selectize-text', {initialized: true});
                    } catch (ex) {
                      console.log('selectize-text 載入失敗:' + ex);
                    }

                    $domEle.prop('disabled', false);

                    var selectize = $domEle.data('selectize');

                    selectize.enable();
                    if (
                      selectize.plugins.requested.drag_drop &&
                      selectize.$control.data('ui-sortable') !== undefined
                    ) {
                      selectize.$control.data('ui-sortable').enable();
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="selectize-text"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);

              var $selCtl = $cEle.next('.selectize-control');
              if ($selCtl.size() > 0) {
                var selInterface = $domEle.data('selectize');

                if (selInterface) {
                  selInterface.refreshOptions();
                }

                var optArr = [];

                optArr.push({'text': $domEle.attr('placeholder'), 'value': ''});

                $selCtl.find('.selectize-dropdown-content > div').each(function(index, domEle) {
                  var $cDomEle = $(domEle);
                  optArr.push({'text': $cDomEle.text(), 'value': $cDomEle.data('value')});
                });

                if (false === $.isEmptyObject(optArr)) {
                  DU.genOption($cEle, optArr);
                }

                $selCtl.remove();
                $cEle.removeAttr('data-sels');
              }

              $domEle.removeClass('selectized');
              $domEle.removeData(['selectize-multiple', 'selectize', 'sels']);

              if ($domEle.is(':hidden')) {
                $domEle.show();
              }
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');

              var selInterface = $domEle.data('selectize');

              if (selInterface) {
                selInterface.clear();
              }
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('selectize-multiple') == undefined) {
                $domEle.prop('disabled', true);

                require(
                  [
                    'selectize'
                  ],
                  function() {
                    try {
                      var def = {
                        plugins: ['remove_button'],
                        onItemAdd: function(value, $item) {
                          var _self = this;

                          $item.removeClass('active');

                          if ($domEle.data('raw_option') !== undefined) {
                            var $optRaw = $domEle.data('raw_option').filter('[value="' + value + '"]');
                            var optData = $optRaw.data();

                            if (optData == undefined) {
                              return;
                            }

                            $.each(optData, function(rodKey, rodVal) {
                              $item
                                .attr('data-' + rodKey.replace(/([A-Z])/g, '-$1').toLowerCase(), rodVal)
                                .data(rodKey, rodVal);

                              if ($item.data('text') !== undefined && $item.text().indexOf($item.data('text')) < 0) {
                                $item.html($item.html().replace($.trim($optRaw.text()), $item.data('text')).replace(/&nbsp;/g, ''));
                              }
                            });
                          }
                        },
                        onDropdownOpen: function($dropdown) {
                          if (this.settings.mode === 'single') {
                            $dropdown.find('.selected').removeClass('selected');
                          }
                        },
                        hideSelected: false,
                        closeAfterSelect: true,
                        disable: false,
                        lock: false,
                        items: []
                      };
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      var createOpt = null;
                      try {
                         createOpt = $.parseJSON(($domEle.data('create-options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         createOpt = $domEle.data('create-options');
                      }

                      if (createOpt != null) {
                        opts.render = {
                          option_create: function(data, escape) {
                            return '<div class="create">新增 <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                          }
                        };

                        opts.create = function(input, callback) {
                          $.ajax({
                            type: 'POST',
                            cache: false,
                            url: createOpt.url || window.location.pathname,
                            data: $.extend(
                              {
                                'is_ajax': 1,
                                'text': input
                              },
                              createOpt.data || {}
                            ),
                            dataType: 'json',
                            beforeSend: ajaxBeforeSend,
                            complete: ajaxComplete,
                            error: ajaxError,
                            success: function(data, textStatus) {
                              if (data.error > 0) {
                                DU.dialog.alert(data.message);
                                return callback();
                              } else {
                                return callback({
                                  value: data.value,
                                  text: data.text
                                });
                              }
                            }
                          });
                        };
                      }

                      $domEle.data('raw_option', $domEle.find('> option, > optgroup > option')); // 保存原始選項
                      $domEle.selectize($.extend(true, {}, def, opts));

                      var selInterface = $domEle.data('selectize');

                      if ($domEle.data('sels') !== undefined) {
                        $.each(($domEle.data('sels') || '').toString().split(','), function(k, v) {
                          selInterface.addItem(v);
                        });
                      }

                      $domEle.data('selectize-multiple', {initialized: true});
                    } catch (ex) {
                      console.log('selectize-multiple 載入失敗:' + ex);
                    }

                    $domEle.prop('disabled', false);

                    var selectize = $domEle.data('selectize');

                    if (selectize) {
                      selectize.enable();

                      selectize.settings.disable == true && selectize.disable();
                      selectize.settings.lock == true && selectize.lock();
                      if (
                        selectize.plugins.requested.drag_drop &&
                        selectize.$control.data('ui-sortable') !== undefined
                      ) {
                        selectize.$control.data('ui-sortable').enable();
                      }

                      selectize.isOpen = true;
                      selectize.refreshOptions();
                      selectize.isOpen = false;
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="selectize-multiple"]'
        )
        .on(
          {
            // 摧毀
            'destroy.DU': function(e) {
              var $domEle = $(this);
              $domEle.removeClass('selectized').next('.selectize-control').remove();
              $domEle.removeData(['selectize-email']);
            },
            // 重建
            'reset.DU rebuild.DU': function(e) {
              var $domEle = $(this);
              $domEle.trigger('destroy.DU').trigger('build.DU');
            },
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('selectize-email') == undefined) {
                $domEle.prop('disabled', true);

                require(
                  [
                    'selectize'
                  ],
                  function() {

                    var REGEX_EMAIL = '([a-z0-9!#$%&\'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&\'*+/=?^_`{|}~-]+)*@'
                      + '(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)';

                    try {
                      var def = {
                        plugins: ['remove_button', 'drag_drop', 'restore_on_backspace'],
                        persist: false,
                        maxItems: null,
                        searchField: ['name', 'email'],
                        render: {
                          item: function(item, escape) {
                            if ((new RegExp('^' + REGEX_EMAIL + '$', 'i')).test(item.value)) {
                              item.email = item.value;
                            }
                            var match = item.value.match(new RegExp('^([^<]*)\<' + REGEX_EMAIL + '\>$', 'i'));
                            if (match) {
                              item.email = match[2];
                              item.name = match[1];
                            }
                            return '<div>' +
                              (item.name ? '<span class="name">' + escape(item.name) + '</span>' : '') +
                              (item.email ? '<span class="email">' + escape(item.email) + '</span>' : '') +
                            '</div>';
                          },
                          option: function(item, escape) {
                            var label = item.name || item.email;
                            var caption = item.name ? item.email : null;
                            return '<div>' +
                              '<span class="label">' + escape(label) + '</span>' +
                              (caption ? '<span class="caption">' + escape(caption) + '</span>' : '') +
                            '</div>';
                          },
                          option_create: function(data, escape) {
                            return '<div class="create">新增 <strong>' + escape(data.input) + '</strong>&hellip;</div>';
                          }
                        },
                        createFilter: function(input) {
                          var match, regex;

                          // email@address.com
                          regex = new RegExp('^' + REGEX_EMAIL + '$', 'i');
                          match = input.match(regex);
                          if (match) return !this.options.hasOwnProperty(match[0]);

                          // name <email@address.com>
                          regex = new RegExp('^([^<]*)\<' + REGEX_EMAIL + '\>$', 'i');
                          match = input.match(regex);
                          if (match) return !this.options.hasOwnProperty(match[2]);

                          return false;
                        },
                        create: function(input) {
                          if ((new RegExp('^' + REGEX_EMAIL + '$', 'i')).test(input)) {
                            return {
                              email: input,
                              value: input
                            };
                          }
                          var match = input.match(new RegExp('^([^<]*)\<' + REGEX_EMAIL + '\>$', 'i'));
                          if (match) {
                            return {
                              email: match[2],
                              name: $.trim(match[1]),
                              value: $.trim(match[1]) + '<' + match[2] + '>'
                            };
                          }
                          DU.dialog.alert('Invalid email address.');
                          return false;
                        }
                      };
                      var opts = null;

                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }

                      $domEle.selectize($.extend(true, {}, def, opts));

                      $domEle.data('selectize-email', {initialized: true});
                    } catch (ex) {
                      console.log('selectize-email 載入失敗:' + ex);
                    }

                    $domEle.prop('disabled', false);

                    var selectize = $domEle.data('selectize');

                    selectize.enable();
                    if (
                      selectize.plugins.requested.drag_drop &&
                      selectize.$control.data('ui-sortable') !== undefined
                    ) {
                      selectize.$control.data('ui-sortable').enable();
                    }
                  }
                );
              }
            }
          },
          '[data-plugins="selectize-email"]'
        )
        .on(
          {
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('tools-tabs') == undefined) {
                require(
                  [
                    'tabs'
                  ],
                  function() {

                    var _scope = $domEle.data('tabs-scope') || 'tabbable';
                    switch (_scope) {
                      case 'tabbable':
                        $paneEle = $domEle.closest('.tabbable').find('> .tab-content > .tab-pane');
                        break;
                      case 'widget':
                        $paneEle = $domEle.closest('.widget-box').find('.widget-main > .tab-content > .tab-pane');
                        break;
                    }

                    if ($domEle.data('page-code') === undefined) {

                      $domEle.tabs($paneEle, {
                        onClick: function(e, tabIndex) {
                          this.getCurrentTab().closest('li').addClass('active').siblings().removeClass('active');
                          var $currentPane = this.getPanes().removeClass('active').eq(tabIndex).addClass('active');
                          if ($currentPane.data('performed') !== true) {
                            $currentPane.data('performed', true).trigger('performed');
                          }

                          $currentPane.find('[data-load-init]:visible').each(function() {
                            var $ele = $(this);
                            if ($ele.data('performed') !== true) {
                              $ele.data('performed', true).trigger($ele.data('load-init') || 'build.DU');
                            }
                          });
                        }
                      });

                    } else {

                      $.cookie.json = true;
                      var historyObj = $.cookie('tabs-history') || {};
                      $domEle.tabs($paneEle, {
                        rotate: false,
                        initialIndex: historyObj.pageCode == $domEle.data('page-code')
                          ? historyObj.tabIndex
                          : 0
                        ,
                        onClick: function(e, tabIndex) {
                          this.getCurrentTab().closest('li').addClass('active').siblings().removeClass('active');
                          var $currentPane = this.getPanes().removeClass('active').eq(tabIndex).addClass('active');
                          if ($currentPane.data('performed') !== true) {
                            $currentPane.data('performed', true).trigger('performed');
                          }

                          $currentPane.find('[data-load-init]:visible').each(function() {
                            var $ele = $(this);
                            if ($ele.data('performed') !== true) {
                              $ele.data('performed', true).trigger($ele.data('load-init') || 'build.DU');
                            }
                          });

                          $.cookie.json = true;
                          $.cookie('tabs-history', {
                            'pageCode': $domEle.data('page-code'),
                            'tabIndex': tabIndex
                          });
                        }
                      });
                    }

                    $domEle.pxTabResize();

                    $domEle.data('tools-tabs', {initialized: true});

                  }
                );
              }
            }
          },
          '[data-plugins="tools-tabs"]'
        )
        .on(
          {
            // 初始化
            'init.DU build.DU': function(e) {
              var $domEle = $(this);
              $domEle.pxTabResize();
            }
          },
          '[data-plugins="resize-tabs"]'
        )
        .on(
          {
            'init.DU': function(e) {
              var $rangGroup = $(this);
              var $startEle = $('[name="' + $rangGroup.data('date-start') + '"]', $rangGroup);
              var $endEle = $('[name="' + $rangGroup.data('date-end') + '"]', $rangGroup);
              var $ctrlEle = $('[name="' + $rangGroup.data('date-ctrl') + '"]', $rangGroup);

              if (
                $rangGroup.data('date-range') == undefined &&
                $startEle.size() > 0 && $endEle.size() > 0 && $ctrlEle.size() > 0
              ) {
                $ctrlEle
                  .on('init.DU click', function(e) {
                    if ($ctrlEle.is(':checked')) {
                      $endEle.prop('disabled', false);

                      if (e.type !== 'init') {
                        $endEle.trigger('update.DU');
                      }
                    } else {
                      $endEle.prop('disabled', true);
                    }
                  })
                  .triggerHandler('init.DU');

                $rangGroup.data('date-range', {initialized: true});
              }
            }
          },
          '[data-toggle="date-range"]'
        )
        .on(
          {
            'init.DU': function(e) {
              var $cDom = $(this);
              var isRange = $cDom.hasClass('input-daterange') || $cDom.data('daterange') == true;

              if ($cDom.size() < 1) {
                return;
              }

              require(
                [
                  'moment',
                  '../i18n/moment.zh-tw',
                  'bootstrap-datetimepicker'
                ],
                function(moment) {

                  var $targetDom = null;
                  if (isRange) {
                    $targetDom = $cDom.find('input');
                  } else {
                    $targetDom = $cDom;
                  }

                  $targetDom.each(function(index, domEle) {
                    var $cInput = $(domEle);

                    if ($cInput.data('datepicker') == undefined) {
                      $cInput.hide();

                      try {

                        var def = {
                          locale: moment.locale(),
                          format: 'YYYY-MM-DD',
                          useCurrent: true,
                          showTodayButton: true,
                          showClear: false,
                          showClose: false,
                          icons: {
                            time: 'far fa-clock',
                            date: 'fas fa-calendar',
                            up: 'fas fa-chevron-up',
                            down: 'fas fa-chevron-down',
                            previous: 'fas fa-chevron-left',
                            next: 'fas fa-chevron-right',
                            today: 'far fa-calendar-check',
                            clear: 'fas fa-trash',
                            close: 'fas fa-times'
                          },
                          tooltips: {
                            today: '今天',
                            clear: '清除選擇',
                            close: '關閉',
                            selectMonth: '選擇月份',
                            prevMonth: '上一個月',
                            nextMonth: '下一個月',
                            selectYear: '選擇年分',
                            prevYear: '上一年份',
                            nextYear: '下一年份',
                            selectDecade: '選擇十年',
                            prevDecade: '上一個十年',
                            nextDecade: '下一個十年',
                            prevCentury: '上一個世紀',
                            nextCentury: '下一個世紀',
                            pickHour: '選擇小時',
                            incrementHour: '遞增一小時',
                            decrementHour: '遞減一小時',
                            pickMinute: '選擇分鐘',
                            incrementMinute: '遞增一分鐘',
                            decrementMinute: '遞減一分鐘',
                            pickSecond: '選擇秒數',
                            incrementSecond: '遞增一秒鐘',
                            decrementSecond: '遞減一秒鐘',
                            togglePeriod: '切換週期',
                            selectTime: '選擇時間'
                          }
                        };
                        var opts = null;

                        if (isRange && index != 0) {
                          def = $.extend(
                            def,
                            {
                              useCurrent: false
                            }
                          );
                        }

                        try {
                           opts = ($cInput.data('options') || $cDom.data('options') || '').replace(/'/g, '"');
                           opts = $.parseJSON(opts || null);
                        } catch(e) {
                           opts = $domEle.data('options');
                        }

                        $cInput.datetimepicker($.extend(true, {}, def, opts));

                        var dp = $cInput.data('DateTimePicker');

                        // 如果僅顯示月份，隱藏年份控制項
                        if (dp.options().viewMode == 'months') {
                          $cInput.on('dp.show', function(e) {
                            $('.datepicker-months .table-condensed > thead', e.target.parentElement).addClass('hidden');
                          });
                        }

                        $cInput.data('datepicker', {initialized: true});
                      } catch (ex) {
                        console.log('datepicker 載入失敗:' + ex);
                      }

                      $cInput.show();
                    }
                  });

                  if (isRange) {

                    $targetDom
                      .each(function(index, domEle) {
                        var $cInput = $(domEle);
                        var isStartPicker = index == 0;

                        $cInput.on({
                          'init.DU update.DU': function(e, data) {
                            var $othInput = $cInput.siblings('input').first();
                            var dpevt = data != undefined ? data.dpevt : {};
                            var dateVal = false;

                            // if (isStartPicker) {
                            //   if ($othInput.data('DateTimePicker') != undefined) {
                            //     if ($othInput.is(':disabled') == false) {
                            //       var cDate = $cInput.data('DateTimePicker').date();
                            //       var oDate = $othInput.data('DateTimePicker').date();

                            //       $othInput.data('DateTimePicker')['minDate'](cDate);

                            //       if (cDate && oDate && cDate.unix() > oDate.unix()) {
                            //         $othInput.data('DateTimePicker')['date'](cDate);
                            //       }
                            //     }
                            //   }
                            // } else {
                            //   if ($othInput.data('DateTimePicker') != undefined) {
                            //     if ($cInput.is(':disabled') == false) {
                            //       var cDate = $cInput.data('DateTimePicker').date();
                            //       var oDate = $othInput.data('DateTimePicker').date();

                            //       if (!cDate) {
                            //         $cInput.data('DateTimePicker')['minDate'](oDate);
                            //         $cInput.data('DateTimePicker')['date'](oDate);
                            //       }

                            //       if (cDate && oDate && cDate.unix() < oDate.unix()) {
                            //         if (dpevt.type != 'dp') {
                            //           $cInput.data('DateTimePicker')['minDate'](oDate);
                            //           $cInput.data('DateTimePicker')['date'](oDate);
                            //         }
                            //       }

                            //       dateVal > 0 && $othInput.data('DateTimePicker')['maxDate'](dateVal);
                            //     }
                            //   }
                            // }
                          },
                          'dp.show': function(e) {
                            $cInput.trigger('update.DU', {'dpevt': e});
                          },
                          'dp.change': function(e) {
                            $cInput.trigger('update.DU', {'dpevt': e});
                          }
                        });
                      })
                      .triggerHandler('init.DU');
                  }
                }
              );
            }
          },
          '[data-plugins="datepicker"]'
        )
        .on(
          {
            // 初始化
            'init.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('numeric') === undefined) {

                require(
                  [
                    'numeric'
                  ],
                  function() {
                    try {
                      var def = {decimal: false, negative: false};
                      try {
                         opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
                      } catch(e) {
                         opts = $domEle.data('options');
                      }
                      $domEle.numeric($.extend(true, {}, def, opts));

                      $domEle.data('numeric', {initialized: true});
                    } catch (ex) {
                      console.log('numeric 載入失敗:' + ex);
                    }

                    $domEle.show();
                  }
                );
              }
            }
          },
          '[data-plugins="numeric"]'
        )

        .on(
          {
            // 初始化
            'init.DU': function(e) {
              var $domEle = $(this);
              if ($domEle.data('prev-value') == undefined) {
                $domEle.data('prev-value', $domEle.prop('defaultValue'));
              }
            },
            // enter 鍵處理
            'keydown': function(e) {
              var $domEle = $(this);
              var cInputVal = parseInt($domEle.val(), 10) || 0;

              if (
                e.keyCode == 13 &&
                $domEle.prop('defaultValue') != cInputVal && $domEle.data('prev-value') != cInputVal
              ) {
                e.preventDefault();

                $domEle.trigger('blur');
              }
            },
            // 數值檢查
            'blur': function(e) {
              var $domEle = $(this);
              var $cForm = $domEle.closest('form');
              var cInputVal = $domEle.val();
              var cPrevVal = $domEle.data('prev-value');

              if ($domEle.prop('defaultValue') != cInputVal && cPrevVal != cInputVal) {

                var $btnSubmit = $cForm.find('[type="submit"]');
                var $cLabel = $domEle.closest('.form-group').find('.control-label');
                $btnSubmit.prop('disabled', true);

                var cfgMsg = '你確定要';
                if ($cLabel.size() > 0) {
                  cfgMsg += '將' + $cLabel.text();
                }
                cfgMsg += '變更為 <strong class="text-danger bigger-150">' + cInputVal + '</strong>？';
                DU.dialog.confirm(
                  cfgMsg,
                  function(res) {
                    if (res === true) {
                      $domEle.data('prev-value', cInputVal).val(cInputVal);
                    } else {
                      e.preventDefault();
                      $domEle.val(cPrevVal);
                    }
                    $btnSubmit.prop('disabled', false);
                  }
                );
              }
            }
          },
          '[data-trigger="cfm-val"]'
        );
    }

    $body.triggerHandler('setup.DU');

  });

});

var DU = {

  dialog: {

    /**
     * 載入 dialog
     *
     * @param     string      skin      面板名稱
     * @param     function    finish    載入完成處理函式
     *
     * @return    void
     */
    load: function(skin, finish) {
      var object = $.data(document, 'BootstrapDialog');
      if (object === undefined || object.initialized === undefined) {

        require(
          [
            'bootstrap-dialog'
          ],
          function(BootstrapDialog) {

            $.data(document, 'BootstrapDialog', {initialized: true});

            $.isFunction(finish) && finish();
          }
        );

      } else {
        $.isFunction(finish) && finish();
      }
    },
    init: function() {
      var _self = this;
      _self.load();
    },
    show: function() {
      var _self = this;

      var options = {};
      var defaultOptions = {
        title: '',
        data: {
          callback: null
        },
        onhide: function(dialog) {
          !dialog.getData('btnClicked') && dialog.isClosable() && typeof dialog.getData('callback') === 'function' && dialog.getData('callback')(false);
        }
      };
      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          message: arguments[0],
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }

      require(
        [
          'bootstrap-dialog'
        ],
        function(BootstrapDialog) {
          BootstrapDialog.show(options);
        }
      );
    },
    alert: function() {
      var _self = this;

      /**
       * 建立顯示訊息
       *
       * @param     mixed      msg     訊息內容
       * @param     boolean    html    是否為 HTML 格式
       *
       * @return    void
       */
      var _createMsg = function(msg, html) {
        msg = msg || '';
        html = !isNaN(html) ? html : true;

        if (msg && msg.constructor === Array) {
          var contentLeng = msg.length;

          if (contentLeng > 1) {
            var msgStr = '錯誤原因如下請重新檢查：\n';
            for (var i = 0; i < contentLeng; i++) {
              msgStr += (i + 1) + '. ' + msg[i] + '\n';
            }
            msg = msgStr;
          } else {
            msg = msg.shift().toString();
          }
        }

        msg = msg.toString();
        msg = html ? msg.replace(/\n/g, '<br>') : msg.replace(/<[^>]*>/g, '');

        return msg;
      };

      var options = {};
      var defaultOptions = {
        size: 'size-small',
        type: 'type-primary',
        title: null,
        message: null,
        closable: false,
        draggable: false,
        data: {
          callback: null
        },
        onshow: function(dialog) {
          dialog.getModalHeader().hide();
          dialog.getModalDialog().addClass('modal-sm');
        },
        onhide: function(dialog) {
          !dialog.getData('btnClicked') && dialog.isClosable() && typeof dialog.getData('callback') === 'function' && dialog.getData('callback')(false);
        },
        buttons: [{
          label: '確定',
          action: function(dialog) {
            dialog.setData('btnClicked', true);
            typeof dialog.getData('callback') === 'function' && dialog.getData('callback')(true);
            dialog.close();
          }
        }]
      };
      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          message: _createMsg(arguments[0], true),
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }

      require(
        [
          'bootstrap-dialog'
        ],
        function(BootstrapDialog) {
          new BootstrapDialog(options).open();
        }
      );
    },
    confirm: function() {
      var _self = this;

      var options = {};
      var defaultOptions = {
        size: 'size-small',
        type: 'type-primary',
        title: '網頁訊息',
        message: null,
        closable: false,
        draggable: false,
        btnOKClass: '',
        data: {
          callback: null
        },
        buttons: [{
          label: '取消',
          action: function (dialog) {
            if (typeof dialog.getData('callback') === 'function' && dialog.getData('callback').call(this, false) === false) {
              return false;
            }
            return dialog.close();
          }
        }, {
          label: '確定',
          cssClass: ['btn', 'type-primary'.split('-')[1]].join('-'),
          action: function (dialog) {
            if (typeof dialog.getData('callback') === 'function' && dialog.getData('callback').call(this, true) === false) {
              return false;
            }
            return dialog.close();
          }
        }]
      };

      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          message: arguments[0],
          closable: false,
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }
      if (options.btnOKClass === null) {
        options.btnOKClass = ['btn', options.type.split('-')[1]].join('-');
      }

      require(
        [
          'bootstrap-dialog'
        ],
        function(BootstrapDialog) {
          new BootstrapDialog(options).open();
        }
      );
    }
  },

  /**
   * ajax 處理物件
   */
  ajax: {
    /**
     * 發送 HTTP 請求
     *
     * @param    string      url             請求的 URL 地址
     * @param    mixed       params          發送參數
     * @param    function    callback        回調函數
     * @param    string      transferMode    請求的方式，GET, POST
     * @param    string      responseType    回應類型，JSON, XML, TEXT
     * @param    boolean     asyn            是否異步請求的方式
     */
    call: function(url, params, callback, transferMode, responseType, asyn) {
      var _self = this;

      $.ajax({
        type: transferMode || 'POST',
        url: url || window.location.pathname,
        cache: false,
        async: typeof asyn === 'boolean' ? asyn : true,
        data: params || {},
        dataType: responseType || 'json',
        beforeSend: _self.beforeSend,
        success: function(data, textStatus) {
          $.isFunction(callback) && callback(data, textStatus);
        },
        error: _self.error,
        complete: _self.complete
      });
    },
    /**
     * 向伺服器發送請求前動作
     *
     * @param     object    XMLHttpRequest    請求物件
     *
     * @return    void
     */
    beforeSend: function(XMLHttpRequest) {
      XMLHttpRequest.setRequestHeader('Request-Type', 'ajax');
      DU.toogleLoader(true);
      if (window.Pace && typeof window.Pace.restart === 'function') {
        window.Pace.restart();
      }
    },
    /**
     * 伺服器完成請求後動作
     *
     * @param     object    XMLHttpRequest    請求物件
     * @param     string    textStatus        請求狀態
     *
     * @return    void
     */
    complete: function(XMLHttpRequest, textStatus) {
      DU.toogleLoader(false);
    },
    /**
     * 伺服器發生錯誤動作
     *
     * @param     object    XMLHttpRequest    請求物件
     * @param     string    textStatus        請求狀態
     * @param     object    errorThrown       異常物件
     *
     * @return    void
     */
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      DU.toogleLoader(false);
    },
  },
  format: {
    /**
     * 將位元組轉成可閱讀格式
     *
     * @param     float      $bytes       位元組
     * @param     integer    $decimals    分位數
     * @param     string     $unit        容量單位
     *
     * @return    string
     */
    byte: function(bytes, decimals, unit) {
      decimals = decimals || 0;
      unit = unit || '';

      var units = {
          'B': 0,             // Byte
          'K': 1,   'KB': 1,  // Kilobyte
          'M': 2,   'MB': 2,  // Megabyte
          'G': 3,   'GB': 3,  // Gigabyte
          'T': 4,   'TB': 4,  // Terabyte
          'P': 5,   'PB': 5,  // Petabyte
          'E': 6,   'EB': 6,  // Exabyte
          'Z': 7,   'ZB': 7,  // Zettabyte
          'Y': 8,   'YB': 8   // Yottabyte
      };

      var value = 0;
      if (bytes > 0) {
        if (false == unit.toUpperCase() in units) {
          var pow = Math.floor(Math.log(bytes) / Math.log(1024));

          unit = DU.object.search(pow, units);
        }

        value = (bytes / Math.pow(1024, Math.floor(units[unit])));
      }

      decimals = Math.floor(Math.abs(decimals));
      if (decimals > 53) {
        decimals = 20;
      }

      return DU.string.printFormat('{0} {1}', [parseFloat(value).toFixed(decimals), unit]);
    },

    /**
     * 格式化價格
     *
     * @param     float     price    價格
     *
     * @return    string
     */
     price: function(price) {
        var _numberFormat = function(number, decimals, dec_point, thousands_sep) {
          number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

          var n = !isFinite(+number) ? 0 : +number,
              prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
              sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
              dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
              s = '',
              toFixedFix = function(n, prec){
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
              };

          // Fix for IE parseFloat(0.55).toFixed(0) = 0;
          s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');

          if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
          }

          if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
          }

          return s.join(dec);
        };

        return _numberFormat(Math.round(price), 0, '.', ',');
    },
  },

  /**
   * 物件擴充方法
   */
  object: {
    PROTOTYPE_FIELDS: [
      'constructor',
      'hasOwnProperty',
      'isPrototypeOf',
      'propertyIsEnumerable',
      'toLocaleString',
      'toString',
      'valueOf'
    ],
    extend: function(target, varArgs) {
      var key, source, newObj;

      newObj = $.extend(true, {}, target);

      for (var i = 1; i < arguments.length; i++) {
        source = arguments[i];

        for (key in source) {
          newObj[key] = source[key];
        }

        for (var j = 0; j < DU.object.PROTOTYPE_FIELDS.length; j++) {
          key = DU.object.PROTOTYPE_FIELDS[j];

          if (Object.prototype.hasOwnProperty.call(source, key)) {
            newObj[key] = source[key];
          }
        }
      }

      return newObj;
    },
    /**
     * 在物件中搜尋給定的值
     *
     * @param     mixed      needle       待搜尋的值
     * @param     object     haystack     物件
     * @param     boolean    argStrict    寬鬆比較
     *
     * @return    boolean
     */
    inObject: function(needle, haystack, argStrict) {
      var key = '', strict = !! argStrict;

      if (strict) {
        for (key in haystack) {
          if (haystack[key] === needle) {
            return true;
          }
        }
      } else {
        for (key in haystack) {
          if (haystack[key] == needle) {
            return true;
          }
        }
      }

      return false;
    },
    /**
     * 回傳包含物件中所有索引值的一個新物件
     *
     * @param     object     obj    物件
     *
     * @return    object
     */
    keys: function(obj) {
      var keyObj = {};
      var i = 0;

      if (obj != undefined) {
        $.each(obj, function(key) {
          keyObj[i] = key;

          i++;
        });
      }

      return keyObj;
    },
    /**
     * 計算物件元素數量
     *
     * @param     object     obj    物件
     *
     * @return    integer
     */
    size: function(obj) {
      var size = 0, key;

      for (key in obj) {
          if (obj.hasOwnProperty(key)) {
            size++;
          }
      }

      return size;
    },
    /**
     * 計算物件元素數量
     *
     * @param     mixed      needle       搜尋的值
     * @param     object     haystack     物件
     * @param     boolean    argStrict    檢查完全相同的元素
     *
     * @return    mixed                   如果找到了 needle 則返回它的索引值
     *                                    否則返回 FALSE
     */
    search: function(needle, haystack, argStrict) {
      var strict = !!argStrict,
          key = '';

      if (typeof needle === 'object' && needle.exec) {
        if (!strict) {
          var flags = 'i' + (needle.global ? 'g' : '')
                    + (needle.multiline ? 'm' : '')
                    + (needle.sticky ? 'y' : '');

          needle = new RegExp(needle.source, flags);
        }

        for (key in haystack) {
          if (haystack.hasOwnProperty(key)) {
            if (needle.test(haystack[key])) {
              return key;
            }
          }
        }

        return false;
      }

      for (key in haystack) {
        if (haystack.hasOwnProperty(key)) {
          if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
            return key;
          }
        }
      }

      return false;
    }
  },

  /**
   * 字串擴充方法
   */
  string: {
    /**
     * 格式化字串
     *
     * @param     string    format    轉換格式
     * @param     mixed     args      字串
     *
     * @return    string
     */
    printFormat: function(format, args) {
      if (arguments.length === 1) {
        return function() {
          var args = $.makeArray(arguments);

          args.unshift(format);

          return DU.string.printFormat.apply(this, args);
        };
      }

      if (arguments.length > 2 && args.constructor !== Array) {
        args = $.makeArray(args).slice(1);
      }

      if (args.constructor !== Array) {
        args = [args];
      }

      $.each(args, function(i, n) {
        format = format.replace(new RegExp('\\{' + i + '\\}', 'g'), function() {
          return n;
        });
      });

      return format;
    },
    /**
     * 從字串的兩端刪除空白字元和其他預定義字元
     *
     * @param     string    str         規定要檢查的字串
     * @param     string    charlist    規定要轉換的字串
     *                                  如果省略該參數，則刪除以下所有字符：
     *                                  "\0"   - NULL
     *                                  "\t"   - 定位符號
     *                                  "\n"   - 換行符號
     *                                  "\x0B" - 垂直制表符號
     *                                  "\r"   - 歸位符號
     *                                  " "    - 普通空白符號
     *
     * @return    string
     */
    trim: function(str, charlist) {
      var whitespace,
          l = 0,
          i = 0;

      str += '';

      if (!charlist) {
        whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
      } else {
        charlist += '';
        whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
      }

      l = str.length;
      for (i = 0; i < l; i++) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
          str = str.substring(i);
          break;
        }
      }

      l = str.length;
      for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
          str = str.substring(0, i + 1);
          break;
        }
      }

      return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
    }
  },

  /**
   * 陣列擴充方法
   */
  array: {
    /**
     * 將陣列的內部指針倒回一位
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    prev: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      var arrpos = pointers.indexOf(arr);
      var cursor = pointers[arrpos + 1];
      if (pointers.indexOf(arr) === -1 || cursor === 0) {
        return false;
      }
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;

        for (var k in arr) {
          if (ct === cursor - 1) {
            pointers[arrpos + 1] -= 1;
            return arr[k];
          }
          ct++;
        }
      }
      if (arr.length === 0) {
        return false;
      }
      pointers[arrpos + 1] -= 1;

      return arr[pointers[arrpos + 1]];
    },
    /**
     * 將陣列中的內部指針向前移動一位
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    next: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      if (pointers.indexOf(arr) === -1) {
        pointers.push(arr, 0);
      }
      var arrpos = pointers.indexOf(arr);
      var cursor = pointers[arrpos + 1];
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;
        for (var k in arr) {
          if (ct === cursor + 1) {
            pointers[arrpos + 1] += 1;
            return arr[k];
          }
          ct++;
        }
        return false; // End
      }
      if (arr.length === 0 || cursor === (arr.length - 1)) {
        return false;
      }
      pointers[arrpos + 1] += 1;

      return arr[pointers[arrpos + 1]];
    },
    /**
     * 將陣列的內部指針指向最後一個元素
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    end: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      if (pointers.indexOf(arr) === -1) {
        pointers.push(arr, 0);
      }
      var arrpos = pointers.indexOf(arr);
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;
        var val;
        for (var k in arr) {
          ct++;
          val = arr[k];
        }
        if (ct === 0) {
          return false; // Empty
        }
        pointers[arrpos + 1] = ct - 1;
        return val;
      }
      if (arr.length === 0) {
        return false;
      }
      pointers[arrpos + 1] = arr.length - 1;

      return arr[pointers[arrpos + 1]];
    },
    unique: function(arr) {
      var key = '',
          tmpArr = {},
          val = '';
      var arraySearch = function(needle, haystack) {
        var fkey = '';

        for (fkey in haystack) {
          if (haystack.hasOwnProperty(fkey)) {
            if ((haystack[fkey] + '') === (needle + '')) {
              return fkey;
            }
          }
        }

        return false;
      };

      for (key in arr) {
        if (arr.hasOwnProperty(key)) {
          val = arr[key];

          if (false === arraySearch(val, tmpArr)) {
            tmpArr[key] = val;
          }
        }
      }

      return tmpArr;
    }
  },

  validate: {
    /**
     * 檢查是否為密碼格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    pwd: function(value) {
      return !/\W+/.test(value);
    },
    /**
     * 檢查是否為 Email 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    email: function(value) {
      return /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(value);
    },
    /**
     * 檢查是否為行動電話格式
     *
     * @param     {string}     value    要檢查的值
     *
     * @return    {boolean}
     */
    mobile: function(value) {
      return /^09([0-9]{2}-[0-9]{3}-[0-9]{3}|[0-9]{2}-[0-9]{6}|[0-9]{8})$/.test(value);
    },
    /**
     * 檢查是否為 URL 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    url: function(value) {
      return /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i.test(value);
    },
    /**
     * 檢查是否為 YouTube URL 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    youtube: function(value) {
      return /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([\w-]{11})(?:.+)?$/i.test(value);
    },
    /**
     * 檢查是否為 DATE 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    date: function(value) {
      return !/Invalid|NaN/.test(new Date(value).toString());
    },
    /**
     * 檢查是否為 dateISO 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    dateIso: function(value) {
      return /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/.test(value);
    },
    /**
     * 檢查是否為 10 進制格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    number: function(value) {
      return /^(?:-?\d+|-?\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value);
    },
    /**
     * 檢查是否為純數字格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    digits: function(value) {
      return /^\d+$/.test(value);
    },
    /**
     * 檢查是否為在範圍內
     *
     * @param     string     value    要檢查的值
     * @param     array      param    要檢查的範圍陣列
     *
     * @return    boolean
     */
    range: function(value, param) {
      return (value >= param[0] && value <= param[1]);
    },
    /**
     * 檢查長度是否為在範圍內
     *
     * @param     string     value    要檢查的值
     * @param     array      param    要檢查的範圍陣列
     *
     * @return    boolean
     */
    rangelength: function(value, param) {
      var length = value.length;
      return (length >= param[0] && length <= param[1]);
    }
  },

  /**
   * 表單處理
   */
  form: {
    /**
     * 重置表單元素
     *
     * @param     {object}     targetObj       目標物件
     * @param     {object}     excludeObj      排除項目物件
     * @param     {boolean}    triggerEvent    觸發原有事件
     * @param     {boolean}    setDefault      現有值設定為預設值
     * @param     {boolean}    setEmpty        是否清空
     *
     * @return    {void}
     */
    reset: function(targetObj, excludeObj, triggerEvent, setDefault, setEmpty) {
      triggerEvent = triggerEvent || false;
      setDefault = setDefault || false;
      excludeObj = excludeObj || {};

      targetObj
        .find('input:not([type="hidden"], [type="submit"], [type="reset"], [type="button"]), select, textarea')
        .not(excludeObj)
        .each(function(index, element) {
          var $element = jQuery(element);

          switch ($element.prop('tagName').toLowerCase()) {
            // 文字區域
            case 'textarea':

              if (setDefault) {
                $element.prop('defaultValue', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.val() : ''));
              }

              $element.val($element.prop('defaultValue'));
              break;

            // 輸入欄位
            case 'input':

              var inpType = $element.prop('type').toLowerCase();
              switch (inpType) {
                // checkbox / radio
                case 'checkbox':
                case 'radio':

                  if (setDefault) {
                    $element.prop('defaultChecked', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.prop('checked') : false));
                  }

                  if ($element.prop('defaultChecked') == true) {
                    if ($element.prop('defaultChecked') != $element.prop('checked') && triggerEvent) {
                      $element.trigger('click');
                    }
                  } else {
                    $element.removeAttr('checked');
                  }
                  break;

                // text / ...
                case 'text':
                case 'number':
                case 'email':
                case 'url':
                case 'tel':
                case 'password':
                case 'file':
                // 例外
                default:

                  if (setDefault) {
                    var emptyVal = (inpType == 'number') ? 0 : '';

                    $element.prop('defaultValue', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.val() : emptyVal));
                  }

                  $element.val($element.prop('defaultValue'));
              }
              break;

            // 選擇項
            case 'select':

              // 取得下拉選項的預設值
              var $selectDefault = targetObj
                .find($element)
                .find('option')
                .filter(function() {
                  if (setDefault) {
                    $element.prop('defaultSelected', setEmpty ? false : this.selected);
                  }

                  if ($element.prop('defaultSelected') == true) {
                    return this;
                  }
                });

              if ($selectDefault.length === 0) {
                $selectDefault = targetObj.find($element).find('option:first')
              }

              if ($selectDefault.val() != $element.val()) {
                $element.val($selectDefault.val());
              }

              if (triggerEvent) {
                $element.trigger('change');
              }
              break;

            // 例外
            default:

              // do something...
          }
        });
    },
    /**
     * 去除表單元素值前後多餘空白
     *
     * @param     {object}    formObj    表單物件
     *
     * @return    {void}
     */
    trim: function(formObj) {
      if (formObj && formObj.length > 0) {
        $.map(
          formObj.get(0).elements,
          function(formEle) {
            if ($.inArray(formEle.type, ['file', 'select-multiple']) == -1) {
              formEle.value = $.trim(formEle.value);
            }
          }
        );
      }
    },
    /**
     * 產生 OPTION
     *
     * @param     {object}     $targetDom    SELECT 目標對象
     * @param     {array}      optArr        選項陣列
     * @param     {integer}    selected      已選值
     *
     * @return    {void}
     */
    genOption: function($targetDom, optArr, selected) {
      if ($targetDom instanceof $ === false) {
        $targetDom = $($targetDom);
      }

      selected = selected || $targetDom.prop('selectedIndex');

      $targetDom.find('option:gt(0)').remove();

      if ($.isEmptyObject(optArr) == true) {
        return;
      }

      $.each(optArr, function(key, val) {
        $('<option>')
          .attr({
            'value': key,
            'selected': (key == selected)
          })
          .text(val)
          .appendTo($targetDom);
      });

      $targetDom.find('option:selected').change();
    },
    /**
     * 初始化日期控制項
     *
     * @param    {object}    $selYear     年的 SELECT 目標對象
     * @param    {object}    $selMonth    月的 SELECT 目標對象
     * @param    {object}    $selDay      日的 SELECT 目標對象
     *
     * return    {void}
     */
    initYMDControls: function($selYear, $selMonth, $selDay) {
      if ($selYear.get(0) < 1 || $selMonth.get(0) < 1 || $selDay.get(0) < 1) {
        return false;
      }
      $selYear.add($selMonth).add($selDay)
        .on(
          {
            'init.DU change': function(e) {
              if (e.namespace == 'DU' && e.type == 'init') {
                e.stopPropagation();
              }

              if ($selMonth.val() < 1) {
                return;
              }

              var $dayOpts = $selDay.children('option').filter(function(i, e) { return e.value > 0; });
              var haveDef = $selDay.children('option').filter(function(i, e) { return e.value < 1; }).size() > 0;

              var days = [31, ((parseInt($selYear.val(), 10) % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
              var noOfDays = days[haveDef ? $selMonth.prop('selectedIndex') - 1 : $selMonth.prop('selectedIndex')];

              $selDay.prop('selectedIndex', Math.min(noOfDays - (haveDef ? 0 : 1), $selDay.prop('selectedIndex')));

              var i = $dayOpts.size();
              for (; i < noOfDays; ++i) {
                var step = i + 1;

                if ($dayOpts.eq(step).size() > 0) {
                  $dayOpts.eq(step).val(step).text(step);
                } else {
                  $('<option>').val(step).text(step).appendTo($selDay);
                }
              }

              var j = $dayOpts.size();
              for (; j > noOfDays; --j) {
                $dayOpts.eq(j - 1).remove();
              }
            }
          }
        )
        .triggerHandler('init.DU');
    }
  },

  /**
   * 切換讀取提示訊息
   *
   * @param     boolean    status    顯示狀態
   *
   * @return    void
   */
  toogleLoader: function(status) {

    require(['blockUI'], function() {

      if (true == status) {

        $.unblockUI();
        $.blockUI({
          message: '<svg width="70px" height="70px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-hourglass"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><g><path fill="none" stroke="#cec9c9" stroke-width="5" stroke-miterlimit="10" d="M58.4,51.7c-0.9-0.9-1.4-2-1.4-2.3s0.5-0.4,1.4-1.4 C70.8,43.8,79.8,30.5,80,15.5H70H30H20c0.2,15,9.2,28.1,21.6,32.3c0.9,0.9,1.4,1.2,1.4,1.5s-0.5,1.6-1.4,2.5 C29.2,56.1,20.2,69.5,20,85.5h10h40h10C79.8,69.5,70.8,55.9,58.4,51.7z" class="glass"></path><clipPath id="uil-hourglass-clip1"><rect x="15" y="20" width="70" height="25" class="clip"><animate attributeName="height" from="25" to="0" dur="1s" repeatCount="indefinite" vlaues="25;0;0" keyTimes="0;0.5;1"></animate><animate attributeName="y" from="20" to="45" dur="1s" repeatCount="indefinite" vlaues="20;45;45" keyTimes="0;0.5;1"></animate></rect></clipPath><clipPath id="uil-hourglass-clip2"><rect x="15" y="55" width="70" height="25" class="clip"><animate attributeName="height" from="0" to="25" dur="1s" repeatCount="indefinite" vlaues="0;25;25" keyTimes="0;0.5;1"></animate><animate attributeName="y" from="80" to="55" dur="1s" repeatCount="indefinite" vlaues="80;55;55" keyTimes="0;0.5;1"></animate></rect></clipPath><path d="M29,23c3.1,11.4,11.3,19.5,21,19.5S67.9,34.4,71,23H29z" clip-path="url(#uil-hourglass-clip1)" fill="#cec9c9" class="sand"></path><path d="M71.6,78c-3-11.6-11.5-20-21.5-20s-18.5,8.4-21.5,20H71.6z" clip-path="url(#uil-hourglass-clip2)" fill="#cec9c9" class="sand"></path><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="180 50 50" repeatCount="indefinite" dur="1s" values="0 50 50;0 50 50;180 50 50" keyTimes="0;0.7;1"></animateTransform></g></svg><p>loading.....<p>',
          baseZ: 2000,
          css: {
            'border': 'none',
            'width': '250px',
            'padding': '5px 3px',
            'left': '50%',
            'margin-left': '-125px',
            'color': '#fff',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px',
            'background-color': 'transparent',
            'font-size': '15px'
          }
        });

      } else {

        var __pollCount = 0;
        var __pollClose = function() {
          try {
            $.unblockUI();
          } catch (ex) {
            __pollCount += 1;
            if (__pollCount < 200) {
              setTimeout(
                function() {
                  __pollClose();
                },
                50
              );
            }
            return;
          }
        };
        __pollClose();
      }
    });
  },

  /**
   * 轉換字串為駝峰式
   *
   * @param     mixed    input    欲轉換的內容
   *
   * @return    mixed
   */
  camelCase: function(input, camelType) {
    var _this = this;
    // 字串
    var str = function(input) {
      input = input.replace(/_/g, '-');

      switch (camelType.toLowerCase()) {
        /**
         * 小駝峰式
         */
        case 'lower':
          if ($.camelCase !== undefined) {
            input = $.camelCase(input);
          } else {
            input = input.replace(/_/g, '-').replace(/-([\da-z])/ig, function(match, first) {
              return match.replace(/-/g, '').toUpperCase();
            });
          }
          break;
        /**
         * 大駝峰式
         */
        case 'upper':

          input = input.replace(
            /(^[a-z]+)|[0-9]+|[A-Z][a-z]+|[A-Z]+(?=[A-Z][a-z]|[0-9])/g,
            function(match, first) {
              if (first) {
                match = match[0].toUpperCase() + match.substr(1);
              }

              return match;
            }
          );
          break;
      }

      return input;
    };
    // 物件
    var obj = function(input) {
      var result = {};

      $.each(input, function(key, val) {
        result[convert(key)] = typeof val == 'object'
                             ? convert(val)
                             : val;
      });

      delete input; // 刪除舊的

      return result;
    };

    // 檢查傳入的值是否在檢查類型中並呼叫對應的方法
    var convert = function(input) {
        var types = {
            '[object Object]': obj,
            '[object String]': str,
            '[object Number]': null,
            '[object Array]': null
        };
        var type = Object.prototype.toString.call(input);

        return input != null && types[type] ? types[type](input) : input;
    };

    camelType = camelType || 'lower';

    return convert(input);
  },

  /**
   * 取得 URL 參數
   *
   * @param     string    url          URL
   * @param     string    paramName    取得指定參數值
   *
   * @return    array
   */
  getUrlParams: function(url, paramName) {
    var regex = /([^=&?]+)=([^&#]*)/g, params = {}, parts, key, value;

    while ((parts = regex.exec(url)) != null) {
      key = parts[1], value = parts[2];
      var isArray = /\[\]$/.test(key);

      if(isArray) {
        params[key] = params[key] || [];
        params[key].push(value);
      } else {
        params[key] = value;
      }
    }

    return paramName !== undefined ? (params[paramName] || '') : params;
  },
  /**
   * 產生 OPTION
   *
   * @param     {object}     targetDom    SELECT 目標對象
   * @param     {array}      optAttrArr   選項屬性陣列
   * @param     {integer}    selected     已選值
   *
   * @return    {void}
   */
  genOption: function(targetDom, optAttrArr, selected) {
    var $targetDom = targetDom instanceof $ ? $(targetDom) : targetDom;

    selected = selected || $targetDom.prop('selectedIndex');

    $targetDom.find('option[value!=""]').remove();

    if (false == $.isEmptyObject(optAttrArr)) {
      /**
       * 設定屬性
       *
       * @param     {object}    $opt        屬性對象
       * @param     {string}    attrName    屬性名稱
       * @param     {mixed}     attrVal     屬性值
       *
       * @return    {void}
       */
      var setAttr = function($opt, attrName, attrVal) {
        if (null !== attrName.match(/^text$/)) {
          $opt.text(attrVal);
        } else if (null !== attrName.match(/^select|selected$/)) {
          if (true == attrVal) {
            $opt.attr('selected', 'selected').prop('selected', true);
          } else {
            $opt.removeAttr('selected').removeProp('selected');
          }
        } else {
          $opt.attr(attrName, attrVal);
        }
      };

      $.each(optAttrArr, function(attrKey, attrData) {
        var $opt = $('<option>');

        if ($.isEmptyObject(attrData) == true) {
          return;
        }

        $.each(attrData, function(attrName, attrVal) {
          attrName = attrName.toString() || '';

          if ((typeof attrVal === 'object') && (attrVal !== null)) {
            attrName = attrName.replace(/_/g, '-');

            $.each(attrVal, function(dataName, dataVal) {
              dataName = dataName.replace(/_/g, '-');

              setAttr($opt, attrName + '-' + dataName, dataVal);
            });
          } else {
            setAttr($opt, attrName, attrVal);
          }
        });

        $opt.appendTo($targetDom);
      });
    }

    if (selected) {
      $targetDom.find('option').each(function(index, opt) {
        var $opt = jQuery(opt);

        if (selected == $opt.val()) {
          $opt.attr('selected', 'selected').prop('selected', true);
        } else {
          $opt.removeAttr('selected').removeProp('selected');
        }
      });
    }

    $targetDom.find('option:selected').change();
  }
};
