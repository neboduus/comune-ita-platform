import Api from "../services/api.service";

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
  $apiService; // API Class

  static init() {

    // Init value variables
    Payment.$spinner = $('.progress-spinner');
    Payment.$spinnerContainer = $('.spinner-container');
    Payment.$callToActionButtons = $('.actions-container');
    Payment.$statusPayment = $('.status');
    Payment.$language = document.documentElement.lang.toString();
    Payment.$alertError = $('.alert-error');
    Payment.$apiService = new Api();

    // Active spinner animations
    Payment.$spinner.addClass('progress-spinner-active');
    Payment.$statusPayment.html(Translator.trans('payment.creation_pending', {}, 'messages', Payment.$language));

    // Get tenant slug
    Payment.$tenant = window.location.pathname.split('/')[1];
    // Get Auth token
    Payment.$apiService.getSessionAuthTokenPromise().then((data) => {
      Payment.$token = data.token;
      Payment.poolingPayment();
    }).catch(() => {
      Payment.handleErrors(Translator.trans('payment.unauth', {}, 'messages', Payment.$language));
    })
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
        Payment.$statusPayment.html(Translator.trans('STATUS_PAYMENT_PENDING', {}, 'messages', Payment.$language));
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
        Payment.handleErrors(Translator.trans('payment.creation_failed_text', {}, 'messages', Payment.$language));
        break;
      default:
        console.log(`Status not found - ${data.status}.`);
    }
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
        Payment.handleErrors(Translator.trans('payment.timeout', {}, 'messages', Payment.$language));
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
            Payment.handleErrors(Translator.trans('payment.timeout', {}, 'messages', Payment.$language));
          } else if (xmlhttprequest.status === 401) {
            Payment.handleErrors(Translator.trans('payment.timeout', {}, 'messages', Payment.$language));
          } else if (xmlhttprequest.status === 404) {
            Payment.retryPooling(this);
            Payment.$statusPayment.html(Translator.trans('payment.not_found', {}, 'messages', Payment.$language));
          } else {
            Payment.handleErrors(Translator.trans('payment.creation_failed_text', {}, 'messages', Payment.$language));
          }
        }
      });
    }
    // Call pooling request
    poolingAjaxRequest();
  }

}

export default Payment;
