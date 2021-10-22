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
    url: location.origin + '/' + explodedPath[1] + '/api/calendars?type=time_fixed_slots'
  },
  valueProperty: 'id',
  template: '<span>{{ item.title }}</span>',
  selectValues: 'Results',
},
  {
    type: 'checkbox',
    label: 'Seleziona orari di apertura',
    key: 'select_opening_hours',
    input: true,
    weight: 1,
    tooltip: 'Abilita per selezionare i singoli orari di apertura',
    defaultValue: false,
  },
  {
    label: "Base url",
    calculateValue: "let url = '"+location.origin + "/" + explodedPath[1]+"'; data.url = url; value=url;",
    key: "url",
    type: "hidden",
    input: true,
    weight: 1
  },
  {
    type: 'select',
    label: 'Orari di apertura',
    key: 'opening_hours',
    input: true,
    weight: 1,
    placeholder: 'Seleziona gli orari di apertura',
    tooltip: 'Seleziona gli orari di apertura',
    multiple: true,
    dataSrc: 'url',
    defaultValue: '',
    data: {
      url: location.origin + '/' + explodedPath[1] + '/api/calendars/{{data.calendarId}}/opening-hours'
    },
    valueProperty: 'id',
    template: '<span>{{ item.name }}</span>',
    selectValues: 'results',
      "conditional": {
          "show": true,
          "when": "select_opening_hours",
          "eq": "true"
      }
  },
  {
    label: "Api Url",
    redrawOn: "data",
    calculateValue: "if (data.calendarId) {\n" +
        "  let url = data.url + '/api/calendars/' + data.calendarId + '/overlaps';\n" +
        "  if (data.select_opening_hours && data.opening_hours.length > 0) {\n" +
        "    let qs = \"?opening_hours=\" + data.opening_hours.join();\n" +
        "    url = url + qs;\n" +
        "  }\n" +
        "  if (data.api_url !== url) value = url;\n" +
        "} else {url = ''}",
    key: "api_url",
    type: "hidden",
    input: true,
    weight: 1
  },
  {
    label: "Sovrapposizioni",
    inputFormat: "plain",
    redrawOn: "api_url",
    calculateValue: "" +
        "function loadJSON(path, success, error){\n" +
        "  var xhr = new XMLHttpRequest();\n" +
        "  xhr.onreadystatechange = function() {\n" +
        "    if (xhr.readyState === XMLHttpRequest.DONE) {\n" +
        "      if (xhr.status === 200) {\n" +
        "        let responseData = JSON.parse(xhr.response);\n" +
        "        success(responseData.count);\n" +
        "      } else {\n" +
        "        error();\n" +
        "      }\n" +
        "    }" +
        "  }\n" +
        "  xhr.open(\"GET\", path, true);\n" +
        "  xhr.send();\n" +
        "}\n" +
        "if(data.calendarId && data.api_url){\n" +
        "  var url = data.api_url;\n" +
        "  loadJSON(url,\n" +
        "    function(count) {\n" +
        "     data.overlaps_validation = count;  "  +
        "      document.querySelector(\"input[name^='data[overlaps_validation]']\").value = count;\n" +
        "    },\n" +
        "    function() {\n" +
        "     data.overlaps_validation = -1;  "  +
        "     document.querySelector(\"input[name^='data[overlaps_validation]']\").value = -1;\n" +
        "    }\n" +
        "  );\n" +
        "}",
    validateOn: "change",
    validate: {
      customMessage: "Vi sono sovrapposizioni negli orari di apertura",
      "custom": "" +
          "if (document.querySelector(\"input[name^='data[overlaps_validation]']\").value === '0') {" +
          "valid=true" +
          "} else {" +
          "valid = 'Sovrapposizione';" +
          "}"
    },
    key: "overlaps_validation",
    attributes: {
      readonly: "true"
    },
    required: true,
    type: "number",
    input: true,
    weight: 1,
  }
];
exports.default = _default;
