import Calendar from "../Calendar";
import DynamicCalendar from "../DynamicCalendar";
import PageBreak from "../PageBreak";
import FinancialReport from "../FinancialReport";
import SdcFile from "../SdcFile";
import "formiojs";
import "formiojs/dist/formio.form.min.css";
import "../../styles/components/cmp/all.scss";
import axios from "axios";
import FormioI18n from "../utils/FormioI18n";

Formio.registerComponent("calendar", Calendar);
Formio.registerComponent("dynamic_calendar", DynamicCalendar);
Formio.registerComponent("pagebreak", PageBreak);
Formio.registerComponent("financial_report", FinancialReport);
Formio.registerComponent("sdcfile", SdcFile);
const language = document.documentElement.lang.toString();

import wizardNav from "./templates/wizardNav/index.js";
import wizardHeader from "./templates/wizardHeader/index.js";
import fieldset from "./templates/fieldset/index.js";

// Overwrite nav buttons formio
Formio.Templates.current = {
  wizardNav: {
    form: (ctx) => wizardNav(ctx),
  },
  wizardHeader: {
    form: (ctx) => wizardHeader(ctx),
  },
  fieldset: {
    form: (ctx) => fieldset(ctx),
  },
};

class Form {
  submissionForm = null;

  static createStepsMobile() {
    $(".info-progress-wrapper[data-loop!='first']").each(function (idx) {
      $(this).attr("data-progress", idx + 1);
    });

    // Hide craue element if formio steps are > 4
    if ($("[data-wizard*='header']").length > 4) {
      $("[data-item='craue']").each(function () {
        $(this).removeClass("d-lg-flex");
      });
    }

    const step =
      ($(".step-active").data("progress")
        ? $(".step-active").data("progress")
        : "1") +
      "/" +
      ($(".info-progress-wrapper").length - 1);
    const stepLabel = $(".step-active span").attr('title');

    $(".step").html(step);
    $(".step-label").html(stepLabel);
  }

