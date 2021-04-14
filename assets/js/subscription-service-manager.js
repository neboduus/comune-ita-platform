require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module
import {TextEditor} from "./utils/TextEditor";



$(document).ready(function () {

  TextEditor.init()

  var prev_code;
  $('.code_edit').focus(function() {
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
