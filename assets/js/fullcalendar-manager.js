require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module

$(document).ready(function () {
  // Hide all buttons
  $("#edit_alert").hide();
  $("#modalApprove").hide();
  $("#modalRefuse").hide();
  $("#modalMissed").hide();
  $("#modalComplete").hide();
  $("#modalSlot").click(function () {
    $("#edit_alert").show();
  });

  // Fullcalendar initialization
  var calendarEl = document.getElementById('fullcalendar');

  var calendar = new FullCalendar.Calendar(calendarEl, {
    plugins: ['bootstrap', 'dayGrid', 'timeGrid', 'list', 'interaction'],
    themeSystem: 'bootstrap',
    locale: 'it',
    timeZone: 'Europe/Rome',
    nowIndicator: true,
    eventColor: '#3478BD',
    events: JSON.parse($('#hidden').attr('data-events')),
    allDaySlot: false,
    defaultView: $(window).width() < 765 ? 'timeGridDay' : 'dayGridMonth',
    header: {
      left: 'prev,today,next',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay, listWeek'
    },
    slotDuration: calculateSlot(),
    contentHeight: 600,
    minTime: '08:00:00',
    maxTime: '19:00:00',
    eventRender: function (info) {
      if (info.event.extendedProps.status === 0) {
        var dotEl = info.el.getElementsByClassName('fc-event-dot')[0];
        if (dotEl) {
          dotEl.style.backgroundColor = 'var(--primary)';
        }
      }
      var textEl = info.el.getElementsByClassName('fc-list-item-title')[0];
      if (textEl && info.event.extendedProps.description) {
        textEl.append(`: ${info.event.extendedProps.description}`);
      } else if (textEl) {
        textEl.append(`Occupato`);
      }
    },
    editable: true,
    eventDurationEditable: false,
    eventDrop: function (info) {
      compileModal(info);
      $("#edit_alert").show();
    },
    eventClick: function (info) {
      if (info.event.id) compileModal(info);
      else if (info.event.title === 'Apertura') newModal(info)
    }
  });

  calendar.render();
});


/**
 * Calculates minumin slot duration
 */
function calculateSlot() {
  let minDuration = $('#hidden').attr('data-duration');
  if (minDuration <= 60) {
    return `00:${minDuration}:00`
  } else return "01:00:00";
}

/**
 * Fills modal data
 * @param info: event
 */
function compileModal(info) {
  $("#edit_alert").hide();
  $("#modalApprove").hide();
  $("#modalRefuse").hide();
  $("#modalMissed").hide();
  $("#modalCancel").hide();
  $("#modalComplete").hide();
  $("#modalReschedule").hide();

  let date = new Date(info.event.start).toISOString().slice(0, 10);
  let start = new Date(info.event.start).toISOString().slice(11, 16);

  // Populate modal

  // Populate datalist
  getSlots(date, start, function (slot) {
    $('#modalSlot').val(slot);
  });

  $('#modalDate').val(date);
  $('#modalId').html(info.event.id);
  $('#modalTitle').html(`[${getStatus(info.event.extendedProps.status).toUpperCase()}] ${info.event.extendedProps.name || 'Nome non fornito'}`);
  $('#modalDescription').val(info.event.extendedProps.description);
  $('#modalVideoconferenceLink').val(info.event.extendedProps.videoconferenceLink);
  $('#modalPhone').val(info.event.extendedProps.phoneNumber);
  $('#modalEmail').val(info.event.extendedProps.email);
  $('#modalStatus').html(info.event.extendedProps.status);

  if (info.event.extendedProps.rescheduled === 1) {
    $('#modalRescheduleText').html(`Quest'appuntamento è stato spostato 1 volta`);
    $("#modalReschedule").show();
  } else if (info.event.extendedProps.rescheduled !== 0) {
    $('#modalRescheduleText').html(`Quest'appuntamento è stato spostato ${info.event.extendedProps.rescheduled} volte`);
    $("#modalReschedule").show();
  }
  switch ($('#modalStatus').html()) {
    case '0': //Attesa
      $('#modalApprove').show();
      $('#modalRefuse').show();
      break;
    case '1': //approvato
      $('#modalComplete').show();
      $('#modalMissed').show();
      $('#modalCancel').show();
      break;
    case '2': //Rifiutato
      $('#modalApprove').show();
      $('#modalRefuse').show();
      break;
    case '3': //assente
      $('#modalComplete').show();
      $('#modalMissed').show();
      break;
    case '4': // Concluso
      $('#modalComplete').show();
      $('#modalMissed').show();
      break;
  }

  $('#modalError').html('');
  $('#modalCenter').modal('show');
  $('#modalClose').click(info.revert)
}

/**
 * Fills modal data
 * @param info: event
 */
function newModal(info) {
  let date = new Date(info.event.start).toISOString().slice(0, 10);
  let start = new Date(info.event.start).toISOString().slice(11, 16);

  // Populate datalist
  getSlots(date,start, function (slot) {
    $('#modalNewSlot').val(slot);
  });

  $('#modalNewDate').val(date);
  $('#modalNewStatus').html(1);
  $('#modalNew').modal('show');

}

/**
 * Retrieves slots for selected date
 * @param date
 * @param start
 * @param callback
 */
function getSlots(date, start, callback) {
  let calendar = $('#hidden').attr('data-calendar');
  let slot;

  let url = $('#hidden').attr('data-url');
  url = url.replace("calendar_id", calendar).replace("date", date);
  url = url + '?all=true';

  $.ajax({
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    url: url,
    type: 'GET',
    success: function (response, textStatus, jqXhr) {
      $('#slots').empty();
      for (let i = 0; i < response.length; i++) {
        let value = response[i]['start_time'] + ' - ' + response[i]['end_time'];
        let available = response[i]['availability'];
        // If start is defined get right slot
        if (start && start === response[i]['start_time']) slot = value;
        if (!available)
          $("#slots").append("<option value='" + value + "'disabled>" + value + "</option>");
        else
          $("#slots").append("<option value='" + value + "'>" + value + "</option>");
      }
      callback(slot);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $('#modalError').html('Impossibile recuperare le disponibilità per la data selezionata');
    }
  });
}

/**
 * Get status as string
 * @param status
 */
function getStatus(status) {
  switch (status) {
    case 0:
      return 'In attesa di conferma';
    case 1:
      return 'Confermato';
    case 2:
      return 'Rifiutato';
    case 3:
      return 'Assente';
    case 4:
      return 'Concluso';
    case 5:
      return 'Annullato';
    default:
      return 'Errore';
  }
}
