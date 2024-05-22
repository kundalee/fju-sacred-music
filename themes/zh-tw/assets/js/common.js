$(document).ready(function(e) {

  var $win = $(window), $body = $('body');
  var $base = $('base');

  var baseUrl = $base.attr('href');
  var themeUrl = baseUrl + $base.data('theme');

  $body.addClass('is-document-loaded');

  $body
    .on('click', 'a[href="#"]', function(e) {
      e.preventDefault();
    });

  var $navbarSwitch = $('#global-header-wrapper .navbar-switch');
  $navbarSwitch.on('click', function(e) {
    e.preventDefault();
    if ($body.hasClass('navbar-open') == false) {
      $body.addClass('navbar-open');
    } else {
      $body.removeClass('navbar-open');
    }
  });

  var $allSubmenu = $('#global-header-wrapper .nav-item > .submenu');
  $('#global-header-wrapper .nav-item > a.nav-link').on('click', function(e) {
    var $domEle = $(this).closest('.nav-item');
    var $subEle = $domEle.find('.submenu');
    if ($navbarSwitch.is(':visible') == true && $subEle.length > 0) {
      e.preventDefault();
      if ($subEle.is(':visible') == false) {
        $allSubmenu.hide();;
        $subEle.stop().fadeIn(200);
      } else {
        $subEle.stop().fadeOut(200);
      }
    }
  });

  require(['parallax']);

  $('[data-img-src]').each(function() {
    var $cEle = $(this);
    $cEle.css('background-image', 'url(' + $cEle.data('img-src') + ')');
  });

  $('[data-bg-src]').each(function() {
    var $cEle = $(this);
    $cEle.css('background-image', 'url(' + $cEle.data('bg-src') + ')');
  });

  if ($('.img-lazy').length) {

    if (typeof Object.assign != 'function') {
      Object.assign = function (target, varArgs) { // .length of function is 2
        'use strict';
        if (target == null) { // TypeError if undefined or null
          throw new TypeError('Cannot convert undefined or null to object');
        }

        var to = Object(target);

        for (var index = 1; index < arguments.length; index++) {
          var nextSource = arguments[index];

          if (nextSource != null) { // Skip over if undefined or null
            for (var nextKey in nextSource) {
              // Avoid bugs when hasOwnProperty is shadowed
              if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                to[nextKey] = nextSource[nextKey];
              }
            }
          }
        }
        return to;
      };
    }

    require(['lozad'], function(lozad) {
      const observer = lozad('.img-lazy');
      observer.observe();
    });

  }



  require([
    'ScrollMagic',
    'ScrollMagic.debug',
    'ScrollMagic.gsap',
    'easing'
  ],
  function(ScrollMagic) {

    var controller = new ScrollMagic.Controller({});

    new ScrollMagic
      .Scene({
        triggerHook: 'onLeave',
        offset: 140
      })
      .on('start', function(event) {
        $('#global-header-wrapper').toggleClass('scroll-view', (event.scrollDirection == 'FORWARD' || event.scrollDirection == 'PAUSED'));
      })
      // .addIndicators()
      .addTo(controller);

    controller.scrollTo(function(newScrollPos, callback) {
      $('html, body').animate({'scrollTop': newScrollPos}, 1000, 'easeInOutExpo', callback);
    });

    $('#global-fixed-wrapper .go-top').on('click', function(e) {
      e.preventDefault();
      controller.scrollTo(0);
    });

    $('[data-go-position]').on('click', function(e) {
      e.preventDefault();
      controller.scrollTo($($(this).data('go-position')).offset().top - 60);
    });

    if ($('[data-js-effect="show-page"]').length) {

      $('[data-js-effect="show-page"] [data-go-page]').on('click', function(e) {
        e.preventDefault();

        var $domEle = $(this);
        var $showPage = $('[data-show-page="' + $domEle.data('go-page') + '"]');
        $showPage.removeClass('d-none');
        $('[data-show-page]').not($showPage).addClass('d-none');
        $domEle.closest('.page-item').addClass('active').siblings().removeClass('active');
        controller.scrollTo(($('#recorded-songs').offset().top - $('#global-header-wrapper').outerHeight(true) - 25));

      });

    }

  });

  $('form[name="search-form"]').on('submit', function(e) {
    if ($.trim($('[name="q"]', this).val()) == '') {
      e.preventDefault();
      DU.dialog.alert('請輸入關鍵字搜尋');
    }
  });

  $('form[name="advanced-form"]').on('submit', function(e) {
    $('input[type="text"],select').each(function() {
      var $domEle = $(this);
      if ($.trim($domEle.val()) == '') {
        $domEle.prop('disabled', true);
      }
    });
  });

  if ($('.img-lazy').length) {

    if (typeof Object.assign != 'function') {
      Object.assign = function (target, varArgs) { // .length of function is 2
        'use strict';
        if (target == null) { // TypeError if undefined or null
          throw new TypeError('Cannot convert undefined or null to object');
        }

        var to = Object(target);

        for (var index = 1; index < arguments.length; index++) {
          var nextSource = arguments[index];

          if (nextSource != null) { // Skip over if undefined or null
            for (var nextKey in nextSource) {
              // Avoid bugs when hasOwnProperty is shadowed
              if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                to[nextKey] = nextSource[nextKey];
              }
            }
          }
        }
        return to;
      };
    }

    require(['lozad'], function(lozad) {
      const observer = lozad('.img-lazy');
      observer.observe();
    });
  }

});