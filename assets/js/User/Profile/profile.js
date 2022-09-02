import "jquery-ui/ui/widgets/datepicker"
import {i18nDatepicker} from "../../translations/i18n-datepicker";

const $language = document.documentElement.lang.toString();

$(document).ready(function ($) {
  //only for profile
  $(".sdc-datepicker").datepicker({
    changeMonth: true,
    changeYear: true,
    yearRange: '1900:2025',
    dateFormat: 'dd-mm-yy',
    firstDay: 1,
  });
  // override default values calendar
  $.datepicker.regional[$language] = i18nDatepicker[$language]
  $.datepicker.setDefaults($.datepicker.regional[$language]);



  $('#form_save').on('click', function (element, child) {
    var form = document.querySelector('form')
    var errorsFieldForm = [];

    function createListErr(ulList) {
      errorsFieldForm.forEach(el => {
          var listItem = $('<li></li>').text(el);
          listItem.appendTo(ulList)
        }
      )
    }

    form.addEventListener('invalid', (e) => {
      if (e.target) {
        errorsFieldForm.push(e.target.previousSibling.innerText)
      }

      if ($('.alert.alert-danger').length > 0) {
        $("#edit_user_profile").find('.alert.alert-danger').remove()
        $('#edit_user_profile').prepend("<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">\n" +
          "  {{ 'operatori.profile.errore_dati' | trans | raw }}:\n" +
          "<ul class='list-error'>" +
          "</ul>" +
          "  <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">\n" +
          "    <span aria-hidden=\"true\">&times;</span>\n" +
          "  </button>\n" +
          "</div>"
        );
        createListErr($('ul.list-error'))

      } else {
        $('#edit_user_profile').prepend("<div class=\"alert alert-danger alert-dismissible fade show\" role=\"alert\">\n" +
          "  {{ 'operatori.profile.errore_dati' | trans | raw }}:\n" +
          "<ul class='list-error'>" +
          "</ul>" +
          "  <button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-label=\"Close\">\n" +
          "    <span aria-hidden=\"true\">&times;</span>\n" +
          "  </button>\n" +
          "</div>"
        );
        createListErr($('ul.list-error'))
      }
    }, true)
  });

  $('#copia_domicilio').on('click', (event) =>{
    copia_domicilio(event);
  })


  $('#copia_residenza').on('click', (event) =>{
    copia_residenza(event);
  })


  function copia_residenza(event) {
    event.preventDefault()
    $('#form_sdc_indirizzo_domicilio').val($('#form_sdc_indirizzo_residenza').val());
    $('#form_sdc_cap_domicilio').val($('#form_sdc_cap_residenza').val());
    $('#form_sdc_citta_domicilio').val($('#form_sdc_citta_residenza').val());
    $('#form_sdc_provincia_domicilio').val($('#form_sdc_provincia_residenza').val());
    $('[data-id=form_sdc_provincia_domicilio]').attr('title', $('#form_sdc_provincia_residenza').val());
    $('[data-id=form_sdc_provincia_domicilio]').find(".filter-option-inner-inner").html($('#form_sdc_provincia_residenza option:selected').text())
    $('#form_sdc_stato_domicilio').val($('#form_sdc_stato_residenza').val());
  }

  function copia_domicilio(event) {
    event.preventDefault()
    $('#form_sdc_indirizzo_residenza').val($('#form_sdc_indirizzo_domicilio').val());
    $('#form_sdc_cap_residenza').val($('#form_sdc_cap_domicilio').val());
    $('#form_sdc_citta_residenza').val($('#form_sdc_citta_domicilio').val());
    $('#form_sdc_provincia_residenza').val($('#form_sdc_provincia_domicilio').val());
    $('[data-id=form_sdc_provincia_residenza]').attr('title', $('#form_sdc_provincia_domicilio').val());
    $('[data-id=form_sdc_provincia_residenza]').find(".filter-option-inner-inner").html($('#form_sdc_provincia_domicilio option:selected').text())
    $('#form_sdc_stato_residenza').val($('#form_sdc_stato_domicilio').val());
  }

});



