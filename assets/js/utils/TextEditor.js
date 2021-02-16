import 'summernote';
import 'summernote/dist/summernote-bs4.css';
import 'summernote-cleaner';
export class TextEditor {

  static init(callback) {
    $('textarea').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol']],
        ['insert', ['link']],
        ['view', ['codeview']],
      ],
      fontNames: ['Titillium Web', 'Geneva', 'Tahoma', 'sans-serif'],
      cleaner:{
        action: 'both', // both|button|paste 'button' only cleans via toolbar button, 'paste' only clean when pasting content, both does both options.
        keepHtml: true, // Remove all Html formats
        keepOnlyTags: ['<p>', '<br>', '<ul>', '<li>', '<b>', '<strong>','<i>', '<a>'], // If keepHtml is true, remove all tags except these
        keepClasses: false, // Remove Classes
        limitChars: 2000, // 0/false|# 0/false disables option
        limitDisplay: 'both', // text|html|both
      },
      callbacks: callback
    });
  }
}


