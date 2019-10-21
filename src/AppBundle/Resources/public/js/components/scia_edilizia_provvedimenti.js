'use strict';

Vue.component('scia_edilizia_provvedimenti', {
    template: `<div>
        <template v-for="(allegato, i) in allegati">
        <div  class="row-upload">
        <div>
            <strong>{{allegato.title}} <i class="mandatory" v-if="allegatiRichiesti[i]"></i></strong>
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
            const isP7m = (file.type === 'application/pkcs7-mime' || file.type === '');
            if (!isP7m)
            {
                this.$message.error('Attenzione: Sono permessi solo file di tipo p7m!!!');
            }
            return isP7m;
        },
        updateFormValue() {
            var el = document.getElementById('scia_pratica_edilizia_provvedimenti_dematerialized_forms'),
                formValue = {
                    'elencoProvvedimenti' : this.allegatiCorrenti
                };
            el.value = JSON.stringify(formValue)
        }
    }
})
