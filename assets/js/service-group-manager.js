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
