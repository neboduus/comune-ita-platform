import '../../core';
import '../../../styles/vendor/_leaflet.scss';
import {TextEditor} from "../../utils/TextEditor";
import Map from "../../utils/Map";
import Swal from 'sweetalert2/src/sweetalert2.js';
import "@sweetalert2/theme-bootstrap-4/bootstrap-4.min.css";


$(document).ready(function () {
  const limitChars = 2000;
  TextEditor.init({
    onInit: function () {
      let chars = $(this).parent().find(".note-editable").text();
      let totalChars = chars.length;

      $(this).parent().append('<small class="form-text text-muted">Si consiglia di inserire un massimo di ' + limitChars + ' caratteri (<span class="total-chars">' + totalChars + '</span> / <span class="max-chars"> ' + limitChars + '</span>)</small>')
    },
    onChange: function () {
      let chars = $(this).parent().find(".note-editable").text();
      let totalChars = chars.length;

      //Update value
      $(this).parent().find(".total-chars").text(totalChars);

      //Check and Limit Charaters
      if (totalChars >= limitChars) {
        return false;
      }
    }
  })
})


const $mapWrapper = $('#map');
const $geoFence = $('#geographic_area_geofence');
const center = [41.9027835, 12.4963655];
const $map = new Map($mapWrapper[0], center, {
  name: '#geographic_area_name_it',
  element: '#geographic_area_geofence'
});

const $importButton = $('#import-button');
const $importUrlField = $('#import-url-field');
//const $importDataField = $('#import-data-field');

if ($geoFence.val()) {
  $map.addGeojsonFeatures(JSON.parse($geoFence.val()));
}

$map.addDrawingLayer({
  nameEl: '#geographic_area_name_it',
  targetEl: '#geographic_area_geofence'
});

$importButton.on('click', function (e) {
  e.preventDefault();

  if (!$importUrlField.val()) {
    Swal.fire(
      'Attenzione!',
      'Devi inserire un valore valido nel campo url o nel campo data per effettuare un import',
      'warning'
    );
  }

  $map.loadGeoJsonFromUrl($importUrlField.val());

})
