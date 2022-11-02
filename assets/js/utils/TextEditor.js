import 'summernote';
import 'summernote/dist/summernote-bs4.css';

// Add extension plugin summernote
(function (factory) {
  /* global define */
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define(['jquery'], factory);
  } else if (typeof module === 'object' && module.exports) {
    // Node/CommonJS
    module.exports = factory(require('jquery'));
  } else {
    // Browser globals
    factory(window.jQuery);
  }
}(function ($) {

  $.extend($.summernote.options, {
    stripTags: ['font', 'style', 'embed', 'param', 'script', 'html', 'body', 'head', 'meta', 'title', 'link', 'iframe', 'applet', 'noframes', 'noscript', 'form', 'input', 'select', 'option', 'colgroup', 'col', 'std', 'xml:', 'st1:', 'o:', 'w:', 'v:','h1','h2'],
    stripAttributes: ['font', 'style', 'embed', 'param', 'script', 'html', 'body', 'head', 'meta', 'title', 'link', 'iframe', 'applet', 'noframes', 'noscript', 'form', 'input', 'select', 'option', 'colgroup', 'col', 'std', 'xml:', 'st1:', 'o:', 'w:', 'v:','h1','h2'],
    onAfterStripTags: function ($html) {
      return $html;
    }
  });

  $.extend($.summernote.plugins, {
    'striptags': function (context) {
      let $note = context.layoutInfo.note;
      let $options = context.options;
      $note.on('summernote.paste', function (e, evt) {
        evt.preventDefault();
        let text = evt.originalEvent.clipboardData.getData('text/plain') || evt.originalEvent.clipboardData.getData('text/html') || e.currentTarget.innerHTML
        if (text) {
          let tagStripper = new RegExp('<[ /]*(' + $options.stripTags.join('|') + ')[^>]*>', 'gi'), attributeStripper = new RegExp(' (' + $options.stripAttributes.join('|') + ')(="[^"]*"|=\'[^\']*\'|=[^ ]+)?', 'gi'), commentStripper = new RegExp('<!--(.*)-->', 'g');
          text = text.toString().replace(commentStripper, '').replace(tagStripper, '').replace(attributeStripper, ' ').replace(/( class=(")?Mso[a-zA-Z]+(")?)/g, ' ').replace(/[\t ]+\</g, "<").replace(/\>[\t ]+\</g, "><").replace(/\>[\t ]+$/g, ">").replace(/[\u2018\u2019\u201A]/g, "'").replace(/[\u201C\u201D\u201E]/g, '"').replace(/\u2026/g, '...').replace(/[\u2013\u2014]/g, '-');
        }
        let $html = $('<div/>').html(text);
        $html = $options.onAfterStripTags($html);
        $note.summernote('insertNode', $html[0]);
        return false;
      });
    }
  });

}));
export class TextEditor {

  static init(callback) {

    $('textarea:not(.simple)').summernote({
      toolbar: [
        ['style', ['bold', 'italic', 'underline', 'clear']],
        ['para', ['ul', 'ol']],
        ['insert', ['link']],
        ['view', ['codeview']],
        ['fontName', ['Titillium Web']]
      ],
      fontNames: ['Titillium Web','sans-serif'],
      callbacks: callback,
      stripTags: ['font', 'style', 'embed', 'param', 'script', 'html', 'body', 'head', 'meta', 'title', 'link', 'iframe', 'applet', 'noframes', 'noscript', 'form', 'input', 'select', 'option', 'colgroup', 'col', 'std', 'xml:', 'st1:', 'o:', 'w:', 'v:','h1','h2'],
      stripAttributes: ['font', 'style', 'embed', 'param', 'script', 'html', 'body', 'head', 'meta', 'title', 'link', 'iframe', 'applet', 'noframes', 'noscript', 'form', 'input', 'select', 'option', 'colgroup', 'col', 'std', 'xml:', 'st1:', 'o:', 'w:', 'v:','h1','h2'],
      striptags: {
        onAfterStripTags: function ($html) {
          $html.find('table').addClass('table');
          return $html;
        }
      }
    })
  }
}


