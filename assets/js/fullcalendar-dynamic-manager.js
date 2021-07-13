require("bootstrap-italia");
require("../css/app.scss");
require("jquery"); // Load jQuery as a module

$(document).ready(function () {
  // Hide all buttons
  $("#edit_alert").hide();
  $("#no_slots_edit_alert").hide();
  $("#no_slots_new_alert").hide();
  $("#modalApprove").hide();
  $("#modalRefuse").hide();
  $("#modalMissed").hide();
  $("#modalComplete").hide();
  $("#modalStatusHelper").hide();
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
      right: 'dayGridMonth,timeGridWeek,timeGridDay, listMonth, listWeek, listDay'
    },
    selectable: true,
    slotDuration: '00:05:00',
    contentHeight: 600,
    minTime: JSON.parse($('#hidden').attr('data-range-time-event')).min,
    maxTime: JSON.parse($('#hidden').attr('data-range-time-event')).max,
    select(selectionInfo) {
      newModal(selectionInfo)
    },
    selectAllow: function (selectInfo) {
      var someEvents = calendar.getEvents().filter(function(evt) {
        return (evt.start <= selectInfo.start
            && evt.end >= selectInfo.end
            && evt.resourceId === selectInfo.resourceId);

      });
      return someEvents.length > 0;
    },
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
    eventDurationEditable: true,
    eventAllow: function (dropInfo, draggedEvent) {
      var someEvents = calendar.getEvents().filter(function(evt) {
        return (evt.start <= dropInfo.start
            && evt.end >= dropInfo.end
            && evt.resourceId === dropInfo.resourceId);
      });
      return someEvents.length > 0;
    },
    eventDrop: function (info) {
      if (!info.event.extendedProps.uid && info.event.extendedProps.status !== 6) {
        compileModal(info);
        $("#edit_alert").show();
      } else {
        info.revert();
      }
    },
    eventResize: function (info) {
      if (!info.event.extendedProps.uid && info.event.extendedProps.status !== 6) {
        compileModal(info);
        $("#edit_alert").show();
      } else {
        info.revert();
      }
    },
    eventClick: function (info) {
      if (info.event.extendedProps.status === 6) {
        deleteDraftModal(info)
      } else if (info.event.id) compileModal(info);
    },
    dateClick: function(info) {
      if (info.view.type === 'dayGridMonth')
        this.changeView("timeGridDay", info.dateStr)
    },
  });

  calendar.render();
  $('.fc-listDay-button').hide()
  $('.fc-listWeek-button').hide()

  $('.fc-dayGridMonth-button').on('click', function () {
    $('.fc-listDay-button').hide()
    $('.fc-listWeek-button').hide()
    $('.fc-listMonth-button').show()
  })
  $('.fc-timeGridWeek-button').on('click', function () {
    $('.fc-listDay-button').hide()
    $('.fc-listMonth-button').hide()
    $('.fc-listWeek-button').show()
  })
  $('.fc-timeGridDay-button').on('click', function () {
    $('.fc-listWeek-button').hide()
    $('.fc-listMonth-button').hide()
    $('.fc-listDay-button').show()
  })
});

/**
 * Fills modal data
 * @param info: event
 */
function compileModal(info) {
  $("#edit_alert").hide();
  $("#no_slots_edit_alert").hide();
  $("#modalApprove").hide();
  $("#modalRefuse").hide();
  $("#modalMissed").hide();
  $("#modalCancel").hide();
  $("#modalComplete").hide();
  $("#modalStatusHelper").hide();
  $("#modalReschedule").hide();

  let date = new Date(info.event.start).toISOString().slice(0, 10);
  let start = new Date(info.event.start).toISOString().slice(11, 16);
  let end = new Date(info.event.end).toISOString().slice(11, 16);

  // Populate modal
  $('#modalId').html(info.event.id);
  $('#modalDate').val(date);
  $('#modalStart').val(start);
  $('#modalEnd').val(end);
  $('#modalOpeningHour').val(info.event.extendedProps.opening_hour);
  $('#modalTitle').html(`[${getStatus(info.event.extendedProps.status).toUpperCase()}] ${info.event.extendedProps.name || 'Nome non fornito'}`);
  $('#modalDescription').val(info.event.extendedProps.description);
  $('#modalMotivationOutcome').val(info.event.extendedProps.motivation_outcome);
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
      $("#modalStatusHelper").show();
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
  $("#no_slots_edit_alert").hide();

  $('#modalClose').click(info.revert)
}

/**
 * Fills modal data
 * @param info: event
 */
function newModal(info) {

  let date = new Date(info.start).toISOString().slice(0, 10);
  let start = new Date(info.start).toISOString().slice(11, 16);
  let end = new Date(info.end).toISOString().slice(11, 16);

  $('#modalNewDate').val(date);
  $('#modalNewStart').val(start);
  $('#modalNewEnd').val(end);
  $('#modalNewStatus').html(1);

  $('#modalNew').modal('show');
  /*

  $('#modalNewStatus').html(1);
  */
}

/**
 * Delefe draft modal
 * @param info: event
 */
function deleteDraftModal(info) {
  $('#modalDraftId').html(info.event.id);

  let date = new Date(info.event.extendedProps.draftExpireTime).toISOString().slice(0, 10);
  let time = new Date(info.event.extendedProps.draftExpireTime).toISOString().slice(11, 16);
  $('#modalDraftExpireTime').html(date);
  $('#modalDraftExpireDate').html(time);

  let description = $('#modalDraftDescription').html()
  description = description.replace("%expire_time%", time).replace("%expire_date%", date)
  $('#modalDraftDescription').html(description)

  $('#modalDeleteDraft').modal('show');
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
    case 6:
      return 'Bozza';
    default:
      return 'Errore';
  }
}
