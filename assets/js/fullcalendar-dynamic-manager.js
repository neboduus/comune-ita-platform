import {getStatus, deleteDraftModal, getCookie, setCookie} from "../js/fullcalendar-common";
import { Calendar, locales } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPligin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import itLocale from '@fullcalendar/core/locales/it';
import enLocale from '@fullcalendar/core/locales/en-gb';
import deLocale from '@fullcalendar/core/locales/de';
import printJS from "print-js";

require('@fullcalendar/core/main.min.css');
require('@fullcalendar/daygrid/main.min.css');
require('@fullcalendar/timegrid/main.min.css');
require('@fullcalendar/list/main.min.css');
require('@fullcalendar/bootstrap/main.min.css');

const language = document.documentElement.lang.toString();

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

  var view_cookie = getCookie("d_view_type")
  var date_cookie = getCookie("d_date_view")
  const language = document.documentElement.lang.toString();

  // Fullcalendar initialization
  var calendarEl = document.getElementById('fullcalendar');
  var calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timeGridPlugin, listPligin, interactionPlugin, bootstrapPlugin],
    themeSystem: 'bootstrap',
    locale: language,
    locales: [itLocale,enLocale,deLocale],
    timeZone: 'Europe/Rome',
    nowIndicator: true,
    eventColor: '#3478BD',
    events: JSON.parse($('#hidden').attr('data-events')),
    allDaySlot: false,
    defaultView: view_cookie ? view_cookie : ($(window).width() < 765 ? 'timeGridDay' : 'dayGridMonth'),
    defaultDate: date_cookie ? new Date(date_cookie) : new Date(),
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
            && evt.resourceId === selectInfo.resourceId && evt.title === 'Apertura');
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
      if (info.event.title === 'OpeningDay'){
        if (info.view.type !== 'dayGridMonth') return false;
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
      setCookie("d_view_type",info.view.type)
      if (info.event.extendedProps.status === 6) {
        deleteDraftModal(info)
      } else if (info.event.id) compileModal(info);
    },
    dateClick: function(info) {
      setCookie("d_date_view",info.dateStr)
      if (info.view.type === 'dayGridMonth'){
        this.changeView("timeGridDay", info.dateStr)
        setCookie("d_view_type",info.view.type)
      }

    },
    datesRender: function (info) {
      date_cookie ? setCookie("d_date_view",new Date(date_cookie)): setCookie("d_date_view",new Date());
    }
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

  $('#print').on('click', () =>{
    printJS({
      printable: 'container-fullcalendar',
      type: 'html',
      scanStyles: false,
      css: ['https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/core/main.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/daygrid/main.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/timegrid/main.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/list/main.min.css',
        'https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/4.2.0/bootstrap/main.min.css']})
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

  //Set cookie
  setCookie("d_date_view",new Date(info.event.start))
  setCookie("d_view_type",info.view.type)

  // Populate modal
  $('#modalId').html(info.event.id);
  $('#modalDate').val(date);
  $('#modalStart').val(start);
  $('#modalEnd').val(end);
  $('#modalOpeningHour').val(info.event.extendedProps.opening_hour);
  $('#modalTitle').html(`[${getStatus(info.event.extendedProps.status)}] ${info.event.extendedProps.name || Translator.trans('meetings.modal.no_name', {}, 'messages',language)}`);
  $('#modalDescription').val(info.event.extendedProps.description);
  $('#modalMotivationOutcome').val(info.event.extendedProps.motivation_outcome);
  $('#modalVideoconferenceLink').val(info.event.extendedProps.videoconferenceLink);
  $('#modalPhone').val(info.event.extendedProps.phoneNumber);
  $('#modalEmail').val(info.event.extendedProps.email);
  $('#modalStatus').html(info.event.extendedProps.status);

  if (info.event.extendedProps.rescheduled === 1) {
    $('#modalRescheduleText').html(`${Translator.trans('meetings.error.one_moved', {}, 'messages',language)}`);
    $("#modalReschedule").show();
  } else if (info.event.extendedProps.rescheduled !== 0) {
    $('#modalRescheduleText').html(`${Translator.trans('meetings.error.moved', {}, 'messages',language)} ${info.event.extendedProps.rescheduled} ${Translator.trans('meetings.error.times', {}, 'messages',language)}`);
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

  //Set cookie
  setCookie("d_date_view",new Date(info.start))
  setCookie("d_view_type",info.view.type)

  $('#modalNewDate').val(date);
  $('#modalNewStart').val(start);
  $('#modalNewEnd').val(end);
  $('#modalNewStatus').html(1);

  $('#modalNew').modal('show');
}


$('.modal-edit').on('click', function editMeeting() {
  if (!confirm($(this).data('confirm'))) {
    return;
  }
  let status = $(this).attr('data-status') ? $(this).attr('data-status') : $('#modalStatus').html();

  let date = $('#modalDate').val();
  let start = $('#modalStart').val();
  let end = $('#modalEnd').val();
  let id = $('#modalId').html();

  if (!start || !end) {
    return $('#modalError').html(`<li><span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${Translator.trans('meetings.error.slot_hours_invalid', {}, 'messages',language)}</li>`);
  }

  setCookie("d_date_view",new Date(date))

  let data = {
    status: status,
    from_time: new Date(`${date}T${start}`).toISOString(),
    to_time: new Date(`${date}T${end}`).toISOString(),
    email: $('#modalEmail').val(),
    phone_number: $('#modalPhone').val(),
    user_message: $('#modalDescription').val(),
    motivation_outcome: $('#modalMotivationOutcome').val(),
    videoconference_link: $('#modalVideoconferenceLink').val(),
    opening_hour: $('#modalOpeningHour').val()
  };
  let url = $(this).attr('data-url');
  let token = $('#hidden').attr('data-token');
  url = url.replace("meeting_id", id);
  $.ajax({
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    url: url,
    type: 'PATCH',
    data: JSON.stringify(data),
    success: function (response, textStatus, jqXhr) {
      location.reload();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      let error = jqXHR.responseJSON.description ? jqXHR.responseJSON.description : `${Translator.trans('meetings.error.save_slot', {}, 'messages',language)}`;
      let errorMessage = `<span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${error}`
      $('#modalError').html(errorMessage);
    },
  });
});

$('.modal-delete').on('click', function () {
  let id = $('#modalDraftId').html();
  let url = $(this).attr('data-url');
  let token = $('#hidden').attr('data-token');
  url = url.replace("meeting_id", id);
  $.ajax({
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    url: url,
    type: 'DELETE',
    success: function (response, textStatus, jqXhr) {
      location.reload();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      let error = jqXHR.responseJSON.description ? jqXHR.responseJSON.description : `${Translator.trans('meetings.error.save_slot', {}, 'messages',language)}`;
      let errorMessage = `<span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${error}`
      $('#modalDraftError').html(errorMessage);
    },
  });
});

$('.modal-create').on('click', function () {
  let calendar = $(this).attr('data-calendar');
  let date = $('#modalNewDate').val();
  let start = $('#modalNewStart').val();
  let end = $('#modalNewEnd').val();

  if (!start || !end) {
    return $('#modalNewError').html(`<li><span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${Translator.trans('meetings.error.slot_hours_invalid', {}, 'messages',language)}</li>`);
  }

  setCookie("d_date_view",new Date(date))

  let data = {
    status: 1,
    from_time: new Date(`${date}T${start}`).toISOString(),
    to_time: new Date(`${date}T${end}`).toISOString(),
    user_message: $('#modalNewDescription').val(),
    motivation_outcome: $('#modalNewMotivationOutcome').val(),
    fiscal_code: $('#modalNewFiscalCode').val(),
    videoconference_link: $('#modalNewVideoconferenceLink').val(),
    email: $('#modalNewEmail').val(),
    name: $('#modalNewName').val(),
    phone_number: $('#modalNewPhone').val(),
    calendar: calendar,
    opening_hour: $('#modalNewOpeningHour').val()
  };

  let url = $(this).attr('data-url');
  let token = $('#hidden').attr('data-token');
  $.ajax({
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    url: url,
    type: 'POST',
    data: JSON.stringify(data),
    success: function (response, textStatus, jqXhr) {
      location.reload();
    },
    error: function (jqXHR, textStatus, errorThrown) {
      let error = jqXHR.responseJSON.description ? jqXHR.responseJSON.description : `${Translator.trans('meetings.error.save_slot', {}, 'messages',language)}`;
      let errorMessage = `<span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${error}`
      $('#modalNewError').html(errorMessage);
    },
  });
});
