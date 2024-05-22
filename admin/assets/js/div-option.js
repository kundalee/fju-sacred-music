var divOption = {

  execution: true,

  url: (location.href.lastIndexOf('?') == -1 ? location.href.substring((location.href.lastIndexOf('/')) + 1) : location.href.substring((location.href.lastIndexOf('/')) + 1, location.href.lastIndexOf('?'))) + '?is_ajax=1',

  params: {},

  callback: function() {

  },

  init: function(objEle, callback) {

    if (divOption.execution) {

      var $objEle = $(objEle);
      $objEle.on('mouseover mouseout', 'span.option-text', function(e) {
        var $lEle = $(this).parent();
        if (!$lEle.is('.readonly')) {
          if (e.type == 'mouseover') {
            $lEle.addClass('here');
          } else if (e.type == 'mouseout') {
            $lEle.removeClass('here');
          }
        }
      }).filter(':even').on('click', 'span.option-text', function(e) {
        var $lEle = $(this).parent();
        if (!$lEle.is('.readonly')) {
          $.ajax({
            type: 'POST',
            dataType: 'json',
            url: divOption.url,
            data: $.extend(divOption.params, {
              action: $lEle.parent().data('action'),
              ops: $lEle.data('op')
            }),
            cache: false,
            success: function(data, textStatus) {
              if (data.error == '') {
                callback = callback || divOption.callback;
                callback(data.res);
              }
            }
          });
          $lEle.addClass('is-add');
        }
      }).end().filter(':odd').on('click', 'span.option-text', function(e) {
        var $lEle = $(this).parent();
        if (!$lEle.is('.readonly')) {
          $.ajax({
            type: 'POST',
            dataType: 'json',
            url: divOption.url,
            data: $.extend(divOption.params, {
              action: $lEle.parent().data('action'),
              ops: $lEle.data('op')
            }),
            cache: false,
            success: function(data, textStatus) {
              if (data.error == '') {
                callback = callback || divOption.callback;
                callback(data.res);
              }
            }
          });
          $lEle.parent().parent().parent().find('td:first div.is-add').filter('[data-op="' + $lEle.data('op') + '"]').removeClass('is-add');
        }
      });

      $objEle.on('mouseover mouseout click', 'span.option-icon', function(e) {
        var $lEle = $(this).parent();
        /* 滑鼠效果 */
        if (e.type == 'mouseover') {
          $lEle.addClass('here');
        } else if (e.type == 'mouseout') {
          $lEle.removeClass('here');
        } else if (e.type == 'click') {
          $(this).next().trigger('click');
        }
      }).find('div:not(.readonly)').prepend('<span class="option-icon"></span>');
    }
  },

  build: function(objEle, ops, css) {

    var $objEle = $(objEle);
    if (!css) {
      css = 'link-drop';
    }
    var string = '', class1 = '', class2 = '';
    for (var i in ops) {
      class1 = ops[i].readonly ? 'readonly' : css;
      class2 = ops[i].link_drop ? ' is-add' : '';

      string += '<div data-op="' + ops[i].data + '" class="' + class1 + class2 + '">' +
                '<span class="option-text">' + ops[i].name + '</span>';
      if (typeof ops[i].sort != 'undefined') {
        string += '<span class="option-input">排序：<input type="text" value="' + ops[i].sort + '"></span>';
      }
      if (typeof ops[i].param != 'undefined') {
        string += '<span class="option-price">';
        for (var i2 in ops[i].param) {
            string += ops[i].param[i2].name + '：<input type="text" value="' + ops[i].param[i2].value + '" class="' + ops[i].param[i2].key + '"></span>';
        }
        string += '</span>';
      }
      string += '</div>';
    }
    $objEle.html(string);
    if ($objEle.data('icon')) {
      $objEle.find('div:not(".readonly")').prepend('<span class="option-icon"></span>');
    }
    // $.unblockUI();
  },

  add: function(objEle, callback) {

    if (divOption.execution) {

      var $objEle = $(objEle);
      var n = $objEle.find('div:not(.readonly, .is-add) > span.option-text');
      if (n.size() > 0) {
        /* 取得選項 */
        var ops = n.map(function() {
          return $(this).parent().data('op');
        }).get().join(',');

        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: divOption.url,
          data: $.extend(divOption.params, {
            action: $objEle.data('action'),
            ops: ops
          }),
          cache: false,
          beforeSend: DU.ajax.beforeSend,
          complete: DU.ajax.complete,
          error: DU.ajax.error,
          success: function(data, textStatus) {
            if (data.error == '') {
              callback = callback || divOption.callback;
              callback(data.res);
            }
          }
        });

        /* 選項處理 */
        n.parent().addClass('is-add');

        return;
      }
    }
  },

  drop: function(objEle, callback) {

    if (divOption.execution) {
      var $objEle = $(objEle);
      var n = $objEle.find('div > span.option-text');
      if (n.size() > 0) {

        /* 取得選項 */
        var ops = n.map(function() {
          return $(this).parent().data('op');
        }).get().join(',');

        $.ajax({
          type: 'POST',
          dataType: 'json',
          url: divOption.url,
          data: $.extend(divOption.params, {
            action: $objEle.data('action'),
            ops: ops
          }),
          cache: false,
          beforeSend: DU.ajax.beforeSend,
          complete: DU.ajax.complete,
          error: DU.ajax.error,
          success: function(data, textStatus) {
            if (data.error == '') {
              callback = callback || divOption.callback;
              callback(data.res);
            }
          }
        });

        /* 選項處理 */
        $objEle.parent().parent().find('td:first div.is-add').removeClass('is-add');

        return;
      }
    }
  }
};