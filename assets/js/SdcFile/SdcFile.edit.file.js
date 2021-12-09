"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

require("core-js/modules/es.array.map.js");

var _Formio = _interopRequireDefault(require("formiojs/Formio"));

var _lodash = _interopRequireDefault(require("lodash"));

function _interopRequireDefault(obj) {
  return obj && obj.__esModule ? obj : {default: obj};
}

var _default = [{
  type: 'select',
  input: true,
  key: 'storage',
  label: 'Storage',
  placeholder: 'Select your file storage provider',
  weight: 0,
  tooltip: 'Which storage to save the files in.',
  valueProperty: 'value',
  dataSrc: 'custom',
  hidden: true,
  data: {
    custom: function custom() {
      return [{
        label: 'url',
        value: 'url'
      }
      ]
    }
  }
}, {
  type: 'textfield',
  input: true,
  key: 'url',
  label: 'Url',
  weight: 10,
  placeholder: 'Enter the url to post the files to',
  hidden: false,
  value: 'url'
},
  {
    type: 'checkbox',
    input: true,
    key: 'protocol_required',
    label: 'Richiede protocollazione?',
    tooltip: '',
    weight: 30
  },
  {
    type: 'textfield',
    input: true,
    key: 'options.indexeddb',
    label: 'Database',
    weight: 10,
    placeholder: 'Enter the indexeddb database name',
    conditional: {
      json: {
        in: [{
          var: 'data.storage'
        }, ['indexeddb']]
      }
    }
  },
  {
    type: 'textfield',
    input: true,
    label: 'Table',
    key: 'options.indexeddbTable',
    weight: 10,
    placeholder: 'Enter the name for indexeddb table',
    conditional: {
      json: {
        in: [{
          var: 'data.storage'
        }, ['indexeddb']]
      }
    }
  },
  {
    type: 'textarea',
    key: 'options',
    label: 'Custom request options',
    tooltip: 'Pass your custom xhr options(optional)',
    rows: 5,
    editor: 'ace',
    input: true,
    weight: 15,
    placeholder: "{\n  \"withCredentials\": true\n}",
    conditional: {
      json: {
        '===': [{
          var: 'data.storage'
        }, 'url']
      }
    }
  },
  {
    type: 'datagrid',
    input: true,
    label: 'File Types',
    key: 'fileTypes',
    tooltip: 'Specify file types to classify the uploads. This is useful if you allow multiple types of uploads but want to allow the user to specify which type of file each is.',
    weight: 11,
    components: [{
      label: 'Label',
      key: 'label',
      input: true,
      type: 'textfield'
    }, {
      label: 'Value',
      key: 'value',
      input: true,
      type: 'textfield'
    }]
  }, {
    type: 'textfield',
    input: true,
    key: 'filePattern',
    label: 'File Pattern',
    placeholder: '.jpg,video/*,application/pdf',
    tooltip: 'See <a href=\'https://github.com/danialfarid/ng-file-upload#full-reference\' target=\'_blank\'>https://github.com/danialfarid/ng-file-upload#full-reference</a> for how to specify file patterns.',
    weight: 50
  }, {
    type: 'textfield',
    input: true,
    key: 'fileMinSize',
    label: 'File Minimum Size',
    placeholder: '1MB',
    tooltip: 'See <a href=\'https://github.com/danialfarid/ng-file-upload#full-reference\' target=\'_blank\'>https://github.com/danialfarid/ng-file-upload#full-reference</a> for how to specify file sizes.',
    weight: 60
  }, {
    type: 'textfield',
    input: true,
    key: 'fileMaxSize',
    label: 'File Maximum Size',
    placeholder: '10MB',
    tooltip: 'See <a href=\'https://github.com/danialfarid/ng-file-upload#full-reference\' target=\'_blank\'>https://github.com/danialfarid/ng-file-upload#full-reference</a> for how to specify file sizes.',
    weight: 70
  }
];
exports.default = _default;

