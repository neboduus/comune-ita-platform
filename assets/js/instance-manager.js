require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module

$(document).ready(function () {


    $('#ente_gateways').find('input[type="checkbox"]').each(function(){
      if(this.checked) {
        $('#ente_' + $(this).val()).removeClass('d-none');
      }
    });

    $('#ente_gateways').find('input[type="checkbox"]').change(function() {
      if(this.checked) {
        $('#ente_' + $(this).val()).removeClass('d-none');
      } else {
        $('#ente_' + $(this).val()).addClass('d-none');
      }
    })


});
