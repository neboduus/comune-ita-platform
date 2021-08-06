import './core';
import './utils/TextEditor';
import {TextEditor} from "./utils/TextEditor";

$(document).ready(function () {
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