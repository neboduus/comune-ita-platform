import axios from "axios";
import 'formiojs/dist/formio.form.min.css';
import {Formio} from "formiojs";

const _ = require('lodash');

class ProtocolProviders {

  $language; // Browser language
  $alertError; // Html element alert errors
  $tenant; // Tenant Slug
  $apiService; // API Class

  $availableConfigs;

  static init() {

    // Init value variables
    ProtocolProviders.$language = document.documentElement.lang.toString();
    ProtocolProviders.$alertError = $('.save-alert');

    // Get tenant slug
    ProtocolProviders.$tenant = window.location.pathname.split('/')[1];
    ProtocolProviders.createPopulateExternalProtocolChoice()
    ProtocolProviders.handleActionButton()
    ProtocolProviders.showParameters();
  }

  static showParameters() {
    // Hide configuration parameters if provider is not enabled
    $('input[name="protocol_data[protocol_handler]"]').each(function (i, e) {
      if (e.checked && $(e).data('url')) {
        $(`[id$="collapse-${e.value}"]`).removeClass('d-none');
        $(`[id$="heading-${e.value}"]`).removeClass('d-none');
      } else {
        $(`[id$="collapse-${e.value}"]`).addClass('d-none');
        $(`[id$="heading-${e.value}"]`).addClass('d-none');
      }
    });
  }

  // Enable hidden radio on click Enable button
  static disablePreviousConfiguration(previousHandler) {
    return new Promise((resolve, reject) => {
      // If there is already a configuration for the previously selected external protocol, disable it
      if (previousHandler.hasClass('external-register-choice') && previousHandler.data('url')) {
        const identifier = previousHandler.data('identifier').replace(/\./g, '');
        const serviceId = previousHandler.data('service');
        let url = `${previousHandler.data('url')}/services/${serviceId}`;

        let customHeaders = previousHandler.data('headers')
        let headers = {
          'Content-Type': 'application/json'
        }
        if (customHeaders) {
          customHeaders.split(',').forEach(function (item) {
            let splits = item.split('=');
            headers[splits[0]] = splits[1]
          })
        }

        // Check if configuration exixts
        ProtocolProviders.getServiceId(url, headers).then((result) => {
          if (result.configs.length === 0) {
            // No configurations, nothing to disable
            resolve();
          }
          result.configs.forEach(function (config) {
            // Disable each configuration
            config['is_active'] = false;
            ProtocolProviders.putServiceId(url, result, headers).then(() => {
              // Service Updated
              resolve();
            }).catch(() => {
              $('#card-collapse-' + identifier).html(`<div>${Translator.trans('servizio.error_disable_external_protocol', {}, 'messages', this.$language)}</div>`);
              reject();
            });
          })
        }).catch(err => {
          if (err.status !== 404) {
            $('#card-collapse-' + identifier).html('<div>' + err.statusText + '</div>')
            reject();
          } else {
            // No previous external configuration
            resolve();
          }
        })
      } else {
        // Nothing to disable for legacy providers
        resolve();
      }
    });
  }


  static enableExistingConfiguration(handler) {
    return new Promise((resolve, reject) => {
      if (handler.hasClass('external-register-choice') && handler.data('url')) {
        const serviceId = handler.data('service');
        const identifier = handler.data('identifier').replace(/\./g, '');
        const url = `${handler.data('url')}/services/${serviceId}`

        let customHeaders = handler.data('headers')
        let headers = {
          'Content-Type': 'application/json'
        }
        if (customHeaders) {
          customHeaders.split(',').forEach(function (item) {
            let splits = item.split('=');
            headers[splits[0]] = splits[1]
          })
        }

        ProtocolProviders.getServiceId(url, headers).then((result) => {
          // Enable existing configuration
          if (result.configs.length === 1) {
            // Enable the only one configuration available
            result.configs[0]['is_active'] = true;
          } else if (result.configs.length > 1) {
            // More configurations: alert user to manually enable deisired configuration
            alert(`${Translator.trans('servizio.mupliple_provider_configurations', {}, 'messages', this.$language)}`);
          } else {
            // No configs, nothing to enable
            resolve()
          }
          ProtocolProviders.putServiceId(url, result, headers).then(() => {
            // Service configurations updated
            resolve();
          }).catch(() => {
            $('#card-collapse-' + identifier).html(`<div>${Translator.trans('servizio.error_enable_external_protocol', {}, 'messages', this.$language)}</div>`)
            reject();
          });
        }).catch(err => {
          if (err.status !== 404) {
            $('#card-collapse-' + identifier).html('<div>' + err.statusText + '</div>')
            reject();
          } else {
            // No configuration found, nothing to enable
            resolve();
          }
        })
      } else {
        // Nothing to enable for legacy providers
        resolve();
      }
    });
  }


  static handleActionButton() {
    $('button[data-parent*="protocol_handler_"]').click(function (e) {
      // Disable previously checked handler
      let previousHandler = $('input[name="protocol_data[protocol_handler]"]:checked');

      ProtocolProviders.disablePreviousConfiguration(previousHandler).then(() => {
        const parentId = $(this).data('parent');
        let input = $(`input[id$="${parentId}"]`)
        const checked = !input.is(':checked')

        // Enable/disable protocol required checkbox
        let protocolRequired = $('#protocol_data_protocol_required');
        protocolRequired.prop('checked', checked);
        protocolRequired.trigger('change');

        if (checked) {
          // User selected new provider
          ProtocolProviders.enableExistingConfiguration(input).then(() => {
            // Enable provider radio
            input.prop("checked", checked);
            input.attr('checked', 'checked').change();

            $("form").first().trigger("submit");
          })
        } else {
          // User selected disable button: Disable protocol required on disable provider button
          $("form").first().trigger("submit");
        }
      })
    })
  }

