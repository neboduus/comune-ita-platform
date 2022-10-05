import Auth from "../auth/Auth";
import axios from "axios";
import 'formiojs/dist/formio.form.min.css';
import {Formio} from "formiojs";
import CompareEveryItemArray from "../../utils/CompareEveryItemArray";

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

   // Gateways.detectChangeValueFormGateway()
    Gateways.createPopulateExternalPayChoice()
    // Mypay Payment gateways
    Gateways.detectChangeLegacyGateway();

    Gateways.handleActionButton()

  }

  //MyPay
  static detectChangeLegacyGateway(){

    const myPayElement = $('input[type="checkbox"][value="mypay"]')
    if(myPayElement.is(':checked')){
        $('#ente_' + myPayElement.val()).removeClass('d-none');
      } else {
        $('#ente_' + myPayElement.val()).addClass('d-none');
      }

    myPayElement.change(function () {
      if (this.checked) {
        $('#ente_' + $(this).val()).removeClass('d-none');
      } else {
        $('#ente_' + $(this).val()).addClass('d-none');
      }
    })
  }


  static detectChangeValueFormGateway(){

    // Init variables
    let initGatewayValue = [] // Value on load page
    let currentGatewayValue = [] // Value on change

    $('.row-payments').find('input[type="checkbox"]').each(function(){
      if(this.checked){
        initGatewayValue.push($(this).val());
        currentGatewayValue = initGatewayValue
      }
    })
    // On change
    $('.row-payments').find('input[type="checkbox"]').change(function () {
      if (this.checked) {
        currentGatewayValue.push(this.value)
      } else {
        currentGatewayValue = currentGatewayValue.filter(item => !this.value.includes(item))
      }
      if(!CompareEveryItemArray(currentGatewayValue,initGatewayValue)){
        Gateways.$alertError.removeClass('d-none').addClass('d-block')
      }else{
        Gateways.$alertError.removeClass('d-block').addClass('d-none')
      }
    })
  }

  // Enable or Disable hidden checkbox gateways on click Enable/Disable button
  static handleActionButton(){
    $('button[data-parent*="ente_gateways_"]').click(function (e) {
      const parentId = $(this).data('parent');
      const newCheckboxValue = !$(`#${parentId}`).is('checked')
      $(`#${parentId}`).prop( "checked", newCheckboxValue );
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
    const newCheckboxValue = !$(`input[data-identifier="${identifier}"]`).is('checked')
    $(`input[data-identifier="${identifier}"]`).prop( "checked", newCheckboxValue );
        $("form").first().trigger("submit");
  }

  static detectChangeFormIO(initForm, data) {
    const objectsEqual =
       Object.keys(initForm).length === Object.keys(data).length
      && Object.keys(initForm).every(p => initForm[p] === data[p]);

    if (!objectsEqual) {
      Gateways.$alertError.removeClass('d-none').addClass('d-block')
    } else {
      Gateways.$alertError.removeClass('d-block').addClass('d-none')
    }
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

              //detect change in form
              let initForm
              let isReady = false
              form.on('change', function (event) {

                if(initForm && isReady){
                  Gateways.detectChangeFormIO(initForm,event.data)
                }
              })

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
