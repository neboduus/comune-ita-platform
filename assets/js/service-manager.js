import './core'
import Calendar from './Calendar';
import PageBreak from './PageBreak';
import FinancialReport from "./FinancialReport";
import 'formiojs'
import {TextEditor} from "./utils/TextEditor";

require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)


Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);

$(document).ready(function () {
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
  // Show/Hide scheduler on init
  hideScheduler();

  // Show/Hide scheduler on access level change
  serviceStatus.change(function () {
    hideScheduler()
  })


  let loginCheckbox = $('#general_data_login_suggested');
  if ($('#general_data_access_level').val() === '0') {
    loginCheckbox.closest('div').show();
  } else {
    loginCheckbox.closest('div').hide();
  }

  // Show/Hide login checkbox
  $('#general_data_access_level').change(function () {
    if (this.value === '0') {
      loginCheckbox.closest('div').show();
    } else {
      loginCheckbox.prop('checked', false);
      loginCheckbox.closest('div').hide();
    }
  })

  // Step Template form
  if ($("#formio_template_service_id").length) {
    const formioEmptyTemplatesContainer = $("#formio-empty-templates-container");
    const formioTemplatesContainer = $("#formio-templates-container");

    formioTemplatesContainer.parent().removeClass('d-none');

    formioEmptyTemplatesContainer.append($.templates("#tpl-form").render({
      id: 'new',
      title: 'Crea nuovo form',
      description: 'Crea nuovo form  da template vuoto',
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

  if ($("#general_data_flow_service_step").length /*|| $("#feedback_messages_data_flow_service_step").length*/) {
    const limitChars = 2000;
    TextEditor.init({
      onInit: function () {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        $(this).parent().append('<small class="form-text text-muted">Si consiglia di inserire un massimo di ' + limitChars + ' caratteri (<span class="total-chars">' + totalChars + '</span> / <span class="max-chars"> ' + limitChars + '</span>)</small>')
      },
      /*onKeydown: function() {
        let chars = $(this).parent().find(".note-editable").text();
        let totalChars = chars.length;

        //Check and Limit Charaters
        if(totalChars >= limitChars){
          return false;
        }
      },*/
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

  if ( $("#feedback_messages_data_flow_service_step").length ) {
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
  if ($("#formio_builder_render_form_id").length) {

    const saveForm = function( saveUrl, targetUrl, type){
      let schema = $("#formio_builder_render_form_schema").val();
      $.ajax( saveUrl,
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
              } else {
                window.open( targetUrl, '_blank');
              }
            } else {
              alert('Si è verificato un errore durante il salvataggio.')
            }
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            console.log(errorMessage);
            alert('Si è verificato un errore durante il salvataggio.')
          }
        });
    };

    let preview = $('#preview');
    preview.removeClass('d-none');
    preview.find('a').click(function (e) {
      e.preventDefault();
      saveForm($(this).data('schema'), $(this).data('target'), $(this).data('type'));
    });

    const storeSchema = function( schema ) {
      $("#formio_builder_render_form_schema").val(schema);
    };

    Formio.icons = "fontawesome";
    Formio.builder(document.getElementById("builder"), $('#formio').data('formserver_url') + "/form/" + $("#formio_builder_render_form_id").val(), {
      language: 'it',
      i18n: formIoI18n,
      builder: {
        basic: false,
        advanced: false,
        data: false,
        layout: false,
        premium: false,
        resource:false,
        customBasic: {
          title: 'Componenti',
          default: true,
          weight: 0,
          components: {
            textfield: true,
            textarea: true,
            checkbox: true,
            number: true,
            select:true,
            radio:true,
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
            file: true,
            financial_report: true,
            address:true,
            survey:true
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
            datagrid:true,
            well:true,
            panel:true,
            editgrid:true,
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
  if ($("#payment_data_flow_service_step").length) {

    const paymentRequiredField = $('#payment_data_payment_required');

    let paymentTypeHelp = function (type) {
      $("#payment-type-help").remove();
      if (type == 1) {
        paymentRequiredField.closest('.form-group').append('<small id="payment-type-help" class="d-block m-2 text-muted">Il pagamento immediato viene richiesto dopo aver confermato la volontà di inviare una pratica, se non effettuato la pratica resta in stato "da pagare", è visibile agli operatori ma non può essere presa in carico.</small>');
      } else if (type == 2) {
        paymentRequiredField.closest('.form-group').append('<small id="payment-type-help" class="d-block m-2 text-muted">Il pagamento posticipato viene richiesto dopo aver inviato la pratica, gli operatori potranno approvare la pratica impostando un importo da pagare. Solo dopo l\'approvazione sarà richiesto il pagamento.</small>');
      }
    };

    if (!paymentRequiredField.val()) {
      $('#payment_data_total_amounts').attr('disabled', 'disabled');
      $('#payment_data_gateways').find('input[type="checkbox"]').attr('disabled', 'disabled');
    }
    paymentTypeHelp(paymentRequiredField.val());

    paymentRequiredField.change(function() {
      if($(this).val() == 0) {
        $('#payment_data_total_amounts').attr('disabled', 'disabled');
        $('#payment_data_gateways').find('input[type="checkbox"]').attr('disabled', 'disabled');
      } else {
        $('#payment_data_total_amounts').removeAttr('disabled');
        $('#payment_data_gateways').find('input[type="checkbox"]').removeAttr('disabled');
      }
      paymentTypeHelp($(this).val());
    });

    $('#payment_data_gateways').find('input[type="checkbox"]').each(function(){
      if(this.checked) {
        $('#payment_data_' + $(this).val()).removeClass('d-none');
        $('#payment_data_' + $(this).val()).find('input').attr('required', 'required');
      }
    });

    $('#payment_data_gateways').find('input[type="checkbox"]').change(function() {
      if(this.checked) {
        $('#payment_data_' + $(this).val()).removeClass('d-none');
        $('#payment_data_' + $(this).val()).find('input').attr('required', 'required');
      } else {
        $('#payment_data_' + $(this).val()).addClass('d-none');
        $('#payment_data_' + $(this).val()).find('input').removeAttr('required');
      }
    })
  }

  // Step Integrations data
  if ($("#integrations_data_flow_service_step").length) {
    $('#integrations_data_trigger').change(function() {
      if ($(this).val() == '0') {
        $('#integrations_data_action').attr('disabled', 'disabled');
      } else {
        $('#integrations_data_action').removeAttr('disabled');
      }
    })
  }

  // Protocol data
  if ($('#protocol_data_flow_service_step').length) {
    $('#protocol_data_protocol_required').change(function () {
      if(this.checked) {
        $('.protocollo_params').removeAttr('disabled');
      } else {
        $('.protocollo_params').attr('disabled', 'disabled');
      }
    })

    let protocolHandler = $('#protocol_data_protocol_handler');
    let setupProtocolSettings = function (){
      $('.protocollo_params').each(function( i, e ) {
        let element = $(e);
        if(element.hasClass(protocolHandler.val())) {
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
  if ($('#io_integration_data_flow_service_step').length) {
    let service_id = $('#io_integration_data_io_service_parameters_IOserviceId');
    let primary_key = $('#io_integration_data_io_service_parameters_primaryKey');
    let secondary_key = $('#io_integration_data_io_service_parameters_secondaryKey');

    if (service_id.val() && primary_key.val() && secondary_key.val()) {
      switchTest(true)
    } else {
      switchTest(false)
    }

    $('#form_io_send_test').click(function (){
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
        success: function(data) {
          $("#error_messages").append("<p class='text-success'><i class='fa fa-check-circle mr-2'></i>La notifica è stata inviata con successo (identificativo " + data.id + ")</p>");
        },
        error: function(data) {
          $("#error_messages").append(
              "<p class='text-danger'><i class='fa fa-exclamation-circle mr-2'></i>la notifica NON è stata inviata a causa dell'errore:<ul class='list-unstyled text-danger'><li>" + data.responseJSON.error + "</li></ul></p>"
          );
        }
      });
    })

    service_id.change(function(){
      if (!service_id.val()) {
        switchTest(false)
      } else if (secondary_key.val() && primary_key.val()) {
        switchTest(true)
      }
    })
    primary_key.change(function(){
      if (!primary_key.val()) {
        switchTest(false)
      }
      else if (service_id.val() && secondary_key.val()) {
        switchTest(true)
      }
    })
    secondary_key.change(function(){
      if (!secondary_key.val()) {
        switchTest(false)
      } else if (service_id.val() && primary_key.val()) {
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
});
