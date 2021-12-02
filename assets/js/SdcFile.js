import File from 'formiojs/components/file/File';
import editForm from './SdcFile/SdcFile.form'
import {uniqueName} from 'formiojs/utils/utils';
import axios from "axios";

const endpoint = window.location.protocol + "//" + window.location.host + "/" + window.location.pathname.split("/")[1] + "/it"

export default class SdcFile extends File {
  constructor(component, options, data) {
    super(component, options, data);
  }

  static schema() {
    return File.schema({
      type: 'sdcfile'
    });
  }

  static builderInfo = {
    title: 'File Sdc',
    group: 'basic',
    icon: 'fa fa-file',
    weight: 70,
    schema: SdcFile.schema()
  }

  static editForm = editForm

  /**
   * Render returns an html string of the fully rendered component.
   *
   * @param children - If this class is extendended, the sub string is passed as children.
   * @returns {string}
   */
  render() {
    // To make this dynamic, we could call this.renderTemplate('templatename', {}).
    return super.render(this.renderTemplate('file', {
      fileSize: this.fileSize,
      files: this.dataValue || [],
      statuses: this.statuses,
      disabled: this.disabled,
      support: this.support,
      fileDropHidden: this.fileDropHidden
    }));
  }

  /**
   * After the html string has been mounted into the dom, the dom element is returned here. Use refs to find specific
   * elements to attach functionality to.
   *
   * @param element
   * @returns {Promise}
   */
  attach(element) {
    return super.attach(element);
  }

  /**
   * Get the value of the component from the dom elements.
   *
   * @returns {Array}
   */
  getValue() {
    return this.dataValue;
  }

  get defaultValue() {
    const value = super.defaultValue;
    return Array.isArray(value) ? value : [];
  }


  /**
   * Set the value of the component into the dom elements.
   *
   * @param value
   * @returns {boolean}
   */
  //setValue(value) {}

