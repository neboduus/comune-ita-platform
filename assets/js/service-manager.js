require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module
require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)
require("summernote");
require("summernote/dist/summernote-bs4.css")

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

    Formio.icons = "fontawesome";
    Formio.builder(document.getElementById("builder"), $('#formio').data('formserver_url') + "/form/" + $("#formio_builder_render_form_id").val(), {
      builder: {
        //premium: false
      }
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

});
