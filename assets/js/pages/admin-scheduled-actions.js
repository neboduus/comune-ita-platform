import 'datatables.net-bs4/css/dataTables.bootstrap4.min.css';
import '../utils/datatables'
import Swal from 'sweetalert2/src/sweetalert2.js'

$(document).ready(function () {

  let table; //Table reference
  const datatableSetting = JSON.parse(decodeURIComponent($('#scheduled-actions').data('config')));
  const datatableOptions = {
    dom: "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" +
      "<'row'<'col-sm-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>",
    searching: true,
    paging: true,
    pagingType: 'simple_numbers',
    "drawCallback": function( settings ) {

      var api = new $.fn.dataTable.Api( settings );
      $('[data-toggle="popover"]').popover();

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

      $('#filter-status').on('change', function (e) {

        api.column(5).search($(this).val()).draw();
      });

      $('.scheduled-action-retry').on('click', function (e) {
        e.preventDefault();
        $.ajax({
          url: $(this).data('retry-url'),
          dataType: 'json',
          cache: false,
          type: 'GET',
          success: function (response, textStatus, jqXhr) {
            api.draw();
          },
          error: function (jqXHR, textStatus, errorThrown) {
            Swal.fire({
              icon: 'error',
              title: 'Oops...',
              text: 'Something went wrong!'
            })
          }
        });
      });
    },
  };


    $('#scheduled-actions').initDataTables(datatableSetting, datatableOptions)
    .then(function (dt) {
      // dt contains the initialized instance of DataTables
      table = dt
    });
});
