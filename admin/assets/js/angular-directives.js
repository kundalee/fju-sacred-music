define([
  'angular'
],
function() {

  return angular
    .module('du-admin')
    .directive(
      'duNav',
      [
        '$parse',
        '$timeout',
        function($parse, $timeout) {

          var AVAILABLE_OPTIONS = ['accordion', 'transitionDuration', 'dropdownCloseDelay', 'enableTooltips', 'animate', 'storeState', 'storagePrefix', 'modes'];

          var EVENTS = ['onExpand', 'onExpanded', 'onCollapse', 'onCollapsed', 'onDestroy', 'onDropdownOpen', 'onDropdownOpened', 'onDropdownClose', 'onDropdownClosed', 'onDropdownFrozen', 'onDropdownUnfrozen'];

          function getEventName(key) {
            return key.replace(/^on([A-Z])/, function(_m_, $1) {
              return $1.toLowerCase();
            }).replace(/[A-Z]/g, function($m) {
              return '-' + $m.toLowerCase();
            });
          }

          return {
            restrict: 'E',
            transclude: true,
            replace: true,
            template: '<nav class="px-nav"></nav>',
            compile: function($element, $attrs) {
              var callbackName = 'ngSidebarContentLoaded_' + pxUtil.generateUniqueId();

              $element.append($attrs.templateUrl ? '<div ng-include="' + $attrs.templateUrl + '" onload="' + callbackName + '()"></div>' : '<div ng-transclude></div>');

              function link($scope) {
                var setInstancePointer = $attrs.instance ? $parse($attrs.instance).assign : angular.noop;
                var options = {};

                AVAILABLE_OPTIONS.forEach(function(optName) {
                  if (typeof $attrs[optName] === 'undefined') {
                    return;
                  }
                  options[optName] = $parse($attrs[optName])($scope);
                });

                function initPxNav() {
                  $element.pxNav(options);

                  // Set events
                  EVENTS.forEach(function(event) {
                    if (!$attrs[event]) {
                      return;
                    }
                    $element.on(getEventName(event) + '.px.nav', $parse($attrs[event])($scope));
                  });

                  // Readonly variable
                  setInstancePointer($scope, $.fn.pxNav.bind($element));

                  $element.on('$destroy', function() {
                    return $element.off().pxNav('destroy');
                  });
                }

                // Initialize immediately if no template url provided
                if (!$attrs.templateUrl) {

                  return $timeout(initPxNav);
                }

                // Else initialize after content loaded
                $scope[callbackName] = function() {
                  delete $scope[callbackName];
                  initPxNav();
                };
              }

              return {pre: link};
            }
          };
        }
      ]
    )
    .directive(
      'duNavbar',
      [
        '$parse',
        '$timeout',
        function($parse, $timeout) {

          return {
            restrict: 'E',
            transclude: true,
            replace: true,
            template: '<nav class="navbar px-navbar"></nav>',

            compile: function($element, $attrs) {
              var callbackName = 'ngNavbarContentLoaded_' + pxUtil.generateUniqueId();

              if ($attrs.templateUrl) {
                $element.append($('<div ng-include="' + $attrs.templateUrl + '" onload="' + callbackName + '()"></div>'));
              } else {
                $element.append('<div ng-transclude></div>');
              }

              function link($scope) {
                var setInstancePointer = $attrs.instance ? $parse($attrs.instance).assign : angular.noop;

                function initPxNavbar() {
                  $element.pxNavbar();

                  // Readonly variable
                  setInstancePointer($scope, $.fn.pxNavbar.bind($element));

                  $element.on('$destroy', function() {
                    return $element.pxNavbar('destroy');
                  });
                }

                // Initialize immediately if no template url provided
                if (!$attrs.templateUrl) {
                  return $timeout(initPxNavbar);
                }

                // Else initialize after content loaded
                $scope[callbackName] = function() {
                  delete $scope[callbackName];
                  initPxNavbar();
                };
              }

              return {pre: link};
            }
          };
        }
      ]
    )
    .directive(
      'duFooter',
      [
        '$rootScope',
        '$parse',
        '$timeout',
        function($rootScope, $parse, $timeout) {
          return {
            restrict: 'E',
            transclude: true,
            replace: true,
            template: '<footer class="px-footer"></footer>',
            compile: function($element, $attrs) {
              var callbackName = 'ngFooterContentLoaded_' + pxUtil.generateUniqueId();

              if ($attrs.templateUrl) {
                $element.append($('<div ng-include="' + $attrs.templateUrl + '" onload="' + callbackName + '()"></div>'));
              } else {
                $element.append('<div ng-transclude></div>');
              }

              function link($scope) {
                var setInstancePointer = $attrs.instance ? $parse($attrs.instance).assign : angular.noop;

                function initPxFooter() {
                  $element.pxFooter();

                  // Readonly variable
                  setInstancePointer($scope, $.fn.pxFooter.bind($element));

                  // Update footer position on page change
                  var contentLoadedHandler = $rootScope.$on('$viewContentLoaded', function() {
                    return $element.pxFooter('update');
                  });

                  $element.on('$destroy', function () {
                    $element.pxFooter('destroy');
                    contentLoadedHandler();
                  });
                }

                // Initialize immediately if no template url provided
                if (!$attrs.templateUrl) {
                  return $timeout(initPxFooter);
                }

                // Else initialize after content loaded
                $scope[callbackName] = function() {
                  delete $scope[callbackName];
                  initPxFooter();
                };
              }

              return {pre: link};
            }
          };
        }
      ]
    )
    .directive(
      'assignQueryInfo',
      [
        '$rootScope',
        function($rootScope) {
          return {
            restrict: 'A',
            compile: function(_element_, _attrs_) {

              $rootScope.query_info = _attrs_.assignQueryInfo;
              angular
                .element('#' + _attrs_.$attr.assignQueryInfo)
                .text(_attrs_.assignQueryInfo);
            }
          };
        }
      ]
    )
    .directive(
      'assignGzipEnabled',
      [
        '$rootScope',
        function($rootScope) {
          return {
            restrict: 'A',
            compile: function(_element_, _attrs_) {

              $rootScope.gzip_enabled = _attrs_.assignGzipEnabled;
              angular
                .element('#' + _attrs_.$attr.assignGzipEnabled)
                .text(_attrs_.assignGzipEnabled);
            }
          };
        }
      ]
    )
    .directive(
      'assignMemoryInfo',
      [
        '$rootScope',
        function($rootScope) {
          return {
            restrict: 'A',
            compile: function(_element_, _attrs_) {

              $rootScope.memory_info = _attrs_.assignMemoryInfo;
              angular
                .element('#' + _attrs_.$attr.assignMemoryInfo)
                .text(_attrs_.assignMemoryInfo);
            }
          };
        }
      ]
    );
});