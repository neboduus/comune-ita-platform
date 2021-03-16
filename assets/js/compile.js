/*require('bootstrap-italia');
require('../css/app.scss');

require('webpack-jquery-ui');
require('webpack-jquery-ui/css');
require('webpack-jquery-ui/datepicker');*/

require('bootstrap');
require('bootstrap-select');
require('svgxuse');
require('bootstrap-italia/src/js/plugins/polyfills/array.from');
require('bootstrap-italia/src/js/plugins/circular-loader/CircularLoader-v1.3');
require('bootstrap-italia/src/js/plugins/password-strength-meter/password-strength-meter');
//require('bootstrap-italia/src/js/plugins/datepicker/locales/it');
//require('bootstrap-italia/src/js/plugins/datepicker/datepicker');
require('bootstrap-italia/src/js/plugins/i-sticky/i-sticky');
require('bootstrap-italia/src/js/plugins/sticky-header');
require('bootstrap-italia/src/js/plugins/sticky-wrapper');
require('bootstrap-italia/src/js/plugins/ie');
require('bootstrap-italia/src/js/plugins/fonts-loader');
require('bootstrap-italia/src/js/plugins/autocomplete');
require('bootstrap-italia/src/js/plugins/back-to-top');
require('bootstrap-italia/src/js/plugins/componente-base');
require('bootstrap-italia/src/js/plugins/cookiebar');
require('bootstrap-italia/src/js/plugins/dropdown');
//require('bootstrap-italia/src/js/plugins/forms');
require('bootstrap-italia/src/js/plugins/track-focus');
require('bootstrap-italia/src/js/plugins/forward');
require('bootstrap-italia/src/js/plugins/navbar');
require('bootstrap-italia/src/js/plugins/navscroll');
require('bootstrap-italia/src/js/plugins/history-back');
require('bootstrap-italia/src/js/plugins/notifications');
require('bootstrap-italia/src/js/plugins/upload');
require('bootstrap-italia/src/js/plugins/progress-donut');
require('bootstrap-italia/src/js/plugins/list');
require('bootstrap-italia/src/js/plugins/imgresponsive');
require('bootstrap-italia/src/js/plugins/timepicker');
require('bootstrap-italia/src/js/plugins/input-number');
//require('bootstrap-italia/src/js/plugins/carousel');
require('bootstrap-italia/src/js/plugins/transfer');
require('bootstrap-italia/src/js/plugins/select');
//require('bootstrap-italia/src/js/plugins/custom-select');
require('bootstrap-italia/src/js/plugins/rating');
require('bootstrap-italia/src/js/plugins/dimmer');


require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
require("summernote");
require("summernote/dist/summernote-bs4.css");
require("../css/app.scss");


import Calendar from './Calendar';
import PageBreak from './PageBreak';
import FinancialReport from "./FinancialReport";
import {Formio} from "formiojs";
import 'formiojs/dist/formio.form.min.css'
require('@fortawesome/fontawesome-free/css/all.min.css')
//overwrite fortawesome 5 - form.io
require("../js/Formio/overwrite/iconClass");

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);


$(document).ready(function () {
    $('.summernote').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol']],
        ['insert', ['link']],
      ]
    });


  if ($('#pratica_summary_flow_formIO_step').length) {
    $('button.craue_formflow_button_class_next').on('click', function (e) {
      var $form = $(this).closest('form');
      e.preventDefault();
      $('#confirm .modal-body').html("Sei sicuro di voler procedere con l'invio della pratica?");
      $('#confirm').modal({backdrop: 'static', keyboard: false})
        .one('click', '#ok', function () {
          $form.trigger('submit'); // submit the form
        });
      /*if ( $("input[name='pratica_select_payment_gateway[payment_type]']:checked").data('identifier') == 'mypay' ) {
        $('#confirm .modal-body').html('Proseguendo la pratica non sarà più modificabile e verrà inviata all\'Ente non appena sarà ultimato il pagamento');
        $('#confirm').modal({backdrop: 'static', keyboard: false})
          .one('click', '#ok', function () {
            $form.trigger('submit'); // submit the form
          });
      } else {
        $form.trigger('submit'); // submit the form
      }*/
    });
  }


  $('button.craue_formflow_button_last').on('click', function (e) {
    var $form = $(this).closest('form');
    e.preventDefault();
    $('#confirm').modal({backdrop: 'static', keyboard: false})
    .one('click', '#ok', function () {
      $form.trigger('submit'); // submit the form
    });
  });
});
