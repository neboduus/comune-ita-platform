"use strict";

require("core-js/modules/es.regexp.flags");

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _default = [
  {
    key: 'multiple',
    ignore: true
  },
  {
    type: 'datagrid',
    input: true,
    label: 'Componenti del bilancio',
    key: 'data.values',
    tooltip: 'Nel caso in cui il pagamento è diviso in vari componenti di bilancio specicarne il valore qui sotto.',
    weight: 0,
    reorder: true,
    components: [
      {
        label: 'Capitolo',
        key: 'codCapitolo',
        tooltip: 'Inserisci il codice del capitolo',
        input: true,
        type: 'textfield',
        validate: {
          required: true
        }
      }, {
        label: 'Ufficio',
        key: 'codUfficio',
        tooltip: 'Inserisci il codice del\' ufficio',
        input: true,
        type: 'textfield',
        validate: {
          required: true
        }
      }, {
        label: 'Accertamento',
        key: 'codAccertamento',
        tooltip: 'Inserisci il codice di accertamento',
        input: true,
        type: 'textfield'
      }, {
        label: 'Importo',
        key: 'importo',
        tooltip: 'Inserisci l\' importo',
        input: true,
        type: 'textfield',
        validate: {
          required: true
        }
      }]
  }
];
exports.default = _default;
