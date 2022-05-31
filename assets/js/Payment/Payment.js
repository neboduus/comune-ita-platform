class Payment {

  $token; // Auth token
  $spinner; // Html Element Spinner
  $spinnerContainer; // Html Element Wrapper Spinner
  $callToActionButtons; // Html Element Wrapper Button Call to Actions
  $statusPayment; // Html Element Status payment
  $pollInterval; // Instance poll interval
  $language; // Browser language
  $alertError; // Html element alert errors
  $tenant; // Tenant Slug

  static $translations = {
    "it": {
      "creation_pending": "Creazione del pagamento in corso",
      "creation_failed": "Opss qualcosa è andato storto!",
      "timeout": "La richiesta sta impiegando più tempo del necessario.. <br><b>Riprova più tardi!</b>",
      "unauth": "Sessione scaduta <br><b>Effettua nuovamente il login</b>.",
      "creation_failed_text": "La creazione del pagamento non è andata a buon fine riprova più tardi oppure contatta l'ufficio amministrativo.",
      "not_found": "La pratica non è ancora pervenuta, la preghiamo di attendere!"
    },
    "en": {
      "creation_pending": "Payment creation in progress",
      "creation_failed": "Oops something went wrong!",
      "timeout": "The request is taking longer than necessary .. <br> <b> Please try again later! </b>",
      "unauth": "Session expired <br> <b> Please login again </b>.",
      "creation_failed_text": "The creation of the payment was not successful, please try again later or contact the administrative office.",
      "not_found": "The pratice has not yet been received, please wait!"
    },
    "de": {
      "creation_pending": "Zahlungserstellung läuft",
      "creation_failed": "OUps! Irgendwas lief schief!",
      "timeout": "Die Anfrage dauert länger als nötig. <br> <b> Bitte versuchen Sie es später erneut! </b>",
      "unauth": "Sitzung abgelaufen <br> <b> Bitte melden Sie sich erneut an </b>.",
      "creation_failed_text": "Die Erstellung der Zahlung war nicht erfolgreich, bitte versuchen Sie es später erneut oder wenden Sie sich an die Geschäftsstelle.",
      "not_found": "Die Datei wurde noch nicht empfangen, bitte warten!"
    }
  }


  static init() {

    // Init value variables
    Payment.$spinner = $('.progress-spinner');
    Payment.$spinnerContainer = $('.spinner-container');
    Payment.$callToActionButtons = $('.actions-container');
    Payment.$statusPayment = $('.status');
    Payment.$language = document.documentElement.lang.toString();
    Payment.$alertError = $('.alert-error');


    // Active spinner animations
    Payment.$spinner.addClass('progress-spinner-active');
    Payment.$statusPayment.html(Payment.$translations[Payment.$language].creation_pending);

    // Get tenant slug
    Payment.$tenant = window.location.pathname.split('/')[1];
    // Get Auth token
    Payment.getAuthToken();
  }

  static handleErrors(errorMessage) {
    Payment.$spinnerContainer.addClass('d-none');
    Payment.$callToActionButtons.removeClass('d-flex').addClass('d-none');
    Payment.$alertError.html(errorMessage);
    Payment.$alertError.removeClass('d-none').addClass('d-block fade show');
  }

  static handleSwitchStatus(data,self) {
    switch (data.status) {
      case 'CREATION_PENDING':
        Payment.$statusPayment.html(Payment.$translations[Payment.$language].creation_pending);
        Payment.retryPooling(self);
        break;
      case 'PAYMENT_PENDING':
        Payment.$spinnerContainer.addClass('d-none');
        $(".online_payment_begin").attr("href", data.links.online_payment_begin.url);
        $(".offline_payment").attr("href", data.links.offline_payment.url);
        Payment.$callToActionButtons.removeClass('d-none').addClass('d-flex');
        break;
      case 'CREATION_FAILED':
        Payment.$spinnerContainer.addClass('d-none');
        Payment.handleErrors(Payment.$translations[Payment.$language].creation_failed_text);
        break;
      default:
        console.log(`Status not found - ${data.status}.`);
    }
  }


  static getAuthToken() {
    $.ajax({
      url: '/'+ Payment.$tenant+ '/api/session-auth',
      dataType: 'json',
      type: 'get',
      success: function (data) {
        Payment.$token = data.token;
        Payment.poolingPayment();
      },
      error: function (xmlhttprequest, textstatus, message) {
        Payment.handleErrors(Payment.$translations[Payment.$language].unauth);
      }
    });
  }


  static retryPooling(self){
    self.tryCount++;
    if (self.tryCount <= self.retryLimit) {
      //try again every 2 seconds
      setTimeout(() => {
        $.ajax(self)
      }, self.retryTimeout)
    }else{
      const timeout = self.retryTimeout * self.tryCount
      if(timeout <=  self.limitTimeout){
        setTimeout(() => {
          $.ajax(self)
        }, timeout)
      }else{
        // if I exceed the timeout I show a message
        Payment.$spinnerContainer.addClass('d-none');
        Payment.handleErrors(Payment.$translations[Payment.$language].timeout);
      }
    }
  }

  static poolingPayment() {

    function poolingAjaxRequest () {
      $.ajax({
        url: Payment.$callToActionButtons.data('api'),
        dataType: 'json',
        type: 'get',
        // timeout: 100, // enable for simulate timeout
        tryCount: 0,
        retryLimit: 5,
        retryTimeout: 2000,
        limitTimeout: 60000, // 1 minutes - 30 retry
        beforeSend: function (xhr) {
          xhr.setRequestHeader('Authorization', `Bearer ${Payment.$token}`);
        },
        success: function (data) {
          Payment.handleSwitchStatus(data,this)
        },
        error: function (xmlhttprequest, textstatus, message) { // error logging
          if (textstatus === "timeout") {
            Payment.handleErrors(Payment.$translations[Payment.$language].timeout);
          } else if (xmlhttprequest.status === 401) {
            Payment.handleErrors(Payment.$translations[Payment.$language].unauth);
          } else if (xmlhttprequest.status === 404) {
            Payment.retryPooling(this);
            Payment.$statusPayment.html(Payment.$translations[Payment.$language].not_found);
          } else {
            Payment.handleErrors(Payment.$translations[Payment.$language].creation_failed_text);
          }
        }
      });
    }
    // Call pooling request
    poolingAjaxRequest();
  }

}

export default Payment;
