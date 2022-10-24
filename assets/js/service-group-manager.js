import './core';
import './utils/TextEditor';
import {TextEditor} from "./utils/TextEditor";

$(document).ready(function () {
  $('.attachment-delete').on('click', function () {
    let btn = $(this);
    let deleteUrl = $(this).data("delete-url");

    $.ajax(deleteUrl,
      {
        method: 'DELETE',
        success: function () {   // success callback function
          btn.closest('li').remove();
        },
        error: function () { // error callback
          alert(`${Translator.trans('servizio.error_missing_filename', {}, 'messages', $language)}`)
        }
      });
  });

  // Show/Hide external card url
  const $externalCardUrlCheckbox = $('#service_group_enable_external_card_url');
  const $externalCardUrl = $('#service_group_external_card_url');
  const $CardFieldsContainer = $('#card-fields-container');
  const hideExternalCardUrl = function () {
    if ($externalCardUrlCheckbox.is(":checked")) {
      $externalCardUrl.closest('div').show();
      $CardFieldsContainer.hide();
    } else {
      $externalCardUrl.val('');
      $externalCardUrl.closest('div').hide();
      $CardFieldsContainer.show();
    }
  };
  hideExternalCardUrl()
  $externalCardUrlCheckbox.click(function () {
    hideExternalCardUrl()
  });

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
