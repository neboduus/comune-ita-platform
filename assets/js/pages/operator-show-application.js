import '../../css/app.scss';
import '../core';
import '../utils/TextEditor';


import Calendar from '../Calendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';

Formio.registerComponent('calendar', Calendar);
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

    let delay = 3;
    form.formReady.then(() => {
      const disableFileLink = function () {
        if (delay === 0) {
          $('.formio-component-file a').each(function () {
            $(this).parent().html($(this).html());
          });
        } else {
          delay--;
          setTimeout(disableFileLink, 500);
        }
      };
      disableFileLink();
    });
  });
};

$(document).ready(function () {

  $('#modal_approve').on('click', function () {
    $('#outcome_outcome_0').prop('checked', true);
    $('#modalTitle').html('Approva pratica');
    $('#email_text').show();
  });

  $('#modal_refuse').on('click', function () {
    $('#outcome_outcome_1').prop('checked', true);
    $('#modalTitle').html('Rigetta pratica');
    $('#email_text').hide();
  });

  $('#write-to-citizen').click(function (e) {
    e.preventDefault();
    $('#messaggi-tab').tab('show');
  })
});
