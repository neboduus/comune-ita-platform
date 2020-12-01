require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module

$(document).ready(function () {


  $('#ente_gateways').find('input[type="checkbox"]').each(function () {
    if (this.checked) {
      $('#ente_' + $(this).val()).removeClass('d-none');
    }
  });

  $('#ente_gateways').find('input[type="checkbox"]').change(function () {
    if (this.checked) {
      $('#ente_' + $(this).val()).removeClass('d-none');
    } else {
      $('#ente_' + $(this).val()).addClass('d-none');
    }
  })


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
      $('#myTab a[href="#'+hash[1]+'"]').tab("show");
      url = location.href.replace(/\/#/, "#");
      history.replaceState(null, null, url);
      setTimeout(() => {
        $(window).scrollTop(0);
      }, 400);
    }

    $('a[data-toggle="tab"]').on("click", function() {
      let newUrl;
      const hash = $(this).attr("href");
        newUrl = url.split("#")[0] + hash;
      history.replaceState(null, null, newUrl);
    });
});
