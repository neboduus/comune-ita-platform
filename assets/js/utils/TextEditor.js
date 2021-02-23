import 'summernote';
import 'summernote/dist/summernote-bs4.css';
export class TextEditor {

  static init(callback) {
    function CleanPastedHTML(input) {
      // 1. remove line breaks / Mso classes
      var stringStripper = /(\n|\r| class=(")?Mso[a-zA-Z]+(")?)/g;
      var output = input.replace(stringStripper, ' ');
      // 2. strip Word generated HTML comments
      var commentSripper = new RegExp('<!--(.*?)-->','g');
      var output = output.replace(commentSripper, '');
      var tagStripper = new RegExp('<(/)*(meta|link|span|\\?xml:|st1:|o:|font)(.*?)>','gi');
      // 3. remove tags leave content if any
      output = output.replace(tagStripper, '');
      // 4. Remove everything in between and including tags '<style(.)style(.)>'
      var badTags = ['style', 'script','applet','embed','noframes','noscript','pre'];

      for (var i=0; i< badTags.length; i++) {
        tagStripper = new RegExp('<'+badTags[i]+'.*?'+badTags[i]+'(.*?)>', 'gi');
        output = output.replace(tagStripper, '');
      }
      // 5. remove attributes ' style="..."'
      var badAttributes = ['style', 'start','pre'];
      for (var i=0; i< badAttributes.length; i++) {
        var attributeStripper = new RegExp(' ' + badAttributes[i] + '="(.*?)"','gi');
        output = output.replace(attributeStripper, '');
      }
      return output;
    }
    $('textarea').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol']],
        ['insert', ['link']],
        ['view', ['codeview']],
      ],
      fontNames: ['Titillium Web', 'Geneva', 'Tahoma', 'sans-serif'],
      callbacks: callback
    }).on('summernote.paste', function(e, ne) {
      e.currentTarget.innerHTML = CleanPastedHTML(e.currentTarget.innerHTML)
    })
  }


}


