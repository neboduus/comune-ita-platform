import moment from "moment";

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

  static $translations = {
    "it": {
      "get_payment": "Recupero pagamento in corso",
      "creation_failed": "Opss qualcosa è andato storto!",
      "timeout": "La richiesta sta impiegando più tempo del necessario.. <br><b>Riprova più tardi!</b>",
      "unauth": "Sessione scaduta <br><b>Effettua nuovamente il login</b>.",
      "get_failed": "Opss qualcosa è andato storto! <br> Riprova più tardi oppure contatta l'ufficio amministrativo.",
      "not_found": "Pagamento non trovato, riprova più tardi!",
      "created_at": "Data invio procedimento",
      "reason": "Causale versamento",
      "payer": "Intestatario",
      "address": "Indirizzo pagatore",
      "email": "Email pagatore",
      "event_created_at": "Evento creato il",
      "type": "Metodo di pagamento",
      "iud": "Identificativo Unico del Dovuto (IUD)",
      "iuv": "Identificativo Univoco pagamento (IUV)",
      "copy": "Copia",
      "amount": "Importo",
      "other_details": "Dettaglio operazione",
      "status_creation_pending": "In attesa di pagamento",
      "status_notification_pending": "Notifica pendente",
      "status_payment_pending": "In attesa di pagamento",
      "status_creation_failed": "Creazione di pagamento fallita",
      "status_payment_failed": "Pagamento fallito",
      "status_payment_started": "Pagamento inviato",
      "status_payment_confirmed": "Pagamento confermato",
      "status_expired": "Pagamento scaduto",
      "status_complete": "Pagamento completato",
      "paid_at": "Pagato il",
      "download_pdf": "Scarica ricevuta pagamento",
    },
    "en": {
      "get_payment": "Payment creation in progress",
      "creation_failed": "Oops something went wrong!",
      "timeout": "The request is taking longer than necessary .. <br> <b> Please try again later! </b>",
      "unauth": "Session expired <br> <b> Please login again </b>.",
      "get_failed": "Oops something went wrong!, please try again later or contact the administrative office.",
      "not_found": "Payment not found, try again later!",
      "created_at": "Procedure submission date",
      "reason": "Reason for payment",
      "payer": "Accountholder",
      "address": "Payer address",
      "email": "Payer email",
      "event_created_at": "Event date",
      "type": "Payment method",
      "iud": "Identificativo Unico del Dovuto (IUD)",
      "iuv": "Identificativo Univoco pagamento (IUV)",
      "copy": "Copy",
      "amount": "Amount",
      "other_details": "Operation detail",
      "status_creation_pending": "In attesa di pagamento",
      "status_notification_pending": "Notifica pendente",
      "status_payment_pending": "In attesa di pagamento",
      "status_creation_failed": "Creazione di pagamento fallita",
      "status_payment_failed": "Pagamento fallito",
      "status_payment_started": "Pagamento inviato",
      "status_payment_confirmed": "Pagamento confermato",
      "status_expired": "Pagamento scaduto",
      "status_complete": "Pagamento completato",
      "paid_at": "Paid on",
      "download_pdf": "Download payment receipt",
    },
    "de": {
      "get_payment": "Zahlungserstellung läuft",
      "creation_failed": "OUps! Irgendwas lief schief!",
      "timeout": "Die Anfrage dauert länger als nötig. <br> <b> Bitte versuchen Sie es später erneut! </b>",
      "unauth": "Sitzung abgelaufen <br> <b> Bitte melden Sie sich erneut an </b>.",
      "get_failed": "OUps! Irgendwas lief schief!, bitte versuchen Sie es später erneut oder wenden Sie sich an die Geschäftsstelle.",
      "not_found": "Zahlung nicht gefunden, versuchen Sie es später erneut!",
      "created_at": "Einreichungsdatum des Verfahrens",
      "reason": "Zahlungsgrund",
      "payer": "Kontoinhaber",
      "address": "Zahleradresse",
      "email": "E-Mail des Zahlers",
      "event_created_at": "Veranstaltungsdatum",
      "type": "Bezahlverfahren",
      "iud": "Identificativo Unico del Dovuto (IUD)",
      "iuv": "Identificativo Univoco pagamento (IUV)",
      "copy": "Kopieren",
      "amount": "Menge",
      "other_details": "Betriebsdetail",
      "status_creation_pending": "In attesa di pagamento",
      "status_notification_pending": "Notifica pendente",
      "status_payment_pending": "In attesa di pagamento",
      "status_creation_failed": "Creazione di pagamento fallita",
      "status_payment_failed": "Pagamento fallito",
      "status_payment_started": "Pagamento inviato",
      "status_payment_confirmed": "Pagamento confermato",
      "status_expired": "Pagamento scaduto",
      "status_complete": "Pagamento completato",
      "paid_at": "Bezahlt am",
      "download_pdf": "Zahlungsbeleg herunterladen",
    }
  }


  static init() {

    // Init value variables
    InfoPayment.$application_id = $('.payment-list').data('id');
    InfoPayment.$gateway = $('.payment-list').data('gateway');
    InfoPayment.$spinner = $('.progress-spinner');
    InfoPayment.$spinnerContainer = $('.spinner-container');
    InfoPayment.$language = document.documentElement.lang.toString();
    InfoPayment.$alertError = $('.alert-error');
    InfoPayment.$statusPayment = $('.status');
    // Active spinner animations
    InfoPayment.$spinner.addClass('progress-spinner-active');
    InfoPayment.$statusPayment.html(InfoPayment.$translations[InfoPayment.$language].get_payment);
    // Get tenant slug
    InfoPayment.$tenant = window.location.pathname.split('/')[1];
    // Get Auth token
    InfoPayment.getAuthToken();
  }

  static handleErrors(errorMessage) {
    InfoPayment.$spinnerContainer.addClass('d-none');
    InfoPayment.$alertError.html(errorMessage);
    InfoPayment.$alertError.removeClass('d-none').addClass('d-block fade show');
  }

  static getAuthToken() {
    $.ajax({
      url: '/' + InfoPayment.$tenant + '/api/session-auth',
      dataType: 'json',
      type: 'get',
      success: function (data) {
        InfoPayment.$token = data.token;
        InfoPayment.detailPayment();
      },
      error: function (xmlhttprequest, textstatus, message) {
        InfoPayment.handleErrors(InfoPayment.$translations[InfoPayment.$language].unauth);
      }
    });
  }

  static detailPayment() {
    // GET API PAYMENTS
    $.ajax({
      url: '/comune-di-bugliano/api/payments?remote_id=' + InfoPayment.$application_id,
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
          InfoPayment.handleErrors(InfoPayment.$translations[InfoPayment.$language].timeout);
        } else if (xmlhttprequest.status === 401) {
          InfoPayment.handleErrors(InfoPayment.$translations[InfoPayment.$language].unauth);
        } else if (xmlhttprequest.status === 404) {
          InfoPayment.handleErrors(InfoPayment.$translations[InfoPayment.$language].not_found);
        } else {
          InfoPayment.handleErrors(InfoPayment.$translations[InfoPayment.$language].get_failed);}
      }
    });
  };

  static switchStatus(status){
    switch (status) {
      case 'CREATION_PENDING':
        return InfoPayment.$translations[InfoPayment.$language].status_creation_pending;
      case 'PAYMENT_PENDING':
        return InfoPayment.$translations[InfoPayment.$language].status_payment_pending;
      case 'CREATION_FAILED':
        return InfoPayment.$translations[InfoPayment.$language].status_creation_failed;
      case 'PAYMENT_STARTED':
        return InfoPayment.$translations[InfoPayment.$language].status_payment_started;
      case 'PAYMENT_CONFIRMED':
        return InfoPayment.$translations[InfoPayment.$language].status_payment_confirmed;
      case 'PAYMENT_FAILED':
        return InfoPayment.$translations[InfoPayment.$language].status_payment_failed;
      case 'NOTIFICATION_PENDING':
        return InfoPayment.$translations[InfoPayment.$language].status_notification_pending;
      case 'COMPLETE':
        return InfoPayment.$translations[InfoPayment.$language].status_complete;
      case 'EXPIRED':
        return InfoPayment.$translations[InfoPayment.$language].status_expired;
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
                  <div class="head-tags mb-0 justify-content-md-between">
                    <div class="category-top">
                      <a class="category" href="javascript:void(0)">${InfoPayment.$translations[InfoPayment.$language].created_at}</a>
                      <span class="data">${moment(data[i].created_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                    </div>
                    <div>
                      <a class="card-tag" href="javascript:void(0)">${InfoPayment.switchStatus(data[i].status)}</a>
                    </div>
                  </div>
                  <div class="top-icon mb-0">
                  ${data[i].type === 'EFIL' ? `<svg class="icon" viewBox="0 0 146.6 73.5">
         <rect x="8.7" y="0.5" class="gb_green" width="13.9" height="12.1"></rect>
         <rect x="60" y="37.7" class="gb_green" width="13.9" height="12.1"></rect>
         <rect x="111.9" y="0.5" class="gb_green" width="13.9" height="12.1"></rect>
         <path class="gb_blu"
               d="M38.3,20.3c3.7,1.6,6.7,4.2,9.1,7.8c2.1,3.1,3.5,6.7,4.2,10.9c0.4,2.4,0.5,5.9,0.5,10.4H13.8	c0.2,5.3,2,9,5.5,11.1c2.1,1.3,4.6,2,7.6,2c3.1,0,5.7-0.8,7.6-2.4c1.1-0.9,2-2.1,2.8-3.6h14c-0.4,3.1-2.1,6.3-5.1,9.5	c-4.7,5.1-11.3,7.7-19.7,7.7c-7,0-13.1-2.2-18.5-6.5S0,55.7,0,46c0-9.1,2.4-16.1,7.2-20.9c4.8-4.9,11.1-7.3,18.8-7.3	C30.6,17.8,34.7,18.6,38.3,20.3z M17.8,32.1c-1.9,2-3.2,4.7-3.7,8.1h23.6c-0.2-3.6-1.5-6.4-3.7-8.3s-4.9-2.8-8.1-2.8	C22.5,29.1,19.8,30.1,17.8,32.1z"></path>
         <path class="gb_blu"
               d="M101.1,0.1c0.7,0,1.7,0.1,4.6,0.2v11c-2.5-0.1-3.7,0-5.6-0.1c-1.8,0-3.1,0.4-3.8,1.2c-0.7,0.9-1,1.8-1,2.8	c0,1,0,2.5,0,4.4h9.7v9.7h-9.7v42.3H81.7V29.4h-8.8v-9.7h8.6v-3.4c0-5.6,0.9-9.5,2.8-11.6c2-3.1,6.8-4.7,14.4-4.7	C99.6,0,100.4,0,101.1,0.1z"></path>
         <path class="gb_blu" d="M111.9,19.2h13.9v52.5h-13.9V19.2z"></path>
         <path class="gb_blu" d="M146.6,71.6l-13.7,0.1v-71l13.7-0.1V71.6z"></path>
       </svg>`
          : `<svg class="icon">
           <use href="/bootstrap-italia/dist/svg/sprite.svg#it-card"></use>
         </svg>`}
                  </div>
                 <h5 class="card-title">${InfoPayment.$gateway}</h5>
                    <p class="card-text">
                    ${InfoPayment.$translations[InfoPayment.$language].amount}: <b>${data[i].payment.currency === 'EUR' ? '€' : data[i].payment.currency} ${data[i].payment.amount}</b>
                    </p>
                  <p class="card-text">
                    ${InfoPayment.$translations[InfoPayment.$language].reason}:
                    <br><b>${data[i].reason}</b>
                  </p>

                  <div class="it-card-footer">
                    <span class="card-signature"></span>
                    <a class="btn btn-outline-primary btn-sm" href="#" data-toggle="collapse" data-target="#collapse1-sc${[i]}" aria-expanded="false" aria-controls="collapse1-sc${[i]}">${InfoPayment.$translations[InfoPayment.$language].other_details}</a>
                  </div>
                  <div id="collapse1-sc${[i]}" class="collapse mt-3" role="region" aria-labelledby="heading1-sc${[i]}">
                    <div class="collapse-body p-0">
                     <div class="it-list-wrapper">
                        <ul class="it-list">
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].payer}</em> ${data[i].payer.name} ${data[i].payer.family_name} - ${data[i].payer.tax_identification_number}</span>
                              </div>
                            </a>
                          </li>   <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].address}</em>
                                ${data[i].payer.street_name ? `${data[i].payer.street_name}, ${data[i].payer.building_number}` : ""}
                                ${data[i].payer.postal_code ? `${data[i].payer.postal_code}` : ""}
                                ${data[i].payer.town_name ? `${data[i].payer.town_name}` : ""}
                                ${data[i].payer.country_subdivision ? `(${data[i].payer.country_subdivision})`: ""}
                                </span>
                              </div>
                            </a>
                          </li>
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].email}</em> ${data[i].payment.email ? `${data[i].payer.email}`: "--"}</span>
                              </div>
                            </a>
                          </li>
                          ${data[i].payment.paid_at ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].paid_at}</em> ${moment(data[i].payment.paid_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                              </div>
                            </a>
                          </li>
                          `:
                          `<li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].event_created_at}</em> ${moment(data[i].event_created_at).locale(InfoPayment.$language).format('DD/MM/YYYY - HH:mm')}</span>
                              </div>
                            </a>
                          </li>
                            `}
                          </li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].type}</em> ${data[i].type}</span>
                              </div>
                            </a>
                          </li>
                          ${data[i].payment.iuv ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].iuv}</em> ${data[i].payment.iuv}</span>
                                <button type="button" class="btn btn-outline-primary copy" data-copy="${data[i].payment.iuv}">${InfoPayment.$translations[InfoPayment.$language].copy}</button>
                              </div>
                            </a>
                          </li>
                          `: ''}
                           ${data[i].payment.iud ? `
                          <li>
                            <a href="javascript:void(0)" data-focus-mouse="true">
                              <div class="it-right-zone">
                                <span class="text"><em>${InfoPayment.$translations[InfoPayment.$language].iud}</em> ${data[i].payment.iud}</span>
                                <button type="button" class="btn btn-outline-primary copy" data-copy="${data[i].payment.iud}">${InfoPayment.$translations[InfoPayment.$language].copy}</button>
                              </div>
                            </a>
                          </li>
                            `: ''}
                        </ul>
                      </div>
                    </div>
                    ${data[i].links.receipt && data[i].links.receipt.url !== "" ?
                   `<div class="text-center mt-5">
                        <a href="${data[i].links.receipt.url}" class="btn btn-primary" role="button" aria-disabled="true" download>
                            ${InfoPayment.$translations[InfoPayment.$language].download_pdf}
                        </a>
                     </div>`
                    : ""}
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
