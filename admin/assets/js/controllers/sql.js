(function() {

  angular

    .module('du-admin')

    .controller('SQLFormCtrl', function() {

      var $theForm = $('form[name="the-form"]');

      // $('textarea[class*=autosize]', $theForm).autosize({append: "\n"});
      $('textarea[name="sql"]', $theForm).trigger('focus');

      /* 檢查表單輸入的數據 */
      $theForm.on('submit', function(e) {

        e.preventDefault();

        // 去掉字符串起始和結尾的空格
        $('input[type="text"], textarea', this).val(function() {
          return $.trim(this.value);
        });
        var errArr = [];
        if ($('textarea[name="sql"]', this).val() == '') {
          errArr.push('SQL語句為空');
        }
        if (errArr.length > 0) {
          DU.dialog.alert(errArr);
          return false;
        }
      });

    });

})();