'use strict';

Vue.component('scia_ediliza_modulo_scia', {
    template: `<div>
        <div>
            <el-upload
              class="vue-upload"
              :data="{type: 'type'}"
              :action="'/pratiche/allegati/upload/scia/'+idPratica"
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
        if(!this.attachments) {
            var el = document.getElementById('scia_pratica_edilizia_modulo_scia_dematerialized_forms').value;
            this.attachments = JSON.parse(el) || [];
        }
    },
    methods: {
        onChange(response, file, fileList){
            this.attachments.push(response);
            this.updateFormValue()
        },
        onRemove(file, fileList) {
            this.attachments = [];
            for (var i = 0; i < fileList.length; i++) {
                if (fileList[i].response)
                {
                    this.attachments.push(fileList[i].response);
                }
                else
                {
                    // Se il file è presente nel database non ho i dati nel response
                    this.attachments.push({
                        id: fileList[i].id,
                        name: fileList[i].name,
                        type: fileList[i].type
                    });
                }

            }
            this.updateFormValue()
        },
        onBeforeUpload(file) {
            this.disableButtons()
            if (this.attachments.length > 0)
            {
                this.$message.error('Attenzione: è possibile caricare solo un file!!!');
                this.enableButtons()
                return false;
            }

            const isP7m = (file.type === 'application/pkcs7-mime' || file.type === '');
            if (!isP7m)
            {
                this.$message.error('Attenzione: Sono permessi solo file di tipo p7m!!!');
                this.enableButtons()
            }
            return isP7m;
        },
        updateFormValue() {
            var el = document.getElementById('scia_pratica_edilizia_modulo_scia_dematerialized_forms')
            el.value = JSON.stringify(this.attachments);
            this.enableButtons()
        },
        enableButtons(){
            $('[type=submit]').attr('disabled', false);
            $('.el-button').attr('disabled', false);
        },
        disableButtons(){
            $('[type=submit]').attr('disabled', 'disabled');
            $('.el-button').attr('disabled', 'disabled');
        }
    }
})
