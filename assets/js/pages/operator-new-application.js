import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import Swal from 'sweetalert2/src/sweetalert2.js'
import "@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css";
import FormioI18n from "../utils/FormioI18n";
import Users from "../rest/users/Users";
import Applications from "../rest/applications/Applications";

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);
const language = document.documentElement.lang.toString();

window.onload = function () {

  const applications = new Applications()
  const applicationFormContainer = $('#formio');
  const feedbackContainer = $('#feedback');
  const applicationOwner = $('#application-owner');
  if (applicationFormContainer.length) {
    Formio.icons = 'fontawesome';
    Formio.createForm(document.getElementById('formio'), applicationFormContainer.data('formserver_url') + '/form/' + applicationFormContainer.data('form_id'), {
      noAlerts: true,
      language: language,
      i18n: FormioI18n.languages(),
      buttonSettings: {
        showCancel: false
      }
    }).then(function (form) {
      form.formReady.then(() => {
        // On ready
      });

      // Recupero i dati della pratica se presenti
      if (applicationFormContainer.data('submission') !== '' && applicationFormContainer.data('submission') !== null) {
        form.submission = {
          data: applicationFormContainer.data('submission').data
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
        let submitButton = applicationFormContainer.find('.btn-wizard-nav-submit');
        submitButton.html(`<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> ${Translator.trans('salva', {}, 'messages',language)} ...`)

        let application = {};
        application.user = applicationFormContainer.data('user');
        application.service = applicationFormContainer.data('service');
        application.data = submission.data;
        application.status = 1900;

        applications.postApplication(JSON.stringify(application))
          .fail(function (xhr, type, exception) {
            submitButton.html(`${Translator.trans('salva', {}, 'messages',language)}`)
            Swal.fire(exception, '', 'error')
          })
          .done(function (data, code, xhr){
            applicationFormContainer.addClass('d-none');
            applicationOwner.addClass('d-none');
            feedbackContainer.removeClass('d-none');
          })
      });
    });
  }
};

$(document).ready(function () {
  let $input = $('#autocomplete-users')
  let $autocomplete = $('#users-list');
  let url = $input.data('url')
  const users = new Users()
  users.init();

  $input.on('input', function (e) {
    const q = $input.val()
    $autocomplete.empty()
    if (q.length === 16) {
      $('.autocomplete-icon').html('<i class="fa fa-circle-o-notch fa-spin fa-fw" aria-hidden="true"></i>')
      users.getUsers(q)
        .fail(function (xhr, type, exception) {
          console.log(xhr)
          console.log(type)
          console.log(exception)
          Swal.fire(
            'Oops...',
            'Something went wrong!',
            'error'
          );
        })
        .done(function (data, code, xhr) {

          if (data.length) {
            for (const item in data) {
              let optionText = data[item].nome + ' ' + data[item].cognome;
              let optionLabel = '<em>' + data[item].codice_fiscale + '</em>';
              let optionIcon =  '<svg class="icon icon-sm"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-user"></use></svg>';
              let optionLink = url + '?user=' + data[item].id;

              $autocomplete.addClass('autocomplete-list-show')
              let option = $(`<li>
                <a href="${optionLink}">
                  ${optionIcon}
                  <span class="autocomplete-list-text">
                    <span>${optionText}</span>
                    ${optionLabel}
                  </span>
                </a>
                </li>`
              )
              $autocomplete.append(option)
            }
          }
          $('.autocomplete-icon').html('<i class="fa fa-search" aria-hidden="true"></i>');
        });

    } else if (q.length === 0) {
      $autocomplete.removeClass('autocomplete-list-show');
    } else {
      $autocomplete.addClass('autocomplete-list-show');
      $autocomplete.html(`<li class="text-danger text-center"><span class="autocomplete-list-text">${Translator.trans('pratica.enter_correct_cf', {}, 'messages', language)}</span></li>`)
    }
  })
});
