import '../../css/app.scss';
import '../core';


import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import {TextEditor} from "../utils/TextEditor";
import {Formio} from "formiojs";
import 'formiojs/dist/formio.form.min.css';

require('@fortawesome/fontawesome-free/css/all.min.css')

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);

window.onload = function () {
  Formio.createForm(document.getElementById('formio_summary'), $('#formio_summary').data('formserver_url') + '/printable/' + $('#formio_summary').data('form_id'), {
    readOnly: true,
    noAlerts: true,
    language: 'it',
    i18n: formIoI18n
  }).then(function (form) {
    form.submission = {
      data: $('#formio_summary').data('submission')
    };
  });
};

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

});
