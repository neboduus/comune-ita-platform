import './core';
import Form from './Formio/Form';
import Payment from "./Payment/Payment";

require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
require("../css/app.scss");
const $language = document.documentElement.lang.toString();

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
      $('#confirm .modal-body').html(Translator.trans('pratica.conferma_invio_pratica', {}, 'messages', $language));
      $('#confirm').modal({backdrop: 'static', keyboard: false})
        .one('click', '#ok', function () {
          $form.trigger('submit'); // submit the form
        });
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

  // Init formIo
  if ($('#formio').length > 0) {
    Form.init('formio');
  }

  if( $('form[name="pratica_payment_gateway"]').length > 0){
    Payment.init();
  }
});
