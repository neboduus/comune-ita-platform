/* global $ */
import Swal from 'sweetalert2/src/sweetalert2.js'

class RequestIntegration {

  static hideDialog() {
      this.els.$triggerCancelBtn.removeClass('d-none');
      this.els.$triggerBtn.removeClass('d-none');
      this.els.$messagesList.removeClass('checkbox-enabled');
      this.els.$dialog.addClass('d-none');
      this.els.$applicationStuff.removeClass('d-none');
  }

  static showDialog() {
      this.els.$triggerCancelBtn.addClass('d-none');
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

  static cancelIntegration() {
    const self = this;
    self.els.$triggerCancelBtn.html('<i class="fa fa-circle-o-notch fa-spin fa-fw"></i> Annullo richiesta...');

    $.ajax({
      url: self.els.$triggerCancelBtn.data('url'),
      dataType: 'json',
      cache: false,
      headers: {
        'Accept': 'application/json',
        'Authorization': `Bearer ${$('#hidden').data('token')}`
      },
      type: 'POST',
      success: function (response, textStatus, jqXhr) {
        Swal.fire({
          icon: 'success',
          title: 'Richiesta integrazione annullata correttamente',
          text: 'Attendere che la pagina venga ricaricata.',
          showConfirmButton: false,
          //timer: 1500
        })
        window.location.reload();
      },
      error: function (jqXHR, textStatus, errorThrown) {
        console.log(errorThrown);
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Something went wrong!'
        })
      }
    });
  }

  static submitIntegration() {
    const self = this;

    let data = this.getSelectedMessages();
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
      $triggerCancelBtn: $('#cancel-integration'),
      $triggerBtn: $('#accept-integration'),
      $dialog: $('#accept-integration-dialog'),
      $cancelBtn: $('#accept-integration-dialog .btn-danger'),
      $submitBtn: $('#accept-integration-dialog .btn-success'),
      $messagesList: $('.messages-list'),
      $applicationStuff: $('#application-stuff')

    }

    this.els.$triggerCancelBtn.on('click', (e) => {
      e.preventDefault();
      this.cancelIntegration();
    });

    this.els.$triggerBtn.on('click', (e) => {
      e.preventDefault();
      this.showDialog();
    });

    this.els.$cancelBtn.on('click', (e) => {
      e.preventDefault();
      this.hideDialog();
    });

    this.els.$submitBtn.on('click', (e) => {
      e.preventDefault();
      $(e.currentTarget).prop('disabled', true);
      this.submitIntegration('hide');
    });
  }

}

export default RequestIntegration;
