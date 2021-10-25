import moment from "moment";

require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module
import {TextEditor} from "./utils/TextEditor";


$(document).ready(function () {
  $('div#spinner').hide()
  $('div#search-results-error').hide()

  $('#search-btn').on('click', function () {
    let search = $('#search-subscriber').val()
    let url =  $(this).attr('data-url')
    $('div#spinner').show();
    $("div#search-results").empty();
    $('div#search-results-error').hide();

    $.ajax({
      url: `${url}?q=${search}`,
      type: "GET",
      success: function(response) {
        $('div#spinner').hide()
        $("div#search-results").append(response);
      },
      error: function() {
        $('div#spinner').hide()
        $("div#search-results-error").show();
      }
    });
  })

  $('.bootstrap-select-wrapper.select-payment-wrapper').hide();
  $('#modal-service-error').hide();
  $('#modal-payment-error').hide();

  TextEditor.init()

  $('#modal_select_service').on('change', function () {
    $('.bootstrap-select-wrapper.select-payment-wrapper').hide();
    $('#modal-service-error').hide();
    let explodedPath = window.location.pathname.split("/");
    $.ajax(location.origin + '/' + explodedPath[1] + '/api/subscription-services/' + this.value + '/payments',
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          if (data && data.length > 0) {
            let options = []
            data.forEach(function (item) {
              options.push({
                text: `${item.payment_reason} [${item.amount}€]` ,
                value: item.payment_identifier
              });
            })
            let select = $('.bootstrap-select-wrapper.select-payment-wrapper');
            select.setOptionsToSelect(options);
            select.show();
          } else {
            $('#modal-service-error').show();
          }
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        }
      });
  })

  $('#modal_select_payment').on('change', function () {
    $('#modal-payment-error').hide();
    let explodedPath = window.location.pathname.split("/");
    $.ajax(location.origin + '/' + explodedPath[1] + '/api/subscription-services/' + $('#modal_select_service').val() + '/payments?identifier=' + this.value,
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          if (data && data.length>0) {
            $('#payment_identifier').attr('data-payment', JSON.stringify(data[0]));
          } else {
            $('#modal-payment-error').show();
          }
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        }
      });
  })

  $('#importPaymentModal').on('shown.bs.modal', function (event) {
    $('#payment_identifier').val(event.relatedTarget.dataset.identifier)
  })

  $('#modal_copy').on('click', function () {
    let identifier_el =  $('#payment_identifier');
    let identifier = identifier_el.val();
    let data = identifier_el.attr('data-payment');
    if (data) {
      data = JSON.parse(data);
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_date`).attr('value', moment(data["date"]).format("YYYY-MM-DD"))
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_amount`).attr('value', data["amount"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_payment_identifier`).attr('value', data["payment_identifier"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_payment_reason`).attr('value', data["payment_reason"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_payment_service`).attr('value', data["payment_service"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_required`).attr('checked', data["required"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_create_draft`).attr('checked', data["create_draft"])
      $(`#appbundle_subscriptionservice_subscription_payments_${identifier}_meta`).text(JSON.stringify(data["meta"]))
    }
  })

  var prev_code;
  $('.code_edit').focus(function () {
    prev_code = $(this).val();
  }).change(function () {
    if (!confirm("Modificare il codice del servizio a sottoscrizione potrebbe causare errori oppure pratiche di pagamento duplicate qualora siano configurati dei pagamenti schedulati. Si raccomanda di non modificare questo valore in prossimità della scadenza di un pagamento. Sei sicuro di voler procedere?")) {
      $(this).val(prev_code);
      return false;
    }
  })

  $('.add-another-payment-widget').click(function (e) {
    let list = $($(this).attr('data-list-selector'));
    // Try to find the counter of the list or use the length of the list
    let counter = list.data('widget-counter') || list.children().length;

    if ($('#no-payments').length) {
      $('#no-payments').remove();
    }

    // grab the prototype template
    let newWidget = list.attr('data-prototype');
    // replace the "__name__" used in the id and name of the prototype
    // with a number that's unique to your emails
    // end name attribute looks like name="contact[emails][2]"
    newWidget = newWidget.replace(/__name__/g, new Date().getTime());
    // Increase the counter
    counter++;
    // And store it, the length cannot be used if deleting widgets is allowed
    list.data('widget-counter', counter);

    // create a new list element and add it to the list
    let newElem = $(list.attr('data-widget-payment')).html(newWidget);
    newElem.appendTo(list);
  });

  $("#payments").on("click", "a.js-remove-payment", function (e) {
    e.preventDefault();
    $(this).closest('.js-payment-item').remove();

    if ($('.js-payment-item').length == 0) {
      $('#payments').append('<div class="alert alert-info" id="no-payments">Non sono presenti pagamenti</div>');
    }

  });

  $('.copy').click(function (e) {
    e.preventDefault();
    let button = $(this);
    let temp = $("<input>");
    $("body").append(temp);
    temp.val(button.data('copy')).select();
    document.execCommand("copy");
    temp.remove();
  })
});
