define(['angular', 'base64_encode'], function() {

  var listTable = {

    query: 'query',

    filter: {},

    url: (location.href.lastIndexOf('?') == -1 ? location.href.substring((location.href.lastIndexOf('/')) + 1) : location.href.substring((location.href.lastIndexOf('/')) + 1, location.href.lastIndexOf('?'))) + '?is_ajax=1',

    loadAjax: true,

    /**
     * 建立一個可編輯區
     *
     * @param     object     obj
     * @param     string     act
     * @param     integer    id
     * @param     string     editType
     * @param     object     args
     *
     * @return    void
     */
    edit: function(obj, act, id, editType, args) {
      var _self = this;
      var tag = obj.firstChild.tagName;
      if (typeof(tag) != 'undefined' && tag.toLowerCase() == 'input') {
        return;
      }

      if (typeof(editType) == 'undefined') {
        editType = 'text';
      }

      var $editObj = $(obj);

      /* 保存原始的內容 */
      var org = $editObj.html();

      if ($('br', $editObj).size() > 0) {
        $('br', $editObj).replaceWith("\n");
      }
      var orgVal = $editObj.text();

      /* 創建一個輸入框 */
      var $txtObj;
      switch (editType) {
        case 'textarea':
          $txtObj = $(document.createElement('TEXTAREA'));
          break;
        case 'intval':
        case 'text':
        default:
          $txtObj = $(document.createElement('INPUT'));
      }
      $txtObj.val((orgVal == 'N/A') ? '' : orgVal);
      if (editType == 'intval') {
        $txtObj.css({textAlign: 'right'});
      }

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
            url: _self.url,
            cache: false,
            data: $.extend({
              action: act,
              val: $txtObj.val(),
              id: id
            }, args || {}),
            dataType: 'json',
            beforeSend: _self.ajaxBeforeSend,
            complete: _self.ajaxComplete,
            error: _self.ajaxError,
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
    },

    /**
     * 切換狀態
     *
     * options:
     *  - act          string    (required)
     *  - id           mixed     (required)
     *  - args         object    (optional)
     *  - trueVal      mixed     (optional)
     *  - falseVal     mixed     (optional)
     *  - trueText     mixed     (optional)
     *  - falseText    mixed     (optional)
     *
     * @param     object    obj
     * @param     object    options
     *
     * @return    void
     */
    toggle: function(obj, options) {
      var _self = this;
      var _default = {
        'action': '',
        'id': null,
        'args': {},
        'trueVal': '1',
        'falseVal': '0',
        'trueText': 'Yes',
        'falseText': 'No'
      };
      var $editObj = obj instanceof jQuery ? obj : $(obj);

      options = $.extend(
        true,
        _default,
        options
      );

      $.ajax({
        type: 'POST',
        url: _self.url,
        cache: false,
        data: $.extend(
          {
            'action': options.action,
            'val': ($editObj.data('status') == options.trueVal ? options.falseVal : options.trueVal),
            'id': options.id
          },
          options.args
        ),
        dataType: 'json',
        beforeSend: _self.ajaxBeforeSend,
        complete: _self.ajaxComplete,
        error: _self.ajaxError,
        success: function(data, textStatus) {
          if (data.message) {
            DU.dialog.alert(data.message);
          }
          if (data.error == 0) {
            $editObj.data('status', data.content);
            if ($editObj.is(':checkbox')) {
              $editObj.prop('checked', data.content == options.trueVal);
            } else {
              $editObj.text(data.content == options.trueVal ? options.trueText : options.falseText);
            }
          }
        }
      });
    },

    /**
     * 切換排序方式
     *
     * @param     string    sortBy
     * @param     string    sortOrder
     *
     * @return    void
     */
    sort: function(sortBy, sortOrder) {
      var _self = this;

      if ($.inArray((sortOrder || '').toUpperCase(), ['DESC', 'ASC']) == -1) {
        sortOrder = _self.filter.sort_by == sortBy && _self.filter.sort_order.toUpperCase() == 'DESC' ? 'ASC' : 'DESC';
      }

      var args = $.extend(
        {},
        _self.filter,
        {
          'action': _self.query,
          'sort_by': sortBy,
          'sort_order': sortOrder,
          'page_size': _self.getPageSize()
        }
      );

      if (_self.loadAjax) {
        $.ajax({
          type: 'POST',
          url: _self.url,
          data: args,
          cache: false,
          dataType: 'json',
          beforeSend: _self.ajaxBeforeSend,
          complete: _self.ajaxComplete,
          error: _self.ajaxError,
          success: _self.listCallback
        });
      } else {
        location.href = _self.url + _self.fixUrl('&' + $.param(args));
      }
    },

    /**
     * 刪除列表中的一個記錄
     */
    remove: function(id, cfm, opt) {
      var _self = this;
      if (opt == null) {
        opt = 'remove';
      }
      DU.dialog.confirm(cfm, function(result) {
        if (result) {
          var args = $.extend(
            {
              'action': opt,
              'id': id
            },
            _self.filter || {}
          );
          if (_self.loadAjax) {
            $.ajax({
              type: 'GET',
              url: _self.url,
              data: args,
              cache: false,
              dataType: 'json',
              beforeSend: _self.ajaxBeforeSend,
              complete: _self.ajaxComplete,
              error: _self.ajaxError,
              success: _self.listCallback
            });
          } else {
            location.href = _self.url + _self.fixUrl('&' + $.param(args));
          }
        }
      });
    },

    /**
     * 前往指定頁數
     *
     * @param     integer    page
     *
     * @return    void
     */
    gotoPage: function(page) {
      var _self = this;
      var args = {};

      if (_self.filter.page == page) {
        return false;
      }

      if (page != null) {
        args.page = page;
      }
      if (_self.filter.page > _self.pageCount) {
        args.page = 1;
      }

      args.page_size = _self.getPageSize();

      _self.loadList(args);
    },

    /**
     * 前往上一頁
     *
     * @return    void
     */
    gotoPageFirst: function() {
      var _self = this;
      if (_self.filter.page > 1) {
        _self.gotoPage(1);
      }
    },

    /**
     * 前往下一頁
     *
     * @return    void
     */
    gotoPagePrev: function() {
      var _self = this;
      if (_self.filter.page > 1) {
        _self.gotoPage(_self.filter.page - 1);
      }
    },

    /**
     * 前往下一頁
     *
     * @return    void
     */
    gotoPageNext: function() {
      var _self = this;
      if (_self.filter.page < _self.pageCount) {
        _self.gotoPage(parseInt(_self.filter.page, 10) + 1);
      }
    },

    /**
     * 前往最後一頁
     *
     * @return    void
     */
    gotoPageLast: function() {
      var _self = this;
      if (_self.filter.page < _self.pageCount) {
        _self.gotoPage(_self.pageCount);
      }
    },

    /**
     * 改變顯示數量
     *
     * @param     object     event
     *
     * @return    void
     */
    changePageSize: function(e) {
      var _self = this;
      var e = (typeof e == 'undefined') ? window.event : e;
      var change = true;

      if (e.type == 'keypress') {
        if (e.keyCode == 13) {
          e.preventDefault();
        } else {
          change = false;
        }
      }

      if (change && _self.getPageSize() != _self.filter['page_size']) {
        _self.gotoPage();
      }
    },

    /**
     * 取得顯示數量
     *
     * @return    void
     */
    getPageSize: function() {
      var ps = 20;
      var $sizeEle = $('#pageSize');
      if ($sizeEle.length && parseInt($sizeEle.val(), 10) > 0) {
        ps = parseInt($sizeEle.val(), 10);
        document.cookie = 'DUSCP[page_size]=' + ps + ";";
      }
      return ps;
    },

    /**
     * 載入列表
     *
     * @return    void
     */
    loadList: function(args) {
      var _self = this;
      var argsObj = $.extend(
        {
          'action': _self.query
        },
        _self.filter || {},
        args || {}
      );

      if (_self.loadAjax) {
        $.ajax({
          type: 'POST',
          url: _self.url,
          data: argsObj,
          cache: false,
          dataType: 'json',
          beforeSend: _self.ajaxBeforeSend,
          complete: _self.ajaxComplete,
          error: _self.ajaxError,
          success: _self.listCallback
        });
      } else {
        location.href = _self.url + _self.fixUrl('&' + $.param(argsObj));
      }
    },

    /**
     * 選擇列表上所有項目
     *
     * @param     object     obj
     * @param     string     chk
     *
     * @return    void
     */
    selectAll: function(obj, chk) {
      var $selAll = $(obj);
      var $thisTb = $(obj).closest('table');
      var $allEle = $thisTb
        .find('tr td:first-child input[type="checkbox"]')
        .filter('[name^="' + (chk || 'checkboxes') + '"][name$="[]"]');
      $allEle.prop('checked', $selAll.is(':checked'));
    },

    /**
     * 修正 url
     */
    fixUrl: function(url) {
      if (
        /msie/.test(navigator.userAgent.toLowerCase()) ||
        (!!navigator.userAgent.match(/(trident)(?:.*? rv ([\w.]+)|)/i))
      ) {
        url = (url || '').replace(/&/g, '&amp;');
      }
      return url;
    },

    /**
     * 執行一個處理動作
     *
     * @param     integer    id
     * @param     string     cfm
     * @param     string     opt
     * @param     object     args
     *
     * @return    void
     */
    handler: function(id, cfm, opt, args) {
      var _self = this;
      var argsObj = $.extend(
        {
          'action': opt,
          'id': id
        },
        _self.filter || {},
        args || {}
      );
      var process = function() {
        if (_self.loadAjax) {
          $.ajax({
            type: 'GET',
            url: _self.url,
            data: argsObj,
            cache: false,
            dataType: 'json',
            beforeSend: _self.ajaxBeforeSend,
            complete: _self.ajaxComplete,
            error: _self.ajaxError,
            success: _self.listCallback
          });
        } else {
          location.href = _self.url + _self.fixUrl('&' + $.param(argsObj));
        }
      };

      if (cfm == undefined) {
        cfm = /remove|drop|delete/.test(opt.toString()) ? '您要刪除這個項目嗎？' : '您要執行這個動作嗎？';
      }

      if (cfm) {
        cfm = cfm.replace(/(\\n)/, String.fromCharCode(10));
        DU.dialog.confirm(cfm, function(result) {
          if (result) {
            process();
          }
        });
      } else {
        process();
      }
    },

    /**
     * 向伺服器發送請求前動作
     *
     * @param     object    XMLHttpRequest    請求物件
     *
     * @return    void
     */
    ajaxBeforeSend: function(XMLHttpRequest) {
      DU.ajax.beforeSend(XMLHttpRequest);
    },

    /**
     * 伺服器完成請求後動作
     *
     * @param     object    XMLHttpRequest    請求物件
     * @param     string    textStatus        請求狀態
     *
     * @return    void
     */
    ajaxComplete: function(XMLHttpRequest, textStatus) {
      DU.ajax.complete(XMLHttpRequest, textStatus);
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
    ajaxError: function(XMLHttpRequest, textStatus, errorThrown) {
      DU.ajax.error(XMLHttpRequest, textStatus, errorThrown);
    },

    /**
     * 更新的回傳動作
     *
     * @param     array     data          回傳資料
     * @param     string    textStatus    狀態
     *
     * @return    void
     */
    listCallback: function(data, textStatus) {
      if (data.error > 0) {
        DU.dialog.alert(data.message);
      } else {
        try {

          var $body = $('body');

          var tplHtml = angular.element(data.content);
          angular
            .element('#list-div')
            .html(tplHtml)
            .injector()
            .invoke(function($compile, $location) {
              var $scope = angular.element(tplHtml).scope();
              $compile(tplHtml)($scope);
              $scope.$digest();
              if (typeof data.filter == 'object') {
                $location.search('query', base64_encode(angular.toJson(data.filter)));
                $scope.$apply();
              }
            });

          $body.triggerHandler('setup.DU');

          if (typeof data.filter == 'object') {
            listTable.filter = data.filter;
          }
          listTable.pageCount = data.page_count;
        } catch (e) {
          DU.dialog.alert(e.message);
        }
      }
    }
  };

  $(document).ready(function(e) {

    $(window).on('popstate', function(e) {
      if (!e.originalEvent.state) {
      } else if (e.originalEvent.state.filter) {
        listTable.loadList(e.originalEvent.state.filter);
      }
    });

    $('body')
      .on('click', '#list-div [data-query-args]', function(e) {
        e.preventDefault();
        var $cEle = $(this);
        var _args = $cEle.data('query-args');
        if (typeof _args != 'object') {
          _args = $.parseJSON((_args || '').replace(/\'/g, '"') || null);
        }
        $.extend(true, listTable.filter, _args);
        listTable.filter.page = 1;
        listTable.loadList();
      })
      .on('click mouseover', '#list-div [data-field-sort]', function(e) {
        e.preventDefault();
        var $cEle = $(this);
        if (e.type == 'mouseover') {
          $cEle.attr('title', '點擊對列表排序');
        } else if (e.type == 'click') {
          if (!$cEle.closest('table').find('.no-records').length) {
            listTable.sort($cEle.data('field-sort'));
          }
        }
      })
      .on('click', '#list-div [data-row-multi="true"]', function(e) {
        var $cEle = $(this);
        if ($cEle.closest('table').find('td.no-records').size() > 0) {
          return false;
        }
        listTable.selectAll(this, $cEle.data('chk'));
      })
      .on('click', '#list-div tr :first-child [type="checkbox"]', function(e) {
        var $formEle = $(this).closest('form');
        var disabled = !$formEle.find('tr td:first-child [type="checkbox"]:checked').size();
        $formEle.find('[type="submit"]').prop('disabled', disabled);
      })
      .on('click', '#list-div a[data-item-handler]', function(e) {
        e.preventDefault();

        e.preventDefault();
        var $cEle = $(this);

        var _id = '';
        var _text = '';
        var _handler = $cEle.data('item-handler');
        var _args = $cEle.data('handler-args');

        if (_handler == '') {
          return false;
        }

        if ($cEle.data('handler-text') !== undefined) {
          _text = $cEle.data('handler-text');
        } else {
          _text = $cEle.closest('#list-div').data(_handler.replace(/_/, '-') + '-text');
        }

        if (typeof _args != 'object') {
          _args = $.parseJSON((_args || '').replace(/\'/g, '"') || null);
        }

        if ($cEle.data('item-id') !== undefined) {
          _id = $cEle.data('item-id');
        } else if ($cEle.closest('td').data('item-id') !== undefined) {
          _id = $cEle.closest('td').data('item-id');
        } else if ($cEle.closest('tr').data('item-id') !== undefined) {
          _id = $cEle.closest('tr').data('item-id');
          var _argsCell = $cEle.closest('tr').data('item-args');
          if (typeof _argsCell != 'object') {
            _argsCell = $.parseJSON((_argsCell || '').replace(/\'/g, '"') || null)
          }
          _args = $.extend(true, _argsCell, _args);
        }
        listTable.handler(_id, _text, _handler, _args);

      })
      .on('click mouseover mouseout', '#list-div [data-cell-edit]', function(e) {
        var $cEle = $(this);
        if ($cEle.data('uneditable') == 1) {
          return;
        }
        if (e.type == 'mouseover') {
          $cEle.attr('title', '點擊修改內容').css({borderBottom: '1px solid #000000'});
        } else if (e.type == 'mouseout') {
          $cEle.css({borderBottom: 'none'});
        } else if (e.type == 'click') {
          var _act = '', _id = '', _editType = '', _args = {};
          _act = 'edit_' + $cEle.data('cell-edit');
          if ($cEle.closest('td').data('item-id') !== undefined) {
            _id = $cEle.closest('td').data('item-id');
            _editType = $cEle.closest('td').data('item-type');
            _args = $.parseJSON(($cEle.closest('td').data('item-args') || '').replace(/\'/g, '"') || null);
          } else if ($cEle.closest('tr').data('item-id') !== undefined) {
            _id = $cEle.closest('tr').data('item-id');
            _editType = $cEle.closest('tr').data('item-type') || $cEle.data('cell-type');
            _args = $.parseJSON(($cEle.closest('tr').data('item-args') || '').replace(/\'/g, '"') || null);
          }
          listTable.edit(this, _act, _id, _editType, _args);
        }
      })
      .on(
        {
          // 列表執行切換狀態動作
          'click': function(e) {
            e.preventDefault();

            var $cEle = $(this);
            var options = {
              'action': 'toggle_' + $cEle.data('cell-toggle'),
              'id': $cEle.data('item-id'),
              'args': (function() {
                var _args = $cEle.data('item-args');

                if (typeof _args != 'object') {
                  $.parseJSON(($cEle.data('item-args') || '').replace(/\'/g, '"') || null);
                }

                return _args;
              })(),
              'trueVal': $cEle.data('true-val'),
              'falseVal': $cEle.data('false-val'),
              'trueText': $cEle.data('true-text'),
              'falseText': $cEle.data('false-text')
            };
            var itemData = {};

            if ($cEle.is(':checkbox')) {
              $cEle.data('status', $cEle.prop('checked') ? 0 : 1);
            }

            if (false == $.isEmptyObject($cEle.closest('tr').data())) {
              itemData = $cEle.closest('tr').data();
            }
            if (false == $.isEmptyObject($cEle.closest('td').data())) {
              itemData = $cEle.closest('td').data();
            }

            $.each(itemData, function(key, val) {
              key = key.replace('item', '');
              key += '';
              key = $.camelCase(key.charAt(0).toLowerCase() + key.substr(1));

              if (key == 'args' && typeof val != 'object') {
                val = $.parseJSON((val || '').replace(/\'/g, '"') || null);
              }

              options[key] = val;
            });

            listTable.toggle($cEle, options);
          },
          'mouseover': function(e) {
            $(this).attr('title', '點擊修改狀態');
          }
        },
        '#list-div [data-cell-toggle]'
      )
      // 列表改變顯示數量
      .on('keypress blur', '#list-div #pageSize', function(e) {
        return listTable.changePageSize(e);
      })
      // 列表前往指定頁數
      .on('change', '#list-div #gotoPage', function(e) {
        listTable.gotoPage(this.value);
      })
      // 列表前往第一頁
      .on('click', '#list-div #gotoPageFirst', function(e) {
        e.preventDefault();
        listTable.gotoPageFirst();
      })
      // 列表前往上一頁
      .on('click', '#list-div #gotoPagePrev', function(e) {
        e.preventDefault();
        listTable.gotoPagePrev();
      })
      // 列表前往下一頁
      .on('click', '#list-div #gotoPageNext', function(e) {
        e.preventDefault();
        listTable.gotoPageNext();
      })
      // 列表前往最後一頁
      .on('click', '#list-div #gotoPageLast', function(e) {
        e.preventDefault();
        listTable.gotoPageLast();
      });

  });

  window.listTable = listTable;

});
