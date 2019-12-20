require('bootstrap-italia');
require('../css/app.scss');

require('webpack-jquery-ui');
require('webpack-jquery-ui/css');
require('webpack-jquery-ui/datepicker');




$(document).ready(function () {

  if ($('#summary_flow_formIO_step').length) {
    $('button.craue_formflow_button_class_next').on('click', function (e) {
      var $form = $(this).closest('form');
      e.preventDefault();
      $('#confirm .modal-body').html('Proseguendo la pratica non sarà più modificabile e verrà inviata all\'Ente non appena sarà ultimato il pagamento');
      $('#confirm').modal({backdrop: 'static', keyboard: false})
        .one('click', '#ok', function () {
          $form.trigger('submit'); // submit the form
        });
    });
  }


  $('button.craue_formflow_button_last').on('click', function (e) {
    var $form = $(this).closest('form');
    e.preventDefault();
    $('#confirm').modal({backdrop: 'static', keyboard: false})
    .one('click', '#ok', function () {
      $form.trigger('submit'); // submit the form
    });
  });


  $.datepicker.setDefaults($.datepicker.regional['it']);
// Datepicker
  $(".datepicker").datepicker({
    dateFormat: "dd-mm-yy",
    changeMonth: true,
    changeYear: true,
    yearRange: "-50:+10"
  });

// Range datepicker
  if ($(".datepicker-range-from").length && $(".datepicker-range-to").length) {
    var dateFormat = "dd-mm-yy",
      from = $(".datepicker-range-from")
      .datepicker({
        defaultDate: "0",
        dateFormat: dateFormat,
        changeMonth: true,
        changeYear: true
      })
      .on("change", function () {
        to.datepicker("option", "minDate", getDate(this));
      }),
      to = $(".datepicker-range-to").datepicker({
        defaultDate: "+1w",
        dateFormat: dateFormat,
        changeMonth: true,
        changeYear: true
      })
      .on("change", function () {
        from.datepicker("option", "maxDate", getDate(this));
      });

    function getDate(element) {
      var date;
      try {
        date = $.datepicker.parseDate(dateFormat, element.value);
      } catch (error) {
        date = null;
      }
      return date;
    }
  }

});
