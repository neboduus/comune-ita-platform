import '../../core';
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';
import axios from "axios";

$(document).ready(function () {

  var calendars_integration_checkbox = $('.operatori_calendars_index');
  if (calendars_integration_checkbox.prop('checked')) {
    $('#linkable_application_meetings').show();
  } else {
    $('#ente_linkable_application_meetings').prop("checked", false);
    $('#linkable_application_meetings').hide();
  }

  calendars_integration_checkbox.change(function () {
    if (calendars_integration_checkbox.prop('checked')) {
      $('#linkable_application_meetings').show();
    } else {
      $('#ente_linkable_application_meetings').prop("checked", false);
      $('#linkable_application_meetings').hide();
    }
  });

  var io_checkbok = $('#ente_io_enabled');
  if (io_checkbok.prop('checked')) {
    $('#io_helper').show();
  } else {
    $('#io_helper').hide();
  }

  io_checkbok.change(function () {
    if (io_checkbok.prop('checked')) {
      $('#io_helper').show();
    } else {
      $('#io_helper').hide();
    }
  });

  $('.external-pay-choice').each((i, e) => {
    const gatewayIdentifier = $(e).data('identifier');
    const tenantId = $(e).data('tenant');
    const url = $(e).data('url') + '/tenants/' + tenantId;
    const $gatewaySettingsContainer = $( '<div id="ente_'+ gatewayIdentifier +'" class="gateway-form-type"></div>' );
    let settings = {
      "id": tenantId
    }
    // Creo l'elemento a cui appendere il form
    $(e).parent('div.form-check').append($gatewaySettingsContainer);

    $.ajax({
      url: url,
      dataType: 'json',
      type: 'get',
      crossDomain: true,
      success: function (result) {
        Formio.createForm(document.getElementById('ente_' + gatewayIdentifier), result.schema, {
          noAlerts: true,
          buttonSettings: {showCancel: false},
        })
          .then(function (form) {
            if (result.data) {
              settings = result.data;
            }
            form.submission = {
              data: settings
            };
            form.nosubmit = true;
            form.on('submit', function (submission) {
              axios.put(url, JSON.stringify(submission.data), {
                headers: {
                  'Content-Type': 'application/json'
                }
              })
                .then(function (reponse) {
                  if (reponse.data.errors) {
                    console.log(response)
                  } else {
                    form.emit('submitDone', submission)
                  }
                });
            });
          });
      },
      error: function (xmlhttprequest, textstatus, message) {
        // error logging
        console.log(message);
      }
    });
  });

  // Payment gateways
  $('#ente_gateways').find('input[type="checkbox"]').change(function () {
    if (this.checked) {
      $('#ente_' + $(this).val()).removeClass('d-none');
    } else {
      $('#ente_' + $(this).val()).addClass('d-none');
    }
  })
  $('#ente_gateways').find('input[type="checkbox"]').trigger('change');


// Mailers
  $('#add-mailer').click(function (e) {
    e.preventDefault();
    let list = $('#current-mailers');
    // Try to find the counter of the list or use the length of the list
    let counter = list.data('widget-counter') || list.children().length;

    if ($('#no-mailers').length) {
      $('#no-mailers').remove();
    }

    // grab the prototype template
    let newWidget = $('#mailer-item-template').text();
    // replace the "__name__" used in the id and name of the prototype
    // with a number that's unique to your emails
    // end name attribute looks like name="contact[emails][2]"
    newWidget = newWidget.replace(/__name__/g, new Date().getTime());
    // Increase the counter
    counter++;
    // And store it, the length cannot be used if deleting widgets is allowed
    list.data('widget-counter', counter);

    // create a new list element and add it to the list
    let newElem = $(list.attr('data-widget-mailer')).html(newWidget);
    console.log(newElem);
    newElem.appendTo(list);
  });

  $("#current-mailers").on("click", "a.js-remove-mailer", function (e) {
    e.preventDefault();
    $(this).closest('.js-mailer-item').remove();

    if ($('.js-mailer-item').length === 0) {
      $('#current-mailers').append('<p class="text-info" id="no-mailers"><i class="fa fa-info-circle"></i> Non sono ancora stati impostati mailer per l\'ente, i messaggi verranno inviati via e-mail dall\'indirizzo di default del sistema</p>');
    }
  });

  let url = location.href.replace(/\/$/, "");

  if (location.hash) {
    const hash = url.split("#");
    $('#myTab a[href="#' + hash[1] + '"]').tab("show");
    url = location.href.replace(/\/#/, "#");
    history.replaceState(null, null, url);
    setTimeout(() => {
      $(window).scrollTop(0);
    }, 400);
  }

  $('a[data-toggle="tab"]').on("click", function () {
    let newUrl;
    const hash = $(this).attr("href");
    newUrl = url.split("#")[0] + hash;
    history.replaceState(null, null, newUrl);
  });
})
;
