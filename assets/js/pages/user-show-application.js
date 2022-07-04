import '../../css/app.scss';
import '../core';
import {TextEditor} from "../utils/TextEditor";
import Form from '../Formio/Form';
import InfoPayment from "../Payment/InfoPayment";
const lang = document.documentElement.lang.toString();


$(document).ready(function () {

  if ($('#answer-integration').length > 0) {
    $('#answer-integration').click(function (e) {
      e.preventDefault();
      $('#messaggi-tab').trigger('click');
      $('#messaggi-tab').on('shown.bs.tab', function (e){
        $('html, body').animate({ scrollTop: $('form').offset().top }, 800);
      });
    })
  }

  if ($('#message_applicant').length > 0) {
    $('#message_applicant').click(function (e) {
      //e.preventDefault();
      if ( $('.summernote').summernote('isEmpty') ) {
        alert(Translator.trans('pratica.messaggio_operatore_vuoto', {}, 'messages', lang));
        return false;
      }
      return confirm(Translator.trans('pratica.messaggio_operatore', {}, 'messages', lang));
    })
  }

  //Init TextArea
  TextEditor.init();

  // Tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Init formIo
  if ($('#formio_summary').length > 0) {
    Form.init('formio_summary');
  }

  // Init Details Payment
  if( $('.payment-list').length > 0){
    InfoPayment.init();
  }

});
