/**
 * ========================================================
 * 版權所有 (C) 2016 鉅潞科技網頁設計公司，並保留所有權利。
 * 網站地址: http://www.grnet.com.tw
 * ========================================================
 * Date: 2016-09-22 10:40
 */

var region = {
  baseUrl: 'common.php?action=regions', // 基礎路徑
  regionsIngore: true, // 排除忽略區域
  regionsData: '', // 地區暫存資料
  zipTargetId: '', // 郵遞區號欄位 ID
  /**
   * 初始化
   *
   * @param     object    options    設定值
   *
   * @return    void
   */
  init: function(options) {
    for (var b in options) {
      this[b] = options[b];
    }
  },
  /**
   * 查詢區域資料
   *
   * @param     integer     parentId       上級地區 ID
   * @param     integer     type           地區類型
   * @param     string      target         連動目標名稱
   * @param     object      options        選填選項
   * @param     string      -[refId]       參考欄位 ID
   * @param     function    -[callback]    執行完成的回傳函數
   *
   * @return    void
   */
  loadRegions: function(parentId, type, target, options) {
    var _self = this;
    var opts = $.extend(
      {
        'refId': null,
        'callback': null
      },
      options
    );

    var $target = $('#' + (target || ''));
    var responseData = $target.data('response');
    var handle = function(data) {
      var _self = this;
      var $selTarget = $('#' + (target || ''));
      var $regionGrp = $('[data-region-type][data-role="' + $selTarget.data('role') + '"]');

      // 清除所有選項值
      $regionGrp
        .filter(function() {
          return $(this).data('region-type') > type;
        })
        .children('option')
        .filter(function() {
          return (parseInt($(this).attr('value'), 10) || 0) > 0;
        })
        .remove();

      // 如果有資料, 新增選項
      if (data) {
        if (
          $.isEmptyObject(data) == false &&
          typeof data[type] !== 'undefined' &&
          (typeof data[type][parentId] !== 'undefined' || typeof data[type][opts.refId] !== 'undefined')
        ) {
          var datas = data[type][parentId];

          if (parentId == 0 && opts.refId > 0) {
            datas = data[type][opts.refId];
          }

          // 預設選取第一個
          $selTarget.css('display', 'inline');

          $.each(datas, function(key, val) {
            $('<option>')
              .attr({
                'value': val.region_id,
                'data-zip': val.region_zip
              })
              .text(val.region_name)
              .appendTo($selTarget);
          });

          // 預設選取第一個
          $selTarget
            .find('option:first')
            .prop('selected', true)
            // .trigger('change');
        }
      }

      // 顯示隱藏控制
      $regionGrp
        .filter(function() {
          return $(this).data('region-type') > type;
        })
        .each(function(index, domEle) {
          var $cEle = $(domEle);
          var status = ($cEle.filter(':not(:hidden)') ? true : false);

          status &= $cEle
            .children('option')
            .filter(function() {
              return parseInt($(this).attr('value') || 0) > 0;
            })
            .size() > 0;
          status &= $cEle.data('region-type') > 0;

          $cEle.toggleClass('hidden', status == false);
          if ($cEle.data('wrapper') !== undefined) {
            $cEle.closest($cEle.data('wrapper')).toggleClass('hidden', status == false);
          }
        });
    };

    if (parentId < 1 && (parseInt(opts.refId, 10) || 0) < 1) {
      handle();
      return false;
    }

    if (
      $.isEmptyObject(_self.regionsData) == false &&
      _self.regionsData[type] != undefined &&
      _self.regionsData[type][parentId] != undefined
    ) {
      handle(_self.regionsData);

      $.isFunction(opts.callback) && opts.callback.call(_self);
    } else {
      $.ajax({
        type: 'GET',
        url: _self.baseUrl,
        cache: true,
        async: false,
        data: {
          'type': type,
          'parent_id': parentId,
          'ref_id': opts.refId,
          'target': target,
          'ignore': _self.regionsIngore ? 1 : 0
        },
        dataType: 'json',
        // async: true,
        beforeSend: DU.ajax.beforeSend,
        complete: DU.ajax.complete,
        error: DU.ajax.error,
        success: function(data, textStatus, jqXHR) {
          if (data.regions) {
            _self.regionsData = $.extend(true, _self.regionsData, data.regions);
          }

          handle(_self.regionsData);

          $.isFunction(opts.callback) && opts.callback.call(_self);
        }
      });
    }
  },
  /**
   * 載入指定的國家下所有的省份
   *
   * @param     integer     country     國家的編號
   * @param     string      selName     列表框的名稱
   * @param     function    callback    執行完成的回傳函數
   *
   * @return    void
   */
  loadProvinces: function(country, selName, callback) {
    var _self = this;
    var objName = (typeof selName == 'undefined')
                ? 'selProvinces'
                : selName;

    _self.loadRegions(country, 1, objName, {'callback': callback});
  },
  /**
   * 載入指定的省份下所有的城市
   *
   * @param     integer     province    省份的編號
   * @param     string      selName     列表框的名稱
   * @param     function    callback    執行完成的回傳函數
   *
   * @return    void
   */
  loadCities: function(province, selName, callback) {
    var _self = this;
    var objName = (typeof selName == 'undefined')
                ? 'selCities'
                : selName;

    _self.loadRegions(province, 2, objName, {'callback': callback});
  },
  /**
   * 載入指定的城市下的區 / 縣
   *
   * @param     integer     city        城市的編號
   * @param     string      selName     列表框的名稱
   * @param     function    callback    執行完成的回傳函數
   *
   * @return    void
   */
  loadDistricts: function(city, selName, callback) {
    var _self = this;
    var objName = (typeof selName == 'undefined')
                ? 'selDistricts'
                : selName;

    _self.loadRegions(city, 3, objName, {'callback': callback});
  },
  /**
   * 處理下拉列表改變的函數
   *
   * @param     object      srcObj            來源下拉物件
   * @param     integer     type              地區類型
   * @param     string      targetName        目標列表框的名稱
   * @param     object      options           選填選項
   * @param     string      -[zipTargetId]    郵遞區號欄位 ID
   * @param     string      -[refTargetId]    參考欄位 ID
   * @param     function    -[callback]       執行完成的回傳函數
   *
   * @return    void
   */
  changed: function(srcObj, type, targetName, options) {
    var _self = this;
    var opts = $.extend(
      {
        'zipTargetId': _self.zipTargetId,
        'refTargetId': null,
        'callback': null
      },
      options
    );
    var $srcObj = srcObj instanceof jQuery ? srcObj : $(srcObj);

    var $zipTarget = $(opts.zipTargetId);
    var $refTargetId = opts.refTargetId instanceof jQuery ? opts.refTargetId : $('#' + opts.refTargetId || '');
    var zip = $srcObj.find('option:checked').data('zip');

    $zipTarget.val(zip != undefined ? zip : '');

    if (type && targetName) {
      var parentId = $srcObj.val();
      if (parentId < 0 && $refTargetId.val() > 0) {
        // parentId = $refTargetId.val();
      }

      _self.loadRegions(
        parentId,
        type,
        targetName,
        {
          'refId': $refTargetId.val(),
          'callback': opts.callback
        }
      );
    }
  }
};

