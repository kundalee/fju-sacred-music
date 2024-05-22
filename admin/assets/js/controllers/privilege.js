(function() {

  angular

    .module('du-admin')

    .controller('PrivilegeListCtrl', function() {

      require(['nestable'], function() {
        var currArray;
        $('.js-siblings-nestable').nestable({
          onDragStart: function(l, e) {
            currArray = l.nestable('serialize');
          }
        }).on('change gainedItem', function(e) {

          var $domEle = $(this);
          var serialize = $domEle.nestable('serialize');
          $.ajax({
            type: 'POST',
            url: 'privilege.php?action=edit_node',
            data: {
              parent_id: $domEle.data('parent-id'),
              serialize: serialize
            }
          });
        });
      });

      $('[data-item-handler="remove"]').on('click', function(e) {
        e.preventDefault();
        var $aEle = $(this);
        var $iEle = $aEle.closest('.dd-item');
        if ($iEle.length == 0) {
          $iEle = $aEle.closest('.panel');
        }
        if ($iEle.find('.dd-list').size()) {
          DU.dialog.alert('很抱歉『 ' + $aEle.data('username') + ' 』不是最底層管理者，不能刪除!');
        } else {
          DU.dialog.confirm('您確定要刪除 『 ' + $aEle.data('username') + ' 』 管理者嗎?', function(result) {
            if (result) {
              $.ajax({
                type: 'POST',
                url: $aEle.data('remove-href'),
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
        }
      });

    })

    .controller('PrivilegeFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');
      $('input[name="chkGroup"]', $theForm).on('click', function(e) {
        var $tEle = $(this).closest('.panel-heading');
        var $aEle = $('input:checkbox[name="action_code[]"]', $tEle.next());
        $aEle.prop('checked', this.checked);
      });

      var $allCodeEle = $('input[name="action_code[]"]', $theForm);
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

      require(
        [
          'plupload',
          'toastr'
        ],
        function(plupload, toastr) {

          var $profileAvatar = $('#profile-avatar');
          var $avatarUpload = $profileAvatar.next();
          $profileAvatar.on('click', function(e) {
            $profileAvatar.addClass('hidden');
            $avatarUpload.removeClass('hidden');
            if ($avatarUpload.data('performed') !== true) {
              $avatarUpload.data('performed', true).trigger('performed');
            }
          });

          $avatarUpload.on('performed', function(e) {

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

            var uploader = new plupload.Uploader({
              runtimes: 'html5,flash,silverlight,html4',
              browse_button: $avatarUpload.find('[data-action="upload"]').get(0),
              container: $avatarUpload.find('.ace-file-container').get(0),
              drop_element: $avatarUpload.find('.ace-file-container').get(0),
              dragdrop: true,
              url: 'privilege.php',
              multipart_params: {
                action: 'avatar_upload',
                is_ajax: 1
              },
              filters: {
                mime_types: [
                  {
                    title: 'Image files',
                    extensions: 'jpg,gif,png'
                  }
                ],
                max_file_size: '100kb' // 最大只能上傳 5MB 的文件
              },
              flash_swf_url: 'assets/Moxie.swf',
              silverlight_xap_url: 'assets/Moxie.xap',
              unique_names: true,
              multi_selection: false
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
              up.start();
              up.refresh(); // Reposition Flash/Silverlight
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
              var obj = $.parseJSON(data.response);
              if (obj.error == 0) {
                var avatarImg = new Image();
                avatarImg.src = obj.data_uri;
                avatarImg.onload = function() {
                  $profileAvatar.attr('src', obj.data_uri).removeClass('hidden');
                  $avatarUpload.addClass('hidden');
                  $('[name="avatar_base64"]').val(obj.data_uri);
                };
              } else {
                toastrTemplate();
                toastr['error'](
                  obj.message,
                  '系統訊息'
                );
              }
            });
            uploader.init();
          }).find('.remove').on('click', function(e) {
            e.preventDefault();
            $profileAvatar.removeClass('hidden');
            $avatarUpload.addClass('hidden');
          });

        }
      );

      $theForm.on('submit', function(e) {

        e.preventDefault();

        $('input[type="text"]', this).val(function() {
          return $.trim(this.value);
        });

        var username = $('input[name="username"]', this).val();
        var eMail = $('input[name="email"]', this).val();
        var confirmPassword = $('input[name="pwd_confirm"]', this).val();
        var errArr = [];

        if (username == '') {
          errArr.push('管理員帳號名不能為空!');
        }
        if (eMail == '') {
          errArr.push('電子郵件不能為空!');
        } else if (!/([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/.test(eMail)) {
          errArr.push('電子郵件格式不正確!');
        }

        if ($('input[name="action"]', this).val() == 'insert') {
          var password = $('input[name="password"]', this).val();
          if (password == '') {
            errArr.push('登入密碼不能為空。');
          } else if (password != confirmPassword) {
            errArr.push('密碼兩次輸入不一致!');
          }
        } else {
          var password = $('input[name="new_password"]', this).val();
          if (password != '') {
            if (password.length < 6 || password.length > 20) {
              errArr.push('新密碼必須為 6 至 20 個字元');
            } else if (password != confirmPassword) {
              errArr.push('新密碼兩次輸入不一致');
            }
          }
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