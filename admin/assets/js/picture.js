require(['cropper'], function() {

  var $win = $(window), $doc = $(document), $body = $('body');

  $body
    .on(
      {
        'click': function(e) {

          e.preventDefault();

          var $domEle = $(this);
          var baseUrl = $domEle.data('base-url') || '';
          var originalImageURL = '';
          var $resEle = $('[name="' + $domEle.data('edit-picture') + '"]');
          if ($resEle.val() != '') {
            originalImageURL = baseUrl + $resEle.val();
          }

          var tpl = '' +
          '<div class="row">' +
            '<div class="col-lg-9">' +
              '<div class="img-container">' +
                '<img id="image" src="' + originalImageURL + '">' +
              '</div>' +
            '</div>' +
            '<div class="col-lg-3 hidden-md hidden-sm hidden-xs">' +
              '<div class="docs-data">' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataX">X</label>' +
                  '<input type="text" class="form-control" id="dataX" placeholder="x">' +
                  '<span class="input-group-addon">px</span>' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataY">Y</label>' +
                  '<input type="text" class="form-control" id="dataY" placeholder="y">' +
                  '<span class="input-group-addon">px</span>' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataWidth">Width</label>' +
                  '<input type="text" class="form-control" id="dataWidth" placeholder="width">' +
                  '<span class="input-group-addon">px</span>' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataHeight">Height</label>' +
                  '<input type="text" class="form-control" id="dataHeight" placeholder="height">' +
                  '<span class="input-group-addon">px</span>' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataRotate">Rotate</label>' +
                  '<input type="text" class="form-control" id="dataRotate" placeholder="rotate">' +
                  '<span class="input-group-addon">deg</span>' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataScaleX">ScaleX</label>' +
                  '<input type="text" class="form-control" id="dataScaleX" placeholder="scaleX">' +
                '</div>' +
                '<div class="input-group input-group-sm">' +
                  '<label class="input-group-addon" for="dataScaleY">ScaleY</label>' +
                  '<input type="text" class="form-control" id="dataScaleY" placeholder="scaleY">' +
                '</div>' +
              '</div>' +
            '</div>' +
          '</div>' +

          '<div class="row">' +
            '<div class="col-lg-9 docs-buttons">' +
              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="setDragMode" data-option="move" title="Move">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrows-alt"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="setDragMode" data-option="crop" title="Crop">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-crop"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="zoom" data-option="0.1" title="Zoom In">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-search-plus"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="zoom" data-option="-0.1" title="Zoom Out">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-search-minus"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="rotate" data-option="-45" title="Rotate Left">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-undo-alt"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="rotate" data-option="45" title="Rotate Right">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-redo-alt"></span>' +
                  '</span>' +
                '</button>' +
              '</div>' +

              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="move" data-option="-10" data-second-option="0" title="Move Left">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrow-left"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="move" data-option="10" data-second-option="0" title="Move Right">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrow-right"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="move" data-option="0" data-second-option="-10" title="Move Up">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrow-up"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="move" data-option="0" data-second-option="10" title="Move Down">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrow-down"></span>' +
                  '</span>' +
                '</button>' +
              '</div>' +

              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="scaleX" data-option="-1" title="Flip Horizontal">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrows-alt-h"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="scaleY" data-option="-1" title="Flip Vertical">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-arrows-alt-v"></span>' +
                  '</span>' +
                '</button>' +
              '</div>' +

              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="crop" title="Crop">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-check"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="clear" title="Clear">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-times"></span>' +
                  '</span>' +
                '</button>' +
              '</div>' +

              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="disable" title="Disable">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-lock"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="enable" title="Enable">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-unlock"></span>' +
                  '</span>' +
                '</button>' +
                '<button type="button" class="btn btn-primary" data-method="reset" title="Reset">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    '<span class="fas fa-sync-alt"></span>' +
                  '</span>' +
                '</button>' +
                '<label class="btn btn-primary btn-upload" title="Upload image file">' +
                  '<input type="file" class="sr-only" name="file" accept=".jpg,.jpeg,.png,.gif,.bmp,.tiff">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="Import image with Blob URLs">' +
                    '<span class="fas fa-upload"></span>' +
                  '</span>' +
                '</label>' +
              '</div>' +

              '<div class="btn-group btn-corner d-flex docs-toggles hidden flex-nowrap" data-toggle="buttons">' +
                '<label class="btn btn-primary active">' +
                  '<input type="radio" class="sr-only" name="aspectRatio" value="1.7777777777777777">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="aspectRatio: 16 / 9">' +
                    '16:9' +
                  '</span>' +
                '</label>' +
                '<label class="btn btn-primary">' +
                  '<input type="radio" class="sr-only" name="aspectRatio" value="1.3333333333333333">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="aspectRatio: 4 / 3">' +
                    '4:3' +
                  '</span>' +
                '</label>' +
                '<label class="btn btn-primary">' +
                  '<input type="radio" class="sr-only" name="aspectRatio" value="1">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="aspectRatio: 1 / 1">' +
                    '1:1' +
                  '</span>' +
                '</label>' +
                '<label class="btn btn-primary">' +
                  '<input type="radio" class="sr-only" name="aspectRatio" value="0.6666666666666666">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="aspectRatio: 2 / 3">' +
                    '2:3' +
                  '</span>' +
                '</label>' +
                '<label class="btn btn-primary">' +
                  '<input type="radio" class="sr-only" name="aspectRatio" value="NaN">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false" title="aspectRatio: NaN">' +
                    'Free' +
                  '</span>' +
                '</label>' +
              '</div>' +

              '<div class="btn-group btn-corner">' +
                '<button type="button" class="btn btn-primary" data-method="zoomTo" data-option="1">' +
                  '<span class="docs-tooltip" data-toggle="tooltip" data-animation="false">' +
                    'Zoom to 100%' +
                  '</span>' +
                '</button>' +
              '</div>' +

            '</div>' +
          '</div>';

          DU.dialog.show({
            type: 'type-primary',
            size: 'size-wide',
            title: '圖片編輯',
            message: tpl,
            onhide: function(dialogRef) {

            },
            buttons: [{
              label: '取消',
              action: function(dialogRef) {
                return dialogRef.close();
              }
            }, {
              label: '確定',
              cssClass: ['btn', 'type-primary'.split('-')[1]].join('-'),
              action: function(dialogRef) {
                if (typeof dialogRef.getData('callback') === 'function' && dialogRef.getData('callback').call(this, true) === false) {
                  return false;
                }
                return dialogRef.close();
              }
            }],
            onshown: function(dialogRef) {

              var $modalBody = dialogRef.getModalBody();

              // Tooltip
              $modalBody.find('[data-toggle="tooltip"]').tooltip();

              var URL = window.URL || window.webkitURL;
              var $image = $('#image');

              var $dataX = $('#dataX');
              var $dataY = $('#dataY');
              var $dataHeight = $('#dataHeight');
              var $dataWidth = $('#dataWidth');
              var $dataRotate = $('#dataRotate');
              var $dataScaleX = $('#dataScaleX');
              var $dataScaleY = $('#dataScaleY');

              var def = {
                viewMode: 0, // 顯示模式 [0: 裁剪框只能在內移動,1: 裁剪框只能在圖片內移動,2: 不全部鋪滿（即縮小時可以有一邊出現空隙）,3: 全部鋪滿（即 再怎麽縮小也不會出現空隙）]
                autoCropArea: 1,
                crop: function(e) {
                  $dataX.val(Math.round(e.x));
                  $dataY.val(Math.round(e.y));
                  $dataHeight.val(Math.round(e.height));
                  $dataWidth.val(Math.round(e.width));
                  $dataRotate.val(e.rotate);
                  $dataScaleX.val(e.scaleX);
                  $dataScaleY.val(e.scaleY);
                }
              };

              var opts = null;
              try {
                 opts = $.parseJSON(($domEle.data('options') || '').replace(/'/g, '"') || null);
              } catch(e) {
                 opts = $domEle.data('options');
              }

              var settings = $.extend(def, opts);

              // Cropper
              $image.cropper(settings);

              dialogRef.setData('callback', function() {
                var canvasObj = $image.cropper('getCroppedCanvas');
                $.ajax({
                  type: 'POST',
                  url: 'common.php',
                  data: {
                    action: 'save_picture',
                    file_data_uri: canvasObj.toDataURL('image/png'),
                    is_ajax: 1
                  },
                  cache: false,
                  dataType: 'json',
                  success: function(data, textStatus) {
                    if (data.error == 0) {
                      $resEle.val(data.file_url);
                    } else {
                      //data.message
                    }
                    dialogRef.close();
                  }
                });
              });

              if (typeof document.createElement('cropper').style.transition === 'undefined') {
                $('button[data-method="rotate"]').prop('disabled', true);
                $('button[data-method="scale"]').prop('disabled', true);
              }

              // Options
              $modalBody.find('.docs-toggles').on('change', 'input', function(e) {
                var $this = $(this);
                var name = $this.attr('name');
                var type = $this.prop('type');
                var cropBoxData;
                var canvasData;
                if (!$image.data('cropper')) {
                  return;
                }
                if (type === 'checkbox') {
                  settings[name] = $this.prop('checked');
                  cropBoxData = $image.cropper('getCropBoxData');
                  canvasData = $image.cropper('getCanvasData');
                  settings.ready = function() {
                    $image.cropper('setCropBoxData', cropBoxData);
                    $image.cropper('setCanvasData', canvasData);
                  };
                } else if (type === 'radio') {
                  settings[name] = $this.val();
                }

                $image.cropper('destroy').cropper(settings);
              });

              // Methods
              $modalBody.find('.docs-buttons').on('click', '[data-method]', function() {
                var $this = $(this);
                var data = $this.data();
                var $target;
                var result;

                if ($this.prop('disabled') || $this.hasClass('disabled')) {
                  return;
                }

                if ($image.data('cropper') && data.method) {
                  data = $.extend({}, data); // Clone a new one

                  if (typeof data.target !== 'undefined') {
                    $target = $(data.target);

                    if (typeof data.option === 'undefined') {
                      try {
                        data.option = JSON.parse($target.val());
                      } catch (e) {
                        console.log(e.message);
                      }
                    }
                  }

                  if (data.method === 'rotate') {
                    $image.cropper('clear');
                  }

                  result = $image.cropper(data.method, data.option, data.secondOption);

                  if (data.method === 'rotate') {
                    $image.cropper('crop');
                  }

                  switch (data.method) {
                    case 'scaleX':
                    case 'scaleY':
                      $(this).data('option', -data.option);
                      break;
                  }

                  if ($.isPlainObject(result) && $target) {
                    try {
                      $target.val(JSON.stringify(result));
                    } catch (e) {
                      console.log(e.message);
                    }
                  }

                }
              });

              $(window).on('resize', function(e) {
                $image.cropper('reset');
              });

              // Keyboard
              $(document.body).on('keydown', function(e) {

                if (!$image.data('cropper') || this.scrollTop > 300) {
                  return;
                }

                switch (e.which) {
                  case 37:
                    e.preventDefault();
                    $image.cropper('move', -1, 0);
                    break;

                  case 38:
                    e.preventDefault();
                    $image.cropper('move', 0, -1);
                    break;

                  case 39:
                    e.preventDefault();
                    $image.cropper('move', 1, 0);
                    break;

                  case 40:
                    e.preventDefault();
                    $image.cropper('move', 0, 1);
                    break;
                }

              });

              // Import image
              var uploadedImageURL;
              var $inputImage = $modalBody.find('[type="file"]');
              if (URL) {
                $inputImage.change(function() {
                  var files = this.files;
                  var file;
                  if (!$image.data('cropper')) {
                    return;
                  }
                  if (files && files.length) {
                    file = files[0];
                    if (/^image\/\w+$/.test(file.type)) {
                      if (uploadedImageURL) {
                        URL.revokeObjectURL(uploadedImageURL);
                      }
                      uploadedImageURL = URL.createObjectURL(file);
                      $image.cropper('destroy').attr('src', uploadedImageURL).cropper(settings);
                      $inputImage.val('');
                    } else {
                      DU.dialog.alert('Please choose an image file.');
                    }
                  }
                });
              } else {
                $inputImage.prop('disabled', true).parent().addClass('disabled');
              }
            }
          });

        }
      },
      '[data-edit-picture]'
    );

});