"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

const location = window.location
const explodedPath = location.pathname.split("/");

var _default = [{
  key: 'labelPosition',
  ignore: true
}, {
  key: 'placeholder',
  ignore: true
}, {
  key: 'description',
  ignore: true
}, {
  key: 'hideLabel',
  ignore: true
}, {
  key: 'autofocus',
  ignore: true
}, {
  key: 'tooltip',
  ignore: true
}, {
  key: 'tabindex',
  ignore: true
}, {
  key: 'disabled',
  ignore: true
}, {
  type: 'select',
  label: 'Nome Calendario',
  key: 'calendarId',
  input: true,
  weight: 1,
  placeholder: 'Nome Calendario',
  tooltip: 'Inserisci il nome del calendario',
  dataSrc: 'url',
  defaultValue: '',
  data: {
    url: location.origin + '/' + explodedPath[1] + '/api/calendars'
  },
  valueProperty: 'id',
  template: '<span>{{ item.title }}</span>',
  selectValues: 'Results'
}];
exports.default = _default;
