(function() {

  angular

    .module('du-admin')

    .controller('SYSLogCtrl', function() {

      require(['list-table', 'prettify'], function() {

        var $listDiv = $('#list-div');

        listTable.url = $listDiv.closest('form').attr('action');
        listTable.loadAjax = true;

        window.prettyPrint && prettyPrint();

        $('[data-trigger="view"]').on('click', function(e) {
          e.preventDefault();
          var $cEle = $(this);

          require(
            [
              'bootstrap-dialog'
            ],
            function(BootstrapDialog) {
              var dialog = new BootstrapDialog({
                size: 'size-wide',
                title: false,
                message: '資料載入中...'
              });
              dialog.open();

              $.ajax({
                type: 'GET',
                url: $cEle.attr('href'),
                dataType: 'json',
                success: function(data, textStatus) {
                  if (data.error == 0) {
                    dialog.getModalBody().html('<pre class="linenums">' + prettyPrintOne(data.content, '', true) + '</pre>');
                  } else {
                    dialog.close();
                    DU.dialog.alert('開啟失敗');
                  }
                }
              });
            }
          );

        });

        $('form[name="list-form"]').on('submit', function(e) {
          var $thisForm = $(this);
          e.preventDefault();
          DU.dialog.confirm('您確定要刪除所選取的項目嗎？', function(result) {
            if (result) {
              $thisForm.off('submit').trigger('submit');
            }
          });
        });
      });

    });

})();