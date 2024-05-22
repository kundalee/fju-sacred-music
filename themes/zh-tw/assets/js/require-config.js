require.config({
  // "urlArgs": "bust=" + (new Date()).getTime(),
  "baseUrl": "themes/zh-tw/assets/js/plugins",
  // 路徑或別名
  "paths": {

    "jquery": "jquery.min",
    "bootstrap": "bootstrap.bundle.min",

    "common": "../common",
    "du-core": "../du-core",

    "bridget": "jQuery/jquery.bridget",
    "easing": "jQuery/jquery.easing.min",
    "mousewheel": "jQuery/jquery.mousewheel.min",
    "blockUI": "jQuery/jquery.blockUI.min",
    "confirm": "jQuery/jquery.confirm.min",

    "photoswipe": "photoswipe.min",
    "photoswipe-ui-default": "photoswipe-ui-default.min",

    "TweenLite": "GSAP/TweenLite.min",
    "TweenMax": "GSAP/TweenMax.min",
    "TimelineLite": "GSAP/TimelineLite.min",
    "TimelineMax": "GSAP/TimelineMax.min",

    "ScrollMagic": "ScrollMagic.min",
    "ScrollMagic.debug": "ScrollMagic/debug.addIndicators.min",
    "ScrollMagic.gsap": "ScrollMagic/animation.gsap.min",

    "simpleParallax": "simpleParallax.min",
    "lozad": "lozad.min",
    "swiper": "swiper.min",

    "numeric": "jQuery/jquery.numeric.min",
    "parallax": "jQuery/jquery.parallax.min",
    "mCustomScrollbar": "jQuery/jquery.mCustomScrollbar.min",
    "rating-stars": "jQuery/jquery.rating-stars.min",

    "imagesLoaded": "jQuery/jquery.imagesLoaded.min",
    "masonry": "jQuery/jquery.masonry.min",

    "bootstrap-touchspin": "Bootstrap/bootstrap-touchspin.min",

    "async": "RequireJS/async",
    "font": "RequireJS/font",
    "goog": "RequireJS/goog",
    "propertyParser": "RequireJS/propertyParser",
    "image": "RequireJS/image",
    "json": "RequireJS/json",
    "noext": "RequireJS/noext",
    "mdown": "RequireJS/mdown"
  },
  // 初始化模組
  "map" : {
      "*": {
        "domReady": "RequireJS/domReady",
        "css": "RequireJS/css.min"
      }
  },
  // 依賴
  "shim" : {
    "bootstrap": {
      "deps": [
        "jquery"
      ]
    },
    "common": {
      "deps": [
        "jquery",
        "bootstrap",
        "du-core"
      ]
    },
    "ScrollMagic.debug": {
      "deps": [
        "ScrollMagic"
      ]
    },
    "ScrollMagic.gsap": {
      "deps": [
        "ScrollMagic"
      ]
    },

    "swiper": {
      "deps": [
        "jquery"
      ]
    },

    "parallax": {
      "deps": [
        "jquery"
      ]
    },
    "parollerjs": {
      "deps": [
        "jquery"
      ]
    },
    "photoswipe": {
      "deps": [
      ]
    },
    "mCustomScrollbar": {
      "deps": [
        "jquery",
        "mousewheel"
      ]
    },
    "imagesLoaded": {
      "deps": [
        "jquery"
      ]
    },
    "masonry": {
      "exports": "Masonry",
      "deps": [
        "jquery",
        "bridget",
        "imagesLoaded"
      ]
    }
  },
  "deps": [
    "common"
  ]
});