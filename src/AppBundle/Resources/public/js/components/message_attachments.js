'use strict';

Vue.component('message_attachments', {
  template: `<div>
      <el-upload
        class="vue-upload pb-0"
        :action="upload_url"
        :on-success="onSuccess"
        :on-remove="onRemove"
        :on-error="onError"
        :before-upload="onBeforeUpload"
        :multiple="true">
        <el-button type="primary">Carica allegato</el-button>
      </el-upload>
    </div>`,
  data: function () {
    return vueBundledDataMessage
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
        this.$message.error('Attenzione: non è possibile caricare file con una dimensione maggiore di 25Mb.');
        return false;
      }
    },
    updateFormValue() {
      document.getElementById('message_attachments').value = JSON.stringify(this.attachments)
    }
  }

})
