(function() {

  angular

    .module('du-admin')

    .controller('ActionPrivCtrl', function() {

      require(['list-table'], function() {

        var $listDiv = $('#list-div');

        listTable.url = $listDiv.closest('form').attr('action');
      });

    });

})();