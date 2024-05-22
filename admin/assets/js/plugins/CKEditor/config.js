/**
 * @license Copyright (c) 2003-2019, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */

CKEDITOR.editorConfig = function( config ) {

  // 斷行設定
  config.enterMode = CKEDITOR.ENTER_BR;

  config.allowedContent = true;

  // config.extraAllowedContent = '*{*}(*)';

  config.protectedSource.push(/<i[^>]*><\/i>/g); // 允許使用 i 標籤 (避免被自動轉換為 em 標籤)

  config.autoUpdateElement = true; // 當提交包含有此編輯器的表單時，是否自動更新元素內的數據

  config.toolbarCanCollapse = true; // 工具欄是否可以被收縮

  config.extraPlugins = 'placeholder'; // 額外插件

  config.toolbar_Normal = [
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo','-','Find','Replace','-','RemoveFormat'],
    ['Link','Unlink','Anchor'],
    ['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak'],
    ['Maximize','ShowBlocks','Preview','-','Source'],
    '/',
    ['Format','FontSize'],
    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['TextColor','BGColor']
  ];

  config.toolbar_Basic = [
    ['FontSize','TextColor','BGColor'],
    ['Bold','Italic','Underline','Strike'],
    ['NumberedList','BulletedList'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['Source']
  ];

  config.toolbar_Template = [
    ['Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo','-','Find','Replace','-','RemoveFormat'],
    ['Link','Unlink','Anchor'],
    ['Image','Flash','Table','HorizontalRule','SpecialChar','PageBreak'],
    ['Maximize','ShowBlocks','-','CreatePlaceholder','Preview','-','Source'],
    '/',
    ['Format','FontSize'],
    ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
    ['NumberedList','BulletedList','-','Outdent','Indent','Blockquote','CreateDiv'],
    ['JustifyLeft','JustifyCenter','JustifyRight'],
    ['TextColor','BGColor']
  ];

};

CKEDITOR.on('instanceReady', function (ev) {
  ev.editor.dataProcessor.htmlFilter.addRules( {
    elements : {
      img: function( el ) {
        // Add bootstrap "img-responsive" class to each inserted image
        // el.addClass('img-responsive');

        // Remove inline "height" and "width" styles and
        // replace them with their attribute counterparts.
        // This ensures that the 'img-responsive' class works
        var style = el.attributes.style;

        if (style) {
          // Get the width from the style.
          var match = /(?:^|\s)width\s*:\s*(\d+)px/i.exec(style),
            width = match && match[1];

          // Get the height from the style.
          match = /(?:^|\s)height\s*:\s*(\d+)px/i.exec(style);
          var height = match && match[1];

          // Replace the width
          if (width) {
            el.attributes.style = el.attributes.style.replace(/(?:^|\s)width\s*:\s*(\d+)px;?/i, '');
            el.attributes.width = width;
          }

          // Replace the height
          if (height) {
            el.attributes.style = el.attributes.style.replace(/(?:^|\s)height\s*:\s*(\d+)px;?/i, '');
            el.attributes.height = height;
          }
        }

        // Remove the style tag if it is empty
        if (!el.attributes.style)
            delete el.attributes.style;
      }
    }
  });
});
