import '../../core';
import Swal from 'sweetalert2/src/sweetalert2.js';
import "@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css";
import BasePath from "../../utils/BasePath";
const $language = document.documentElement.lang.toString();

$('#submit_or_confirm_user_group').on('click', function (e) {
  let form = $(this).closest('form');
  let email = $('#user_group_coreContactPoint_email').val();
  let pec = $('#user_group_coreContactPoint_pec').val();
  let phoneNumber = $('#user_group_coreContactPoint_phoneNumber').val();
  if(!email && !pec && !phoneNumber) {
    Swal.fire({
      icon: 'warning',
      text: Translator.trans('user_group.confirm', {}, 'messages', $language),
      showCancelButton: true,
      confirmButtonText: Translator.trans('steps.common.conferma.si', {}, 'messages', $language),
      cancelButtonText: Translator.trans('steps.common.conferma.no', {}, 'messages', $language),
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        form.trigger('submit'); // submit the form
      }
    });
  } else {
    form.trigger('submit'); // submit the form
  }
});


const userGroupCalendar = $('#user_group_calendar');

userGroupCalendar.on('change', function () {
  if (!this.value){
    $('#new-calendar').addClass('d-none');
    $('#calendar-cards').addClass('d-none');
  } else if (this.value === 'crete_new_calendar'){
    $('#new-calendar').removeClass('d-none');
    $('#calendar-cards').addClass('d-none');
  } else {
    $('#new-calendar').addClass('d-none');

    const basePath = new BasePath().getBasePath()
    $.ajax( basePath + '/api/calendars/' + this.value,
      {
        method: "GET",
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          $('#calendar-cards').removeClass('d-none');
          $('#calendar-title').text(data.title);
          $('#opening-hours').html('');
          if (data.opening_hours.length > 0) {
            data.opening_hours.forEach(function (i){
              $('#opening-hours').append('<p>'+ i.name +'</p>')
            })
          }

          if (data.location) {
            $('#location-container').removeClass('d-none');
            $('#location-container p').text(data.location)
          } else {
            $('#location-container').addClass('d-none');
          }

          if (data.contact_email) {
            $('#contact-container').removeClass('d-none');
            $('#contact-container p').text(data.contact_email)
          } else {
            $('#location-container').removeClass('d-none');
          }

        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
         console.log(errorMessage)
        }
      }
    );
  }
});
userGroupCalendar.trigger('change');


