/* *
 * SelectZone 類
 */
function SelectZone() {

  var _self = this;

  this.filters   = {};
  this.id        = arguments[0] ? arguments[0] : 1;    // 過濾條件
  this.sourceSel = arguments[1] ? arguments[1] : null; // 來源 select 對像
  this.targetSel = arguments[2] ? arguments[2] : null; // 目標 select 對像
  this.priceObj  = arguments[3] ? arguments[3] : null; // 1: 關聯, 2: 組合、贈品（帶價格）

  this.fileName = (-1 != location.href.lastIndexOf('?')
                ? location.href.substring((location.href.lastIndexOf('/')) + 1, location.href.lastIndexOf('?'))
                : location.href)
                + '?is_ajax=1';

  /**
   * 載入來源 select 物件的 options
   *
   * @param   string      act        執行動作名稱
   * @param   object      filters    過濾條件陣列
   * @param   function    finish     執行完成函數
   */
  this.loadOptions = function(act, filters, finish) {
    $.ajax({
      type: 'POST',
      url: this.fileName + '&action=' + act,
      cache: false,
      data: filters,
      dataType: 'json',
      beforeSend: DU.ajax.beforeSend,
      complete: DU.ajax.complete,
      error: DU.ajax.error,
      success: function(data, textStatus) {
        // 將回傳的資料解析為 options 的形式
        if (!data.error) {
          _self.createOptions(_self.sourceSel, data.content);
          $.isFunction(finish) && finish.call(_self, data, textStatus);
        }

        if (data.message.length > 0) {
          DU.dialog.alert(data.message);
        }
      }
    });
  };

  /**
   * 檢查對象
   *
   * @return    boolean
   */
  this.check = function() {

    // source select
    if (!this.sourceSel) {
      DU.dialog.alert('source select undefined');
      return false;
    } else {
      if (this.sourceSel.nodeName != 'SELECT') {
        DU.dialog.alert('source select is not SELECT');
        return false;
      }
    }

    // target select
    if (!this.targetSel) {
      DU.dialog.alert('target select undefined');
      return false;
    } else {
      if (this.targetSel.nodeName != 'SELECT') {
        DU.dialog.alert('target select is not SELECT');
        return false;
      }
    }

    // price object
    if (this.id == 2 && ! this.priceObj) {
      DU.dialog.alert('price obj undefined');
      return false;
    }

    return true;
  };

  /**
   * 新增選中項目
   *
   * @param    boolean    all
   * @param    string     act
   * @param    object     args    其他參數
   */
  this.addItem = function(all, act, args) {

    if (!this.check()) {
      return;
    }

    var selOpt = [];
    for (var i = 0; i < this.sourceSel.length; i ++) {

      if (!this.sourceSel.options[i].selected && all == false) continue;

      if (this.targetSel.length > 0) {
        var exsits = false;
        for (var j = 0; j < this.targetSel.length; j ++) {
          if (this.targetSel.options[j].value == this.sourceSel.options[i].value) {
            exsits = true;
            break;
          }
        }
        if (!exsits) {
          selOpt[selOpt.length] = this.sourceSel.options[i].value;
        }
      } else {
        selOpt[selOpt.length] = this.sourceSel.options[i].value;
      }
    }

    if (selOpt.length > 0) {
      $.ajax({
        type: 'POST',
        url: this.fileName + '&action=' + act,
        cache: false,
        data: $.extend(
          true,
          {
            'add_ids': selOpt
          },
          args || {}
        ),
        dataType: 'json',
        beforeSend: DU.ajax.beforeSend,
        complete: DU.ajax.complete,
        error: DU.ajax.error,
        success: this.addRemoveItemResponse
      });
    }
  };

  /**
   * 刪除選中項目
   *
   * @param    boolean    all
   * @param    string     act
   * @param    object     args    其他參數
   */
  this.dropItem = function(all, act, args) {

    if (!this.check()) {
      return;
    }

    var selOpt = [];
    for (var i = this.targetSel.length - 1; i >= 0 ; i--) {
      if (this.targetSel.options[i].selected || all) {
        selOpt[selOpt.length] = this.targetSel.options[i].value;
      }
    }

    if (selOpt.length > 0) {
      $.ajax({
        type: 'POST',
        url: this.fileName + "&action=" + act,
        cache: false,
        data: $.extend(
          true,
          {
            'drop_ids': selOpt
          },
          args || {}
        ),
        dataType: 'json',
        beforeSend: DU.ajax.beforeSend,
        complete: DU.ajax.complete,
        error: DU.ajax.error,
        success: this.addRemoveItemResponse
      });
    }
  };

  /**
   * 處理新增項目回傳資料
   */
  this.addRemoveItemResponse = function(data, textStatus) {
    if (!data.error) {
      _self.createOptions(_self.targetSel, data.content);
    }

    if (data.message.length > 0) {
      DU.dialog.alert(data.message);
    }
  };

  /**
   * 為 select 元素建立 options
   */
  this.createOptions = function(obj, arr) {
    if (obj == null) {
      return;
    }

    obj.length = 0;

    var i = 0, arrLen = arr.length;
    for (i; i < arrLen; i++) {
      var opt = document.createElement('OPTION');

      $.each(arr[i], function(attrName, attrVal) {
        if (attrName == 'data') {
          if ($.isEmptyObject(attrVal) || attrVal.length < 1) {
            return;
          }

          $.each(attrVal, function(dKey, dVal) {
            opt.dataset[$.camelCase(dKey.toString())] = dVal;
          });
        } else {
          opt[attrName.toString()] = attrVal;
        }
      });

      obj.options.add(opt);
    }
  };
}