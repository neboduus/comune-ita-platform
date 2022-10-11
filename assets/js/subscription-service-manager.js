import moment from "moment";
import {TextEditor} from "./utils/TextEditor";
const lang = document.documentElement.lang.toString();
moment.locale(lang)

$('.edit-subscription-modal').on('show.bs.modal', function (event) {
  $(this).find('.error').hide();
  $(this).find('.error ul').empty();

  let triggerElement = $(event.relatedTarget); // Button that triggered the modal
  let modalBtn = $(this).find('.action-btn');
  modalBtn.attr('data-url', triggerElement.attr('data-url'));
  modalBtn.attr('data-id', triggerElement.attr('data-id'));
  modalBtn.attr('data-redirect', triggerElement.attr('data-redirect'));

  if (triggerElement.attr('data-subscription-service-id')) {
    $('#new-subscription-service').val(triggerElement.attr('data-subscription-service-id')).change();
  }
});

$('#searchModal').on('show.bs.modal', function () {
  $('#search-results-error').hide();
  $('div#search-results').empty();
  $('div#search-results-error').hide();
  $("#search-subscriber").val('');
});

function onTypeChange(el) {
  let value = $(el).find('input:checked').val()

  let createDraft = $(el).parent().find('.create-draft-input').first();
  let dueDate = $(el).parent().find('.due-date input');
  // Show create draft application if additional fee

  if (value === 'additional_fee'){
    createDraft.closest('.create-draft-option').removeClass('d-none');
  } else {
    createDraft.closest('.create-draft-option').addClass('d-none');
    createDraft.prop("checked", false);
  }
  // Disable payment due date and autocomplete with subscriptions due date
  if (value === 'subscription_fee'){
    dueDate.val($('#App_subscriptionservice_subscription_end').val());
    dueDate.attr('readonly', true);
  } else {
    if (dueDate.attr('readonly')) {
      // Clear value if readonly
      dueDate.val("");
    }
    dueDate.attr('readonly', false);
  }
}

$(document).on("change", ".radio-type", function(){
  onTypeChange(this)
});

