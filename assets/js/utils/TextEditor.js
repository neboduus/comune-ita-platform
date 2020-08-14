import 'summernote';
import 'summernote/dist/summernote-bs4.css';

class TextEditor {
  static init() {
    $('textarea').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol', 'paragraph']],
        ['insert', ['link']],
        ['view', ['codeview']],
      ]
    });
  }
}

$(() => {
  TextEditor.init();
});


