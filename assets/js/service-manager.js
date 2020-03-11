require('bootstrap');
require('bootstrap-select');
require('svgxuse');
require('bootstrap-italia/src/js/plugins/polyfills/array.from');
require('bootstrap-italia/src/js/plugins/circular-loader/CircularLoader-v1.3');
require('bootstrap-italia/src/js/plugins/password-strength-meter/password-strength-meter');
//require('bootstrap-italia/src/js/plugins/datepicker/locales/it');
//require('bootstrap-italia/src/js/plugins/datepicker/datepicker');
require('bootstrap-italia/src/js/plugins/i-sticky/i-sticky');
require('bootstrap-italia/src/js/plugins/sticky-header');
require('bootstrap-italia/src/js/plugins/sticky-wrapper');
require('bootstrap-italia/src/js/plugins/ie');
require('bootstrap-italia/src/js/plugins/fonts-loader');
require('bootstrap-italia/src/js/plugins/autocomplete');
require('bootstrap-italia/src/js/plugins/back-to-top');
require('bootstrap-italia/src/js/plugins/componente-base');
require('bootstrap-italia/src/js/plugins/cookiebar');
require('bootstrap-italia/src/js/plugins/dropdown');
require('bootstrap-italia/src/js/plugins/forms');
require('bootstrap-italia/src/js/plugins/track-focus');
require('bootstrap-italia/src/js/plugins/forward');
require('bootstrap-italia/src/js/plugins/navbar');
require('bootstrap-italia/src/js/plugins/navscroll');
require('bootstrap-italia/src/js/plugins/history-back');
require('bootstrap-italia/src/js/plugins/notifications');
require('bootstrap-italia/src/js/plugins/upload');
require('bootstrap-italia/src/js/plugins/progress-donut');
require('bootstrap-italia/src/js/plugins/list');
require('bootstrap-italia/src/js/plugins/imgresponsive');
require('bootstrap-italia/src/js/plugins/timepicker');
require('bootstrap-italia/src/js/plugins/input-number');
//require('bootstrap-italia/src/js/plugins/carousel');
require('bootstrap-italia/src/js/plugins/transfer');
require('bootstrap-italia/src/js/plugins/select');
//require('bootstrap-italia/src/js/plugins/custom-select');
require('bootstrap-italia/src/js/plugins/rating');
require('bootstrap-italia/src/js/plugins/dimmer');

require("../css/app.scss");
require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
require("summernote");
require("summernote/dist/summernote-bs4.css")

import Calendar from './Calendar';
import PageBreak from './PageBreak';
import 'formiojs'

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('pagebreak', PageBreak);

$(document).ready(function () {

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

  if ($("#general_data_flow_service_step").length) {
    $('textarea').summernote({
      toolbar: [
        ['style', ['style']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['codeview']],
      ]
    });
  }

  // Step Form Fields
  if ($("#formio_builder_render_form_id").length) {

    let preview = $('#preview');
    preview.removeClass('d-none');
    preview.find('a').click(function (e) {
      let printUrl = $(this).data('print');
      e.preventDefault();
      $.ajax($(this).data('schema'),
        {
          dataType: 'json', // type of response data
          method: 'POST',
          data: {
            schema: JSON.parse($("#formio_builder_render_form_schema").val())
          },
          success: function (data, status, xhr) {   // success callback function
            window.location.href = printUrl;
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            console.log(errorMessage);
          }
        });
    });

    Formio.icons = "fontawesome";
    Formio.builder(document.getElementById("builder"), $('#formio').data('formserver_url') + "/form/" + $("#formio_builder_render_form_id").val(), {
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
            calendar: true
          }
        },
        customLayout: {
          title: 'Layout',
          default: false,
          weight: 0,
          components: {
            htmlelement: true,
            columns: true,
            pagebreak: true
          }
        },
      },
    }).then(function (builder) {

      // Inserisco lo schema in un input hidden
      $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))

      builder.on("updateComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

      // Inserisco lo schema in un input hidden sulla modifica di un componente
      builder.on("editComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

      // Inserisco lo schema in un input hidden sul salvataggio di un componente
      builder.on("saveComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

      // Inserisco lo schema in un input hidden su aggiunta di un componente
      builder.on("addComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

      // Inserisco lo schema in un input hidden su rimozione di un componente
      builder.on("removeComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

    });

  }



  // Step Payment data
  if ($("#payment_data_flow_service_step").length) {


    if (!$('#payment_data_payment_required').prop('checked')) {
      $('#payment_data_total_amounts').attr('disabled', 'disabled');
      $('#payment_data_gateways').find('input[type="checkbox"]').attr('disabled', 'disabled');
    }


    $('#payment_data_payment_required').change(function() {
      if(this.checked) {
        $('#payment_data_total_amounts').removeAttr('disabled');
        $('#payment_data_gateways').find('input[type="checkbox"]').removeAttr('disabled');
      } else {
        $('#payment_data_total_amounts').attr('disabled', 'disabled');
        $('#payment_data_gateways').find('input[type="checkbox"]').attr('disabled', 'disabled');
      }
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

  // Step Payment data
  if ($("#integrations_data_flow_service_step").length) {
    $('#integrations_data_trigger').change(function() {
      if ($(this).val() == '0') {
        $('#integrations_data_action').attr('disabled', 'disabled');
      } else {
        $('#integrations_data_action').removeAttr('disabled');
      }
    })
  }

});