  upload(files) {
    // Only allow one upload if not multiple.
    if (!this.component.multiple) {
      files = Array.prototype.slice.call(files, 0, 1);
    }
    if (this.component.storage && files && files.length) {
      this.fileDropHidden = true;

      // files is not really an array and does not have a forEach method, so fake it.
      Array.prototype.forEach.call(files, async (file) => {
        const fileName = uniqueName(file.name, this.component.fileNameTemplate, this.evalContext());
        let fileUpload = {
          name: fileName,
          original_filename: file.name,
          size: file.size,
          status: 'info',
          message: this.t('Starting upload'),
          protocol_required: this.component.protocol_required,
          mime_type: file.type
        };

        // Check if file with the same name is being uploaded
        const fileWithSameNameUploaded = this.dataValue.some(fileStatus => fileStatus.originalName === file.name);
        const fileWithSameNameUploadedWithError = this.statuses.findIndex(fileStatus =>
          fileStatus.originalName === file.name
          && fileStatus.status === 'error'
        );

        if (fileWithSameNameUploaded) {
          fileUpload.status = 'error';
          fileUpload.message = this.t('File with the same name is already uploaded');
        }

        if (fileWithSameNameUploadedWithError !== -1) {
          this.statuses.splice(fileWithSameNameUploadedWithError, 1);
          this.redraw();
        }


        // Check file pattern
        if (this.component.filePattern && !this.validatePattern(file, this.component.filePattern)) {
          fileUpload.status = 'error';
          fileUpload.message = this.t('File is the wrong type; it must be {{ pattern }}', {
            pattern: this.component.filePattern,
          });
        }

        // Check file minimum size
        if (this.component.fileMinSize && !this.validateMinSize(file, this.component.fileMinSize)) {
          fileUpload.status = 'error';
          fileUpload.message = this.t('File is too small; it must be at least {{ size }}', {
            size: this.component.fileMinSize,
          });
        }

        // Check file maximum size
        if (this.component.fileMaxSize && !this.validateMaxSize(file, this.component.fileMaxSize)) {
          fileUpload.status = 'error';
          fileUpload.message = this.t('File is too big; it must be at most {{ size }}', {
            size: this.component.fileMaxSize,
          });
        }

        // Get a unique name for this file to keep file collisions from occurring.
        const dir = this.interpolate(this.component.dir || '');
        const {fileService} = this;
        if (!fileService) {
          fileUpload.status = 'error';
          fileUpload.message = this.t('File Service not provided.');
        }

        this.statuses.push(fileUpload);
        this.redraw();

        if (fileUpload.status !== 'error') {
          if (this.component.privateDownload) {
            file.private = true;
          }
          const {storage, options = {}} = this.component;
          const url = this.interpolate(this.component.url);
          let groupKey = null;
          let groupPermissions = null;

          //Iterate through form components to find group resource if one exists
          this.root.everyComponent((element) => {
            if (element.component?.submissionAccess || element.component?.defaultPermission) {
              groupPermissions = !element.component.submissionAccess ? [
                {
                  type: element.component.defaultPermission,
                  roles: [],
                },
              ] : element.component.submissionAccess;

              groupPermissions.forEach((permission) => {
                groupKey = ['admin', 'write', 'create'].includes(permission.type) ? element.component.key : null;
              });
            }
          });

          const fileKey = this.component.fileKey || 'file';
          const groupResourceId = groupKey ? this.currentForm.submission.data[groupKey]._id : null;
          let processedFile = null;
          if (this.root.options.fileProcessor) {
            try {
              if (this.refs.fileProcessingLoader) {
                this.refs.fileProcessingLoader.style.display = 'block';
              }
              const fileProcessorHandler = fileProcessor(this.fileService, this.root.options.fileProcessor);
              processedFile = await fileProcessorHandler(file, this.component.properties);
            } catch (err) {
              fileUpload.status = 'error';
              fileUpload.message = this.t('File processing has been failed.');
              this.fileDropHidden = false;
              this.redraw();
              return;
            } finally {
              if (this.refs.fileProcessingLoader) {
                this.refs.fileProcessingLoader.style.display = 'none';
              }
            }
          }

          fileUpload.message = this.t('Starting upload.');
          this.redraw();

          let idUpload;
          axios.post(url, fileUpload, {
            maxContentLength: Infinity,
            maxBodyLength: Infinity,
          })
            .then((fileInfo) => {
              idUpload = fileInfo.data.id
              fileUpload.status = 'progress';
              fileUpload.progress = parseInt(0);
              const formData = new FormData();
              formData.append('file', file)
              axios.put(fileInfo.data.uri, formData, {
                onUploadProgress: progressEvent => {
                  let percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                  fileUpload.progress = parseInt(percentCompleted);
                  console.log('file progress', fileUpload.progress)
                  fileUpload.status = 'progress';
                  delete fileUpload.message;
                  this.fileDropHidden = false;
                  this.redraw();
                  this.triggerChange();
                },
                maxContentLength: Infinity,
                maxBodyLength: Infinity,
                headers: {'Content-Type': 'multipart/form-data'}

              }).then(response => {
                const regex = /\\|"/gi;
                const etag = response.headers.etag.replace(regex, '') || null

                const index = this.statuses.indexOf(fileUpload);
                if (index !== -1) {
                  this.statuses.splice(index, 1);
                }

                fileInfo.originalName = file.name;
                fileInfo.name = fileUpload.name;
                fileInfo.size = fileUpload.size;
                fileInfo.mime_type = fileUpload.mime_type
                fileInfo.url = endpoint + "/allegati/"+ idUpload
                fileInfo.data.baseUrl = endpoint + "/allegati/"+ idUpload
                fileInfo.storage = 'url'
                fileInfo.protocol_required = fileUpload.protocol_required
                if (!this.hasValue()) {
                  this.dataValue = [];
                }
                this.dataValue.push(fileInfo);
                this.redraw();
                this.triggerChange();
                axios.put(endpoint + '/upload/' + idUpload, {
                  file_hash: etag
                }).then(resp => {
                }).catch(err => {
                  console.error('error put finalize', err)
                  fileUpload.status = 'error';
                  fileUpload.message = err;
                  delete fileUpload.progress;
                  this.fileDropHidden = false;
                  this.redraw();
                  this.triggerChange();
                })
              }).catch(err => {
                console.error('error put aws', err)
                fileUpload.status = 'error';
                fileUpload.message = err;
                delete fileUpload.progress;
                this.fileDropHidden = false;
                this.redraw();
                this.triggerChange();
              })
            })
            .catch((response) => {
              fileUpload.status = 'error';
              fileUpload.message = response;
              delete fileUpload.progress;
              this.fileDropHidden = false;
              this.redraw();
              this.triggerChange();
            });
        }
      });
    }
  }


  fileReader(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        resolve(reader.result)
      };

      reader.readAsArrayBuffer(file)
    })
  }
}
