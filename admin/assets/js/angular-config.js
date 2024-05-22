define([
  'angular-ui-router',
  'base64_decode'
],
function() {

  var $win = $(window), $body = $('body');

  // 防止中風
  $win.on('mousewheel DOMMouseScroll', function(e) {
    $('html, body').stop();
  });

  angular

    .module('du-admin', ['ui.router', 'oc.lazyLoad'])

    .config(function($urlRouterProvider, $locationProvider, $httpProvider, $qProvider) {

      $qProvider.errorOnUnhandledRejections(false);

      $httpProvider.defaults.headers.common = {'Request-Type': 'ajax'};

      if ($body.filter('[data-state-router-url]').length) {

        // Default url
        $urlRouterProvider.otherwise('/dashboards');

        $locationProvider.hashPrefix('!');
      }

    })

    .config(function($stateProvider, $urlMatcherFactoryProvider, $ocLazyLoadProvider) {

      $urlMatcherFactoryProvider.type('level-rule', {
        encode: function(val) {
          return val.toString().replace(/\_/g, '-');
        },
        decode: function(val) {
          return val.toString().replace(/\-/g, '_');
        },
        is: function(val) {
          return this.decode(val.toString()) === val;;
        },
        pattern: /[^/]*/
      });

      $ocLazyLoadProvider.config({
        jsLoader: requirejs
      });

      var getTemplateParams = function($stateParams, $http, fileName) {
        var toParams = $stateParams;
        try {
          if ($stateParams.query) {
            toParams = base64_decode($stateParams.query);
            toParams = angular.fromJson(toParams);
            $.extend(toParams, $stateParams);
          }
        } catch(e) {
          toParams = $stateParams;
        }
        delete toParams.query;

        toParams = _.omit(toParams, function(value, key, object) {
          return (value === null || value === undefined || angular.equals({}, value));
        });

        return $http
          .get(fileName, {
            params: toParams,
            headers: {
              Accept: 'text/html'
            }
          })
          .then(function(response) {
            return response.data;
          }, function(response) {

          });
      };

      $stateProvider
        .state('dashboards', {
          url: '/dashboards',
          params: {
            action: 'dashboards'
          },
          templateProvider: getTemplateParams,
          resolve: {
            fileName: function() {
              return '';
            }
          }
        })
        .state('clear_cache', {
          url: '/clear-cache',
          params: {
            action: 'clear_cache'
          },
          templateProvider: getTemplateParams,
          resolve: {
            fileName: function() {
              return '';
            }
          }
        });

      var navConfig = $body.data('state-router-url');
      if (navConfig !== undefined) {
        $.each(navConfig, function(index, value) {
          $stateProvider.state(value, {
            reloadOnSearch: false,
            url: '/' + (value.replace(/_/g, '-')) + '-{action:level-rule}?&id&query',
            templateProvider: getTemplateParams,
            resolve: {
              fileName: function() {
                return value + '.php';
              },
              loadPlugin: function($ocLazyLoad) {
                return $ocLazyLoad.load('controller_' + value);
              }
            },
            onEnter: function($state) {

            },
            onExit: function() {

            }
          });
        });
      }
    })

    .run(function($rootScope, $state, $interval, $transitions, $urlRouter) {

      $rootScope.$state = $state;

      $transitions.onStart({}, function(transition) {
        if (window.Pace && typeof window.Pace.restart === 'function') {
          window.Pace.restart();
        }
        if ($rootScope.messageAutoTime !== undefined) {
          $interval.cancel($rootScope.messageAutoTime);
        }
      });

      $transitions.onSuccess({}, function(transition) {
        $body.triggerHandler('setup.DU');
      });

      $rootScope.$on('$viewContentAnimationEnded', function() {

        require(['easing'], function() {
          $('html, body').stop().animate({
            scrollTop: 0
          }, 600, 'easeInOutExpo', function() {

          });
        });

      });

    })

    .controller('DashboardsCtrl', function() {

      require(['echarts'], function(echarts) {

        $('[data-chart-option]').css({height: '300px'}).on('load-chart', function(e) {

          // 初始化echarts實例
          var chart = echarts.init(this);
          // 使用制定的配置項和數據顯示圖表
          chart.setOption($(this).data('chart-option'));
          $(window).on('resize chart-resize', function(e) {
            chart.resize();
          });

        }).trigger('load-chart');

      });
    })

    .controller('UISettingsCtrl', function() {

      var setTheme = function(themeName) {

        var currentTheme = $('.px-stylesheet-theme').attr('href').replace(
          /^(.*?)([^\/\.]+)((?:\.rtl)?(?:\.min)?\.css(?:\?.*))/,
          function(match, path, name, suffix) {
            return name;
          }
        );

        setSidebarState('disabled');

        function setSidebarState(state) {
          $('#px-sidebar input').prop('disabled', state === 'disabled');
          $('#px-sidebar-loader')[currentTheme.indexOf('dark') === -1 ? 'removeClass': 'addClass']('form-loading-inverted');
          $('#px-sidebar-loader')[state === 'disabled' ? 'show': 'hide']();
        }

        function createStylesheetLink(href, className, cb) {

          var head = document.getElementsByTagName('head')[0];
          var link = document.createElement('link');

          link.className = className;
          link.type = 'text/css';
          link.rel = 'stylesheet';
          link.href = href;

          var done = false;

          link.onload = link.onreadystatechange = function() {

            if (!done && (!this.readyState || this.readyState === 'complete')) {
              done = true;

              var links = document.getElementsByClassName(className);

              if (links.length > 1) {
                for (var i = 1, l = links.length; i < l; i++) {
                  head.removeChild(links[i]);
                }
              }

              document.documentElement.className =
                document.documentElement.className.replace(/\s*px-no-transition/, '');
            }

            if (cb) {
              cb();
            }
          };

          document.documentElement.className += ' px-no-transition';

          return link;
        }

        var _isDark = themeName.indexOf('dark') !== -1;
        var themePath = 'assets/css/themes/' + themeName + '.min.css';

        var linksToLoad = [];
        var _assetCls = [
          'px-stylesheet-bs',
          'px-stylesheet-core',
          'px-stylesheet-widgets'
        ];

        $.each(_assetCls, function(i, v) {
          linksToLoad.push(
            [
              $('.' + v).attr('href').replace(
                /^(.*?)([^\/\.]+)((?:\.rtl)?(?:\.min)?\.css(?:\?.*))/,
                function(match, path, name, suffix) {
                  return path + name.replace('-dark', '') + (_isDark ? '-dark' : '') + suffix;
                }
              ),
              v
            ]
          );
        });

        linksToLoad.push([themePath, 'px-stylesheet-theme']);

        var linksContainer = document.createDocumentFragment();
        var loadedLinks = 0;

        function _cb() {

          loadedLinks++;
          if (loadedLinks < linksToLoad.length) {
            return;
          }

          setSidebarState('enabled');
        }

        for (var i = 0, l = linksToLoad.length; i < l; i++) {
          linksContainer.appendChild(
            createStylesheetLink(linksToLoad[i][0], linksToLoad[i][1], _cb));
        }

        document.getElementsByTagName('head')[0].insertBefore(
          linksContainer,
          document.getElementsByClassName('px-stylesheet-bs')[0]
        );
      };

      var $win = $(window);

      require(['cookie'], function() {

        $.cookie.raw = true;

        $('.fixed-navbar-toggler').on('change', function(e) {
          $('input.fixed-navbar-toggler').prop('checked', this.checked);
          $('body').toggleClass('px-navbar-fixed', this.checked);
          $.cookie('UI[fixed_navbar]', this.checked ? 1 : 0);
        });

        $('.fixed-nav-toggler').on('change', function(e) {
          $('input.fixed-nav-toggler').prop('checked', this.checked);
          $('body > .px-nav').toggleClass('px-nav-fixed', this.checked);
          $win.trigger('scroll');
          $.cookie('UI[fixed_nav]', this.checked ? 1 : 0);
        });

        $('.nav-right-toggler').on('change', function(e) {
          $('input.nav-right-toggler').prop('checked', this.checked);
          var $navEl  = $('body > .px-nav');
          if (this.checked) {
            $navEl.addClass('px-nav-right').removeClass('px-nav-left');
          } else {
            $navEl.addClass('px-nav-left').removeClass('px-nav-right');
          }
          $.cookie('UI[right_nav]', this.checked ? 1 : 0);
        });

        $('.nav-off-canvas-toggler').on('change', function(e) {
          $('input.nav-off-canvas-toggler').prop('checked', this.checked);
          var $navEl  = $('body > .px-nav');
          $navEl.toggleClass('px-nav-off-canvas', this.checked);
          $win.trigger('resize');
          $.cookie('UI[off_canvas_nav]', this.checked ? 1 : 0);
        });

        $('.px-themes-toggler').on('change', function() {
          var val = $(this).filter(':checked').val();
          $('.px-themes-toggler[type="radio"][value="' + val + '"]').prop('checked', this.checked);
          setTheme(val);
          $.cookie('UI[theme]', val);
        });

      });

    })

    .controller('MessageCtrl', function($rootScope, $interval) {

      var $redirect = $('#redirection-msg');
      if ($redirect.length) {

        var $messageUrl = $('#msg-url li a');
        var $defaultEle = $messageUrl.eq(0);
        var _defaultUrl = $defaultEle.attr('href');
        if (!/javascript:history.go(-1)/.test(_defaultUrl) && window.history.length == 0) {
          $redirect.text('');
        }

        var seconds = 3;
        $rootScope.messageAutoTime = $interval(function() {
          seconds--;
          $('#msg-seconds').text(seconds);
          if (seconds == 0) {
            if ($defaultEle.is('[data-ui-sref]')) {
              $defaultEle.trigger('click');
            } else {
              window.location.href = _defaultUrl;
            }
          }
        }, 1000, 3);

        $messageUrl.on('click', function(e) {
          $interval.cancel($rootScope.messageAutoTime);
        });
      }
    })

    .controller('SafeLoginCtrl', function() {

      var $theForm = $('form[name="the-form"]');
      $('[name="password"]', $theForm).trigger('focus');

      /* 檢查表單輸入的內容 */
      $theForm.on('submit', function(e) {

        e.preventDefault();

        var errArr = [];
        if ($('[name="password"]', this).val() == '') {
          errArr.push('安全密碼不能為空!');
        }
        if (errArr.length > 0) {
          DU.dialog.alert(errArr);
          return false;
        }

        $.ajax({
          type: 'POST',
          url: $theForm.attr('action'),
          data: $theForm.serialize(),
          cache: false,
          beforeSend: DU.ajax.beforeSend,
          complete: DU.ajax.complete,
          error: DU.ajax.error,
          success: function(data, textStatus) {
            var tplHtml = angular.element(data);
            angular.element('[ui-view]').html(tplHtml);
            angular.element(document).injector().invoke(function($compile) {
              var scope = angular.element(tplHtml).scope();
              $compile(tplHtml)(scope);
              scope.$digest();
            });
          }
        });

      }).trigger('reset');

    })

    .controller('PassportCtrl', function() {

      require(['cookie'], function() {

        var $win = $(window);

        $('#px-sidebar').pxSidebar();

        $('.px-themes-toggler').on('change', function() {
          var themeVal = $(this).filter(':checked').val();
          setTheme(themeVal);
          $.cookie('UI[theme]', themeVal);
        });

        $('#page-signin-forgot-link').on('click', function(e) {
          e.preventDefault();

          $('#page-signin-form, #page-signin-social')
            .css({opacity: 1})
            .animate({ opacity: 0}, 200, function() {
              $(this).hide();

              $('#page-forgot-form')
                .css({opacity: 0, display: 'block'})
                .animate({ opacity: 1}, 200)
                .find('.form-control').first().focus();

              $win.trigger('resize');
            });
        });

        $('#page-signin-forgot-back').on('click', function(e) {
          e.preventDefault();

          $('#page-forgot-form')
            .animate({ opacity: 0}, 200, function() {
              $(this).css({ display: 'none'});

              $('#page-signin-form, #page-signin-social')
                .show()
                .animate({opacity: 1}, 200)
                .find('.form-control').first().focus();

              $win.trigger('resize');
            });
        });

        /* 處理驗證碼輸入框的按鍵事件，將所有輸入的內容轉換為大寫 */
        $('[name="captcha"]').on('keyup', function(e) {
          $(this).val($(this).val().toUpperCase());
        });

        $('#refresh').on('click', function(e) {
          e.preventDefault();
          $('#captcha-img').attr('src', './?action=captcha&' + Math.random());
          $('[name="captcha"]').val('');
        });

        $('#page-signin-form').on('submit', function(e) {
          var errArr = [];
          var username = $('[name="username"]', this).val();
          var password = $('input[name="password"]', this).val();
          var captcha = $('[name="captcha"]', this).val();
          if (username == '' && password == '') {
            errArr.push('請輸入帳號及密碼！');
          } else if (username == '') {
            errArr.push('請輸入帳號！');
          } else if (password == '') {
            errArr.push('請輸入密碼！');
          } else if (captcha == '') {
            errArr.push('請輸入驗證碼！');
          }

          if (errArr.length > 0) {
            DU.dialog.alert(errArr);
            return false;
          }
        });

        $('#page-forgot-form').on('submit', function(e) {
          var $theForm = $(this);
          var username = $('[name="username"]', this).val();
          var eMail = $('[name="email"]', this).val();
          var errArr = [];
          if (username == '' && eMail == '') {
            errArr.push('請輸入帳號及電子郵件！');
          } else if (username == '') {
            errArr.push('請輸入帳號！');
          } else if (eMail == '') {
            errArr.push('請輸入您的電子郵件!');
          } else if (!/([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)/.test(eMail)) {
            errArr.push('您的電子郵件格式不正確!');
          }
          if (errArr.length > 0) {
            DU.dialog.alert(errArr);
          } else {

            $.ajax({
              type: 'POST',
              url: $theForm.attr('action'),
              cache: false,
              data: $theForm.serialize(),
              dataType: 'json',
              beforeSend: DU.ajax.beforeSend,
              complete: DU.ajax.complete,
              error: DU.ajax.error,
              success: function(data, textStatus) {
                DU.dialog.alert(data.message);
                if (data.error == 0) {
                  $theForm.trigger('reset');
                }
              }
            });

          }
          return false;
        });

        $('#page-password-form').on('submit', function(e) {
          var errArr = [];
          var newPassword = $('[name="new_password"]', this).val();
          var confirmPassword = $('[name="confirm_password"]', this).val();
          if (newPassword == '') {
            errArr.push('請輸入您的登入密碼！');
          } else if (confirmPassword == '') {
            errArr.push('請輸入您的確認密碼！');
          } else if (newPassword != '' && confirmPassword != '' && newPassword != confirmPassword) {
            errArr.push('您兩次輸入的密碼不一致！');
          }

          if (errArr.length > 0) {
            DU.dialog.alert(errArr);
            return false;
          }
        });

      });
    });
});