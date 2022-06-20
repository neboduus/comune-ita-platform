
import {i18n} from "../translations/i18n"
import Api from "../services/api.service";

class ApplicationsMessage {

  $apiService; // API Class
  $modalSubmitButton // Html Element Submit modal button
  $token; // Auth token
  $applicationId; // Application id
  $spinner // Html Element spinner
  $language; // Browser language

  static init() {

    ApplicationsMessage.$apiService = new Api();
    ApplicationsMessage.$modalSubmitButton = $('#change_paid_application');
    ApplicationsMessage.$applicationId = $('#change_paid_modal').data('id');
    ApplicationsMessage.$spinner = $('.progress-indeterminate');
    ApplicationsMessage.$language = document.documentElement.lang.toString();

    ApplicationsMessage.$modalSubmitButton.on('click', () =>{
      ApplicationsMessage.$spinner.addClass('progress');
      ApplicationsMessage.$modalSubmitButton.attr('disabled','disabled')
      // Get Auth token
      ApplicationsMessage.$apiService.getSessionAuthTokenPromise().then((data) => {
        ApplicationsMessage.$token = data.token
        if($('#note').val() !== ''){
          // Send new message
          ApplicationsMessage.createMessage().then(() =>{
            ApplicationsMessage.updateApplicationsStatus()
          })
        }else{
          ApplicationsMessage.updateApplicationsStatus()
        }
      }).catch((err) => {
        console.log(err)
        ApplicationsMessage.removeAttributes(true)
      })
    })
  }

  static createMessage() {
    const DATA_MESSAGE =
      {
        "message": $('#note').val(),
        "subject": i18n[ApplicationsMessage.$language].operator.change_status_message,
        "visibility": $('input[name="checkNote"]:checked').val(),
      };
    return new Promise((resolve, reject) => {
      $.ajax({
        url: ApplicationsMessage.$apiService.getBasePath() + `/api/applications/${ApplicationsMessage.$applicationId}/messages`,
        dataType: 'json',
        type: 'POST',
        data: JSON.stringify(DATA_MESSAGE),
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization', `Bearer ${ApplicationsMessage.$token}`);
        },
        success: function (data) {
          resolve(data)
        },
        error: function (error) {
          ApplicationsMessage.removeAttributes(true)
          reject(error)
        }
      })
    })
  }

  static updateApplicationsStatus() {

    return new Promise((resolve, reject) => {
      $.ajax({
        url: ApplicationsMessage.$apiService.getBasePath() + `/api/applications/${ApplicationsMessage.$applicationId}/transition/complete-payment`,
        dataType: 'json',
        type: 'POST',
        data: null,
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization', `Bearer ${ApplicationsMessage.$token}`);
        },
        success: function (data) {
          ApplicationsMessage.removeAttributes(false)
          resolve(data)
          location.reload()
        },
        error: function (error) {
          ApplicationsMessage.removeAttributes(true)
          reject(error)
        }
      })
    })
  }

  static removeAttributes(showError){
    ApplicationsMessage.$modalSubmitButton.removeAttr('disabled')
    ApplicationsMessage.$spinner.removeClass('progress')
    if(showError){
      $('.alert').removeClass('d-none');
    }
  }
}

export default ApplicationsMessage;
