'use strict';

Vue.component('scia_ediliza_soggetti', {
    template: `<div>
        <div>
            <el-upload
              class="vue-upload"
              :data="{type: 'type'}"
              :action="prefix+'/pratiche/allegati/upload/scia/'+idPratica"
              :file-list="files"
              :on-success="onChange"
              :on-remove="onRemove"
              :before-upload="onBeforeUpload">
              <el-button size="small" type="primary">Carica allegato</el-button>
              <div slot="tip" class="el-upload__tip">Sono permessi solo file di tipo p7m</div>
            </el-upload>
        </div>
    </div>`,
    data: function () {
        return vueBundledData
    },
    computed: {},
    created: function () {
        if(!this.elencoSoggettiAventiTitolo) {
            var el = document.getElementById('scia_pratica_edilizia_soggetti_dematerialized_forms').value;
            this.elencoSoggettiAventiTitolo = JSON.parse(el) || [];
        }
    },
    methods: {
        onChange(response, file, fileList){
            this.elencoSoggettiAventiTitolo.push(response)
            this.updateFormValue()
        },
        onRemove(file, fileList) {
            this.elencoSoggettiAventiTitolo = fileList
            this.updateFormValue()
        },
        onBeforeUpload(file) {
            const isP7m = (file.type === 'application/pkcs7-mime' || file.type === '');
            if (!isP7m)
            {
                this.$message.error('Attenzione: Sono permessi solo file di tipo p7m!!!');
            }
            return isP7m;
        },
        updateFormValue() {
            var el = document.getElementById('scia_pratica_edilizia_soggetti_dematerialized_forms')
            el.value = JSON.stringify(this.elencoSoggettiAventiTitolo);
        }
    }
})

