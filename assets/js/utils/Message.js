import { Dropzone } from "dropzone";
import './Api';


class Message {

  static init() {

    const $uploadList = $('.upload-list');
    const uploadListItem = `<div class="nk-upload-item">
                              <div class="nk-upload-icon">
                                <input name="documents[]" type="hidden" class="attachments">
                                <a class="nk-file-link" href="#" target="_blank"><em class="icon ni ni-file fa-3x"></em></a>
                              </div>
                              <div class="nk-upload-info">
                                <div class="nk-upload-title">
                                  <a class="nk-file-link mr-1 nk-tooltip" href="#" target="_blank" title="Scarica"><em class="icon ni ni-download"></em></a>
                                  <span class="title" data-dz-name></span>
                                </div>
                                <div class="nk-upload-size" data-dz-size></div>
                                <div class="nk-upload-progress">
                                  <div class="progress progress-sm">
                                    <div class="progress-bar" style="width: 0;"
                                         data-progress="0" data-dz-uploadprogress
                                         aria-valuenow="0" aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                  </div>
                                </div>
                              </div>
                              <div class="nk-upload-action">
                                <button type="button"
                                   class="btn btn-icon btn-trigger js-delete-btn" data-dz-remove><em
                                   class="icon ni ni-trash"></em></button>
                              </div>
                            </div>`;

    const dropzone = new Dropzone(".dropzone", {
      url: '/',
      previewTemplate: uploadListItem,
      previewsContainer: $uploadList.get(0),
    });


    console.log('init');
  }

}

export default Message;