$(document).ready(function(e) {
  // 地址區域下拉處理
  $('[data-region-target]')
    .on(
      {
        'handle_zip': function(e) {
          var $cEle = $(this);
          var $rRole = $('[data-role="' + $cEle.data('role') + '"]');
          var location = $rRole.find('option:checked[data-location]').data('location');
          var $zipField = $rRole.filter($rRole.filter('[data-region-zipfield]').data('region-zipfield') || '');

          if ($zipField.size() > 0 && $zipField.is(':not([type="hidden"])') && location != undefined) {
            if (location == 0) {
              $zipField.addClass('hidden').val('');
            } else {
              $zipField.removeClass('hidden');
            }
          }
        },
        'change': function(e) {
          var $cEle = $(this);

          region.changed(
            $cEle,
            $cEle.data('region-type'),
            $cEle.data('region-target'),
            {
              'refTargetId': $cEle.data('region-refer'),
              'zipTargetId': $cEle.data('region-zipfield'),
              'callback': function(data) {
                var $tgt = $('#' + ($cEle.data('region-target') || ''));

                $cEle.trigger('handle_zip');

                if (region.regionsIngore == true && $tgt.size() > 0) {
                  region.changed(
                    $tgt,
                    $tgt.data('region-type'),
                    $tgt.data('region-target'),
                    {
                      'refTargetId': $('[data-region-type="1"][data-role="' + $tgt.data('role') + '"]')
                    }
                  );
                }
              }
            }
          );
        }
      }
    )
    .trigger('handle_zip');
});
