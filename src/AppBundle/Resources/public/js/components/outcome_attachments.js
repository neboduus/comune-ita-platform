'use strict';
Vue.component('outcome_attachments', {
  template: `<div>
      <el-upload
        class="vue-upload pb-0"
        :action="prefix+'/operatori/allegati/upload/'+applicationId"
        :on-success="onSuccess"
        :on-remove="onRemove"
        :on-error="onError"
        :before-upload="onBeforeUpload"
        :multiple="true">
        <el-button type="primary">${Translator.trans('pratica.upload_attachment', {}, 'messages', 'it')}</el-button>
      </el-upload>
    </div>`,
  data: function () {
    return vueBundledData
  },
  computed: {},
  created: function () {
    this.updateFormValue()
  },
  methods: {
    onSuccess(response, file, fileList) {
      this.attachments.push({
        id: response.id,
        name: response.name,
        type: response.type
      })
      this.updateFormValue()
    },
    onError(response, file, fileList) {
      alert(response);
      return false;
    },
    onRemove(file, fileList) {
      if (!file.id && file.response) {
        file.id = file.response.id
      }
      for (var o = 0; o < this.attachments.length; o++) {
        if (this.attachments[o].id === file.id) {
          this.attachments.splice(o, 1);
        }
      }
      this.updateFormValue()
    },
    onBeforeUpload(file) {
      if (file.size / 1024 / 1024 > 25) {
        this.$message.error(`${Translator.trans('pratica.upload_warning', {}, 'messages', 'it')}`);
        return false;
      }
    },
    updateFormValue() {
      document.getElementById('outcome_attachments').value = JSON.stringify(this.attachments)
    }
  }
})
