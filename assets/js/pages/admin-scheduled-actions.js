import '../../css/app.scss';
import '../core';
import 'datatables.net-bs4/js/dataTables.bootstrap4.min';
import 'datatables.net-bs4/css/dataTables.bootstrap4.min.css';
import '../utils/datatables'

$(document).ready(function () {

  const datatableSetting = JSON.parse(decodeURIComponent($('#scheduled-actions').data('config')));

  $('#scheduled-actions').initDataTables(datatableSetting, {
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    searching: true,
    paging: true,
    pagingType: 'simple_numbers',
  }).then(function (dt) {
    // dt contains the initialized instance of DataTables
    dt.on('draw', function () {
      //alert('Redrawing table');
      $('[data-toggle="popover"]').popover()
    })
  });
});
