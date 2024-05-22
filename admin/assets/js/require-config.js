require.config({
  // 避免緩存
  // "urlArgs": "bust=" + (new Date()).getTime(),
  "baseUrl": "assets/js/plugins",
  "autoLoadExtra": {
    "prefix": "controller_",
    "urlPath": "../controllers"
  },
  // 路徑或別名
  "paths": {

    "jquery": "jquery.min",
    "bootstrap": "bootstrap.min",
    "angular": "angular.min",

    "moment": "moment.min",
    "moxie": "moxie.min",
    "plupload": "plupload.min",
    "prettify": "prettify.min",
    "cropper": "cropper.min",
    "underscore": "underscore.min",
    "echarts": "echarts.min",

    "ckeditor": ["CKEditor/ckeditor"],
    "ckfinder": ["../../../includes/CKFinder/ckfinder"],

    "angular-ui-router": "AngularJS/angular-ui-router.min",
    "angular-base64": "AngularJS/angular-base64.min",

    "ocLazyLoad": "ocLazyLoad.require.min",

    "angular-config": "../angular-config",
    "angular-bootstrap": "../angular-bootstrap",
    "angular-directives": "../angular-directives",

    "du-core": "../du-admin/core",
    "list-table": "../list-table",
    "div-option": "../div-option",
    "editor": "../editor",
    "picture": "../picture",
    "selectzone": "../selectzone",
    "region": "../region",
    "common": "../common",

    "base64_decode": "PHPJS/base64_decode",
    "base64_encode": "PHPJS/base64_encode",

    "async": "RequireJS/async",
    "goog": "RequireJS/goog",
    "propertyParser": "RequireJS/propertyParser",

    "easing": "jQuery/jquery.easing.min",
    "mousewheel": "jQuery/jquery.mousewheel.min",
    "mobile": "jQuery/jquery.mobile.custom.min",
    "blockUI": "jQuery/jquery.blockUI.min",
    "serializeObject": "jQuery/jquery.serializeObject.min",

    "perfect-scrollbar": "jQuery/jquery.perfect-scrollbar.min",
    "ui": "jQuery/jquery.ui.custom.min",
    "ui.touch-punch": "jQuery/jquery.ui.touch-punch.min",
    "cookie": "jQuery/jquery.cookie.min",
    "tabs": "jQuery/jquery.tools.tabs.min",
    "selectize": "jQuery/jquery.selectize.min",
    "toastr": "jQuery/jquery.toastr.min",
    "autosize": "jQuery/jquery.autosize.min",
    "treegrid": "jQuery/jquery.treegrid.min",
    "nestable": "jQuery/jquery.nestable.min",
    "numeric": "jQuery/jquery.numeric.min",
    "minicolors": "jQuery/jquery.minicolors.min",
    "magnific-popup": "jQuery/jquery.magnific-popup.min",
    "ion.rangeSlider": "jQuery/jquery.ion.rangeSlider.min",
    "placepicker": "jQuery/jquery.placepicker.min",
    "confirm": "jQuery/jquery.confirm.min",
    "mCustomScrollbar": "jQuery/jquery.mCustomScrollbar.min",

    "bootstrap-dialog": "Bootstrap/bootstrap-dialog.min",
    "bootstrap-touchspin": "Bootstrap/bootstrap-touchspin.min",
    "bootstrap-datetimepicker": "Bootstrap/bootstrap-datetimepicker.min"
  },
  // 初始化模組
  "map" : {
    "*": {
      "css": "RequireJS/css.min"
    }
  },
  // 依賴
  "shim" : {
    "ckeditor": {
      "exports": "CKEDITOR"
    },
    "ckfinder": {
      "exports": "CKFinder"
    },
    "editor": {
      "deps": [
        "jquery",
        "ckeditor",
        "ckfinder"
      ]
    },
    "plupload": {
      "exports": "plupload"
    },
    "cropper": {
      "deps": [
        "jquery",
        "bootstrap",
        "css!../../css/cropper"
      ]
    },
    "angular": {
      "deps": [
        "underscore",
        "jquery",
        "bootstrap"
      ],
      "exports": "angular"
    },
    "angular-ui-router": {
      "deps": [
        "angular",
        "ocLazyLoad"
      ]
    },
    "angular-base64": {
      "deps": [
        "angular"
      ]
    },
    "ocLazyLoad": {
      "deps": [
        "angular"
      ]
    },
    "angular-bootstrap": {
      "deps": [
        "angular"
      ]
    },
    "angular-directives": {
      "deps": [
        "angular",
        "angular-config"
      ]
    },
    "prettify": {
      "exports": "prettify"
    },
    "underscore": {
      "exports": "_"
    },
    "bootstrap": {
      "deps": [
        "jquery"
      ]
    },
    "jquery": {
      "exports": "$"
    },
    "mobile": {
      "deps": [
        "jquery"
      ]
    },
    "blockUI": {
      "deps": [
        "jquery"
      ]
    },
    "bootstrap-dialog": {
      "deps": [
        "jquery",
        "bootstrap",
        "css!../../css/bootstrap-dialog.min"
      ]
    },
    "perfect-scrollbar": {
      "deps": [
        "jquery"
      ]
    },
    "easing": {
      "deps": [
        "jquery"
      ]
    },
    "cookie": {
      "deps": [
        "jquery"
      ]
    },
    "tabs": {
      "deps": [
        "cookie",
        "picture"
      ]
    },
    "selectize": {
      "deps": [
        "jquery",
        "ui",
        "ui.touch-punch",
        "css!../../css/selectize"
      ]
    },
    "autosize": {
      "deps": [
        "jquery"
      ]
    },
    "treegrid": {
      "deps": [
        "jquery",
        "cookie"
      ]
    },
    "nestable": {
      "deps": [
        "jquery"
      ]
    },
    "magnific-popup": {
      "deps": [
        "jquery",
        "css!../../css/magnific-popup.min"
      ]
    },
    "mCustomScrollbar": {
      "deps": [
        "jquery",
        "mousewheel",
        "css!../../css/mCustomScrollbar.min"
      ]
    },
    "ion.rangeSlider": {
      "deps": [
        "jquery"
      ]
    },
    "placepicker": {
      "deps": [
        "jquery"
      ]
    },
    "confirm": {
      "deps": [
        "jquery",
        "css!../../css/confirm.min"
      ]
    },
    "list-table": {
      "deps": [
        "du-core",
        "serializeObject"
      ]
    },
    "du-core": {
      "deps": [
        "underscore",
        "jquery",
        "bootstrap",
        "angular",
        "mobile",
        "easing",
        "perfect-scrollbar"
      ]
    },
    "common": {
      "deps": [
        "du-core"
      ]
    }
  },
  "deps": [
    "angular-bootstrap",
    "common"
  ]
});