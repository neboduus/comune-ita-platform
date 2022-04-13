/* global $ */
import './Api';
import Swal from 'sweetalert2/src/sweetalert2.js'

class RequestIntegration {

  static hideDialog() {
      this.els.$triggerBtn.removeClass('d-none');
      this.els.$messagesList.removeClass('checkbox-enabled');
      this.els.$dialog.addClass('d-none');
      this.els.$applicationStuff.removeClass('d-none');
  }

  static showDialog() {
      this.els.$triggerBtn.addClass('d-none');
      this.els.$messagesList.addClass('checkbox-enabled');
      this.els.$dialog.removeClass('d-none');
      this.els.$applicationStuff.addClass('d-none');
  }

  static getSelectedMessages() {
    let selectedMessages = [];
    $('.checkbox-message:checked').each((i, e) => {
      selectedMessages.push($(e).data('message-id'));
    });
    return selectedMessages;
  }

  static submitIntegration() {
    const self = this;
    let data = this.getSelectedMessages();
    console.log(data);
    self.els.$submitBtn.html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> Salvataggio...');

    $.ajax({
      url: self.els.$triggerBtn.data('url'),
      dataType: 'json',
      cache: false,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${$('#hidden').data('token')}`
      },
      type: 'POST',
      data: data,
      success: function (response, textStatus, jqXhr) {
        Swal.fire({
          icon: 'success',
          title: 'Integrazioni accettate correttamente',
          text: 'Attendere che la pagina venga ricaricata.',
          showConfirmButton: false,
          //timer: 1500
        })
        window.location.reload();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        self.els.$submitBtn.html('Accetta integrazioni')
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Something went wrong!'
        })
      }
    });
  }

  static init() {

    this.els = {
      $triggerBtn: $('#accept-integration'),
      $dialog: $('#accept-integration-dialog'),
      $cancelBtn: $('#accept-integration-dialog .btn-danger'),
      $submitBtn: $('#accept-integration-dialog .btn-success'),
      $messagesList: $('.messages-list'),
      $applicationStuff: $('#application-stuff')
    }

    this.els.$triggerBtn.on('click', (e) => {
      this.showDialog();
    });

    this.els.$cancelBtn.on('click', (e) => {
      this.hideDialog();
    });

    this.els.$submitBtn.on('click', (e) => {
      this.submitIntegration('hide');
    });
  }

}

export default RequestIntegration;
