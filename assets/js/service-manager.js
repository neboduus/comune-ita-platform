require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module
require("jsrender")();    // Load JsRender as jQuery plugin (jQuery instance as parameter)


$(document).ready(function () {
  const news = $('#news');
  const deadlines = $('#deadlines');
  news.html($.templates("#tpl-news-spinner").render({}));
  $.get(news.data('url'), function (data) {
    if (data.length > 0) {
      news.parent().css({'max-height': '300px', 'overflow-y': 'scroll', 'overflow-x': 'hidden'});
      news.html($.templates("#tpl-news").render(data));
    } else {
      news.parent().removeAttr('style');
      news.html($.templates("#tpl-news-empty").render({}));
    }
  });
  deadlines.html($.templates("#tpl-deadlines-spinner").render({}));
  $.get(deadlines.data('url'), function (data) {
    if (data.length > 0)
      deadlines.html($.templates("#tpl-deadlines").render(data));
    else
      deadlines.html($.templates("#tpl-deadlines-empty").render({}));
  });
});

$(document).ready(function () {

  // Step Template form
  if ($("#formio_template_form_id").length) {
    const formioTemplatesContainer = $("#formio-templates-container");
    formioTemplatesContainer.parent().removeClass('d-none');

    formioTemplatesContainer.append($.templates("#tpl-form").render({
      _id: 'new',
      title: 'Crea nuovo form',
      description: 'Crea nuovo form  da template vuoto',
    }));

    $.get("https://formserver.opencontent.it/form?exclude_tags=component", function (data) {
      if (data.length > 0) {
        $.each(data, function (index, value) {
          //cl.$root.find('#multiplier_detail tbody').append(cl._prepareBetRow(value));
          /*if (value.tags.includes('basic') || value.tags.includes('custom')) {
            formioTemplatesContainer.append($.templates("#tpl-form").render(value));
          }*/
          formioTemplatesContainer.append($.templates("#tpl-form").render(value));
        });
        if ($("#formio_template_form_id").val()) {
          $('#' + $("#formio_template_form_id").val()).addClass('card-bg-success');
        }
      }
    }).always(function () {
      $('.formio-template').click(function (e) {
        $('.card-bg-success').removeClass('card-bg-success');
        $(this).addClass('card-bg-success');
        $("#formio_template_form_id").val($(this).data('id'));
      })
    });
  }

  // Step Form Fields
  if ($("#formio_builder_render_form_id").length) {

    Formio.icons = "fontawesome";
    //Formio.createForm(document.getElementById("formio"), "https://examples.form.io/example")

    Formio.builder(document.getElementById("builder"), "https://formserver.opencontent.it/form/" + $("#formio_builder_render_form_id").val(), {
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

      // Inserisco lo schema in un input hidden sul salvataggio di un componente
      builder.on("addComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });

      // Inserisco lo schema in un input hidden sul salvataggio di un componente
      builder.on("removeComponent", function () {
        $("#formio_builder_render_form_schema").val(JSON.stringify(builder.schema))
      });
    });
  }
});
