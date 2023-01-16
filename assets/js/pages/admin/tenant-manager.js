import '../../core';
import Gateways from "../../rest/gateways/Gateways";
import GraphicAspectTenant from "../../utils/GraphicAspectTenant";

$(document).ready(function () {

  GraphicAspectTenant.init();

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


  if( $('#payments-tab').length > 0){
    Gateways.init();
  }

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

  $('#myTab a[data-toggle="tab"]').on("click", function () {
    let newUrl;
    const hash = $(this).attr("href");
    newUrl = url.split("#")[0] + hash;
    history.replaceState(null, null, newUrl);
  });


})
;
