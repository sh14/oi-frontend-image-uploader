(function ($) {
  function get_ratio (width, height) {
    let ratio = 1

    // если ширина меньше высоты
    if (width < height) {

      // пропорции - большая сторона делится на меньшую
      ratio = height / width
    } else {
      ratio = width / height
    }

    return ratio
  }

  function get_image_info (width, height) {
    width    = parseInt(width)
    height   = parseInt(height)
    let info = {
      'width': width,
      'height': height,
      'ratio': get_ratio(width, height),
    }
    info.min = height
    if (info.min > width) {
      info.min = width
    }
    info.orient = 'portrait'
    if (height < width) {
      info.orient = 'album'
    } else if (height === width) {
      info.orient = 'square'
    }

    return info
  }

  function cl (data, label, clear) {
    if (undefined !== clear) {
      console.clear()
      return
    }

    if (undefined !== label && undefined !== data) {
      console.log(label, data)
    } else {
      if (undefined !== data) {
        console.log(data)
      }
    }
  }

  function set_fileapi_image (selector) {

    let what         = $(selector).attr('data-what')
    let image_width  = $(selector).attr('data-width')
    let image_height = $(selector).attr('data-height')
    let max_side     = $(selector).attr('data-max_side')
    let post_id      = $(selector).attr('data-post_id')

    $('body').append(
      '<div class="popup popup-' + what + '" style="display: none;">' +
      '<div class="popup__body">' +
      '<div class="js-img"></div>' +
      '</div>' +
      '<div style="margin: 0 0 5px; text-align: center;">' +
      '<div class="js-upload btn btn_browse btn_browse_small">OK</div>' +
      '</div>' +
      '</div>'
    )

    $(selector).fileapi({
      url: oifrontend_object.ajax_url,
      accept: 'image/*',
      imageSize: { minWidth: image_width, minHeight: image_height },
      data: {
        action: 'oi_image_uploader',
        data: {
          width: image_width,
          height: image_height,
          user_meta: 'user_' + what,
          user_id: oifrontend_object.user_id,
          post_id: post_id,
          type: what
        }
      },
      //debug: true,
      media: false,
      elements: {
        active: { show: '.js-upload', hide: '.js-browse' },
        progress: '.js-progress'
      },
      onSelect: function (evt, ui) {

        let file = ui.files[0]
        if (file === undefined) {
          /*window.swal( {
            type : 'error',
            title : 'Файл не подходит',
            text : "Выбранный файл не подходит! Вероятно он слишком мал.",
            showCancelButton : false
          } );*/
          $('.popup-' + what).modal({
            closeOnEsc: true,
            closeOnOverlayClick: false,
            onOpen: function (overlay) {
              $('.js-img', overlay).append('Выбранный файл не подходит! Вероятно он слишком мал.')
              // при клике на ОК
              $(overlay).on('click', '.js-upload', function () {

                // закрывается окно кропера
                jQuery.modal().close()

              })
            }
          }).open()
        }

        if (!FileAPI.support.transform) {
          alert('В вашем браузере не установлен Flash :(')
        } else if (file) {
          $('.popup-' + what).modal({
            closeOnEsc: true,
            closeOnOverlayClick: false,
            onOpen: function (overlay) {
              // overlay - это модальное окно с картинкой для кропера

              // при клике на ОК
              $(overlay).on('click', '.js-upload', function () {

                // закрывается окно кропера
                jQuery.modal().close()
                $(this).remove()

                // файл загружается
                $(selector).fileapi('upload')

              })

              let image_data = {
                width: 1,
                height: 1,
              }
              FileAPI.getInfo(file, function (err/**String*/, info/**Object*/) {

                if (err) {
                  image_data = info
                }
              })

              // определение оригинальных размеров
              let original = get_image_info(image_data.width, image_data.height)

              // определение минимальных размеров изображения
              let min = get_image_info(image_width, image_height)

              // определение максимальных размеров изображения
              let max

              // опеределение окна для кропа(пропорционально уменьшенное изображение)
              let editor_window

              // определение размеров рамки обрезки
              // let select

              // определение данных окна браузера
              let win = get_image_info($(window).width(), $(window).height())

              // высота минимального размера изображения меньше его ширины
              if ('album' === min.orient) {
                let ratio = max_side / image_width

                // определение максимальных размеров
                max = get_image_info(max_side, image_height * ratio)
              } else {
                let ratio = max_side / image_height

                // определение максимальных размеров
                max = get_image_info(image_width * ratio, max_side)
              }

              cl(original, 'original')
              cl(min, 'min')
              cl(max, 'max')
              cl(win, 'win')

              // высота окна меньше его ширины
              // if ( 'album' === win.orient ) {
              //
              // 	// высота минимального изображения меньше его ширины
              // 	if ( 'album' === min.orient ) {
              // 		ratio         = min.height / win.height;
              // 		editor_window = [
              // 			Math.ceil( win.height / original.ratio ),
              // 			win.height,
              // 		];
              // 	}else{
              // 		ratio         = min.height / win.height;
              // 		editor_window = [
              // 			Math.ceil( win.height / original.ratio ),
              // 			win.height,
              // 		];
              // 	}
              //
              // } else {
              // 	if ( 'album' === min.orient ) {
              // 		ratio         = min.width / win.width;
              // 		editor_window = [
              // 			win.width,
              // 			Math.ceil( min.height / ratio ),
              // 		];
              // 	}
              // }
              if ('album' === win.orient) {

                // окно меньше картинки
                if (win.height < max.height) {
                  editor_window = [
                    win.height,
                    win.height,
                  ]
                } else {
                  editor_window = [
                    max.height,
                    max.height,
                  ]
                }
              } else {
                if (win.width < max.width) {
                  editor_window = [
                    win.width,
                    win.width,
                  ]
                } else {
                  editor_window = [
                    max.width,
                    max.width,
                  ]
                }
              }
              editor_window = get_image_info(editor_window[0], editor_window[1])

              // if ('album' === min.orient) {
              //   select = [
              //     editor_window.width,
              //     editor_window.width / min.ratio,
              //   ]
              // } else {
              //   select = [
              //     editor_window.height / min.ratio,
              //     editor_window.height,
              //   ]
              // }
              // select = get_image_info(select[0], select[1])

              $('.js-img', overlay).cropper({
                file: file,
                // цвет заливки области, не входящей в результирующую картинку
                bgColor: '#fff',
                // начальные координаты и размер выбранной обоасти
                setSelect: [0, 0, 300, 300],
                // минимальный размер изображения на холсте
                minSize: [300, 300],
                // максимальный размер изображения на холсте
                maxSize: [editor_window.width, editor_window.height],
                // пропорции изображения
                aspectRatio: min.ratio,
                // максимальный размер выбранной области от размера изображения
                selection: '100%',
                onSelect: function (coords) {

                  // приведение размеров к целым значениям
                  coords.w = Math.floor(coords.w)
                  coords.h = Math.floor(coords.h)

                  // cl( original, 'original' );
                  // cl( min, 'min' );
                  // cl( max, 'max' );
                  // cl( win, 'win' );
                  // cl( editor_window, 'editor_window' );
                  // cl( select, 'select' );
                  // cl( select.width, 'select.width' );
                  // cl( select.height, 'select.height' );
                  // cl( file, 'file' );
                  // cl( overlay, 'overlay' );
                  // cl( coords, 'coords' );

                  $(selector).fileapi('crop', file, coords)

                  FileAPI
                    .Image(file)
                    .crop(coords.x, coords.y, coords.w, coords.h)
                    .get(function (err, file) {

                      FileAPI
                        .Image(file)
                        .preview(coords.w, coords.h)
                        .get(function (err, file) {

                          let dataURL = file.toDataURL()
                          $(selector)
                            .find('.js-preview')
                            .css({ backgroundImage: 'url(' + dataURL + ')' })
                        })
                    })
                }
              })
            }
          }).open()
        }
      }
    })
  }

  $('.js-upload-image').on('click', function () {
    set_fileapi_image(this)
  })
})(jQuery)
