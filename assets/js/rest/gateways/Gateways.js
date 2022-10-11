import Auth from "../auth/Auth";
import axios from "axios";
import 'formiojs/dist/formio.form.min.css';
import {Formio} from "formiojs";

class Gateways {

  $token; // Auth token
  $language; // Browser language
  $alertError; // Html element alert errors
  $tenant; // Tenant Slug
  $apiService; // API Class

  static init() {

    // Init value variables
    Gateways.$language = document.documentElement.lang.toString();
    Gateways.$alertError = $('.save-alert');
    Gateways.$apiService = new Auth();

    // Get tenant slug
    Gateways.$tenant = window.location.pathname.split('/')[1];

    // Get Auth token
    Gateways.$apiService.getSessionAuthTokenPromise().then((data) => {
      Gateways.$token = data.token;
    })

    Gateways.hideSubmitTabPayments()
    Gateways.createPopulateExternalPayChoice()
    // Mypay Payment gateways show form
    Gateways.detectChangeLegacyGateway();
    Gateways.handleActionButton()

  }

  static hideSubmitTabPayments() {
    $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
      if(e.target.id === 'payments-tab'){
        $('#ente_save_container').hide()
      }else{
        $('#ente_save_container').show()
      }
    })
  }



  //MyPay
  static detectChangeLegacyGateway(){

    const myPayElement = $('input[type="checkbox"][value="mypay"]')

    if(myPayElement.is(':checked')){
        $(`[id$="_${myPayElement.val()}"]`).removeClass('d-none');
        $(`[id$="heading-${myPayElement.val()}"]`).removeClass('d-none');
      } else {
      $(`[id$="_${myPayElement.val()}"]`).addClass('d-none');
      $(`[id$="heading-${myPayElement.val()}"]`).addClass('d-none');
      }
  }


  // Enable or Disable hidden checkbox gateways on click Enable/Disable button
  static handleActionButton(){

    $('button[data-parent*="gateways_"]').click(function (e) {
      const parentId = $(this).data('parent');
      let input = $(`input[id$="${parentId}"]`)
      const checked = !input.is(':checked')
      input.prop( "checked", checked );
      input.attr('checked', checked);

      if(input.val() === 'mypay'){
        console.log($(`[id$="_${input.val()}"]`))
        if (checked) {
          $(`[id$="_${input.val()}"]`).removeClass('d-none');
        } else {
          $(`[id$="_${input.val()}"]`).addClass('d-none');
        }
      }
    })
  }


  static handleDeleteExternalPayChoice(identifier,url){
    $(`button[data-identifier="ente_gateways_${identifier}"]`).click(function () {
      const parentId = $(this).data('parent');
      const id = $(this).data('identifier');
      if(id && !document.getElementById(parentId).checked){
        Gateways.deleteTenantId(url).then(() => {
          $( "form" ).first().trigger( "submit" );
        })
      }
    })
  }

  static handleCreateExternalPayChoice(identifier){
    const newCheckboxValue = !$(`input[data-identifier="${identifier}"]`).is(':checked')
    $(`input[data-identifier="${identifier}"]`).prop( "checked", newCheckboxValue );
        $("form").first().trigger("submit");
  }

  static createPopulateExternalPayChoice() {
    $('.external-pay-choice').each((i, e) => {
      const gatewayIdentifier = $(e).data('identifier');
      const tenantId = $(e).data('tenant');
      const serviceId = $(e).data('service');
      const gatewayType = serviceId ? 'services' : 'tenants'
      let isFirstConf = false;
      const url = $(e).data('url') + '/' + gatewayType + '/schema';

      // Sanitized for "." in name
      const sanitizeIdentifier = gatewayIdentifier.replace(/\./g, '');

      const $gatewaySettingsContainer = $('<div id="ente_' + sanitizeIdentifier + '" class="gateway-form-type"></div>');
      let settings = {
        "id": serviceId ? serviceId : tenantId,
        ...serviceId  && { tenant_id: tenantId },
      }

      Gateways.getTenantsSchema(url).then((result) => {
        // Creo l'elemento a cui appendere il form
        $('#card-collapse-' + sanitizeIdentifier).html($gatewaySettingsContainer)
        if(document.getElementById('ente_' + sanitizeIdentifier)){
          Formio.createForm(document.getElementById('ente_' + sanitizeIdentifier), result, {
            noAlerts: true,
            buttonSettings: {showCancel: false},
          })
            .then(function (form) {

              if (result.data) {
                settings = result.data;
              }
              form.submission = {
                data: settings
              };

              let initForm
              let isReady = false

              form.nosubmit = true;
              form.on('submit', function (submission) {
                const url = $(e).data('url') + '/' + gatewayType + '/' + settings.id;
                  Gateways.putTenantId(url, JSON.stringify({...submission.data, active: true }))
                    .then(function (response) {
                      form.emit('submitDone', submission)
                      if(isFirstConf || !submission.data.active){
                        Gateways.handleCreateExternalPayChoice(sanitizeIdentifier)
                      }
                    }).catch((error) => {
                    form.emit('submitError')
                  });
              });
              form.ready.then((event) => {
                const url = $(e).data('url') + '/' + gatewayType + '/' + settings.id;
                Gateways.getTenantId(url).then((res)=>{
                  form.submission = {
                    data: res
                  };
                  initForm = {...res, submit: false}
                  isReady = true
                  // Prepare button for delete
                  $(`button[data-identifier="ente_gateways_${sanitizeIdentifier}"]`).removeAttr("type").attr("type", "button");
                  Gateways.handleDeleteExternalPayChoice(sanitizeIdentifier,url)
                }).catch((error) => {
                  // If first config don't exist
                  initForm = {...form.submission.data}
                  isReady = true
                  if(error.status === 404){
                    isFirstConf = true;
                    $(`button[data-identifier="submit_${sanitizeIdentifier}"]`).after(`<h5 class="align-self-end">${Translator.trans('iscrizioni.no_payments_config', {}, 'messages', this.$language)}</h5>`)
                  }
                });
              });
            })
        }

      }).catch(err => {
        if(err.status === 404){
          $('#card-collapse-' + sanitizeIdentifier).html(`<div>${Translator.trans('iscrizioni.no_payments_config', {}, 'messages', this.$language)}</div>`)
        }else{
          $('#card-collapse-' + sanitizeIdentifier).html('<div>'+ err.statusText + '</div>')
        }

      })
    })
  }


  static getTenantsSchema(url){
    return new Promise((resolve, reject) => {
      $.ajax({
        url: url,
        dataType: 'json',
        type: 'GET',
        success: function (data) {
          resolve(data)
        },
        error: function (error) {
          reject(error)
        }
      })
    })
  }


  static putTenantId(url,data){
    return new Promise((resolve, reject) => {
      axios.put(url, data, {
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${Gateways.$token}`

        }
      }).then((res)=>{
        resolve(res)
      }).catch((err) =>{
        reject(err)
      })
    })
  }

  static patchTenantId(url,data){
    return new Promise((resolve, reject) => {
      axios.patch(url, data, {
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${Gateways.$token}`
        }
      }).then((res)=>{
        resolve(res)
      }).catch((err) =>{
        reject(err)
      })
    })
  }

  static postTenants(url,data){
    return new Promise((resolve, reject) => {
      axios.post(url, data, {
        headers: {
          'Content-Type': 'application/json',
          Authorization: `Bearer ${Gateways.$token}`
        }
      }).then((res)=>{
        resolve(res)
      }).catch((err) =>{
        reject(err)
      })
    })
  }

  static getTenantId(url) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: url,
        dataType: 'json',
        type: 'get',
        crossDomain: true,
        success: function (data) {
          resolve(data)
        },
        error: function (error) {
          reject(error)
        }
      })
    })
  }

  static deleteTenantId(url) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: url,
        dataType: 'json',
        type: 'delete',
        crossDomain: true,
        success: function (data) {
          resolve(data)
        },
        error: function (error) {
          reject(error)
        }
      })
    })
  }


}

export default Gateways;
