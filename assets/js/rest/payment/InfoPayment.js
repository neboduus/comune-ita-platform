import moment from "moment";
import Auth from "../auth/Auth";


const PAGOPA = require('../../../images/payments/pagppa.png');

class InfoPayment {

  $token; // Auth token
  $spinner; // Html Element Spinner
  $spinnerContainer; // Html Element Wrapper Spinner
  $application_id; // Application id
  $statusPayment; // Html Element Status payment
  $language; // Browser language
  $alertError; // Html element alert errors
  $tenant; // Tenant Slug
  $gateway; // Gateway details
  $apiService; // API Class


  static init() {

    // Init value variables
    InfoPayment.$application_id = $('.payment-list').data('id');
    InfoPayment.$gateway = $('.payment-list').data('gateway');
    InfoPayment.$spinner = $('.progress-spinner');
    InfoPayment.$spinnerContainer = $('.spinner-container');
    InfoPayment.$language = document.documentElement.lang.toString();
    InfoPayment.$alertError = $('.alert-error');
    InfoPayment.$statusPayment = $('.status');
    InfoPayment.$apiService = new Auth();

    // Active spinner animations
    InfoPayment.$spinner.addClass('progress-spinner-active');
    InfoPayment.$statusPayment.html(Translator.trans('payment.get_payment', {}, 'messages', InfoPayment.$language));
    // Get tenant slug
    InfoPayment.$tenant = window.location.pathname.split('/')[1];
    // Get Auth token
    InfoPayment.$apiService.getSessionAuthTokenPromise().then((data) => {
      InfoPayment.$token = data.token;
      InfoPayment.detailPayment();
    }).catch(() => {
      InfoPayment.handleErrors(Translator.trans('payment.unauth', {}, 'messages', InfoPayment.$language));
    })
  }

  static handleErrors(errorMessage) {
    InfoPayment.$spinnerContainer.addClass('d-none');
    InfoPayment.$alertError.html(errorMessage);
    InfoPayment.$alertError.removeClass('d-none').addClass('d-block fade show');
  }


  static detailPayment() {
    // GET API PAYMENTS
    $.ajax({
      url: InfoPayment.$spinnerContainer.data('api'),
      dataType: 'json',
      type: 'get',
      //timeout: 100, // enable for simulate timeout
      beforeSend: function (xhr) {
        xhr.setRequestHeader('Authorization', `Bearer ${InfoPayment.$token}`);
      },
      success: function (data) {
        InfoPayment.$spinnerContainer.addClass('d-none');
        InfoPayment.generateHtmlData(data)
      },
      error: function (xmlhttprequest, textstatus, message) { // error logging
        if (textstatus === "timeout") {
          InfoPayment.handleErrors(Translator.trans('payment.timeout', {}, 'messages', InfoPayment.$language));
        } else if (xmlhttprequest.status === 401) {
          InfoPayment.handleErrors(Translator.trans('payment.unauth', {}, 'messages', InfoPayment.$language));
        } else if (xmlhttprequest.status === 404) {
          InfoPayment.handleErrors(Translator.trans('payment.not_found', {}, 'messages', InfoPayment.$language));
        } else {
          InfoPayment.handleErrors(Translator.trans('payment.get_failed', {}, 'messages', InfoPayment.$language));
        }
      }
    });
  };

  static switchStatus(status) {
    switch (status) {
      case 'CREATION_PENDING':
        return Translator.trans('STATUS_PAYMENT_PENDING', {}, 'messages', InfoPayment.$language);
      case 'PAYMENT_PENDING':
        return Translator.trans('STATUS_PAYMENT_PENDING', {}, 'messages', InfoPayment.$language);
      case 'CREATION_FAILED':
        return Translator.trans('STATUS_PAYMENT_CREATION_FAIL', {}, 'messages', InfoPayment.$language);
      case 'PAYMENT_STARTED':
        return Translator.trans('STATUS_PAYMENT_STARTED', {}, 'messages', InfoPayment.$language);
      case 'PAYMENT_CONFIRMED':
        return Translator.trans('STATUS_PAYMENT_SUCCESS', {}, 'messages', InfoPayment.$language);
      case 'PAYMENT_FAILED':
        return Translator.trans('STATUS_PAYMENT_ERROR', {}, 'messages', InfoPayment.$language);
      case 'NOTIFICATION_PENDING':
        return Translator.trans('STATUS_NOTIFICATION_PENDING', {}, 'messages', InfoPayment.$language);
      case 'COMPLETE':
        return Translator.trans('STATUS_PAYMENT_COMPLETE', {}, 'messages', InfoPayment.$language);
      case 'EXPIRED':
        return Translator.trans('STATUS_PAYMENT_EXPIRED', {}, 'messages', InfoPayment.$language);
      default:
        console.log(`Status not found - ${status}.`);
    }
  }

