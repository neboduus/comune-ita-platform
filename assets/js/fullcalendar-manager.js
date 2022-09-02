import {getStatus, deleteDraftModal, getCookie, setCookie} from "./fullcalendar-common";
import { Calendar, locales } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import listPligin from '@fullcalendar/list';
import interactionPlugin from '@fullcalendar/interaction';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import allLocales from '@fullcalendar/core/locales-all';
import printJS from "print-js";

require("bootstrap-italia");
require("../css/app.scss");

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

  var view_cookie = getCookie("view_type")
  var date_cookie = getCookie("date_view")

  // Calculate slots when date changes

  // NEW MODAL
  $("#modalNewOpeningHour, #modalNewDate").change(function () {
    $("#modalNewSlot").val('');
    $("#no_slots_new_alert").hide();
    getSlots($("#modalNewDate").val(), null, $("#modalNewOpeningHour").val(), null,function(slot) {
      if (slot) {
        $("#modalNewSlot").val(slot)
      } else {
        $("#no_slots_new_alert").show();
      }
    })
  });

  // EDIT MODAL
  $("#modalOpeningHour, #modalDate").change(function () {
    $("#modalSlot").val('');
    $("#no_slots_edit_alert").hide();
    getSlots($("#modalDate").val(), null,   $("#modalOpeningHour").val(), $("#modalId").html(),function(slot) {
      if (slot) {
        $("#no_slots_edit_alert").hide();
        $("#modalSlot").val(slot)
      } else {
        $("#no_slots_edit_alert").show();
      }
    })
  });

  // Fullcalendar initialization
  const language = document.documentElement.lang.toString();
  var calendarEl = document.getElementById('fullcalendar');
  var calendar = new Calendar(calendarEl, {
    plugins: [dayGridPlugin, timeGridPlugin, listPligin, interactionPlugin, bootstrapPlugin],
    themeSystem: 'bootstrap',
    locale: language,
    locales: allLocales,
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
    slotDuration: calculateSlot(),
    contentHeight: 600,
    minTime: JSON.parse($('#hidden').attr('data-range-time-event')).min,
    maxTime: JSON.parse($('#hidden').attr('data-range-time-event')).max,
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
    eventDurationEditable: false,
    eventDrop: function (info) {
      if (!info.event.extendedProps.uid && info.event.extendedProps.status !== 6) {
        compileModal(info);
        $("#edit_alert").show();
      } else {
        info.revert();
      }
    },
    eventClick: function (info) {
      setCookie("view_type",info.view.type)
      if (info.event.extendedProps.status === 6) {
        deleteDraftModal(info)
      } else if (info.event.id) compileModal(info);
      else if (info.event.title === 'Apertura') newModal(info)
    },
    dateClick: function(info) {
      setCookie("date_view",info.dateStr)
      if (info.view.type === 'dayGridMonth'){
        this.changeView("timeGridDay", info.dateStr)
        setCookie("view_type",info.view.type)
      }
    },
    datesRender: function (info) {
      date_cookie ? setCookie("date_view",new Date(date_cookie)): setCookie("date_view",new Date());
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
 * Calculates minimum slot duration
 */
function calculateSlot() {
  let minDuration = $('#hidden').attr('data-duration');
  if (minDuration <= 60) {
    return `00:${("0" + minDuration).slice(-2)}:00`
  } else return "01:00:00";
}

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

  //Set cookie
  setCookie("date_view",new Date(info.event.start))
  setCookie("view_type",info.view.type)

  // Populate modal
  $('#modalId').html(info.event.id);
  $('#modalOpeningHour').val(info.event.extendedProps.opening_hour)
  $('#modalDate').val(date);
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

  $("#modalSlot").val('');
  $("#no_slots_edit_alert").hide();

  getSlots($("#modalDate").val(), start, $("#modalOpeningHour").val(), $("#modalId").html(),function(slot) {
    if (slot) {
      $("#no_slots_edit_alert").hide();
      $("#modalSlot").val(slot)
    } else {
      $("#no_slots_edit_alert").show();
    }
    $('#modalCenter').modal('show');
  })

  $('#modalClose').click(info.revert)
}

/**
 * Fills modal data
 * @param info: event
 */
function newModal(info) {
  let date = new Date(info.event.start).toISOString().slice(0, 10);
  let start = new Date(info.event.start).toISOString().slice(11, 16);

  //Set cookie
  setCookie("date_view",new Date(info.event.start))
  setCookie("view_type",info.view.type)

  $('#modalNewDate').val(date);
  $('#modalNewStatus').html(1);

  $("#modalNewSlot").val('');
  $("#no_slots_new_alert").hide();
  getSlots($("#modalNewDate").val(), start,   $("#modalNewOpeningHour").val(), null,function(slot) {
    if (slot) {
      $("#modalNewSlot").val(slot)
    } else {
      $("#no_slots_new_alert").show();
    }
    $('#modalNew').modal('show');
  })
}


/**
 * Retrieves slots for selected date
 * @param date
 * @param start
 * @param opening_hour
 * @param exclude_id
 * @param callback
 */
function getSlots(date, start, opening_hour, exclude_id, callback) {
  $('#modalError').html('');
  let calendar = $('#hidden').attr('data-calendar');
  let slot;
  let overlaps = $('#hidden').attr('data-overlaps');

  let url = $('#hidden').attr('data-url');
  url = url.replace("calendar_id", calendar).replace("date", date);
  url = url + '?all=true';

  if (overlaps && opening_hour){
    url = url + '&opening_hours=' + opening_hour;
  }

  if (exclude_id){
    url = url + '&exclude=' + exclude_id;
  }

  setCookie("date_view",new Date(date))

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
        else if (!slot && !start && available) slot = value;
        if (available)
          $("#slots").append("<option value='" + value + "'>" + value + "</option>");
      }
      callback(slot);
    },
    error: function (jqXHR, textStatus, errorThrown) {
      $('#modalError').html(`${Translator.trans('meetings.error.unable_availabilities', {}, 'messages',language)}`);
    }
  });
}


$('.modal-edit').on('click', function editMeeting(e) {
  if (!confirm($(this).data('confirm'))){
    return;
  }
  let status = $(this).attr('data-status') ? $(this).attr('data-status') : $('#modalStatus').html();


  let date = $('#modalDate').val();
  let slot = $('#modalSlot').val().split(' - ');
  let start = slot[0];
  let end = slot[1];
  let id = $('#modalId').html();

  if (!start || !end) {
    return $('#modalError').html(`<li><span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${Translator.trans('meetings.error.slot_hours_invalid', {}, 'messages',language)}</li>`);
  }

  setCookie("date_view",new Date(date))

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
  let slot = $('#modalNewSlot').val().split(' - ');
  let start = slot[0];
  let end = slot[1];

  if (!start || !end) {
    return $('#modalNewError').html(`<li><span class="badge badge-danger mr-2">${Translator.trans('status_error', {}, 'messages',language)}</span>${Translator.trans('meetings.error.slot_hours_invalid', {}, 'messages',language)}</li>`);
  }

  setCookie("date_view",new Date(date))

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
