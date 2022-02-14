import '../../css/app.scss';
import '../core';
import {TextEditor} from "../utils/TextEditor";
import Form from '../Formio/Form';


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
        alert('Attenzione! non puoi inviare un messaggio vuoto.');
        return false;
      }
      return confirm("Sei sicuro di voler procedere? Se decidi di continuare verrà inviata un email all\'operatore che ha in carico la tua pratica");
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

});