  static generateHtmlData(data) {
    const container = $('.payment-list');
    let output = "";
    for (let i = 0; i < data.length; i++) {
      output +=
        `<div class="col-12">
            <!--start card-->
            <div class="card-wrapper card-space">
              <div class="card card-bg card-big no-after">
                <div class="card-body">
                  <div class="head-tags mb-0 flex-column">
                     <div class="mb-3 text-right">
                      <a class="card-tag px-0 px-sm-2" href="javascript:void(0)">${InfoPayment.switchStatus(data[i].status)}</a>
                    </div>
                    <div class="category-top">
                      <a class="category" href="javascript:void(0)">${Translator.trans('payment.created_at', {}, 'messages', InfoPayment.$language)}</a>
                      <span class="data">${moment(data[i].created_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                    </div>
                  </div>
                  <div class="top-icon mb-0">
                  ${data[i].type === 'EFIL' ? `
                   <svg class="icon" viewBox="0 0 146.6 73.5">
                    <rect x="8.7" y="0.5" class="gb_green" width="13.9" height="12.1"></rect>
                    <rect x="60" y="37.7" class="gb_green" width="13.9" height="12.1"></rect>
                    <rect x="111.9" y="0.5" class="gb_green" width="13.9" height="12.1"></rect>
                    <path class="gb_blu"
                        d="M38.3,20.3c3.7,1.6,6.7,4.2,9.1,7.8c2.1,3.1,3.5,6.7,4.2,10.9c0.4,2.4,0.5,5.9,0.5,10.4H13.8	c0.2,5.3,2,9,5.5,11.1c2.1,1.3,4.6,2,7.6,2c3.1,0,5.7-0.8,7.6-2.4c1.1-0.9,2-2.1,2.8-3.6h14c-0.4,3.1-2.1,6.3-5.1,9.5	c-4.7,5.1-11.3,7.7-19.7,7.7c-7,0-13.1-2.2-18.5-6.5S0,55.7,0,46c0-9.1,2.4-16.1,7.2-20.9c4.8-4.9,11.1-7.3,18.8-7.3	C30.6,17.8,34.7,18.6,38.3,20.3z M17.8,32.1c-1.9,2-3.2,4.7-3.7,8.1h23.6c-0.2-3.6-1.5-6.4-3.7-8.3s-4.9-2.8-8.1-2.8	C22.5,29.1,19.8,30.1,17.8,32.1z"></path>
                    <path class="gb_blu"
                        d="M101.1,0.1c0.7,0,1.7,0.1,4.6,0.2v11c-2.5-0.1-3.7,0-5.6-0.1c-1.8,0-3.1,0.4-3.8,1.2c-0.7,0.9-1,1.8-1,2.8	c0,1,0,2.5,0,4.4h9.7v9.7h-9.7v42.3H81.7V29.4h-8.8v-9.7h8.6v-3.4c0-5.6,0.9-9.5,2.8-11.6c2-3.1,6.8-4.7,14.4-4.7	C99.6,0,100.4,0,101.1,0.1z"></path>
                    <path class="gb_blu" d="M111.9,19.2h13.9v52.5h-13.9V19.2z"></path>
                    <path class="gb_blu" d="M146.6,71.6l-13.7,0.1v-71l13.7-0.1V71.6z"></path>
                  </svg>` : ""}
           ${data[i].type === 'PAGOPA' ? `<div><img class="icon" alt="PAGOPA" src="${PAGOPA}"/></div>` : ""}
           ${data[i].type !== 'PAGOPA' && data[i].type !== 'EFIL' ? `<svg class="icon"><use href="/bootstrap-italia/dist/svg/sprite.svg#it-card"></use></svg>` : ""}
            </div>
                 <h5 class="card-title">${InfoPayment.$gateway}</h5>
                    <p class="card-text">
                    <span>${Translator.trans('payment.amount', {}, 'messages', InfoPayment.$language)}:</span>
                    <span class="float-right h4">${Number(data[i].payment.amount)} ${data[i].payment.currency === 'EUR' ? '€' : data[i].payment.currency}</span>
                    <hr>
                    </p>
                  <p class="card-text">
                    ${Translator.trans('payment.reason', {}, 'messages', InfoPayment.$language)}:
                    <br><b>${data[i].reason}</b>
                  </p>

                  <div class="it-card-footer">
                    <span class="card-signature"></span>
                    <a class="btn btn-outline-primary btn-sm" href="#" data-toggle="collapse" data-target="#collapse1-sc${[i]}" aria-expanded="false" aria-controls="collapse1-sc${[i]}">${Translator.trans('payment.other_details', {}, 'messages', InfoPayment.$language)}</a>
                  </div>
                  <div id="collapse1-sc${[i]}" class="collapse mt-3" role="region" aria-labelledby="heading1-sc${[i]}">
                    <div class="collapse-body p-0">
                     <div class="it-list-wrapper">
                        <ul class="it-list">
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.payer', {}, 'messages', InfoPayment.$language)}</em> ${data[i].payer.name} ${data[i].payer.family_name} - ${data[i].payer.tax_identification_number}</span>
                              </div>
                            </a>
                          </li>   <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.address', {}, 'messages', InfoPayment.$language)}</em>
                                ${data[i].payer.street_name ? `${data[i].payer.street_name}, ${data[i].payer.building_number}` : "--"}
                                ${data[i].payer.postal_code ? `${data[i].payer.postal_code}` : ""}
                                ${data[i].payer.town_name ? `${data[i].payer.town_name}` : ""}
                                ${data[i].payer.country_subdivision ? `(${data[i].payer.country_subdivision})` : ""}
                                </span>
                              </div>
                            </a>
                          </li>
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.email', {}, 'messages', InfoPayment.$language)}</em> ${data[i].payer.email ? `${data[i].payer.email}` : "--"}</span>
                              </div>
                            </a>
                          </li>
                          ${data[i].payment.paid_at ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.paid_at', {}, 'messages', InfoPayment.$language)}</em> ${moment(data[i].payment.paid_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                              </div>
                            </a>
                          </li>
                          ` :
          `<li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.event_created_at', {}, 'messages', InfoPayment.$language)}</em> ${moment(data[i].event_created_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                              </div>
                            </a>
                          </li>
                            `}
                          </li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${Translator.trans('payment.type', {}, 'messages', InfoPayment.$language)}</em> ${data[i].type}</span>
                              </div>
                            </a>
                          </li>
                          ${data[i].payment.iuv ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text text-truncate"><em>${Translator.trans('payment.iuv', {}, 'messages', InfoPayment.$language)}</em> ${data[i].payment.iuv}</span>
                                <button type="button" class="btn btn-outline-primary copy" data-copy="${data[i].payment.iuv}">${Translator.trans('payment.copy', {}, 'messages', InfoPayment.$language)}</button>
                              </div>
                            </a>
                          </li>
                          ` : ''}
                           ${data[i].payment.iud ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text text-truncate"><em>${Translator.trans('payment.iud', {}, 'messages', InfoPayment.$language)}</em> ${data[i].payment.iud}</span>
                                <button type="button" class="btn btn-outline-primary copy" data-copy="${data[i].payment.iud}">${Translator.trans('payment.copy', {}, 'messages', InfoPayment.$language)}</button>
                              </div>
                            </a>
                          </li>
                            ` : ''}
                        </ul>
                      </div>
                    </div>
                    ${data[i].status === 'COMPLETE' && data[i].links.receipt.url !== null ?
          `<div class="text-center mt-5">
                        <a target="_blank" href="${data[i].links.receipt.url}" class="btn btn-primary" role="button" aria-disabled="true" download>
                            ${Translator.trans('payment.download_pdf', {}, 'messages', InfoPayment.$language)}
                        </a>
                    </div>` : ""}
                    ${data[i].status === 'CREATION_FAILED' ?
          `<div></div>` : ""}
                    ${data[i].status !== 'CREATION_FAILED'
        && data[i].status !== 'COMPLETE'
        && data[i].status !== 'PAYMENT_STARTED'
        && data[i].status !== 'PAYMENT_CONFIRMED'
        && data[i].status !== 'PAYMENT_FAILED'
        && data[i].status !== 'NOTIFICATION_PENDING'
        && data[i].status !== 'EXPIRED'
          ?
          `<div class="text-center mt-5">
${data[i].links.online_payment_begin.url !== null ?
            `<a target="_blank" href="${data[i].links.online_payment_begin.url}" class="btn btn-primary btn-lg mr-3 online_payment_begin" role="button" aria-pressed="true"> ${Translator.trans('payment.paga_online', {}, 'messages', InfoPayment.$language)}</a>` : ""}
${data[i].links.offline_payment.url !== null ?
            `<a target="_blank" href="${data[i].links.offline_payment.url}" class="btn btn-secondary btn-lg offline_payment" role="button" aria-pressed="true" download> ${Translator.trans('payment.paga_offline', {}, 'messages', InfoPayment.$language)}</a>` : ""}
                      </div>
                    ` : ""}
                  </div>
                </div>
              </div>
            </div>
            <!--end card-->
    </div>`
    }
    container.html(output);

    // Work only in HTTPS
    $('button.copy').on('click', (e) => {
      e.preventDefault();
      navigator.clipboard.writeText(e.currentTarget.dataset.copy).catch(err => console.log(err));
    });
  }


}

export default InfoPayment;