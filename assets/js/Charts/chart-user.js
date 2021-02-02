const $ = require( "jquery" );
const Highcharts = require('highcharts');
// Load module after Highcharts is loaded
require('highcharts/modules/exporting')(Highcharts);
const axios = require('axios');
// Load the full build.
const _ = require('lodash');

const location = window.location
const explodedPath = location.pathname.split("/");
const endpointUrl = location.origin + '/' + explodedPath[1] + '/admin/usage/metriche'

let selectStatus =  $('select.select-status');
let selectServices =  $('select.select-services');
let selectTime =  $('select.select-time');
let multiSelect = $('select.select-status, select.select-services, select.select-time');

var chart  = Highcharts.chart('container', {
  chart: {
    type: 'column'
  },
  title: {
    text: 'Statistiche Utenti'
  },
  subtitle: {
    text: ''
  },
  xAxis: {
    categories: [
      'Gen',
      'Feb',
      'Mar',
      'Apr',
      'Mag',
      'Giu',
      'Lug',
      'Aug',
      'Sep',
      'Ott',
      'Nov',
      'Dic'
    ],
    crosshair: true
  },
  yAxis: {
    title: {
      text: 'N° utenti'
    },
    labels: {
      formatter: function () {
        return this.value / 1;
      }
    }
  },
  tooltip: {
    split: true,
    valuePrefix: 'N° utenti '
  },
  plotOptions: {
    column: {
      pointPadding: 0.2,
      borderWidth: 0
    }
  },
  series: []
});

function onChangeSelect(){
  multiSelect.on('change', function() {
    filterData()
  });
}

function filterData(){

  axios.get(endpointUrl, {
    params: {
      services: selectServices.val(),
      status: selectStatus.val(),
      time: selectTime.val()
    }
  })
    .then(function (response) {
      if (response.status === 200) {
        if(response.data.series){
          console.log(response.data.series)
          chart.update({
            series: response.data.series
          },true,true)
        }else {
          chart.update({
            series: []
          },true,true)
        }

      }
    })
}


$(document).ready(function () {
  chart.setSeriesData();
  filterData();
  onChangeSelect()

})
