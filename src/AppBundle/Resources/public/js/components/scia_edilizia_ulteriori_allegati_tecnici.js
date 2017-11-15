'use strict';

Vue.component('scia_edilizia_ulteriori_allegati_tecnici', {
    template: `<div>
        <template v-for="(allegato, i) in allegati">
        <div>
            <strong>{{allegato.title}} <i class="text-danger fa fa-asterisk" style="padding-left: 5px;" v-if="allegatiRichiesti[i]"></i></strong>
            <p class="description" v-html="allegato.description"></p>
        </div>

        <div>
            <el-upload
              class="vue-upload"
              :data="{type: allegato.type, index: allegato.identifier}"
              :action="prefix+'/pratiche/allegati/upload/scia/'+idPratica"
              :file-list="allegato.files"
              :on-success="onSuccess"
              :on-remove="onRemove"
              :before-upload="onBeforeUpload">
              <el-button size="small" type="primary">Carica allegato</el-button>
              <div slot="tip" class="el-upload__tip">Sono permessi solo file di tipo p7m</div>
            </el-upload>
        </div>
        </template>
    </div>`,
    data: function () {
        return vueBundledData
    },
    computed: {},
    created: function () {
        this.allegatiCorrenti.toJSON = function(){
            return Object.assign({}, this)
        }
        this.updateFormValue()
    },
    methods: {
        onSuccess(response, file, fileList){
            if(!this.allegatiCorrenti[response.index]) {
                this.allegatiCorrenti[response.index] = []
            }
            this.allegatiCorrenti[response.index].push({
                id: response.id,
                name: response.name,
                type: response.type
            })
            this.updateFormValue()
        },
        onRemove(file, fileList) {
            if (!file.id && file.response){
                file.id = file.response.id
            }
            for (var prop in this.allegatiCorrenti) {
                for (var o = 0; o < this.allegatiCorrenti[prop].length; o++) {
                    if(this.allegatiCorrenti[prop][o].id == file.id){
                        this.allegatiCorrenti[prop].splice(o, 1);
                    }
                }
            }
            this.updateFormValue()
        },
        onBeforeUpload(file) {
            this.disableButtons()
            const isP7m = (file.type === 'application/pkcs7-mime' || file.type === '');
            if (!isP7m)
            {
                this.$message.error('Attenzione: Sono permessi solo file di tipo p7m!!!');
                this.enableButtons()
            }
            return isP7m;
        },
        updateFormValue() {
            var el = document.getElementById('scia_pratica_edilizia_ulteriori_allegati_tecnici_dematerialized_forms'),
                formValue = {
                    'elencoUlterioriAllegatiTecnici' : this.allegatiCorrenti
                };
            el.value = JSON.stringify(formValue)
            this.enableButtons()
        },
        enableButtons(){
            $('[type=submit]').attr('disabled', false);
        },
        disableButtons(){
            $('[type=submit]').attr('disabled', 'disabled');
        }
    }
})
