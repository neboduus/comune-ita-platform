import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import axios from "axios";

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);
const language = document.documentElement.lang.toString();

import wizardNav from "./templates/wizardNav/index.js";
import wizardHeader from "./templates/wizardHeader/index.js";

// Overwrite nav buttons formio
Formio.Templates.current = {
  wizardNav:{
    form: (ctx) => wizardNav(ctx)
  },
  wizardHeader: {
    form: (ctx) => wizardHeader(ctx)
  }
}


class Form {

  submissionForm = null

  static createStepsMobile() {
    $(".info-progress-wrapper[data-loop!='first']").each(function(idx) {
      $( this ).attr('data-progress', idx+1);
    });

        const step = ($('.step-active').data('progress') ? $('.step-active').data('progress') : '1') + '/' + ($('.info-progress-wrapper').length - 1)
        const stepLabel = $('.step-active span').text();

        $('.step').html(step)
        $('.step-label').html(stepLabel)
  }

  static initEditableAnonymous(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');

    $.getJSON(formUrl + '/i18n?lang=' + $container.data('locale'), function (data) {

      let customErrorContainer = $('#formio-custom-errors');
      // Nascondo input symfony, trovare modo di fare submit di formio da esterno
      $('.craue_formflow_buttons').addClass('d-none');

      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById('formio'), $('#formio').data('formserver_url') + '/form/' + $('#formio_render_form_id').val(), {
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data,
        buttonSettings: {showCancel: false},
        hooks: {
          beforeCancel: () => Form.handleBeforeSubmit(event)
        },
        sanitizeConfig: {
          allowedAttrs: ['ref', 'src', 'url', 'data-oembed-url'],
          allowedTags: ['oembed','svg','use'],
          addTags: ['oembed','svg','use'],
          addAttr: ['url', 'data-oembed-url']
        }
      })
        .then(function (form) {

          form.formReady.then(() => {
            setTimeout(Form.disableBreadcrumbButton, 500);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.submissionForm = form
            Form.initDraftButton()
          })

          if (form.hasOwnProperty('wizard')) {
            $('.craue_formflow_current_step.active').addClass('wizard');
          }

          let dataContainer = $('#formio_render_dematerialized_forms');
          // Recupero i dati della pratica se presenti
          if (dataContainer.val()) {
            form.submission = {
              data: JSON.parse(dataContainer.val()).data
            };
          }

          form.on('nextPage', function () {
            document.getElementById("formio").scrollIntoView();
            setTimeout(Form.disableBreadcrumbButton, 500);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.saveDraft(form)
            Form.initDraftButton()
          });

          form.on('prevPage', function () {
            setTimeout(Form.disableBreadcrumbButton, 500);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.initDraftButton()
          });

          $('.btn-wizard-nav-cancel').on('click', function (e) {
            e.preventDefault()
            location.reload();
          })

          let realSubmitButton = $('.craue_formflow_button_class_next');
          form.nosubmit = true;
          // Triggered when they click the submit button.
          form.on('submit', function (submission) {
            let submitButton = $('#formio button');
            submitButton.hide();
            $(`<a href="#" id="loading-button" class="btn btn-secondary"><i class="fa fa-refresh fa-spin"></i>${Translator.trans('waiting', {}, 'messages', language)}...</a>`).insertAfter(submitButton.last());
            customErrorContainer.empty().hide();
            axios.post($container.data('form_validate'), JSON.stringify(submission.data))
              .then(function (reponse) {
                customErrorContainer.empty();
                let submitErrors = null;
                if (reponse.data.errors) {
                  reponse.data.errors.forEach((error) => {
                    customErrorContainer.append('<p class="m-0">' + error.toString() + '</p>');
                  });
                  customErrorContainer.show();
                  $('#formio #loading-button').remove();
                  submitButton.show();
                } else {
                  form.emit('submitDone', submission)
                  let data = $('form[name="formio_render"]').serialize();
                  dataContainer.val(JSON.stringify(submission.data));
                  realSubmitButton.trigger('click');
                }
              });
          });
        });
      Form.autoCloseAlert(customErrorContainer);
    });
  }

  static initEditable(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');

    $.getJSON(formUrl + '/i18n?lang=' + $container.data('locale'), function (data) {

      let customErrorContainer = $('#formio-custom-errors');
      // Nascondo input symfony, trovare modo di fare submit di formio da esterno
      $('.craue_formflow_buttons').addClass('d-none');

      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById(containerId), formUrl, {
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data,
        buttonSettings: {showCancel: false},
        hooks: {
          beforeCancel: () => Form.handleBeforeSubmit(event)
        },
        sanitizeConfig: {
          allowedAttrs: ['ref', 'src', 'url', 'data-oembed-url'],
          allowedTags: ['oembed','svg','use'],
          addTags: ['oembed','svg','use'],
          addAttr: ['url', 'data-oembed-url']
        }
      }).then(function (form) {

        form.formReady.then(() => {
          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          Form.createStepsMobile();
          Form.submissionForm = form
          Form.initDraftButton()
        });

        if (form.hasOwnProperty('wizard')) {
          $('.craue_formflow_current_step.active').addClass('wizard');
        }

        let dataContainer = $('#formio_render_dematerialized_forms');
        // Recupero i dati della pratica se presenti
        if (dataContainer.val()) {
          form.submission = {
            data: JSON.parse(dataContainer.val()).data
          };
        }



        form.on('nextPage', function (e) {
          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          setTimeout(Form.checkWizardNavCancelButton, 500);
          document.getElementById("formio").scrollIntoView();
          Form.createStepsMobile()
          Form.saveDraft()
          Form.initDraftButton()
        });

        form.on('prevPage', function () {
          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          setTimeout(Form.checkWizardNavCancelButton, 500);
          Form.createStepsMobile()
          Form.initDraftButton()
        });

        let realSubmitButton = $('.craue_formflow_button_class_next');
        form.nosubmit = true;

        // Triggered when they click the submit button.
        form.on('submit', function (submission) {
          let submitButton = $('#formio button');
          submitButton.hide();
          $(`<a href="#" id="loading-button" class="btn btn-secondary"><i class="fas fa-sync fa-spin"></i>${Translator.trans('waiting', {}, 'messages', language)}...</a>`).insertAfter(submitButton.last());
          customErrorContainer.empty().hide();
          axios.post($container.data('form_validate'), JSON.stringify(submission.data))
            .then(function (reponse) {
              customErrorContainer.empty();
              let submitErrors = null;
              if (reponse.data.errors) {
                reponse.data.errors.forEach((error) => {
                  customErrorContainer.append('<p class="m-0">' + error.toString() + '</p>');
                });
                customErrorContainer.show();
                $('#formio #loading-button').remove();
                submitButton.show();
              } else {
                form.emit('submitDone', submission)
                let data = $('form[name="formio_render"]').serialize();
                dataContainer.val(JSON.stringify(submission.data));
                realSubmitButton.trigger('click');
              }
            });
        });
      });
      Form.autoCloseAlert(customErrorContainer);

      //Funzione per rendere il form Applicant readOnly
      const disableApplicant = function () {
        $('.formio-component-applicant input').each(function (k) {
          if ($(this).closest(".formio-component-address").length <= 0) {
            if ($(this).prop("type") === "radio") {
              let name = $(this).prop('name');
              if ($(this).prop("checked")) {
                $("input[name='" + name + "']").attr('disabled', 'disabled');
              }
            } else if ($(this).val()) {
              $(this).attr('disabled', 'disabled');
            }
          }
        });
      }

    });

  }

  static autoCloseAlert(customErrorContainer) {
    if (customErrorContainer && customErrorContainer.length > 0) {
      customErrorContainer.each(function () {
        var time_period = customErrorContainer.attr('auto-close');
        setTimeout(function () {
          customErrorContainer.empty().hide();
        }, time_period);
      });
    }
  }

  static initPrintable(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/printable/' + $container.data('form_id');
    $.getJSON($container.data('formserver_url') + '/form/' + $container.data('form_id') + '/i18n?lang=' + $container.data('locale'), function (data) {
      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById(containerId), formUrl, {
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data,
        readOnly: true,
        buttonSettings: {showCancel: false},
        hooks: {
          beforeCancel: () => Form.handleBeforeSubmit(event)
        }
        //renderMode: 'html'
      })
        .then(function (form) {
          // Recupero i dati della pratica se presenti
          if ($('#formio_render_dematerialized_forms').val() != '') {
            form.submission = {
              data: JSON.parse($('#formio_render_dematerialized_forms').val()).data
            };
          }
        });
    });
  }

  static initPreview(containerId) {

    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');
    $.getJSON(formUrl + '/i18n?lang=' + $container.data('locale'), function (data) {
      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById(containerId), formUrl, {
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data,
        readOnly: false,
        buttonSettings: {showCancel: false},
        hooks: {
          beforeCancel: () => Form.handleBeforeSubmit(event)
        }
        //renderMode: 'html'
      }).then(function (form) {
        form.formReady.then(() => {
          setTimeout(Form.disableBreadcrumbButton, 500);
          setTimeout(Form.checkWizardNavCancelButton, 500);
        });
      });
    });
  }

  static initSummary(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');
    const printableFormUrl = $container.data('formserver_url') + '/printable/' + $container.data('form_id');

    $.getJSON(formUrl + '/i18n?lang=' + $container.data('locale'), function (data) {
      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById(containerId), printableFormUrl, {
        readOnly: true,
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data,
        sanitizeConfig: {
          allowedAttrs: ['ref', 'src', 'url', 'data-oembed-url','svg'],
          allowedTags: [ 'oembed','svg'],
          addTags: ['oembed','svg'],
          addAttr: ['url', 'data-oembed-url']
        }
      }).then(function (form) {
        form.submission = {
          data: $container.data('submission')
        };
        form.formReady.then(() => {
          Form.getStoredSteps()
          Form.createStepsMobile()
        })
      });
    });
  }


  static init(containerId) {

    // Init form editable anonymous
    if ($('#' + containerId + '.editable-anonymous').length > 0) {
      this.initEditableAnonymous(containerId);
    }

    // Init form editable
    if ($('#' + containerId + '.editable').length > 0) {
      this.initEditable(containerId);
    }

    // Init form printable
    if ($('#' + containerId + '.printable').length > 0) {
      this.initPrintable(containerId);
    }

    // Init form preview
    if ($('#' + containerId + '.preview').length > 0) {
      this.initPreview(containerId);
    }

    // Init form summary
    if ($('#' + containerId + '.formio-summary').length > 0) {
      this.initSummary(containerId);
    }
  }

  //Funzione per disabilitare i pulsanti Breadcrumb per il form wizard
  static disableBreadcrumbButton() {
    const $breadcrumb = $('button.page-link');
    if ($breadcrumb) {
      $('.pagination li').css('cursor', 'default')
      $breadcrumb.css('cursor', 'default')
      $breadcrumb.attr('disabled', true)
    }
  }

  //Funzione per aggiungere l'attributo type=button al pulsante "Annulla" se è visibile
  static checkWizardNavCancelButton() {
    if ($('.btn-wizard-nav-cancel').length > 0) {
      $('.btn-wizard-nav-cancel').attr('type', 'button')
    }
  }

  // Refresh page on handle "cancel button"
  static handleBeforeSubmit() {
    if (confirm(`${Translator.trans('pratica.you_want_cancel', {}, 'messages', language)}`)) {
      document.location.reload()
    }
  }

  static saveDraft(){
    const draftButton = $('#save-draft');
    const draftInfo = $('.save-draft-info');
    const draftTextInfo = draftInfo.find('span');
        let text = draftButton.html();
        draftButton.html(`<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>${Translator.trans('save_processing', {}, 'messages', language)}`)
          axios.post($('#formio').data('save-draft-url'), Form.submissionForm.data)
          .then(function (response) {
            draftTextInfo.html(`<i class="fa fa-clock-o" aria-hidden="true"></i> ${Translator.trans('buttons.last_save', {}, 'messages', language)} ${Translator.trans('time.few_seconds_ago', {}, 'messages', language)}`)
          })
          .catch(function (error) {
            draftTextInfo.text(`${Translator.trans('servizio.error_from_save', {}, 'messages', language)}`)
          })
          .finally(function () {
            draftButton.html(text)
          });
  }

  static initDraftButton(){
    $('#save-draft').on('click', function (e) {
      e.preventDefault();
      Form.saveDraft()
    })
  }

  static getStoredSteps(){
    let parent = $('#wizardHeader')
    const steps = JSON.parse(localStorage.getItem("steps")) || null
    if(parent && steps){
      parent.prepend(steps.map(function(x){return x.replace(/step-active/g, '');}))
    }
  }



}

export default Form;
