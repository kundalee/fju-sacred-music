var DU = {
  config: {
    init: function() {
      var _self = this;
      _self.stylePath = $('link[href*="assets/css/style."][href*=".css"]')
        .add('link[href*="assets/css/custom."][href*=".css"]')
        .add('link[href*="assets/css/site.core."][href*=".css"]')
        .attr('href') || '';
      _self.themeDir = _self.stylePath.replace(/assets\/.*/i, '');
      _self.mined = (/(?:style|custom|site\.core)\.min\.css(?:\?t=\d{0,})?/i).test(_self.stylePath) == true;
    },
    stylePath: '', // 樣版路徑
    themeDir: '', // 樣版資料夾名稱
    mined: false
  },

  dialog: {
    /**
     * 載入 dialog
     *
     * @param     string      skin      面板名稱
     * @param     function    finish    載入完成處理函式
     *
     * @return    void
     */
    load: function(skin, finish) {
      var object = $.data(document, 'confirm');
      if (object === undefined || object.initialized === undefined) {

        require(['confirm'], function() {
          $.data(document, 'confirm', {initialized: true});
          $.isFunction(finish) && finish();
        });

      } else {
        $.isFunction(finish) && finish();
      }
    },
    init: function() {
      var _self = this;
      _self.load();
    },
    show: function() {
      var _self = this;

      var options = {};
      var defaultOptions = {
        theme: 'bootstrap',
        type: 'blue',
        columnClass: 'col-xl-4 col-lg-6 col-md-8 col-sm-10',
        containerFluid: true,
        draggable: false,
        content: '',
        data: {
          callback: null
        }
      };
      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          content: arguments[0],
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }

      require(['confirm'], function() {
        $.dialog(options);
      });
    },
    alert: function() {
      var _self = this;

      /**
       * 建立顯示訊息
       *
       * @param     mixed      msg     訊息內容
       * @param     boolean    html    是否為 HTML 格式
       *
       * @return    void
       */
      var _createMsg = function(msg, html) {
        msg = msg || '';
        html = !isNaN(html) ? html : true;

        if (msg && msg.constructor === Array) {
          var contentLeng = msg.length;

          if (contentLeng > 1) {
            var msgStr = '錯誤原因如下請重新檢查：\n';
            for (var i = 0; i < contentLeng; i++) {
              msgStr += (i + 1) + '. ' + msg[i] + '\n';
            }
            msg = msgStr;
          } else {
            msg = msg.shift().toString();
          }
        }

        msg = msg.toString();
        msg = html ? msg.replace(/\n/g, '<br>') : msg.replace(/<[^>]*>/g, '');

        return msg;
      };

      var options = {};
      var defaultOptions = {
        theme: 'bootstrap',
        type: 'blue',
        columnClass: 'col-xl-4 col-lg-6 col-md-8 col-sm-10',
        containerFluid: true,
        draggable: false,
        title: '網頁訊息',
        content: '',
        data: {
          callback: null
        },
        buttons: [{
          text: '確定',
          btnClass: 'btn-blue',
          action: function() {
            typeof this.data.callback === 'function' && this.data.callback(true);
          }
        }]
      };

      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          content: _createMsg(arguments[0], true),
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }

      require(['confirm'], function() {
        $.alert(options);
      });
    },
    confirm: function() {
      var _self = this;

      var options = {};
      var defaultOptions = {
        theme: 'bootstrap',
        type: 'blue',
        columnClass: 'col-xl-4 col-lg-6 col-md-8 col-sm-10',
        containerFluid: true,
        draggable: false,
        title: '網頁訊息',
        content: '',
        data: {
          callback: null
        },
        buttons: [{
          text: '取消',
          action: function() {
            typeof this.data.callback === 'function' && this.data.callback(false);
          }
        }, {
          text: '確定',
          btnClass: 'btn-primary',
          action: function() {
            typeof this.data.callback === 'function' && this.data.callback(true);
          }
        }]
      };

      if (typeof arguments[0] === 'object' && arguments[0].constructor === {}.constructor) {
        options = $.extend(true, defaultOptions, arguments[0]);
      } else {
        options = $.extend(true, defaultOptions, {
          content: arguments[0],
          data: {
            callback: typeof arguments[1] !== 'undefined' ? arguments[1] : null
          }
        });
      }

      require(['confirm'], function() {
        $.confirm(options);
      });
    }
  },

  /**
   * ajax 處理物件
   */
  ajax: {
    config: {
      overlay: false
    },
    /**
     * 發送 HTTP 請求
     *
     * @param    {string}      url                  請求的 URL 地址
     * @param    {mixed}       params               發送參數
     * @param    {function}    callback             成功的回調函數
     * @param    {object}      options              其他可選參數
     * @param    {boolean}     options.async        是否異步請求的方式
     * @param    {string}      options.overlay      是否顯示遮罩
     * @param    {string}      options.method       請求的方式：GET、POST
     * @param    {string}      options.respType     回應類型：JSON、XML、TEXT…
     * @param    {object}      options.jqAjaxSet    jQuery ajax 設定
     */
    call: function(url, params, callback, options) {
      var _self = this;

      options = $.extend(
        {
          async: true,
          overlay: _self.config.overlay || false,
          method: 'POST',
          respType: 'json',
          jqAjaxSet: null
        },
        options
      );

      var ajaxSet = {
        type: options.method,
        url: url || window.location.pathname,
        cache: false,
        async: Boolean(options.async),
        data: params || {},
        dataType: options.respType,
        beforeSend: function(jqXHR, settings) {
          options.overlay && DU.toogleLoader(true);
          _self.beforeSend.apply(_self, arguments);
        },
        success: function(data, textStatus, jqXHR) {
          $.isFunction(callback) && callback.apply(_self, arguments);
        },
        error: function(jqXHR, textStatus, errorThrown) {
          options.overlay && DU.toogleLoader(false);
          _self.error.apply(_self, arguments);
        },
        complete: function(jqXHR, textStatus) {
          options.overlay && DU.toogleLoader(false);
          _self.complete.apply(_self, arguments);
        }
      };

      if (false != $.isEmptyObject(options.jqAjaxSet)) {
        ajaxSet = $.extend(ajaxSet, options.jqAjaxSet);
      }

      var xhr = $.ajax(ajaxSet);

      return xhr;
    },
    /**
     * 向伺服器發送請求前動作
     *
     * @param     object    XMLHttpRequest    請求物件
     *
     * @return    void
     */
    beforeSend: function(XMLHttpRequest) {
      XMLHttpRequest.setRequestHeader('Request-Type', 'ajax');
    },
    /**
     * 伺服器完成請求後動作
     *
     * @param     object    XMLHttpRequest    請求物件
     * @param     string    textStatus        請求狀態
     *
     * @return    void
     */
    complete: function(XMLHttpRequest, textStatus) {

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
    error: function(XMLHttpRequest, textStatus, errorThrown) {

    }
  },

  format: {
    /**
     * 將位元組轉成可閱讀格式
     *
     * @param     float      $bytes       位元組
     * @param     integer    $decimals    分位數
     * @param     string     $unit        容量單位
     *
     * @return    string
     */
    byte: function(bytes, decimals, unit) {
      decimals = decimals || 0;
      unit = unit || '';

      var units = {
          'B': 0,             // Byte
          'K': 1,   'KB': 1,  // Kilobyte
          'M': 2,   'MB': 2,  // Megabyte
          'G': 3,   'GB': 3,  // Gigabyte
          'T': 4,   'TB': 4,  // Terabyte
          'P': 5,   'PB': 5,  // Petabyte
          'E': 6,   'EB': 6,  // Exabyte
          'Z': 7,   'ZB': 7,  // Zettabyte
          'Y': 8,   'YB': 8   // Yottabyte
      };

      var value = 0;
      if (bytes > 0) {
        if (false == unit.toUpperCase() in units) {
          var pow = Math.floor(Math.log(bytes) / Math.log(1024));

          unit = DU.object.search(pow, units);
        }

        value = (bytes / Math.pow(1024, Math.floor(units[unit])));
      }

      decimals = Math.floor(Math.abs(decimals));
      if (decimals > 53) {
        decimals = 20;
      }

      return DU.string.printFormat('{0} {1}', [parseFloat(value).toFixed(decimals), unit]);
    },

    /**
     * 格式化價格
     *
     * @param     float     price    價格
     *
     * @return    string
     */
     price: function(price) {
        var _numberFormat = function(number, decimals, dec_point, thousands_sep) {
          number = (number + '').replace(/[^0-9+\-Ee.]/g, '');

          var n = !isFinite(+number) ? 0 : +number,
              prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
              sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
              dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
              s = '',
              toFixedFix = function(n, prec){
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
              };

          // Fix for IE parseFloat(0.55).toFixed(0) = 0;
          s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');

          if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
          }

          if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
          }

          return s.join(dec);
        };

        return _numberFormat(Math.round(price), 0, '.', ',');
    },
  },

  /**
   * 物件擴充方法
   */
  object: {
    PROTOTYPE_FIELDS: [
      'constructor',
      'hasOwnProperty',
      'isPrototypeOf',
      'propertyIsEnumerable',
      'toLocaleString',
      'toString',
      'valueOf'
    ],
    extend: function(target, varArgs) {
      var key, source, newObj;

      newObj = jQuery.extend(true, {}, target);

      for (var i = 1; i < arguments.length; i++) {
        source = arguments[i];

        for (key in source) {
          newObj[key] = source[key];
        }

        for (var j = 0; j < DU.object.PROTOTYPE_FIELDS.length; j++) {
          key = DU.object.PROTOTYPE_FIELDS[j];

          if (Object.prototype.hasOwnProperty.call(source, key)) {
            newObj[key] = source[key];
          }
        }
      }

      return newObj;
    },
    /**
     * 在物件中搜尋給定的值
     *
     * @param     mixed      needle       待搜尋的值
     * @param     object     haystack     物件
     * @param     boolean    argStrict    寬鬆比較
     *
     * @return    boolean
     */
    inObject: function(needle, haystack, argStrict) {
      var key = '', strict = !! argStrict;

      if (strict) {
        for (key in haystack) {
          if (haystack[key] === needle) {
            return true;
          }
        }
      } else {
        for (key in haystack) {
          if (haystack[key] == needle) {
            return true;
          }
        }
      }

      return false;
    },
    /**
     * 回傳包含物件中所有索引值的一個新物件
     *
     * @param     object     obj    物件
     *
     * @return    object
     */
    keys: function(obj) {
      var keyObj = {};
      var i = 0;

      if (obj != undefined) {
        $.each(obj, function(key) {
          keyObj[i] = key;

          i++;
        });
      }

      return keyObj;
    },
    /**
     * 計算物件元素數量
     *
     * @param     object     obj    物件
     *
     * @return    integer
     */
    size: function(obj) {
      var size = 0, key;

      for (key in obj) {
          if (obj.hasOwnProperty(key)) {
            size++;
          }
      }

      return size;
    },
    /**
     * 計算物件元素數量
     *
     * @param     mixed      needle       搜尋的值
     * @param     object     haystack     物件
     * @param     boolean    argStrict    檢查完全相同的元素
     *
     * @return    mixed                   如果找到了 needle 則返回它的索引值
     *                                    否則返回 FALSE
     */
    search: function(needle, haystack, argStrict) {
      var strict = !!argStrict,
          key = '';

      if (typeof needle === 'object' && needle.exec) {
        if (!strict) {
          var flags = 'i' + (needle.global ? 'g' : '')
                    + (needle.multiline ? 'm' : '')
                    + (needle.sticky ? 'y' : '');

          needle = new RegExp(needle.source, flags);
        }

        for (key in haystack) {
          if (haystack.hasOwnProperty(key)) {
            if (needle.test(haystack[key])) {
              return key;
            }
          }
        }

        return false;
      }

      for (key in haystack) {
        if (haystack.hasOwnProperty(key)) {
          if ((strict && haystack[key] === needle) || (!strict && haystack[key] == needle)) {
            return key;
          }
        }
      }

      return false;
    }
  },

  /**
   * 字串擴充方法
   */
  string: {
    /**
     * 格式化字串
     *
     * @param     string    format    轉換格式
     * @param     mixed     args      字串
     *
     * @return    string
     */
    printFormat: function(format, args) {
      if (arguments.length === 1) {
        return function() {
          var args = $.makeArray(arguments);

          args.unshift(format);

          return DU.string.printFormat.apply(this, args);
        };
      }

      if (arguments.length > 2 && args.constructor !== Array) {
        args = $.makeArray(args).slice(1);
      }

      if (args.constructor !== Array) {
        args = [args];
      }

      $.each(args, function(i, n) {
        format = format.replace(new RegExp('\\{' + i + '\\}', 'g'), function() {
          return n;
        });
      });

      return format;
    },
    /**
     * 從字串的兩端刪除空白字元和其他預定義字元
     *
     * @param     string    str         規定要檢查的字串
     * @param     string    charlist    規定要轉換的字串
     *                                  如果省略該參數，則刪除以下所有字符：
     *                                  "\0"   - NULL
     *                                  "\t"   - 定位符號
     *                                  "\n"   - 換行符號
     *                                  "\x0B" - 垂直制表符號
     *                                  "\r"   - 歸位符號
     *                                  " "    - 普通空白符號
     *
     * @return    string
     */
    trim: function(str, charlist) {
      var whitespace,
          l = 0,
          i = 0;

      str += '';

      if (!charlist) {
        whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
      } else {
        charlist += '';
        whitespace = charlist.replace(/([\[\]\(\)\.\?\/\*\{\}\+\$\^\:])/g, '$1');
      }

      l = str.length;
      for (i = 0; i < l; i++) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
          str = str.substring(i);
          break;
        }
      }

      l = str.length;
      for (i = l - 1; i >= 0; i--) {
        if (whitespace.indexOf(str.charAt(i)) === -1) {
          str = str.substring(0, i + 1);
          break;
        }
      }

      return whitespace.indexOf(str.charAt(0)) === -1 ? str : '';
    }
  },

  /**
   * 陣列擴充方法
   */
  array: {
    /**
     * 將陣列的內部指針倒回一位
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    prev: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      var arrpos = pointers.indexOf(arr);
      var cursor = pointers[arrpos + 1];
      if (pointers.indexOf(arr) === -1 || cursor === 0) {
        return false;
      }
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;

        for (var k in arr) {
          if (ct === cursor - 1) {
            pointers[arrpos + 1] -= 1;
            return arr[k];
          }
          ct++;
        }
      }
      if (arr.length === 0) {
        return false;
      }
      pointers[arrpos + 1] -= 1;

      return arr[pointers[arrpos + 1]];
    },
    /**
     * 將陣列中的內部指針向前移動一位
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    next: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      if (pointers.indexOf(arr) === -1) {
        pointers.push(arr, 0);
      }
      var arrpos = pointers.indexOf(arr);
      var cursor = pointers[arrpos + 1];
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;
        for (var k in arr) {
          if (ct === cursor + 1) {
            pointers[arrpos + 1] += 1;
            return arr[k];
          }
          ct++;
        }
        return false; // End
      }
      if (arr.length === 0 || cursor === (arr.length - 1)) {
        return false;
      }
      pointers[arrpos + 1] += 1;

      return arr[pointers[arrpos + 1]];
    },
    /**
     * 將陣列的內部指針指向最後一個元素
     *
     * @param     array    arr    規定要使用的陣列
     *
     * @return    array
     */
    end: function(arr) {
      this.pointers = this.pointers || [];

      var indexOf = function (value){
        for (var i = 0, length = this.length; i < length; i++) {
          if (this[i] === value) {
            return i;
          }
        }
        return -1;
      };

      var pointers = this.pointers;
      if (!pointers.indexOf) {
        pointers.indexOf = indexOf;
      }
      if (pointers.indexOf(arr) === -1) {
        pointers.push(arr, 0);
      }
      var arrpos = pointers.indexOf(arr);
      if (Object.prototype.toString.call(arr) !== '[object Array]') {
        var ct = 0;
        var val;
        for (var k in arr) {
          ct++;
          val = arr[k];
        }
        if (ct === 0) {
          return false; // Empty
        }
        pointers[arrpos + 1] = ct - 1;
        return val;
      }
      if (arr.length === 0) {
        return false;
      }
      pointers[arrpos + 1] = arr.length - 1;

      return arr[pointers[arrpos + 1]];
    },
    unique: function(arr) {
      var key = '',
          tmpArr = {},
          val = '';
      var arraySearch = function(needle, haystack) {
        var fkey = '';

        for (fkey in haystack) {
          if (haystack.hasOwnProperty(fkey)) {
            if ((haystack[fkey] + '') === (needle + '')) {
              return fkey;
            }
          }
        }

        return false;
      };

      for (key in arr) {
        if (arr.hasOwnProperty(key)) {
          val = arr[key];

          if (false === arraySearch(val, tmpArr)) {
            tmpArr[key] = val;
          }
        }
      }

      return tmpArr;
    }
  },

  validate: {
    /**
     * 檢查是否為密碼格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    pwd: function(value) {
      return !/\W+/.test(value);
    },
    /**
     * 檢查是否為 Email 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    email: function(value) {
      return /^[a-zA-Z0-9.!#$%&'*+\/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/.test(value);
    },
    /**
     * 檢查是否為行動電話格式
     *
     * @param     {string}     value    要檢查的值
     *
     * @return    {boolean}
     */
    mobile: function(value) {
      return /^09([0-9]{2}-[0-9]{3}-[0-9]{3}|[0-9]{2}-[0-9]{6}|[0-9]{8})$/.test(value);
    },
    /**
     * 檢查是否為 URL 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    url: function(value) {
      return /^(?:(?:(?:https?|ftp):)?\/\/)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)(?:\.(?:[a-z\u00a1-\uffff0-9]-*)*[a-z\u00a1-\uffff0-9]+)*(?:\.(?:[a-z\u00a1-\uffff]{2,})).?)(?::\d{2,5})?(?:[/?#]\S*)?$/i.test(value);
    },
    /**
     * 檢查是否為 YouTube URL 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    youtube: function(value) {
      return /^(?:https?:\/\/)?(?:www\.)?(?:youtu\.be\/|youtube\.com(?:\/embed\/|\/v\/|\/watch\?v=|\/watch\?.+&v=))([\w-]{11})(?:.+)?$/i.test(value);
    },
    /**
     * 檢查是否為 DATE 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    date: function(value) {
      return !/Invalid|NaN/.test(new Date(value).toString());
    },
    /**
     * 檢查是否為 dateISO 格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    dateIso: function(value) {
      return /^\d{4}[\/\-](0?[1-9]|1[012])[\/\-](0?[1-9]|[12][0-9]|3[01])$/.test(value);
    },
    /**
     * 檢查是否為 10 進制格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    number: function(value) {
      return /^(?:-?\d+|-?\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value);
    },
    /**
     * 檢查是否為純數字格式
     *
     * @param     string     value    要檢查的值
     *
     * @return    boolean
     */
    digits: function(value) {
      return /^\d+$/.test(value);
    },
    /**
     * 檢查是否為在範圍內
     *
     * @param     string     value    要檢查的值
     * @param     array      param    要檢查的範圍陣列
     *
     * @return    boolean
     */
    range: function(value, param) {
      return (value >= param[0] && value <= param[1]);
    },
    /**
     * 檢查長度是否為在範圍內
     *
     * @param     string     value    要檢查的值
     * @param     array      param    要檢查的範圍陣列
     *
     * @return    boolean
     */
    rangelength: function(value, param) {
      var length = value.length;
      return (length >= param[0] && length <= param[1]);
    }
  },

  /**
   * 表單處理
   */
  form: {
    /**
     * 重置表單元素
     *
     * @param     {object}     targetObj       目標物件
     * @param     {object}     excludeObj      排除項目物件
     * @param     {boolean}    triggerEvent    觸發原有事件
     * @param     {boolean}    setDefault      現有值設定為預設值
     * @param     {boolean}    setEmpty        是否清空
     *
     * @return    {void}
     */
    reset: function(targetObj, excludeObj, triggerEvent, setDefault, setEmpty) {
      triggerEvent = triggerEvent || false;
      setDefault = setDefault || false;
      excludeObj = excludeObj || {};

      targetObj
        .find('input:not([type="hidden"], [type="submit"], [type="reset"], [type="button"]), select, textarea')
        .not(excludeObj)
        .each(function(index, element) {
          var $element = jQuery(element);

          switch ($element.prop('tagName').toLowerCase()) {
            // 文字區域
            case 'textarea':

              if (setDefault) {
                $element.prop('defaultValue', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.val() : ''));
              }

              $element.val($element.prop('defaultValue'));
              break;

            // 輸入欄位
            case 'input':

              var inpType = $element.prop('type').toLowerCase();
              switch (inpType) {
                // checkbox / radio
                case 'checkbox':
                case 'radio':

                  if (setDefault) {
                    $element.prop('defaultChecked', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.prop('checked') : false));
                  }

                  if ($element.prop('defaultChecked') == true) {
                    if ($element.prop('defaultChecked') != $element.prop('checked') && triggerEvent) {
                      $element.trigger('click');
                    }
                  } else {
                    $element.removeAttr('checked');
                  }
                  break;

                // text / ...
                case 'text':
                case 'number':
                case 'email':
                case 'url':
                case 'tel':
                case 'password':
                case 'file':
                // 例外
                default:

                  if (setDefault) {
                    var emptyVal = (inpType == 'number') ? 0 : '';

                    $element.prop('defaultValue', $element.data('def-val') != undefined ? $element.data('def-val') : (!setEmpty ? $element.val() : emptyVal));
                  }

                  $element.val($element.prop('defaultValue'));
              }
              break;

            // 選擇項
            case 'select':

              // 取得下拉選項的預設值
              var $selectDefault = targetObj
                .find($element)
                .find('option')
                .filter(function() {
                  if (setDefault) {
                    $element.prop('defaultSelected', setEmpty ? false : this.selected);
                  }

                  if ($element.prop('defaultSelected') == true) {
                    return this;
                  }
                });

              if ($selectDefault.length === 0) {
                $selectDefault = targetObj.find($element).find('option:first')
              }

              if ($selectDefault.val() != $element.val()) {
                $element.val($selectDefault.val());
              }

              if (triggerEvent) {
                $element.trigger('change');
              }
              break;

            // 例外
            default:

              // do something...
          }
        });
    },
    /**
     * 去除表單元素值前後多餘空白
     *
     * @param     {object}    formObj    表單物件
     *
     * @return    {void}
     */
    trim: function(formObj) {
      if (formObj && formObj.length > 0) {
        $.map(
          formObj.get(0).elements,
          function(formEle) {
            if ($.inArray(formEle.type, ['file', 'select-multiple']) == -1) {
              formEle.value = $.trim(formEle.value);
            }
          }
        );
      }
    },
    /**
     * 產生 OPTION
     *
     * @param     {object}     $targetDom    SELECT 目標對象
     * @param     {array}      optArr        選項陣列
     * @param     {integer}    selected      已選值
     *
     * @return    {void}
     */
    genOption: function($targetDom, optArr, selected) {
      if ($targetDom instanceof $ === false) {
        $targetDom = $($targetDom);
      }

      selected = selected || $targetDom.prop('selectedIndex');

      $targetDom.find('option:gt(0)').remove();

      if ($.isEmptyObject(optArr) == true) {
        return;
      }

      $.each(optArr, function(key, val) {
        $('<option>')
          .attr({
            'value': key,
            'selected': (key == selected)
          })
          .text(val)
          .appendTo($targetDom);
      });

      $targetDom.find('option:selected').change();
    },
    /**
     * 初始化日期控制項
     *
     * @param    {object}    $selYear     年的 SELECT 目標對象
     * @param    {object}    $selMonth    月的 SELECT 目標對象
     * @param    {object}    $selDay      日的 SELECT 目標對象
     *
     * return    {void}
     */
    initYMDControls: function($selYear, $selMonth, $selDay) {
      if ($selYear.get(0) < 1 || $selMonth.get(0) < 1 || $selDay.get(0) < 1) {
        return false;
      }
      $selYear.add($selMonth).add($selDay)
        .on(
          {
            'init.DU change': function(e) {
              if (e.namespace == 'DU' && e.type == 'init') {
                e.stopPropagation();
              }

              if ($selMonth.val() < 1) {
                return;
              }

              var $dayOpts = $selDay.children('option').filter(function(i, e) { return e.value > 0; });
              var haveDef = $selDay.children('option').filter(function(i, e) { return e.value < 1; }).size() > 0;

              var days = [31, ((parseInt($selYear.val(), 10) % 4 == 0) ? 29 : 28), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
              var noOfDays = days[haveDef ? $selMonth.prop('selectedIndex') - 1 : $selMonth.prop('selectedIndex')];

              $selDay.prop('selectedIndex', Math.min(noOfDays - (haveDef ? 0 : 1), $selDay.prop('selectedIndex')));

              var i = $dayOpts.size();
              for (; i < noOfDays; ++i) {
                var step = i + 1;

                if ($dayOpts.eq(step).size() > 0) {
                  $dayOpts.eq(step).val(step).text(step);
                } else {
                  $('<option>').val(step).text(step).appendTo($selDay);
                }
              }

              var j = $dayOpts.size();
              for (; j > noOfDays; --j) {
                $dayOpts.eq(j - 1).remove();
              }
            }
          }
        )
        .triggerHandler('init.DU');
    }
  },

  /**
   * 切換讀取提示訊息
   *
   * @param     boolean    status    顯示狀態
   *
   * @return    void
   */
  toogleLoader: function(status) {
    require(['blockUI'], function() {

      if (true == status) {
        $.blockUI({
          message: '<svg width="70px" height="70px" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="xMidYMid" class="uil-hourglass"><rect x="0" y="0" width="100" height="100" fill="none" class="bk"></rect><g><path fill="none" stroke="#cec9c9" stroke-width="5" stroke-miterlimit="10" d="M58.4,51.7c-0.9-0.9-1.4-2-1.4-2.3s0.5-0.4,1.4-1.4 C70.8,43.8,79.8,30.5,80,15.5H70H30H20c0.2,15,9.2,28.1,21.6,32.3c0.9,0.9,1.4,1.2,1.4,1.5s-0.5,1.6-1.4,2.5 C29.2,56.1,20.2,69.5,20,85.5h10h40h10C79.8,69.5,70.8,55.9,58.4,51.7z" class="glass"></path><clipPath id="uil-hourglass-clip1"><rect x="15" y="20" width="70" height="25" class="clip"><animate attributeName="height" from="25" to="0" dur="1s" repeatCount="indefinite" vlaues="25;0;0" keyTimes="0;0.5;1"></animate><animate attributeName="y" from="20" to="45" dur="1s" repeatCount="indefinite" vlaues="20;45;45" keyTimes="0;0.5;1"></animate></rect></clipPath><clipPath id="uil-hourglass-clip2"><rect x="15" y="55" width="70" height="25" class="clip"><animate attributeName="height" from="0" to="25" dur="1s" repeatCount="indefinite" vlaues="0;25;25" keyTimes="0;0.5;1"></animate><animate attributeName="y" from="80" to="55" dur="1s" repeatCount="indefinite" vlaues="80;55;55" keyTimes="0;0.5;1"></animate></rect></clipPath><path d="M29,23c3.1,11.4,11.3,19.5,21,19.5S67.9,34.4,71,23H29z" clip-path="url(#uil-hourglass-clip1)" fill="#cec9c9" class="sand"></path><path d="M71.6,78c-3-11.6-11.5-20-21.5-20s-18.5,8.4-21.5,20H71.6z" clip-path="url(#uil-hourglass-clip2)" fill="#cec9c9" class="sand"></path><animateTransform attributeName="transform" type="rotate" from="0 50 50" to="180 50 50" repeatCount="indefinite" dur="1s" values="0 50 50;0 50 50;180 50 50" keyTimes="0;0.7;1"></animateTransform></g></svg><p>loading.....<p>',
          // baseZ: 2000,
          css: {
            'border': 'none',
            'width': '250px',
            'padding': '5px 3px',
            'left': '50%',
            'margin-left': '-125px',
            'color': '#fff',
            '-webkit-border-radius': '10px',
            '-moz-border-radius': '10px',
            'background-color': 'transparent',
            'font-size': '15px'
          }
        });
      } else {
        var __pollCount = 0;
        var __pollClose = function() {
          try {
            $.unblockUI();
          } catch (ex) {
            __pollCount += 1;
            if (__pollCount < 200) {
              setTimeout(
                function() {
                  __pollClose();
                },
                50
              );
            }
            return;
          }
        };
        __pollClose();
      }
    });
  },

  /**
   * 轉換字串為駝峰式
   *
   * @param     mixed    input    欲轉換的內容
   *
   * @return    mixed
   */
  camelCase: function(input, camelType) {
    var _this = this;
    // 字串
    var str = function(input) {
      input = input.replace(/_/g, '-');

      switch (camelType.toLowerCase()) {
        /**
         * 小駝峰式
         */
        case 'lower':
          if ($.camelCase !== undefined) {
            input = $.camelCase(input);
          } else {
            input = input.replace(/_/g, '-').replace(/-([\da-z])/ig, function(match, first) {
              return match.replace(/-/g, '').toUpperCase();
            });
          }
          break;
        /**
         * 大駝峰式
         */
        case 'upper':

          input = input.replace(
            /(^[a-z]+)|[0-9]+|[A-Z][a-z]+|[A-Z]+(?=[A-Z][a-z]|[0-9])/g,
            function(match, first) {
              if (first) {
                match = match[0].toUpperCase() + match.substr(1);
              }

              return match;
            }
          );
          break;
      }

      return input;
    };
    // 物件
    var obj = function(input) {
      var result = {};

      $.each(input, function(key, val) {
        result[convert(key)] = typeof val == 'object'
                             ? convert(val)
                             : val;
      });

      delete input; // 刪除舊的

      return result;
    };

    // 檢查傳入的值是否在檢查類型中並呼叫對應的方法
    var convert = function(input) {
        var types = {
            '[object Object]': obj,
            '[object String]': str,
            '[object Number]': null,
            '[object Array]': null
        };
        var type = Object.prototype.toString.call(input);

        return input != null && types[type] ? types[type](input) : input;
    };

    camelType = camelType || 'lower';

    return convert(input);
  },

  /**
   * 取得 URL 參數
   *
   * @param     string    url          URL
   * @param     string    paramName    取得指定參數值
   *
   * @return    array
   */
  getUrlParams: function(url, paramName) {
    var regex = /([^=&?]+)=([^&#]*)/g, params = {}, parts, key, value;

    while ((parts = regex.exec(url)) != null) {
      key = parts[1], value = parts[2];
      var isArray = /\[\]$/.test(key);

      if(isArray) {
        params[key] = params[key] || [];
        params[key].push(value);
      } else {
        params[key] = value;
      }
    }

    return paramName !== undefined ? (params[paramName] || '') : params;
  }
};