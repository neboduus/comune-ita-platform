import 'formiojs/dist/formio.form.min.css';
import {Formio} from "formiojs";


const language = document.documentElement.lang.toString();

class GraphicAspectTenant {
  static init() {
    let formContainers = document.getElementsByClassName("graphic-aspect");

    Array.from(formContainers).forEach((formContainer, index) => {
      let schema = formContainer.getAttribute("data-url");
      let locale = formContainer.getAttribute("data-locale");
      let enteMetaTextarea = document.getElementById('ente_meta_' + locale);

      Formio.createForm(formContainer, schema, {
        noAlerts: true,
        buttonSettings: {showCancel: false},
      }).then(function (form) {
        form.nosubmit = true;

        if(enteMetaTextarea.value){
          form.submission = {
            data: JSON.parse(enteMetaTextarea.value)
          }
        }

        //form.ready.then(() => {});
        form.on('change', (changed) => {
          enteMetaTextarea.value = JSON.stringify(changed.data);
          if (form.checkValidity(null, true, null, false)) {
            $('#meta-alert-'+locale).addClass('d-none');
          } else {
            let lang = locale.charAt(0).toUpperCase() + locale.slice(1); //capitalize first letter
            $('#meta-alert-'+locale).removeClass('d-none')
              .html(Translator.trans('errori.tenant.graphic_aspect', {"lang": lang}, 'messages', language));
          }
        });

      });
    });

  }

}


export default GraphicAspectTenant;

