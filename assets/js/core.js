import "../styles/core.scss";

require("jquery");
// TODO remove summernote
require("summernote");
require("summernote/dist/summernote-bs4.css");
require("bootstrap");
require("svgxuse");
require("bootstrap-italia/src/js/plugins/polyfills/array.from");
require("bootstrap-italia/src/js/plugins/circular-loader/CircularLoader-v1.3");
require("bootstrap-italia/src/js/plugins/password-strength-meter/password-strength-meter");
//require('bootstrap-italia/src/js/plugins/datepicker/locales/it');
//require('bootstrap-italia/src/js/plugins/datepicker/datepicker');
require("bootstrap-italia/src/js/plugins/i-sticky/i-sticky");
require("bootstrap-italia/src/js/plugins/sticky-header");
require("bootstrap-italia/src/js/plugins/sticky-wrapper");
require("bootstrap-italia/src/js/plugins/ie");
//require('bootstrap-italia/src/js/plugins/fonts-loader');
require("bootstrap-italia/src/js/plugins/autocomplete");
require("bootstrap-italia/src/js/plugins/back-to-top");
require("bootstrap-italia/src/js/plugins/componente-base");
require("bootstrap-italia/src/js/plugins/content-watcher");
// require('bootstrap-italia/src/js/plugins/cookiebar');
require("bootstrap-italia/src/js/plugins/dropdown");
require("bootstrap-italia/src/js/plugins/collapse");
require("bootstrap-italia/src/js/plugins/forms");
require("bootstrap-italia/src/js/plugins/class-watcher");
require("bootstrap-italia/src/js/plugins/track-focus");
require("bootstrap-italia/src/js/plugins/forward");
require("bootstrap-italia/src/js/plugins/navbar");
require("bootstrap-italia/src/js/plugins/navscroll");
require("bootstrap-italia/src/js/plugins/history-back");
require("bootstrap-italia/src/js/plugins/notifications");
require("bootstrap-italia/src/js/plugins/upload");
require("bootstrap-italia/src/js/plugins/progress-donut");
require("bootstrap-italia/src/js/plugins/list");
require("bootstrap-italia/src/js/plugins/imgresponsive");
require("bootstrap-italia/src/js/plugins/timepicker");
require("bootstrap-italia/src/js/plugins/input-number");
//require('bootstrap-italia/src/js/plugins/carousel');
require("bootstrap-italia/src/js/plugins/transfer");
//require("bootstrap-italia/src/js/plugins/select");
//require("bootstrap-italia/src/js/plugins/custom-select");
require("bootstrap-italia/src/js/plugins/rating");
require("bootstrap-italia/src/js/plugins/dimmer");
require("bootstrap-italia/src/js/plugins/side-menu");
//require("bootstrap-italia/src/js/plugins/version");

import "./utils/Search";

// On ready page
$(function () {
  $(".select_tabs").on("change", function (e) {
    // With $(this).val(), you can **(and have to!)** specify the target in your <option> values.
    //$('#the-tab li a').eq($(this).val()).tab('show');
    // If you do not care about the sorting, you can work with $(this).index().
    // $('#the-tab li a').eq($(this).index()).tab('show');
    //$('.nav-tabs li a').eq($(this).val()).tab('show');
    $('.nav-tabs li a[href="' + $(this).val() + '"]').tab("show");
  });

  $('[data-toggle="popover"]').popover();
});
