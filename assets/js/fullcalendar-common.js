export function getCookie(name) {
  var nameEQ = name + "=";
  var ca = document.cookie.split(';');
  for(var i=0;i < ca.length;i++) {
    var c = ca[i];
    while (c.charAt(0)===' ') c = c.substring(1,c.length);
    if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length,c.length);
  }
  return null;
}

/**
 * Get status as string
 * @param status
 */
export function getStatus(status) {
  const language = document.documentElement.lang.toString();

  switch (status) {
    case 0:
      return Translator.trans('meetings.status.pending', {}, 'messages', language);
    case 1:
      return Translator.trans('meetings.status.approved', {}, 'messages', language);
    case 2:
      return Translator.trans('meetings.status.refused', {}, 'messages', language);
    case 3:
      return Translator.trans('meetings.status.missed', {}, 'messages', language);
    case 4:
      return Translator.trans('meetings.status.concluded', {}, 'messages', language);
    case 5:
      return Translator.trans('meetings.status.cancelled', {}, 'messages', language);
    case 6:
      return Translator.trans('meetings.status.draft', {}, 'messages', language);
    default:
      return Translator.trans('status_error', {}, 'messages', language);
  }
}

/**
 * Delefe draft modal
 * @param info: event
 */
export function deleteDraftModal(info) {
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
