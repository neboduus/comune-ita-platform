import './core'
require("../css/app.scss");

$(document).ready(function($) {
  if (location.hostname !== 'stanzadelcittadino.it') {
    $('body').append('<div style="position: fixed; top: 38px; padding: 5px 0; right: -50px; left: auto; width: 200px; transform: rotate(45deg); -webkit-transform: rotate(45deg); background-color: #e43; color: #fff; text-align: center; z-index:9999">Sito di sviluppo</div>');
    $('body').css('overflow-x', 'hidden');
  }


    $('.select_tabs').on('change', function (e) {
      // With $(this).val(), you can **(and have to!)** specify the target in your <option> values.
      //$('#the-tab li a').eq($(this).val()).tab('show');
      // If you do not care about the sorting, you can work with $(this).index().
      // $('#the-tab li a').eq($(this).index()).tab('show');
      //$('.nav-tabs li a').eq($(this).val()).tab('show');
      $('.nav-tabs li a[href="'+ $(this).val() +'"]').tab('show');
    });

});
