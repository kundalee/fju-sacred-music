define([
  'angular',
  'angular-config',
  'angular-directives',
],
function(angular) {
  'use strict';
  angular.element(document).ready(function() {
    angular.bootstrap(document, ['du-admin']);
  });
});