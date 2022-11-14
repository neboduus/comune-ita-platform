import Form from './Formio/Form';
import Payment from "./rest/payment/Payment";
import axios from "axios";
require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
const $language = document.documentElement.lang.toString();

$(document).ready(function () {

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

  if($('#step-payments').length > 0){
    Form.init('step-payments');
  }

  //Modal add form note
  if($('#modalNote').length > 0){
    const inputs = $('#textareaDescription'), submitBtn = $('#textareaSaveButton'), modalNote = $('#modalNote');
    const endpointUrl = modalNote.data('endpoint-url')

    inputs.on('input', function(e){
      let invalid = inputs.is(function(index, element){
        return !$(element).val().trim();
      });
      if(invalid){
        submitBtn.addClass("isDisabled").prop("disabled", true);
      } else {
        submitBtn.removeClass("isDisabled").prop("disabled", false);
      }
    });

    modalNote.on('show.bs.modal', function (e) {
      // Get note if exist
      axios.get(endpointUrl)
        .then(function (res) {
          if (res.data) {
            inputs.val(res.data);
          }
        })
    })

    submitBtn.on('click', (e) => {
      e.preventDefault()
      const options = {
        method: 'POST',
        data: inputs.val(),
        url: endpointUrl
      };
      axios(options).then( (res) => {
        modalNote.modal('hide')
      })
    })
  }

});
