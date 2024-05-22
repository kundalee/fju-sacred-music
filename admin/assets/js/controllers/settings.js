(function() {

  angular

    .module('du-admin')

    .controller('SettingsWebSiteCtrl', function() {

      $('form[name="scope-form"] select[name="scope"]').on('change', function(e) {
        $(this).closest('form').trigger('submit');
      });

      $('input[type="radio"][id^="rewrite"][value="1"]').on('click', function(e) {
        e.preventDefault();
        var $ele = $(this);
        DU.dialog.confirm('URL Rewrite 功能要求您的 Web Server 必須是 Apache，<br>並且啟用了 rewrite 模組。<br>同時請您確認是否已經將htaccess.txt檔案重命名為.htaccess。', function(result) {
          $ele.prop('checked', result);
        });
      });

      $('input[type="radio"][id^="enable_gzip"][value="1"]').on('click', function(e) {
        e.preventDefault();
        var $ele = $(this);
        DU.dialog.confirm('GZip 功能需要您的伺服器支援 zlib 功能。<br>如果您發現開啟Gzip後頁面出現亂碼，可能是您的伺服器已經開啟了Gzip，您不需要再次開啟。', function(result) {
          $ele.prop('checked', result);
        });
      });

      $('.picture-file-help [data-toggle="remove"]').on('click', function(e) {
        e.preventDefault();
        var $rEle = $(this);
        DU.dialog.confirm('您確認要刪除圖檔嗎？', function(result) {
          if (result) {
            $.ajax({
              type: 'POST',
              data: $.extend({
                is_ajax: '1',
                action: 'del'
              }, $rEle.data('args') || {}),
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

      var $theForm = $('form[name="the-form"]');
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

    .controller('SettingsMailServerCtrl', function() {

      var $theForm = $('form[name="theForm"]');

      /* 測試郵件的發送 */
      $('[name="sendTestEmail"]').on('click', function(e) {
        var mailService = $('[name="value[mail_service]"]:checked', $theForm).val();
        var replyEmail = $('[name="value[smtp_mail]"]', $theForm).val();
        var mailCharset = $('[name="value[mail_charset]"]:checked', $theForm).val();
        var smtpHost = $('[name="value[smtp_host]"]', $theForm).val();
        var smtpPort = $('[name="value[smtp_port]"]', $theForm).val();
        var smtpUseAuthentication = $('[name="value[smtp_use_authentication]"]:checked', $theForm).val();
        var smtpUser = $('[name="value[smtp_user]"]', $theForm).val();
        var smtpPass = $('[name="value[smtp_pass]"]', $theForm).val();
        var smtpSecure = $('[name="value[smtp_secure]"]:checked', $theForm).val();
        var testMailAddress = $('[name="test_mail_address"]', $theForm).val();
        var errArr = [];
        if (testMailAddress == '') {
          errArr.push('必須提供至少一個收件人地址。');
        } else if (!/([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/.test(testMailAddress)) {
          errArr.push('電子郵件信箱格式不正確');
        }
        if (errArr.length > 0) {
          DU.dialog.alert(errArr);
        } else {
          $.ajax({
            type: 'POST',
            url: $theForm.attr('action'),
            cache: false,
            dataType: 'json',
            data: {
              is_ajax: 1,
              action: 'send_test_email',
              email: testMailAddress,
              mail_charset: mailCharset,
              mail_service: mailService,
              smtp_host: smtpHost,
              smtp_port: smtpPort,
              smtp_secure: smtpSecure,
              smtp_use_authentication: smtpUseAuthentication,
              smtp_user: smtpUser,
              smtp_pass: smtpPass,
              reply_email: replyEmail
            },
            beforeSend: DU.ajax.beforeSend,
            complete: DU.ajax.complete,
            error: DU.ajax.error,
            success: function(data, textStatus) {
              DU.dialog.alert(data.message);
            }
          });
        }
        return false;
      });

      var $theForm = $('form[name="the-form"]');
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

    });

})();