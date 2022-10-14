import 'summernote';
import 'summernote/dist/summernote-bs4.css';
import './utils/TextEditor';

$(document).ready(function () {

  const language = document.documentElement.lang.toString();

  $('textarea').summernote({
    toolbar: [
      ['style', ['style']],
      ['para', ['ul', 'ol', 'paragraph']],
      ['insert', ['link']],
      ['view', ['codeview']],
    ]
  });

  $('.add-another-closing_period-widget').click(function (e) {
    let list = $($(this).attr('data-list-selector'));
    // Try to find the counter of the list or use the length of the list
    let counter = list.data('widget-counter') || list.children().length;

    if ($('#no-closing_periods').length) {
      $('#no-closing_periods').remove();
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
    let newElem = $(list.attr('data-widget-closing_period')).html(newWidget);
    newElem.appendTo(list);
  });

  $("#closing_periods").on("click", "a.js-remove-closing_period", function (e) {
    e.preventDefault();
    $(this).closest('.js-closing_period-item').remove();

    if ($('.js-closing_period-item').length === 0) {
      $('#closing_periods').append(`<div class="alert alert-info" id="no-closing_periods">${Translator.trans('backoffice.integration.calendars.no_closure_days', {}, 'messages', language)}</div>`);
    }
  });

  $('.add-another-opening_hour-widget').click(function (e) {
    let list = $($(this).attr('data-list-selector'));
    // Try to find the counter of the list or use the length of the list
    let counter = list.data('widget-counter') || list.children().length;

    if ($('#no-opening_hours').length) {
      $('#no-opening_hours').remove();
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
    let newElem = $(list.attr('data-widget-opening_hour')).html(newWidget);
    newElem.appendTo(list);
  });

  $("#opening_hours").on("click", "a.js-remove-opening_hour", function (e) {
    e.preventDefault();
    $(this).closest('.js-opening_hour-item').remove();

    if ($('.js-opening_hour-item').length === 0) {
      $('#no-opening_hours').append(`<div class="alert alert-info" id="no-opening_hours">${Translator.trans('calendars.opening_hours.no_opening_hours', {}, 'messages', language)}</div>`);
    }
  });

  $('.add-another-external_calendar-widget').click(function (e) {
    let list = $($(this).attr('data-list-selector'));
    // Try to find the counter of the list or use the length of the list
    let counter = list.data('widget-counter') || list.children().length;

    if ($('#no-external_calendars').length) {
      $('#no-external_calendars').remove();
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
    let newElem = $(list.attr('data-widget-external_calendar')).html(newWidget);
    newElem.appendTo(list);
  });

  $("#external_calendars").on("click", "a.js-remove-external_calendar", function (e) {
    e.preventDefault();
    $(this).closest('.js-external_calendar-item').remove();

    if ($('.js-external_calendar-item').length === 0) {
      $('#no-external_calendars').append(`<div class="alert alert-info" id="no-external_calendars">${Translator.trans('backoffice.integration.calendars.no_external_calendar', {}, 'messages', language)}</div>`);
    }
  });

  $('.clone').click(function (e) {
    e.preventDefault()
    let button = $(this)
    let temp = $("<input>")
    $("body").append(temp)
    temp.val($('#App_calendar_id').val()).select()
    document.execCommand("copy")
    button.find('span').text(`${Translator.trans('copied_id', {}, 'messages', language)}`)
    temp.remove();
    setTimeout(function () {
      button.find('span').text(`${Translator.trans('copy_id', {}, 'messages', language)}`)
    }, 2000);
  })
});