$(document).ready(function () {

  // On subscriptions end date change, change all subription fee payments due date
  $('#App_subscriptionservice_subscription_end').on('change', function () {

    $('.due-date input').each(function (index, item) {
      let paymentItem = item.closest('.js-payment-item');
      if ($(paymentItem).find('.type_subscription_fee').first().prop('checked')) {
        $(item).val($('#App_subscriptionservice_subscription_end').val());
      }
    });
  })

  $('.edit-btn-modal').on('click', function () {
    let url = $(this).attr('data-url');
    let redirectUrl = $(this).attr('data-redirect');

    let newSubscriptionService = $(`#new-subscription-service`).val();
    $(`div#spinner`).show();
    let modalEl = $('#edit-subscription-modal');
    modalEl.find('.error').hide();
    modalEl.find('.error ul').empty();

    $.ajax({
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${$('#token').attr('data-token')}`
      },
      url:  url,
      type: "PATCH",
      data: JSON.stringify({
        "subscription_service": `${newSubscriptionService}`
      }),
      success: function() {
        $('div#spinner').hide()
        window.location.replace(redirectUrl);
      },
      error: function(response) {
        $(`div#spinner`).hide()
        let data = response.responseJSON;
        if (data.errors) {
          $.each(data.errors, function (index) {
            modalEl.find('.error ul').append(`<li>${data.errors[index]}</li>`);
          });
        }
        modalEl.find('.error').show();
      }
    });
  });

  $('.toggle-status-btn-modal').on('click', function () {
    let url = $(this).attr('data-url');
    let redirectUrl = $(this).attr('data-redirect');
    let newStatus = $(this).attr('data-status');

    $(`div#spinner`).show();
    let modalEl = $('#edit-subscription-modal');
    modalEl.find('.error').hide();
    modalEl.find('.error ul').empty();

    $.ajax({
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${$('#token').attr('data-token')}`
      },
      url:  url,
      type: "PATCH",
      data: JSON.stringify({
        "status": newStatus
      }),
      success: function() {
        $('div#spinner').hide()
        window.location.replace(redirectUrl);
      },
      error: function(response) {
        $(`div#spinner`).hide()
        let data = response.responseJSON;
        if (data.errors) {
          $.each(data.errors, function (index) {
            modalEl.find('.error ul').append(`<li>${data.errors[index]}</li>`);
          });
        }
        modalEl.find('.error').show();
      }
    });
  });

  $('.delete-btn-modal').on('click', function () {
    let url = $(this).attr('data-url');
    let redirectUrl = $(this).attr('data-redirect');

    $(`div#spinner`).show();
    let modalEl = $('#delete-subscription-modal');
    modalEl.find('.error').hide();
    modalEl.find('.error ul').empty();

    $.ajax({
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${$('#token').attr('data-token')}`
      },
      url: url,
      type: "DELETE",
      success: function() {
        $('div#spinner').hide()
        window.location.href = redirectUrl;
      },
      error: function(response) {
        $(`div#spinner`).hide()
        let data = response.responseJSON;
        if (data.errors) {
          $.each(data.errors, function (index) {
            modalEl.find('.error ul').append(`<li>${data.errors[index]}</li>`);
          });
        }
        modalEl.find('.error').show();
      }
    });
  });

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

  $('.select-wrapper.select-payment-wrapper').hide();
  $('#modal-service-error').hide();
  $('#modal-payment-error').hide();

  TextEditor.init()

  $('#modal_select_service').on('change', function () {
    $('#modal_copy').attr('disabled', true);
    let select = $('.select-wrapper.select-payment-wrapper');
    select.options = [];
    select.hide();
    if (!this.value) {
      return
    }
    $('#spinner').show();
    $('#modal-service-error').hide();
    let explodedPath = window.location.pathname.split("/");
    $.ajax(location.origin + '/' + explodedPath[1] + '/api/subscription-services/' + this.value + '/payment-settings',
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
            select.setOptionsToSelect(options);
            select.show();
          } else {
            $('#modal-service-error').show();
          }
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        },
        complete: function () {
          $('#spinner').hide();
        }
      });
  })

  $('#modal_select_payment').on('change', function () {
    if (!this.value) {
      return
    }
    $('#spinner').show();
    $('#modal-payment-error').hide();
    let explodedPath = window.location.pathname.split("/");
    $.ajax(location.origin + '/' + explodedPath[1] + '/api/subscription-services/' + $('#modal_select_service').val() + '/payment-settings?identifier=' + this.value,
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          if (data && data.length>0) {
            $('#payment_identifier').attr('data-payment', JSON.stringify(data[0]));
            $('#modal_copy').attr('disabled', false);
          } else {
            $('#modal-payment-error').show();
          }
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
        },
        complete: function () {
          $('#spinner').hide();
        }
      });
  })

  $('#importPaymentModal').on('shown.bs.modal', function (event) {
    $('#payment_identifier').val(event.relatedTarget.dataset.identifier)
  })

  $('#modal_import_payments').on('hidden.bs.modal', function () {
    $('#select_payment').val("").change()
  })

  $('#select_payment').on('change', function () {
    let btn = $('#import_payments_btn');
    if ($(this).val()) {
      btn.attr('disabled', false);
    } else {
      btn.attr('disabled', true);
    }
  })

  $('#importPaymentModal').on('hidden.bs.modal', function () {
    $('#modal_select_service').val("").change()
    $('#modal_select_payment').val("").change();
    $('#modal_copy').attr('disabled', true);
  })

  $('#modal_copy').on('click', function () {
    let identifier_el =  $('#payment_identifier');
    let identifier = identifier_el.val();
    let data = identifier_el.attr('data-payment');
    if (data) {
      data = JSON.parse(data);
        $(`#App_subscriptionservice_subscription_payments_${identifier}_amount`).val(data["amount"])
        $(`#App_subscriptionservice_subscription_payments_${identifier}_payment_identifier`).val(data["payment_identifier"])
        $(`#App_subscriptionservice_subscription_payments_${identifier}_payment_reason`).val(data["payment_reason"])
        $(`#App_subscriptionservice_subscription_payments_${identifier}_payment_service`).val(data["payment_service"]).change()
        $(`#App_subscriptionservice_subscription_payments_${identifier}_create_draft`).prop('checked', data["create_draft"])
        $(`#App_subscriptionservice_subscription_payments_${identifier}_meta`).text(JSON.stringify(data["meta"]))
        $(`#App_subscriptionservice_subscription_payments_${identifier}_type`).find(`.type_${data["type"]}`).first().prop('checked', true).change();
        $(`#App_subscriptionservice_subscription_payments_${identifier}_date`).val(moment(data["date"]).format("YYYY-MM-DD"))
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
    newWidget = newWidget.replace(/__code__/g, $('#App_subscriptionservice_code').val());
    newWidget = newWidget.replace(/__counter__/g, counter);
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
