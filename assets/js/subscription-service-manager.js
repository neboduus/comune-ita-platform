require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module
require("summernote");
require("summernote/dist/summernote-bs4.css");


$(document).ready(function () {

  $('textarea').summernote({
    toolbar: [
      ['style', ['style']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['insert', ['link']],
      ['view', ['codeview']],
    ]
  });

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
});
