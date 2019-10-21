require('jquery'); // Load jQuery as a module
require('jsrender')();    // Load JsRender as jQuery plugin (jQuery instance as parameter)


$(document).ready(function () {
  const news = $('#news');
  const deadlines = $('#deadlines');
  news.html($.templates("#tpl-news-spinner").render({}));
  $.get(news.data('url'), function (data) {
    if (data.length > 0) {
      news.parent().css({'max-height': '300px', 'overflow-y': 'scroll', 'overflow-x': 'hidden'});
      news.html($.templates("#tpl-news").render(data));
    } else {
      news.parent().removeAttr('style');
      news.html($.templates("#tpl-news-empty").render({}));
    }
  });
  deadlines.html($.templates("#tpl-deadlines-spinner").render({}));
  $.get(deadlines.data('url'), function (data) {
    if (data.length > 0)
      deadlines.html($.templates("#tpl-deadlines").render(data));
    else
      deadlines.html($.templates("#tpl-deadlines-empty").render({}));
  });
});