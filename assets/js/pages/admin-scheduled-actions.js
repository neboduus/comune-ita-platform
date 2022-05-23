import '../../css/app.scss';
import '../core';
import 'datatables.net-bs4/js/dataTables.bootstrap4.min';
import 'datatables.net-bs4/css/dataTables.bootstrap4.min.css';
import '../utils/datatables'
import Swal from 'sweetalert2/src/sweetalert2.js'

$(document).ready(function () {

  const datatableSetting = JSON.parse(decodeURIComponent($('#scheduled-actions').data('config')));
  const datatableOptions = {
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    searching: true,
    paging: true,
    pagingType: 'simple_numbers',
  };

  $('#scheduled-actions').initDataTables(datatableSetting, datatableOptions)
    .then(function (dt) {
      // dt contains the initialized instance of DataTables
      dt.on('draw', function () {
        $('.action-log').on('click', function (e) {
          e.preventDefault();
          let target = $(this);
          Swal.fire({
            title: target.data('title'),
            text: target.data('content'),
            showCloseButton: true,
            showConfirmButton: false,
          })
        });
      })
    });
});
