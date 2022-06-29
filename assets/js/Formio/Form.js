import Calendar from '../Calendar';
import DynamicCalendar from '../DynamicCalendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import axios from "axios";
import Swal from "sweetalert2";
//import FormioI18n from "../utils/FormioI18n";


Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);

class Form {


  static initEditableAnonymous(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');

    $.getJSON(formUrl + '/i18n', function (data) {

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
        }
      })
        .then(function (form) {

          form.formReady.then(() => {
            setTimeout(Form.disableBreadcrumbButton, 500);
            setTimeout(Form.checkWizardNavCancelButton, 500);
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
          });

          form.on('prevPage', function () {
            setTimeout(Form.disableBreadcrumbButton, 500);
            setTimeout(Form.checkWizardNavCancelButton, 500);
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
            $('<a href="#" id="loading-button" class="btn btn-secondary"><i class="fa fa-refresh fa-spin"></i> Attendere...</a>').insertAfter(submitButton.last());
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

    $.getJSON(formUrl + '/i18n', function (data) {

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
          beforeCancel: () => Form.handleBeforeSubmit(event),
          addComponent: (component) => {
            console.log(component)
            return component;
          },
          addComponents: (components, instance) => {
            console.log(components)
            console.log(instance)
          }

        }
      }).then(function (form) {

        form.formReady.then(() => {

          console.log(form);
          console.log(form.loading);


          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          const draftButton = $('#save-draft');
          const draftInfo = $('.save-draft-info');
          const draftTextInfo = draftInfo.find('span');
          if (draftButton.length) {
            draftButton.parent().removeClass('d-none');
            draftButton.on('click', function (e) {
              e.preventDefault();
              let text = draftButton.html();
              draftButton.html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> Salvataggio in corso...')
              axios.post(draftButton.data('save-draft-url'), form.submission.data)
                .then(function (response) {
                  draftInfo.removeClass('d-none');
                  draftTextInfo.text('pochi secondi fa')
                })
                .catch(function (error) {
                  draftInfo.removeClass('d-none');
                  draftTextInfo.text('si è verificato un errore durante il salvataggio')
                })
                .finally(function () {
                  draftButton.html(text)
                });
            });
          }
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

        form.on('nextPage', function () {
          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          setTimeout(Form.checkWizardNavCancelButton, 500);
          document.getElementById("formio").scrollIntoView();
          $('#save-draft').trigger('click');
        });

        form.on('prevPage', function () {
          setTimeout(disableApplicant, 1000);
          setTimeout(Form.disableBreadcrumbButton, 500);
          setTimeout(Form.checkWizardNavCancelButton, 500);
        });

        let realSubmitButton = $('.craue_formflow_button_class_next');
        form.nosubmit = true;

        // Triggered when they click the submit button.
        form.on('submit', function (submission) {
          let submitButton = $('#formio button');
          submitButton.hide();
          $('<a href="#" id="loading-button" class="btn btn-secondary"><i class="fas fa-sync fa-spin"></i> Attendere...</a>').insertAfter(submitButton.last());
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
    $.getJSON($container.data('formserver_url') + '/form/' + $container.data('form_id') + '/i18n', function (data) {
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

          form.formReady.then(() => {

            console.log('aaaaa');
            console.log(form.loading);
            $('#print-tag-check').removeClass('d-none');

          });

        });
    });
  }

  static initPreview(containerId) {
    const $container = $('#' + containerId);
    const formUrl = $container.data('formserver_url') + '/form/' + $container.data('form_id');
    $.getJSON(formUrl + '/i18n', function (data) {
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
    $.getJSON(formUrl + '/i18n', function (data) {
      Formio.icons = 'fontawesome';
      Formio.createForm(document.getElementById(containerId), printableFormUrl, {
        readOnly: true,
        noAlerts: true,
        language: $container.data('locale'),
        i18n: data
      }).then(function (form) {
        form.submission = {
          data: $container.data('submission')
        };
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
    if (confirm("Sei sicuro di voler annullare?")) {
      document.location.reload()
    }
  }
}

export default Form;
