import 'datatables.net-bs4/css/dataTables.bootstrap4.min.css'
import 'datatables.net-bs4/js/dataTables.bootstrap4.min'
import '../../styles/vendor/_datatables.scss'


$(document).ready(function () {

  const lang = document.documentElement.lang.toString();
//Default
  let url_language = '/bundles/app/js/libs/datatables/it-IT.json';
  if(lang === 'en'){
    url_language = '/bundles/app/js/libs/datatables/en-GB.json';
  }else if(lang === 'de'){
    url_language = '/bundles/app/js/libs/datatables/de-DE.json';
  }

  // Datatable
  $('#service-table').DataTable({
    "order": [[ 0, "asc" ]],
    columnDefs: [
      { orderable: false, targets: 2 },
      { orderable: false, targets: 3 },
      { orderable: false, targets: 4 },
      { orderable: false, targets: 5 }
    ],
    language: {
      url: url_language
    },
    stateSave: true,
    dom: "<'row'<'col-6 pt-2'l><'col-6'f>>" +
      "<'row'<'col-12'tr>>" +
      "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7 mt-2'p>>"
  });

  $('.clone').click(function (e) {
    e.preventDefault()
    let button = $(this)
    let temp = $("<input>")
    $("body").append(temp)
    temp.val(button.data('url')).select()
    document.execCommand("copy")
    temp.remove();
  })

})