  static initEditableAnonymous(containerId) {
    const $container = $("#" + containerId);
    const formUrl =
      $container.data("formserver_url") + "/form/" + $container.data("form_id");

    $.getJSON(
      formUrl + "/i18n?lang=" + $container.data("locale"),
      function (data) {
        let customErrorContainer = $("#formio-custom-errors");
        // Nascondo input symfony, trovare modo di fare submit di formio da esterno
        $(".craue_formflow_buttons").addClass("d-none");

        Formio.icons = "fontawesome";
        Formio.createForm(
          document.getElementById("formio"),
          $("#formio").data("formserver_url") +
          "/form/" +
          $("#formio_render_form_id").val(),
          {
            noAlerts: true,
            language: $container.data("locale"),
            i18n: data,
            buttonSettings: {showCancel: false},
            breadcrumbSettings: {
              clickable: true,
            },
            breadCrumb: {clickable: true},
            hooks: {
              beforeCancel: () => Form.handleBeforeSubmit(event),
            },
            sanitizeConfig: {
              allowedAttrs: ["ref", "src", "url", "data-oembed-url"],
              allowedTags: ["oembed", "svg", "use"],
              addTags: ["oembed", "svg", "use"],
              addAttr: ["url", "data-oembed-url"],
            },
          }
        ).then(function (form) {
          form.formReady.then(() => {
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.submissionForm = form;
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          if (form.hasOwnProperty("wizard")) {
            $(".craue_formflow_current_step.active").addClass("wizard");
          }

          let dataContainer = $("#formio_render_dematerialized_forms");
          // Recupero i dati della pratica se presenti
          if (dataContainer.val()) {
            form.submission = {
              data: JSON.parse(dataContainer.val()).data,
            };
          }

          form.on("nextPage", function () {
            document.getElementById("formio").scrollIntoView();
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.saveDraft(form);
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("prevPage", function () {
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("pagesChanged", function () {
            Form.customBreadcrumbButton(form);
          });

          $(".btn-wizard-nav-cancel").on("click", function (e) {
            e.preventDefault();
            location.reload();
          });

          let realSubmitButton = $(".craue_formflow_button_class_next");
          form.nosubmit = true;
          // Triggered when they click the submit button.
          form.on("submit", function (submission) {
            let submitButton = $("#formio button");
            submitButton.hide();
            $(
              `<a href="#" id="loading-button" class="btn btn-secondary"><i class="fa fa-refresh fa-spin"></i>${Translator.trans(
                "waiting",
                {},
                "messages",
                language
              )}...</a>`
            ).insertAfter(submitButton.last());
            customErrorContainer.empty().hide();
            axios
              .post(
                $container.data("form_validate"),
                JSON.stringify(submission.data)
              )
              .then(function (reponse) {
                customErrorContainer.empty();
                let submitErrors = null;
                if (reponse.data.errors) {
                  reponse.data.errors.forEach((error) => {
                    customErrorContainer.append(
                      '<p class="m-0">' + error.toString() + "</p>"
                    );
                  });
                  customErrorContainer.show();
                  $("#formio #loading-button").remove();
                  submitButton.show();
                } else {
                  form.emit("submitDone", submission);
                  let data = $('form[name="formio_render"]').serialize();
                  dataContainer.val(JSON.stringify(submission.data));
                  realSubmitButton.trigger("click");
                }
              });
          });
        });
        Form.autoCloseAlert(customErrorContainer);
      }
    );
  }

  static initEditable(containerId) {
    const $container = $("#" + containerId);
    const formUrl =
      $container.data("formserver_url") + "/form/" + $container.data("form_id");

    $.getJSON(
      formUrl + "/i18n?lang=" + $container.data("locale"),
      function (data) {
        let customErrorContainer = $("#formio-custom-errors");
        // Nascondo input symfony, trovare modo di fare submit di formio da esterno
        $(".craue_formflow_buttons").addClass("d-none");

        Formio.icons = "fontawesome";
        Formio.createForm(document.getElementById(containerId), formUrl, {
          noAlerts: true,
          language: $container.data("locale"),
          i18n: data,
          buttonSettings: {showCancel: false},
          breadcrumbSettings: {
            clickable: true,
          },
          breadCrumb: {clickable: true},
          hooks: {
            beforeCancel: () => Form.handleBeforeSubmit(event),
          },
          sanitizeConfig: {
            allowedAttrs: ["ref", "src", "url", "data-oembed-url"],
            allowedTags: ["oembed", "svg", "use"],
            addTags: ["oembed", "svg", "use"],
            addAttr: ["url", "data-oembed-url"],
          },
        }).then(function (form) {
          form.formReady.then(() => {
            setTimeout(disableApplicant, 1000);
            Form.createStepsMobile();
            Form.submissionForm = form;
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          if (form.hasOwnProperty("wizard")) {
            $(".craue_formflow_current_step.active").addClass("wizard");
          }

          let dataContainer = $("#formio_render_dematerialized_forms");
          // Recupero i dati della pratica se presenti
          if (dataContainer.val()) {
            form.submission = {
              data: JSON.parse(dataContainer.val()).data,
            };
          }

          form.on("nextPage", function (e) {
            setTimeout(disableApplicant, 1000);
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            document.getElementById("formio").scrollIntoView();
            Form.createStepsMobile();
            Form.saveDraft();
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("prevPage", function () {
            setTimeout(disableApplicant, 1000);
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.initDraftButton();
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("pagesChanged", function () {
            Form.customBreadcrumbButton(form);
          });

          let realSubmitButton = $(".craue_formflow_button_class_next");
          form.nosubmit = true;

          // Triggered when they click the submit button.
          form.on("submit", function (submission) {
            let submitButton = $("#formio button");
            submitButton.hide();
            $(
              `<a href="#" id="loading-button" class="btn btn-secondary"><i class="fas fa-sync fa-spin"></i>${Translator.trans(
                "waiting",
                {},
                "messages",
                language
              )}...</a>`
            ).insertAfter(submitButton.last());
            customErrorContainer.empty().hide();
            axios
              .post(
                $container.data("form_validate"),
                JSON.stringify(submission.data)
              )
              .then(function (reponse) {
                customErrorContainer.empty();
                let submitErrors = null;
                if (reponse.data.errors) {
                  reponse.data.errors.forEach((error) => {
                    customErrorContainer.append(
                      '<p class="m-0">' + error.toString() + "</p>"
                    );
                  });
                  customErrorContainer.show();
                  $("#formio #loading-button").remove();
                  submitButton.show();
                } else {
                  form.emit("submitDone", submission);
                  let data = $('form[name="formio_render"]').serialize();
                  dataContainer.val(JSON.stringify(submission.data));
                  realSubmitButton.trigger("click");
                }
              });
          });
        });
        Form.autoCloseAlert(customErrorContainer);

        //Funzione per rendere il form Applicant readOnly
        const disableApplicant = function () {
          $(".formio-component-applicant input").each(function (k) {
            if ($(this).closest(".formio-component-address").length <= 0) {
              if ($(this).prop("type") === "radio") {
                let name = $(this).prop("name");
                if ($(this).prop("checked")) {
                  $("input[name='" + name + "']").attr("disabled", "disabled");
                }
              } else if ($(this).val()) {
                $(this).attr("disabled", "disabled");
              }
            }
          });
        };
      }
    );
  }

  static autoCloseAlert(customErrorContainer) {
    if (customErrorContainer && customErrorContainer.length > 0) {
      customErrorContainer.each(function () {
        var time_period = customErrorContainer.attr("auto-close");
        setTimeout(function () {
          customErrorContainer.empty().hide();
        }, time_period);
      });
    }
  }

  static initPrintable(containerId) {
    const $container = $("#" + containerId);
    const formUrl =
      $container.data("formserver_url") +
      "/printable/" +
      $container.data("form_id");
    $.getJSON(
      $container.data("formserver_url") +
      "/form/" +
      $container.data("form_id") +
      "/i18n?lang=" +
      $container.data("locale"),
      function (data) {
        Formio.icons = "fontawesome";
        Formio.createForm(document.getElementById(containerId), formUrl, {
          noAlerts: true,
          language: $container.data("locale"),
          i18n: data,
          readOnly: true,
          buttonSettings: {showCancel: false},
          hooks: {
            beforeCancel: () => Form.handleBeforeSubmit(event),
          },
          //renderMode: 'html'
        }).then(function (form) {
          // Recupero i dati della pratica se presenti
          if ($("#formio_render_dematerialized_forms").val() != "") {
            form.submission = {
              data: JSON.parse($("#formio_render_dematerialized_forms").val())
                .data,
            };
          }

          // Called when the form has completed the render, attach, and one initialization change event loop
          // Da migliorare il controllo sulla fine del rendering del form
          form.on('initialized', () => {
            setTimeout(function () {
              console.log('initialized');
              window.status = 'ready'
            }, 5000)
          });

        });
      }
    );
  }

  static initPrintableBuiltIn(containerId) {
    const $container = $("#" + containerId);
    const formUrl = $container.data("url");
    const baseUrl = $container.data("formserver_url");

    Form.getFormSchemaSchema(formUrl, true).then((formSchema) => {
      Formio.setBaseUrl(baseUrl);
      Formio.icons = "fontawesome";

      Formio.createForm(document.getElementById(containerId), formSchema, {
        noAlerts: true,
        language: $container.data("locale"),
        i18n: FormioI18n.languages(),
        readOnly: true,
        buttonSettings: {showCancel: false},
        hooks: {
          beforeCancel: () => Form.handleBeforeSubmit(event),
        },
        //renderMode: 'html'
      }).then(function (form) {
        // Recupero i dati della pratica se presenti
        if ($("#formio_render_dematerialized_forms").val() != "") {
          form.submission = {
            data: JSON.parse($("#formio_render_dematerialized_forms").val()).data,
          };
        }

        // Called when the form has completed the render, attach, and one initialization change event loop
        // Da migliorare il controllo sulla fine del rendering del form
        form.on('initialized', () => {
          setTimeout(function () {
            console.log('initialized');
            window.status = 'ready'
          }, 5000)
        });
      });
    });
  }

  static initPreview(containerId) {
    const $container = $("#" + containerId);
    const formUrl =
      $container.data("formserver_url") + "/form/" + $container.data("form_id");
    $.getJSON(
      formUrl + "/i18n?lang=" + $container.data("locale"),
      function (data) {
        Formio.icons = "fontawesome";
        Formio.createForm(document.getElementById(containerId), formUrl, {
          noAlerts: true,
          language: $container.data("locale"),
          i18n: data,
          readOnly: false,
          buttonSettings: {showCancel: false},
          hooks: {
            beforeCancel: () => Form.handleBeforeSubmit(event),
          },
          //renderMode: 'html'
        }).then(function (form) {
          form.formReady.then(() => {
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("nextPage", function (e) {
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.createCustomNavItem(form.component, false, data)
          });

          form.on("prevPage", function () {
            Form.customBreadcrumbButton(form);
            setTimeout(Form.checkWizardNavCancelButton, 500);
            Form.createStepsMobile();
            Form.createCustomNavItem(form.component, false, data)
          });
        });
      }
    );
  }

  static initSummary(containerId) {
    const $container = $("#" + containerId);
    const formUrl =
      $container.data("formserver_url") + "/form/" + $container.data("form_id");
    const printableFormUrl =
      $container.data("formserver_url") + "/printable/" + $container.data("form_id");

    $.getJSON(
      formUrl + "/i18n?lang=" + $container.data("locale"),
      function (data) {
        Formio.icons = "fontawesome";
        Formio.createForm(
          document.getElementById(containerId),
          printableFormUrl,
          {
            readOnly: true,
            noAlerts: true,
            language: $container.data("locale"),
            i18n: data,
            sanitizeConfig: {
              allowedAttrs: ["ref", "src", "url", "data-oembed-url", "svg"],
              allowedTags: ["oembed", "svg"],
              addTags: ["oembed", "svg"],
              addAttr: ["url", "data-oembed-url"],
            },
          }
        ).then(function (form) {
          form.submission = {
            data: $container.data("submission"),
          };

          Form.getStoredSteps();
          Form.createStepsMobile();
          Form.createCustomNavItem(form.component, true, data)

          form.ready.then(() => {
            $('.formio-component-file a,.formio-component-sdcfile a').each(function () {
              $(this).parent().html($(this).html());
            });
          });
        });
      }
    );
  }

  static initSummaryBuiltIn(containerId) {
    const $container = $("#" + containerId);
    const formUrl = $container.data("url");
    const baseUrl = $container.data("formserver_url");

    Form.getFormSchemaSchema(formUrl, true).then((formSchema) => {
      Formio.setBaseUrl(baseUrl);

      Formio.icons = "fontawesome";
      Formio.createForm(
        document.getElementById(containerId),
        formSchema,
        {
          readOnly: true,
          noAlerts: true,
          language: $container.data("locale"),
          i18n: FormioI18n.languages(),
          sanitizeConfig: {
            allowedAttrs: ["ref", "src", "url", "data-oembed-url", "svg"],
            allowedTags: ["oembed", "svg"],
            addTags: ["oembed", "svg"],
            addAttr: ["url", "data-oembed-url"],
          },
        }
      ).then(function (form) {
        form.submission = {
          data: $container.data("submission"),
        };

        Form.getStoredSteps();
        Form.createStepsMobile();
        Form.createCustomNavItem(form.component, true, FormioI18n.languages())

        form.ready.then(() => {
          $('.formio-component-file a,.formio-component-sdcfile a').each(function () {
            $(this).parent().html($(this).html());
          });
        });
      });
    });
  }

  static init(containerId) {
    // Init form editable anonymous
    if ($("#" + containerId + ".editable-anonymous").length > 0) {
      this.initEditableAnonymous(containerId);
    }

    // Init form editable
    if ($("#" + containerId + ".editable").length > 0) {
      this.initEditable(containerId);
    }

    // Init form printable
    if ($("#" + containerId + ".printable").length > 0) {
      this.initPrintable(containerId);
    }

    // Init form preview
    if ($("#" + containerId + ".preview").length > 0) {
      this.initPreview(containerId);
    }

    // Init form summary
    if ($("#" + containerId + ".formio-summary").length > 0) {
      this.initSummary(containerId);
    }

    // Init built-in printable
    if ($("#" + containerId + ".built-in-printable").length > 0) {
      this.initPrintableBuiltIn(containerId);
    }

    // Init built-in summary
    if ($("#" + containerId + ".formio-built-in-summary").length > 0) {
      this.initSummaryBuiltIn(containerId);
    }
  }

  static customBreadcrumbButton(form) {
    // Handle click Breadcrumb Buttons
    $(".info-progress-body.completed").on("click", function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();
      const indexPage = $(this).data("index");
      if (indexPage >= 0) {
        form.setPage(indexPage);
        Form.customBreadcrumbButton(form);
      }
    });
  }

//Funzione per aggiungere l'attributo type=button al pulsante "Annulla" se è visibile
  static checkWizardNavCancelButton() {
    if ($(".btn-wizard-nav-cancel").length > 0) {
      $(".btn-wizard-nav-cancel").attr("type", "button");
    }
  }

// Refresh page on handle "cancel button"
  static handleBeforeSubmit() {
    if (
      confirm(
        `${Translator.trans(
          "pratica.you_want_cancel",
          {},
          "messages",
          language
        )}`
      )
    ) {
      document.location.reload();
    }
  }

  static saveDraft() {
    const draftButton = $("#save-draft");
    const draftInfo = $(".save-draft-info");
    const draftTextInfo = draftInfo.find("span");
    let text = draftButton.html();
    draftButton.html(
      `<i class="fa fa-circle-o-notch fa-spin fa-fw"></i>${Translator.trans(
        "save_processing",
        {},
        "messages",
        language
      )}`
    );
    axios
      .post($("#formio").data("save-draft-url"), Form.submissionForm.data)
      .then(function (response) {
        draftTextInfo.html(
          `<i class="fa fa-clock-o" aria-hidden="true"></i> ${Translator.trans(
            "buttons.last_save",
            {},
            "messages",
            language
          )} ${Translator.trans(
            "time.few_seconds_ago",
            {},
            "messages",
            language
          )}`
        );
      })
      .catch(function (error) {
        draftTextInfo.text(
          `${Translator.trans(
            "servizio.error_from_save",
            {},
            "messages",
            language
          )}`
        );
      })
      .finally(function () {
        draftButton.html(text);
      });
  }

  static initDraftButton() {
    $("#save-draft").on("click", function (e) {
      e.preventDefault();
      Form.saveDraft();
    });
  }

  static getStoredSteps() {
    let parent = $("#wizardHeader");
    const steps = JSON.parse(localStorage.getItem("steps")) || null;
    if (parent && steps) {
      parent.prepend(
        steps.map(function (x) {
          return x.replace(/step-active/g, "");
        })
      );
    }
  }

  static createCustomNavItem(data, isSummary, translations) {
    // Filter only fieldset components
    let navItem = []
    if (isSummary) {
      data.components.forEach(item => {
        if (item.components.filter(el => el.type === 'fieldset').length) {
          const filtered = item.components.filter(el => el.type === 'fieldset')
          navItem = [...navItem, ...filtered]
        }
      })
    } else {
      navItem = data.components.filter(item => item.type === 'fieldset')
    }

    // Reset list items
    $('#navItems').empty();
    navItem.forEach((el, idx) => {
      // Write item
      $('#navItems').append(`<li class="nav-item"><a class="nav-link ${idx === 0 ? 'active' : ''}" href="#${el.key}">
        <span class="title-medium">${translations[language] && translations[language][el.legend] ? translations[language][el.legend] : el.legend}
        </span></a></li>`);
    })
  }

  static getFormSchemaSchema(formUrl, printable = false) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: formUrl,
        dataType: 'json',
        type: 'GET',
        success: function (form) {
          if (printable) {
            form = Form.wizardConverter(form)
          }
          resolve(form)
        },
        error: function (error) {
          reject(error)
        }
      })
    })
  }

  static wizardConverter(form) {
    if (form.display === 'wizard') {
      // Metodo 1: conversione semplice: espansione delle pagine in verticale
      form.display = 'form';
    }
    return Form.disable(form);
  }

  static disable(form) {
    let disabledForm = JSON.parse(JSON.stringify(form))

    if (disabledForm.components) {
      // Disable form components
      disabledForm.components.forEach(function (component, index) {
        disabledForm.components[index] = Form.disable(component);
      })
    } else if (disabledForm.columns) {
      // Disable columns components
      disabledForm.columns.forEach(function (column, index) {
        disabledForm.columns[index] = Form.disable(column);
      })
    } else {
      // Simple component
      // Disable JS custom calculated values
      if (disabledForm.type === 'select' && ['custom', 'url'].includes(disabledForm['dataSrc'])) {
        disabledForm.dataSrc = 'custom';
        if (disabledForm.data && disabledForm.data.custom) {
          // Disable custom js
          disabledForm.data.custom = '';
        }
      }
      // Disable JS custom default values
      if (disabledForm.customDefaultValue) {
        disabledForm.customDefaultValue = '';
      }
      // Disable JS custom calculated values
      if (disabledForm.calculateValue) {
        disabledForm.calculateValue = '';
      }
      // Disable JS validation
      if (disabledForm.validate) {
        disabledForm.validate.custom = '';
      }
    }
    return disabledForm;
  }

}

export default Form;
