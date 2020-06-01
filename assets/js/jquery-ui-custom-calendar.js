import datepickerFactory from 'jquery-datepicker';
import datepickerITFactory from 'jquery-datepicker/i18n/jquery.ui.datepicker-it';

datepickerFactory($);
datepickerITFactory($);


$(document).ready(function ($) {
  //only for profile
  $(".sdc-datepicker").datepicker({
    changeMonth: true,
    changeYear: true,
    yearRange: '1900:2025',
    dateFormat: 'dd-mm-yy',
    firstDay: 1,
  });
  $.datepicker.regional['it'];

});

