import './core'
require("../css/app.scss");

// On ready page
$(function () {

    $('.select_tabs').on('change', function (e) {
      // With $(this).val(), you can **(and have to!)** specify the target in your <option> values.
      //$('#the-tab li a').eq($(this).val()).tab('show');
      // If you do not care about the sorting, you can work with $(this).index().
      // $('#the-tab li a').eq($(this).index()).tab('show');
      //$('.nav-tabs li a').eq($(this).val()).tab('show');
      $('.nav-tabs li a[href="' + $(this).val() + '"]').tab('show');
    });

    $('[data-toggle="popover"]').popover();

})




