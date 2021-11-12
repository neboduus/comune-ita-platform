import '../../css/app.scss';
import '../core';


import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import {TextEditor} from "../utils/TextEditor";

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);

window.onload = function () {
  // Application summary
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


  // Backoffice
  const backofficeFormContainer = $('#backoffice-form');
  if (backofficeFormContainer.length) {
    const saveInfo = $('.save-backoffice-info');
    const backofficeTextInfo = saveInfo.find('span');
    const backofficeFormIOI18n = {
      en: {},
      sp: {},
      it: {
        next: 'Successivo',
        previous: 'Precedente',
        cancel: 'Annulla',
        submit: 'Salva',
      }
    }
    Formio.icons = 'fontawesome';
    Formio.createForm(document.getElementById('backoffice-form'), backofficeFormContainer.data('formserver_url') + '/form/' + backofficeFormContainer.data('form_id'), {
      noAlerts: true,
      language: 'it',
      i18n: backofficeFormIOI18n,
      buttonSettings: {
        showCancel: false
      }
    }).then(function (form) {
      form.formReady.then(() => {
        // On ready
      });

      // Recupero i dati della pratica se presenti
      if (backofficeFormContainer.data('submission') !== '' && backofficeFormContainer.data('submission') !== null) {
        form.submission = {
          data: backofficeFormContainer.data('submission').data
        };
      }

      form.on('prevPage', function () {});

      $('.btn-wizard-nav-cancel').on('click', function (e) {
        e.preventDefault()
        location.reload();
      })

      form.nosubmit = true;

      // Triggered when they click the submit button.
      form.on('submit', function (submission) {
        let submitButton = backofficeFormContainer.find('.btn-wizard-nav-submit');
        submitButton.html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> Salva ...')
        axios.post(backofficeFormContainer.data('backoffice-save-url'), submission.data)
          .then(function (reponse) {
            saveInfo.removeClass('d-none');
            backofficeTextInfo.text('pochi secondi fa')
            form.emit('submitDone', submission)
          })
          .catch(function (error) {
            saveInfo.removeClass('d-none');
            backofficeTextInfo.text('si è verificato un errore durante il salvataggio')
          })
          .then(function () {
            submitButton.html('Salva')
          });
      });
    });
  }

};

$(document).ready(function () {

  $('#modal_approve').on('click', function () {
    $('#outcome_outcome_0').prop('checked', true);
    $('#modalTitle').html('Approva pratica');
    $('#email_text').show();
    if ($('#outcome_payment_amount').length > 0) {
      $('#outcome_payment_amount').closest('.form-group').removeClass('d-none');
      $('#outcome_payment_amount').attr('required', 'required');
    }
  });

  $('#modal_refuse').on('click', function () {
    $('#outcome_outcome_1').prop('checked', true);
    $('#modalTitle').html('Rigetta pratica');
    $('#email_text').hide();
    if ($('#outcome_payment_amount').length > 0) {
      $('#outcome_payment_amount').closest('.form-group').addClass('d-none');
      $('#outcome_payment_amount').removeAttr('required');
    }
  });

  $('#write-to-citizen').click(function (e) {
    e.preventDefault();
    $('#messaggi-tab').tab('show');
  })

  //Init TextArea
  TextEditor.init();

  // Tooltips
  $('[data-toggle="tooltip"]').tooltip();
});
