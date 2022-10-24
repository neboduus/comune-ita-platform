import './core'
import Calendar from './Calendar';
import DynamicCalendar from './DynamicCalendar';
import PageBreak from './PageBreak';
import FinancialReport from "./FinancialReport";
import SdcFile from "./SdcFile";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import FormioI18n from "./utils/FormioI18n";
import axios from "axios";
import {TextEditor} from "./utils/TextEditor";
import Gateways from "./rest/gateways/Gateways";

require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)


Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('dynamic_calendar', DynamicCalendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);
Formio.registerComponent('sdcfile', SdcFile);
const $language = document.documentElement.lang.toString();

$(document).ready(function () {

  $('.attachment-delete').on('click', function () {
    let btn = $(this);
    let deleteUrl = $(this).data("delete-url");

    $.ajax(deleteUrl,
      {
        method: 'DELETE',
        success: function () {   // success callback function
          btn.closest('li').remove();
        },
        error: function () { // error callback
          alert(`${Translator.trans('servizio.error_missing_filename', {}, 'messages', $language)}`)
        }
      });
  });

  // Show/Hide scheduler
  const serviceStatus = $('#general_data_status');
  const scheduledFrom = $('#general_data_scheduled_from').parent();
  const scheduledTo = $('#general_data_scheduled_to').parent();
  const hideScheduler = function () {
    if (serviceStatus.val() === '4') {
      scheduledFrom.show();
      scheduledTo.show();
    } else {
      scheduledFrom.hide();
      scheduledTo.hide();
    }
  }
  hideScheduler();
  serviceStatus.change(function () {
    hideScheduler()
  })

  // Show/Hide shared checkbox
  const serviceGroup = $('#general_data_service_group');
  const sharedCheckbox = $('#general_data_shared_with_group');
  const hideSharedCheckbox = function () {
    if (serviceGroup.val()) {
      sharedCheckbox.closest('div.form-group').show();
    } else {
      sharedCheckbox.prop('checked', false);
      sharedCheckbox.closest('div.form-group').hide();
    }
  }
  hideSharedCheckbox();
  serviceGroup.change(function () {
    hideSharedCheckbox()
  })

  // Show/Hide login checkbox
  const $accessLevel = $('#general_data_access_level');
  const $loginCheckbox = $('#general_data_login_suggested');
  const hideLoginCheckbox = function () {
    if ($accessLevel.val() === '0') {
      $loginCheckbox.closest('div').show();
    } else {
      $loginCheckbox.prop('checked', false);
      $loginCheckbox.closest('div').hide();
    }
  }
  $accessLevel.change(function () {
    hideLoginCheckbox();
  })

  // Show/Hide max response time
  const workflow = $('#general_data_workflow');
  const maxResponseTime = $('#general_data_max_response_time');
  const hideMaxResponseTime = function (){
    if(workflow.val() === '0'){
      maxResponseTime.closest('div').show();
    } else {
      maxResponseTime.removeAttr('value');
      maxResponseTime.closest('div').hide();
    }
  };
  hideMaxResponseTime();
  workflow.change(function () {
    hideMaxResponseTime()
  });

  // Show/Hide external card url
  const $externalCardUrlCheckbox = $('#general_data_enable_external_card_url');
  const $externalCardUrl = $('#general_data_external_card_url');
  const hideExternalCardUrl = function () {
    if($externalCardUrlCheckbox.is(":checked")){
      $externalCardUrl.closest('div').show();
    } else {
      $externalCardUrl.val('');
      $externalCardUrl.closest('div').hide();
    }
  };
  hideExternalCardUrl()
  $externalCardUrlCheckbox.click(function () {
    hideExternalCardUrl()
  });

  // Disable integrations
  const integrationTrigger = $('#integrations_data_trigger');
  const integrationAction = $('#integrations_data_action');
  const disableIntegration = function () {
    if (integrationTrigger.val() === "0") {
      integrationAction.attr('disabled', true);
    } else {
      integrationAction.attr('disabled', false);
    }
  }
  disableIntegration();
  integrationTrigger.on("change", function () {
    disableIntegration();
  })


  if ($('#form-step-messages').length) {
    $('.placeholders').append(function () {
      return $(`<button type="button" class="btn btn-outline-primary btn-xs float-right">${Translator.trans('servizio.placeholders_available', {}, 'messages', $language)}</button>`).on('click', function () {
        $('#form_placeholders').modal('toggle')
      });
    });
    let draftMessage = $('#feedback_messages_data_i18n_it_feedback_messages_7');
    draftMessage.closest('div').append(`<p id="draft_helper" class="small text-info mb-0">${Translator.trans('servizio.communication_citizen', {}, 'messages', $language)}</p>`)
  }



  // Step Template form
  if ($("#form-step-template").length) {
    const formioEmptyTemplatesContainer = $("#formio-empty-templates-container");
    const formioTemplatesContainer = $("#formio-templates-container");

    formioTemplatesContainer.parent().removeClass('d-none');
    formioEmptyTemplatesContainer.append($.templates("#tpl-form").render({
      id: 'new',
      title: `${Translator.trans('servizio.create_new_form', {}, 'messages', $language)}`,
      description: `${Translator.trans('servizio.create_new_form_blank_template', {}, 'messages', $language)}`,
    }));

    $.get(formioTemplatesContainer.data('url') + "?t=" + Date.now(), function (data) {
      if (data.length > 1) {
        $.each(data, function (index, value) {
          if (value.id !== $('#formio_template_current_id').val()) {
            formioTemplatesContainer.append($.templates("#tpl-form").render(value));
          }
        });
        if ($("#formio_template_service_id").val()) {
          $('#' + $("#formio_template_service_id").val()).addClass('card-bg-success');
        }
      } else {
        formioTemplatesContainer.append($.templates("#tpl-empty").render());
      }
    }).always(function () {
      $('.formio-template').click(function (e) {
        $('.card-bg-success').removeClass('card-bg-success');
        $(this).addClass('card-bg-success');
        $("#formio_template_service_id").val($(this).data('id'));
      })
    });
  }

  if ($("#form-step-general").length) {

    const serviceStatus = $('#general_data_status');
    const scheduledFrom = $('#general_data_scheduled_from').parent();
    const scheduledTo = $('#general_data_scheduled_to').parent();
    const accessLevel = $('#general_data_access_level');
    const loginCheckbox = $('#general_data_login_suggested');

    const hideScheduler = function () {
      if (serviceStatus.val() === '4') {
        scheduledFrom.show();
        scheduledTo.show();
      } else {
        scheduledFrom.hide();
        scheduledTo.hide();
      }
    }

    // Show/Hide scheduler on init
    hideScheduler();

    // Show/Hide scheduler on access level change
    serviceStatus.change(function () {
      hideScheduler()
    })

    // Show/Hide login checkbox
    accessLevel.change(function () {
      if (this.value === '0') {
        loginCheckbox.closest('div').show();
      } else {
        loginCheckbox.prop('checked', false);
        loginCheckbox.closest('div').hide();
      }
    })
    accessLevel.trigger('change');


    const limitChars = 2000;
    TextEditor.init({
      onInit: function () {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        $(this).parent().append(`<small class="form-text text-muted">${Translator.trans('servizio.max_limit_of', {}, 'messages', $language)} ${limitChars} ${Translator.trans('characters', {}, 'messages', $language)} (<span class="total-chars"> ${totalChars} </span> / <span class="max-chars"> ${limitChars} </span>)</small>`)
      },
      onChange: function () {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        //Update value
        $(this).parent().find(".total-chars").text(totalChars);


        //Check and Limit Charaters
        if (totalChars >= limitChars) {
          return false;
        }
      }
    })
  }

  if ($("#form-step-card").length) {
    const limitChars = 2000;
    TextEditor.init({
      onInit: function () {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        $(this).parent().append(`<small class="form-text text-muted">${Translator.trans('servizio.max_limit_of', {}, 'messages', $language)} ${limitChars} ${Translator.trans('characters', {}, 'messages', $language)} (<span class="total-chars"> ${totalChars} </span> / <span class="max-chars"> ${limitChars} </span>)</small>`)
      },
      onChange: function () {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        //Update value
        $(this).parent().find(".total-chars").text(totalChars);


        //Check and Limit Charaters
        if (totalChars >= limitChars) {
          return false;
        }
      }
    })
  }

  if ($("#form-step-messages").length) {

    let draftMessage = $('#feedback_messages_data_feedback_messages_7_is_active');
    draftMessage.closest('div').append(`<p id="draft_helper" class="small text-info mb-0">${Translator.trans('servizio.draft_message', {}, 'messages', $language)}</p>`)

    $('textarea').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['insert', ['link']],
        ['view', ['codeview']],
      ],
      hint: {
        mentions: ['%pratica_id%', '%servizio%', '%protocollo%', '%messaggio_personale%', '%user_name%', '%indirizzo%'],
        match: /\B%(\w*)$/,
        search: function (keyword, callback) {
          callback($.grep(this.mentions, function (item) {
            return item.indexOf(keyword) == 0;
          }));
        },
        content: function (item) {
          return item;
        }
      }
    });
  }

  // Step Form Fields
  if ($("#form-step-formio").length) {

    const saveForm = function (saveUrl, targetUrl, type) {
      let schema = $("#formio_builder_render_form_schema").val();
      $.ajax(saveUrl,
        {
          dataType: 'json', // type of response data
          method: 'POST',
          data: {
            schema: schema
          },
          success: function (data, status, xhr) {   // success callback function
            if (data.status === 'success') {
              if (type === 'print') {
                window.location.href = targetUrl;
              } else if (type === 'draft') {
                $('.toast').toast('show')
              } else {
                window.open(targetUrl, '_blank');
              }
            } else {
              alert(`${Translator.trans('servizio.error_from_save', {}, 'messages', $language)}`)
            }
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            console.log(errorMessage);
            alert(`${Translator.trans('servizio.error_from_save', {}, 'messages', $language)}`)
          }
        });
    };

    let preview = $('#preview');
    preview.find('a').click(function (e) {
      e.preventDefault();
      saveForm($(this).data('schema'), $(this).data('target'), $(this).data('type'));
    });

    const storeSchema = function (schema) {
      $("#formio_builder_render_form_schema").val(schema);
    };

    Formio.icons = "fontawesome";
    Formio.builder(document.getElementById("builder"), $('#formio').data('formserver_url') + "/form/" + $("#formio_builder_render_form_id").val(), {
      language: $language,
      i18n: FormioI18n.languages(),
      builder: {
        basic: false,
        advanced: false,
        data: false,
        layout: false,
        premium: false,
        resource: false,
        customBasic: {
          title: 'Componenti',
          default: true,
          weight: 0,
          components: {
            textfield: true,
            textarea: true,
            checkbox: true,
            number: true,
            select: true,
            radio: true,
            selectboxes: true,
            email: true,
            phoneNumber: true,
            url: true,
            datetime: true,
            day: true,
            time: true,
            currency: true,
            hidden: true,
            form: true,
            calendar: true,
            dynamic_calendar: true,
            file: {
              title: 'File',
              key: 'file',
              icon: 'file',
              schema: {
                label: 'File',
                type: 'file',
                key: 'file',
                input: true,
                storage: "url",
                fileMinSize: "1KB",
                fileMaxSize: "10MB",
                url: window.location.protocol + "//" + window.location.host + "/" + window.location.pathname.split("/")[1] + "/allegati",
              }
            },
            sdcfile: {
              title: 'File Sdc',
              key: 'sdcfile',
              icon: 'file',
              schema: {
                label: 'File',
                type: 'sdcfile',
                key: 'sdcfile',
                input: true,
                storage: "url",
                fileMinSize: "1KB",
                fileMaxSize: "10MB",
                url: window.location.protocol + "//" + window.location.host + "/" + window.location.pathname.split("/")[1] + "/it/upload",
              }
            },
            financial_report: true,
            address: true,
            survey: true
          }
        },
        customLayout: {
          title: 'Layout',
          default: false,
          weight: 0,
          components: {
            htmlelement: true,
            columns: true,
            pagebreak: true,
            table: true,
            datagrid: {
              title: 'Datagrid',
              key: 'Datagrid',
              icon: 'th',
              schema: {
                label: 'Datagrid',
                type: 'datagrid',
                key: 'datagrid',
                input: true,
                customDefaultValue: "value = [{}]",
              }
            },
            well: true,
            panel: true,
            editgrid: true,
            fieldset: true
          }
        },
      },
    }).then(function (builder) {
      // Inserisco lo schema in un input hidden
      //$("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      storeSchema(JSON.stringify(builder.schema));

      builder.on("updateComponent", function () {
        storeSchema(JSON.stringify(builder.schema));
      });

      // Inserisco lo schema in un input hidden sulla modifica di un componente
      builder.on("editComponent", function () {
        storeSchema(JSON.stringify(builder.schema));
      });

      // Inserisco lo schema in un input hidden sul salvataggio di un componente
      builder.on("saveComponent", function () {
        storeSchema(JSON.stringify(builder.schema));
      });

      // Inserisco lo schema in un input hidden su aggiunta di un componente
      builder.on("addComponent", function () {
        storeSchema(JSON.stringify(builder.schema));
      });

      // Inserisco lo schema in un input hidden su rimozione di un componente
      builder.on("removeComponent", function () {
        storeSchema(JSON.stringify(builder.schema));
      });
    });
  }

  // Step Payment data
  if ($("#form-step-payments").length) {

    const paymentRequiredField = $('#payment_data_payment_required');

    const paymentTypeHelp = function (type) {
      $("#payment-type-help").remove();
      if (type == 1) {
        paymentRequiredField.closest('.form-group').append(`<small id="payment-type-help" class="d-block m-2 text-muted">${Translator.trans('pratica.payment.immediate_payment_description', {}, 'messages', $language)}</small>`);
      } else if (type == 2) {
        paymentRequiredField.closest('.form-group').append(`<small id="payment-type-help" class="d-block m-2 text-muted">${Translator.trans('pratica.payment.delayed_payment_description', {}, 'messages', $language)}</small>`);
      }
    };

    paymentRequiredField.change(function () {
      if ($(this).val() == 0) {
        $('#payment_data_total_amounts').attr('disabled', 'disabled');
        $('.row-payments').addClass('d-none').removeClass('d-block')
      } else {
        $('#payment_data_total_amounts').removeAttr('disabled');
        $('.row-payments').addClass('d-block').removeClass('d-none')
      }
      paymentTypeHelp($(this).val());
    });
    paymentRequiredField.trigger('change');

    if( $('#gateways-tab, #payments-tab').length > 0){
      Gateways.init();
    }
  }

  // Step Integrations data
  if ($("#form-step-backoffices").length) {
    $('#integrations_data_trigger').change(function () {
      if ($(this).val() == '0') {
        $('#integrations_data_action').attr('disabled', 'disabled');
      } else {
        $('#integrations_data_action').removeAttr('disabled');
      }
    })
  }

  // Protocol data
  if ($('#form-step-protocol').length) {
    $('#protocol_data_protocol_required').change(function () {
      if (this.checked) {
        $('.protocollo_params').removeAttr('disabled');
      } else {
        $('.protocollo_params').attr('disabled', 'disabled');
      }
    })

    let protocolHandler = $('#protocol_data_protocol_handler');
    let setupProtocolSettings = function () {
      $('.protocollo_params').each(function (i, e) {
        let element = $(e);
        if (element.hasClass(protocolHandler.val())) {
          element.closest('div').removeClass('d-none');
          element.removeAttr('disabled');
        } else {
          element.closest('div').addClass('d-none');
          element.attr('disabled', 'disabled');
        }
      });
    }
    setupProtocolSettings();
    protocolHandler.change(function () {
      setupProtocolSettings();
    });
  }


  // IO config
  if ($('#form-step-app-io').length) {
    let service_id = $('#io_integration_data_io_service_parameters_IOserviceId');
    let primary_key = $('#io_integration_data_io_service_parameters_primaryKey');
    let secondary_key = $('#io_integration_data_io_service_parameters_secondaryKey');

    if (service_id.val() && primary_key.val()) {
      switchTest(true)
    } else {
      switchTest(false)
    }

    $('#form_io_send_test').click(function () {
      $("#error_messages").empty();
      let url = $("#form_io_send_test").data("url")
      $.ajax({
        url: url,
        type: "POST",
        data: {
          "service_id": service_id.val(),
          "primary_key": primary_key.val(),
          "secondary_key": secondary_key.val(),
          "fiscal_code": $('#form_io_send_test_fiscal_code').val()
        },
        success: function (data) {
          $("#error_messages").append(`<p class='text-success'><i class='fa fa-check-circle mr-2'></i>${Translator.trans('app_io.notify.success_send', {}, 'messages', $language)} ${data.id} </p>`);
        },
        error: function (data) {
          $("#error_messages").append(
            `<p class='text-danger'><i class='fa fa-exclamation-circle mr-2'></i>${Translator.trans('app_io.notify.no_send', {}, 'messages', $language)}<ul class='list-unstyled text-danger'><li>${data.responseJSON.error}</li></ul></p>`
          );
        }
      });
    })

    service_id.change(function () {
      if (!service_id.val()) {
        switchTest(false)
      } else if (primary_key.val()) {
        switchTest(true)
      }
    })
    primary_key.change(function () {
      if (!primary_key.val()) {
        switchTest(false)
      } else if (service_id.val()) {
        switchTest(true)
      }
    })
  }

  function switchTest(enabled) {
    if (enabled) {
      $("#io_test").show();
    } else {
      $("#io_test").hide();
    }
  }

  // Save form.io draft
  if ($("#btn-draft").length) {
    $("#btn-draft").on('click', function (e) {
      e.preventDefault();
      let schema = $("#formio_builder_render_form_schema").val();
      $.ajax($(this).data('schema'),
        {
          dataType: 'json', // type of response data
          method: 'POST',
          data: {
            schema: schema
          },
          success: function (data, status, xhr) {   // success callback function
            if (data.status === 'success') {
              $('.toast').toast('show')
            } else {
              alert(`${Translator.trans('servizio.error_from_save', {}, 'messages', $language)}`)
            }
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            console.log(errorMessage);
            alert(`${Translator.trans('servizio.error_from_save', {}, 'messages', $language)}`)
          }
        });
    })
  }

  // Show or hidden warning alert if service's name is too long
  $("input[name^='general_data[name]']").on("input", function() {
    if($(this).val().length > 50) {
      $(this).addClass('is-invalid warning')
      if ($('#warning-text-length').length === 0) {
        $(`${Translator.trans('pratica.warning_long_text', {}, 'messages', $language)}`).insertAfter($(this));
      }
    }else{
      if($('#warning-text-length').length){
        $(this).removeClass('is-invalid warning')
        $('#warning-text-length').remove();
      }
    }
  });
});
