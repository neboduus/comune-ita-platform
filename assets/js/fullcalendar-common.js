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
