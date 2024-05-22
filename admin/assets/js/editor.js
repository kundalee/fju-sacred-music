(function($) {
  $.extend({
    ckeditorConfig: function(ckeditorOption) {
      if (typeof CKEDITOR === 'undefined') {
        return;
      }

      for (var set_option in ckeditorOption) {
        eval('CKEDITOR.config.' + set_option + ' = ckeditorOption[set_option];');
      }
    }
  });
  $.fn.extend({
    getCkeditor: function() {
      if (typeof CKEDITOR === 'undefined') {
        return;
      }

      var textareaName = $(this).attr('name');

      return eval('CKEDITOR.instances["' + textareaName + '"].getData();');
    },
    setCkeditor: function(ckeditorValue) {
      if (typeof CKEDITOR === 'undefined') {
        return;
      }

      ckeditorValue = String(ckeditorValue);
      var textareaName = $(this).attr('name');
      eval('CKEDITOR.instances["' + textareaName + '"].setData(ckeditorValue);');
    },
    insertCkeditor:function(ckeditorValue) {
      if (typeof CKEDITOR === 'undefined') {
        return;
      }

      ckeditorValue = String(ckeditorValue);
      var textareaName = $(this).attr('name');
      eval('CKEDITOR.instances["' + textareaName + '"].insertHtml(ckeditorValue);');
    }
  });
})($);
