const $ = require( "jquery" );
const moment = require('moment');
const Highcharts = require('highcharts');
// Load module after Highcharts is loaded
require('highcharts/modules/exporting')(Highcharts);
const axios = require('axios');
// Load the full build.
const _ = require('lodash');

const location = window.location
const explodedPath = location.pathname.split("/");
const endpointUrl = location.origin + '/' + explodedPath[1] + '/' + explodedPath[2] + '/operatori/usage/metriche'

let selectStatus =  $('select.select-status');
let selectServices =  $('select.select-services');
let selectTime =  $('select.select-time');
let multiSelect = $('select.select-status, select.select-services, select.select-time');

let typeSlot = 'day';

var chart  = Highcharts.chart('container', {
  chart: {
    type: 'area'
  },
  title: {
    text: 'Statistiche Pratiche'
  },
  subtitle: {
    text: ''
  },
  xAxis: {
    categories: ["9:00","9:30","10:00","10:30","11:00"],
    tickmarkPlacement: 'on',
    title: {
      enabled: false
    }
  },
  yAxis: {
    title: {
      text: 'N° pratiche'
    },
    labels: {
      formatter: function () {
        //alert(this.value )
        return this.value / 1;
      }
    }
  },
  tooltip: {
    split: true,
    valuePrefix: 'N° pratiche '
  },
  plotOptions: {
    area: {
      stacking: 'normal',
      lineColor: '#666666',
      lineWidth: 1,
      marker: {
        lineWidth: 1,
        lineColor: '#666666'
      }
    }
  },
  series: []
});

function onChangeSelect(){
  multiSelect.on('change', function() {
    filterData()
    if (selectTime.val() <= 180 ) {
      typeSlot = 'minute';
    } else if ( selectTime.val() <= 1440 ) {
      typeSlot = 'hour';
    } else {
      typeSlot = 'day';
    }
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
          chart.update({
            xAxis: {
              categories: response.data.categories.map(function (x) {
                if (typeSlot === 'minute') {
                  return moment(x).format('HH:mm');
                } else if (typeSlot === 'hour') {
                  return moment(x).format('DD/MM/YYYY HH:mm');
                } else {
                  return moment(x).format('DD/MM/YYYY');
                }
              })
            },

          })
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
  /*axios.get(endpointUrl, {
      params: {
        status: 'all',
        services:'all',
        time: selectTime.val()
      }
    }
  )
    .then(function (response) {
      if (response.status === 200) {

        if(response.data.series){
          chart.update({
            xAxis: {
              categories: response.data.categories.map(function (x) {
                return moment(x).format('DD/MM/YYYY')
              })
            },

          })
          chart.update({
            series: response.data.series
          },true,true)
        }else {
          chart.update({
            series: []
          },true,true)
        }

      }
    })*/

  onChangeSelect()

})
