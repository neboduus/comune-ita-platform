import '../../core';
import Swal from 'sweetalert2/src/sweetalert2.js';
import "@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css";
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

$('#user_group_calendar').on('change', function () {
  console.log(this.value);
  if (!this.value){
    console.log('vuoto');
    $('#new_calendar').addClass('d-none');
    $('#calendar_cards').addClass('d-none');
  } else if (this.value === 'new'){
    console.log('new');
    $('#new_calendar').removeClass('d-none');
    $('#calendar_cards').addClass('d-none');
  } else {
    console.log('altro');
    $('#new_calendar').addClass('d-none');
    $('#calendar_cards').removeClass('d-none');
  }
});
