import '../../css/app.scss';
import '../core';
import '../utils/TextEditor';


import Calendar from '../Calendar';
import PageBreak from '../PageBreak';
import FinancialReport from "../FinancialReport";
import 'formiojs';
import 'formiojs/dist/formio.form.min.css';

Formio.registerComponent('calendar', Calendar);
Formio.registerComponent('pagebreak', PageBreak);
Formio.registerComponent('financial_report', FinancialReport);


$(document).ready(function () {
  $('#write-to-citizen').click(function (e) {
    e.preventDefault();
    $('#messaggi-tab').tab('show');
  })
});