  static cleanSubmission(submission) {
    return JSON.parse(JSON.stringify(submission), (key, value) => {
      // Replace all empty strings with null values
      return value === '' ? null : value;
    });
  }


  static createPopulateExternalProtocolChoice() {
    $('.external-register-choice').each((i, e) => {
      let checked = $(e).is(':checked');
      let baseUrl = $(e).data('url');

      if (!checked || !baseUrl) {
        // If provider is not selected don't render schema or provider has not an external url
        return
      }

      const identifier = $(e).data('identifier');
      // Sanitized for "." in name
      const sanitizeIdentifier = identifier.replace(/\./g, '');

      const tenantId = $(e).data('tenant');
      const serviceId = $(e).data('service');

      let isFirstConf = false;
      let customHeaders = $(e).data('headers')
      let headers = {
        'Content-Type': 'application/json'
      }
      if (customHeaders) {
        customHeaders.split(',').forEach(function (item) {
          let splits = item.split('=');
          headers[splits[0]] = splits[1]
        })
      }

      const $providerSettingsContainer = $('<div id="ente_' + sanitizeIdentifier + '" class="provider-form-type"></div>');
      let description = `${ProtocolProviders.$tenant} / ${serviceId}`
      let settings = {
        "description": description,
        "tenant_sdc_id": tenantId,
        "sdc_id": serviceId,
        "application_states": [
          "status_submitted_after_integration",
          "status_request_integration",
          "status_cancelled",
          "status_complete",
          "status_withdraw",
          "status_submitted"
        ],
        "configs": [
          {
            "description": description,
            "is_active": true
          }
        ]
      }

      ProtocolProviders.getServiceSchema(`${baseUrl}/schema`, headers).then((result) => {
        $('#card-collapse-' + sanitizeIdentifier).html($providerSettingsContainer)
        if (document.getElementById('ente_' + sanitizeIdentifier)) {
          Formio.createForm(document.getElementById('ente_' + sanitizeIdentifier), result, {
            noAlerts: true,
            buttonSettings: {showCancel: false},
            readOnly: !checked,
          }).then(function (form) {
            if (result.data) {
              settings = result.data;
            }

            // Populate default submission
            form.submission = {
              data: settings
            };
            let initForm
            let isReady = false

            form.nosubmit = true;
            form.on('submit', function (submission) {
              submission = ProtocolProviders.cleanSubmission(submission)
              if (isFirstConf) {
                // No previous configurations, create new one
                ProtocolProviders.postServiceId(`${baseUrl}/services/`, submission.data, headers)
                  .then(function (response) {
                    form.emit('submitDone', submission)
                  }).catch((error) => {
                  form.emit('submitError')
                });
              } else {
                // Update existing configuration
                ProtocolProviders.putServiceId(`${baseUrl}/services/${serviceId}`, submission.data, headers)
                  .then(function (response) {
                    form.emit('submitDone', submission)
                  }).catch((error) => {
                  form.emit('submitError')
                });
              }
            });
            form.ready.then((event) => {
              ProtocolProviders.getServiceId(`${baseUrl}/services/${serviceId}`, headers).then((res) => {
                if (!res.configs.length) {
                  // Overwrite default config if not set
                  res.configs = settings.configs
                }

                // Overwrite form submission with existing data
                form.submission = {
                  data: res
                };

                initForm = {...res, submit: false}
                isReady = true
              }).catch((error) => {
                initForm = {...form.submission.data}
                isReady = true
                if (error.status === 404) {
                  // No configuration found, first one
                  isFirstConf = true;
                }
              });
            });
          })
        }
      }).catch(err => {
        // schema not found or not available
        if (err.status === 404) {
          $('#card-collapse-' + sanitizeIdentifier).html(`<div>${Translator.trans('servizio.no_protocol_form_schema', {}, 'messages', this.$language)}</div>`)
        } else {
          $('#card-collapse-' + sanitizeIdentifier).html('<div>' + err.statusText + '</div>')
        }
      })
    });
  }


  static getServiceSchema(url, headers) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: url,
        dataType: 'json',
        headers: headers,
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

  static getServiceId(url, headers) {
    return new Promise((resolve, reject) => {
      $.ajax({
        url: url,
        dataType: 'json',
        headers: headers,
        type: 'GET',
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

  static putServiceId(url, data, headers) {
    return new Promise((resolve, reject) => {
      axios.put(url, data, {
        headers: headers,
      }).then((res) => {
        resolve(res)
      }).catch((err) => {
        reject(err)
      })
    })
  }

  static postServiceId(url, data, headers) {
    return new Promise((resolve, reject) => {
      axios.post(url, data, {
        headers: headers,
      }).then((res) => {
        resolve(res)
      }).catch((err) => {
        reject(err)
      })
    })
  }
}

export default ProtocolProviders;
