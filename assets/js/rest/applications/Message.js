import Auth from "../auth/Auth";
import BasePath from "../../utils/BasePath";


class ApplicationsMessage {

  $apiService; // API Class
  $modalSubmitButton // Html Element Submit modal button
  $token; // Auth token
  $applicationId; // Application id
  $spinner // Html Element spinner
  $language; // Browser language
  $basePath;

  static init() {


    ApplicationsMessage.$modalSubmitButton = $('#change_paid_application');
    ApplicationsMessage.$applicationId = $('#change_paid_modal').data('id');
    ApplicationsMessage.$spinner = $('.progress-indeterminate');
    ApplicationsMessage.$language = document.documentElement.lang.toString();
    ApplicationsMessage.$apiService = new Auth();
    ApplicationsMessage.$basePath = new BasePath().getBasePath();

    ApplicationsMessage.$modalSubmitButton.on('click', () =>{
      ApplicationsMessage.$spinner.addClass('progress');
      ApplicationsMessage.$modalSubmitButton.attr('disabled','disabled')
      // Get Auth token
      ApplicationsMessage.$apiService.getSessionAuthTokenPromise().then((data) => {
        ApplicationsMessage.$token = data.token
        ApplicationsMessage.updateApplicationsStatus()
      }).catch((err) => {
        console.log(err)
        ApplicationsMessage.removeAttributes(true)
      })
    })
  }

  static updateApplicationsStatus() {

    const DATA_MESSAGE =
      {
        "message": $('#note').val() || null,
        "subject": Translator.trans('payment.operator.change_status_message', {}, 'messages', ApplicationsMessage.$language),
        "visibility": $('input[name="checkNote"]:checked').val(),
        "attachments": []
      };

    return new Promise((resolve, reject) => {
      $.ajax({
        url: `${ApplicationsMessage.$basePath}/api/applications/${ApplicationsMessage.$applicationId}/transition/complete-payment`,
        dataType: 'json',
        type: 'POST',
        data: JSON.stringify(DATA_MESSAGE),
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
