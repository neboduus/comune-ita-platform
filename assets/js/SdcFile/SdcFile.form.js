"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = _default;

require("core-js/modules/es.array.concat");

var _Component = _interopRequireDefault(require("formiojs/components/_classes/component/Component.form"));

var _FileSdcDisplay = _interopRequireDefault(require("./SdcFile.edit.display"));

var _FileSdcData = _interopRequireDefault(require("./SdcFile.edit.data"));

var _FileSdcFile = _interopRequireDefault(require("./SdcFile.edit.file"));

var _FileSdcValidation = _interopRequireDefault(require("./SdcFile.edit.validation"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _default() {
  for (var _len = arguments.length, extend = new Array(_len), _key = 0; _key < _len; _key++) {
    extend[_key] = arguments[_key];
  }

  return _Component.default.apply(void 0, [[
    {
      key: 'display',
      components: _FileSdcDisplay.default
    }, {
      key: 'data',
      components: _FileSdcData.default
    }, {
      label: 'File',
      key: 'file',
      weight: 5,
      components: _FileSdcFile.default
    },
    {
      key: 'validation',
      components: _FileSdcValidation.default
    }
  ]].concat(extend));
}
