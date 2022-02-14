import './core';
import Form from './Formio/Form';

require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
require("../css/app.scss");
require('@fortawesome/fontawesome-free/css/all.min.css')

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

});
