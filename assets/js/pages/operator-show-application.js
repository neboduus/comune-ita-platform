import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import 'formiojs';
import '../../styles/vendor/_formio.scss';
import '../../styles/components/_messages.scss';
import {TextEditor} from "../utils/TextEditor";
import moment from "moment";
import RequestIntegration from "../utils/RequestIntegration";
import InfoPayment from "../rest/payment/InfoPayment";
import ApplicationsMessage from "../rest/applications/Message";
import Form from '../Formio/Form';
import Users from "../rest/users/Users";
import Swal from 'sweetalert2/src/sweetalert2.js'

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);
const language = document.documentElement.lang.toString();

const users = new Users()
users.init();

// Todo: spostare in ./assets/js/Formio/Formio.js
window.onload = function () {
  // Init formIo
  if ($('#formio_summary').length > 0) {
    Form.init('formio_summary');
  }

  // Backoffice
  const backofficeFormContainer = $('#backoffice-form');
  if (backofficeFormContainer.length) {
    const saveInfo = $('.save-backoffice-info');
    const backofficeTextInfo = saveInfo.find('span');
    const backofficeFormIOI18n = {
      en: {},
      de: {},
      it: {
        next: `${Translator.trans('following', {}, 'messages', language)}`,
        previous: `${Translator.trans('previous', {}, 'messages', language)}`,
        cancel: `${Translator.trans('annulla', {}, 'messages', language)}`,
        submit: `${Translator.trans('salva', {}, 'messages', language)}`,
      }
    }
    Formio.icons = 'fontawesome';
    Formio.createForm(document.getElementById('backoffice-form'), backofficeFormContainer.data('formserver_url') + '/form/' + backofficeFormContainer.data('form_id'), {
      noAlerts: true,
      language: language,
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

      form.on('prevPage', function () {
      });

      $('.btn-wizard-nav-cancel').on('click', function (e) {
        e.preventDefault()
        location.reload();
      })

      form.nosubmit = true;

      // Triggered when they click the submit button.
      form.on('submit', function (submission) {
        let submitButton = backofficeFormContainer.find('.btn-wizard-nav-submit');
        submitButton.html(`<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>${Translator.trans('salva', {}, 'messages', language)}`)
        axios.post(backofficeFormContainer.data('backoffice-save-url'), submission.data)
          .then(function (response) {
            saveInfo.removeClass('d-none');
            backofficeTextInfo.text(`${Translator.trans('time.few_seconds_ago', {}, 'messages', language)}`)
            form.emit('submitDone', submission)
          })
          .catch(function (error) {
            saveInfo.removeClass('d-none');
            backofficeTextInfo.text(`${Translator.trans('servizio.error_from_save', {}, 'messages', language)}`)
          })
          .then(function () {
            submitButton.html(`${Translator.trans('salva', {}, 'messages', language)}`)
          });
      });
    });
  }
};

$(document).ready(function () {
  const userGroupInput = $('#user_group');
  const operatorInput = $('#operator');
  const assignBtn = $('#assign_operator_btn');

  function initUserGroupsSelect() {
    users.getUserGroups()
      .fail(function (xhr, type, exception) {
        Swal.fire(
          `${Translator.trans('error_message_detail', {}, 'messages', language)}`,
          `${Translator.trans('operatori.error_get_users_groups', {}, 'messages', language)}`,
          'error'
        );
      })
      .done((data) => {
        let emptyOption = $(`<option disabled selected value>${Translator.trans('operatori.select_user_group', {}, 'messages', language)}</option>`);
        userGroupInput.append(emptyOption);
        data.forEach((item) => {
          let option = $(`<option value="${item.id}">${item.name}</option>`);
          userGroupInput.append(option);
        })
        userGroupInput.trigger('change');
      })
  }

  initUserGroupsSelect();
  function initFilteredOperatorsSelect() {
    operatorInput.attr("disabled", "disabled");
    operatorInput.empty();

    if (userGroupInput.val()) {
      users.getFilteredOperators(userGroupInput.val())
        .fail(function (xhr, type, exception) {
          Swal.fire(
            `${Translator.trans('error_message_detail', {}, 'messages', language)}`,
            `${Translator.trans('operatori.error_get_operators', {}, 'messages', language)}`,
            'error'
          );
        })
        .done((data) => {
          let emptyOption = $(`<option disabled selected value>${Translator.trans('operatori.select_operator', {}, 'messages', language)}</option>`);
          operatorInput.append(emptyOption);
          data.forEach((item) => {
            let option = $(`<option value="${item.id}">${item.full_name}</option>`);
            operatorInput.append(option);
          })

          operatorInput.removeClass("d-none");
          operatorInput.removeAttr("disabled");
        })
    }
  }

  userGroupInput.on('change', () => {
    if (userGroupInput.val()) {
      assignBtn.removeAttr("disabled");
    }
    initFilteredOperatorsSelect();
  });

  $('.edit-meeting').on('click', function editMeeting(e) {
    let el = $(e.target)
    let payload = {}
    if (el.data('status')) {
      payload['status'] = el.data('status');
    }

    if (el.data('expiration') && el.data('extend-seconds')) {
      let currentExpiration = moment(el.data('expiration'));
      let extendSeconds = parseInt(el.data('extend-seconds'));
      let newExpiration = currentExpiration.add(extendSeconds, 's');
      payload['draft_expiration'] = newExpiration.format()
    }

    if ($.isEmptyObject(payload)) {
      return;
    }

    let errorEl = el.closest('div').find('.update_error');
    errorEl.addClass('d-none');

    $.ajax({
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${$('#hidden').data('token')}`
      },
      url: el.data('url'),
      type: 'PATCH',
      data: JSON.stringify(payload),
      success: function (response, textStatus, jqXhr) {
        location.reload();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        errorEl.removeClass('d-none');
      }
    });
  });

  $('#modal_approve').on('click', function () {
    $('#outcome_outcome_0').prop('checked', true);
    $('#modalTitle').html(`${Translator.trans('pratica.approved_pratice', {}, 'messages', language)}`);
    $('#email_text').show();
    if ($('#outcome_payment_amount').length > 0) {
      $('#outcome_payment_amount').closest('.form-group').removeClass('d-none');
      $('#outcome_payment_amount').attr('required', 'required');
    }
  });

  $('#modal_refuse').on('click', function () {
    $('#outcome_outcome_1').prop('checked', true);
    $('#modalTitle').html(`${Translator.trans('pratica.reject_pratice', {}, 'messages', language)}`);
    $('#email_text').hide();
    if ($('#outcome_payment_amount').length > 0) {
      $('#outcome_payment_amount').closest('.form-group').addClass('d-none');
      $('#outcome_payment_amount').removeAttr('required');
    }
  });

  RequestIntegration.init()

  //Init TextArea
  TextEditor.init();

  // Tooltips
  $('[data-toggle="tooltip"]').tooltip();

  // Init Details Payment
  if ($('.payment-list').length > 0) {
    InfoPayment.init();
  }

  //Operator Message
  if ($('#change_paid_modal').length > 0) {
    ApplicationsMessage.init();
  }

});
