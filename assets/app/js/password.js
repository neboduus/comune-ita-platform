!function ($) {

  'use strict';

  var Password = function (element, options) {
    this.options = options;
    this.$element = $(element);
    this.template = '<div id="pswd_info" style="display: none">\n' +
      '              <ul class="list-unstyled">\n' +
      '              <li class="length">Almeno <strong>8</strong> caratteri ( obbligatorio )</li>\n' +
      '              <li class="letter">Almeno <strong>una lettera</strong> ( obbligatorio )</li>\n' +
      '              <li class="capital">Almeno <strong>una lettera maiuscola</strong> ( raccomandato )</li>\n' +
      '              <li class="number">Almeno <strong>un numero o uno di !,@,#,$,%,^,*</strong> ( obbligatorio )</li>\n' +
      '              <li class="meter"></li>\n' +
      '              </ul>\n' +
      '              </div>';
    this.isShown = false;
    this.$infoContainer = $(this.template).appendTo(this.$element.parent());

    this.init();
  };

  Password.DEFAULTS = {
    message: 'Mostra/nascondi password',
    strengthMeter: true,
    validCssClass: 'text-success',
    invalidCssClass: 'text-error',
    minLength: 8,
    infoContainer: '#pswd_info',
    hierarchy: {
      '0': ['text-danger', 'Valutazione della complessità: pessima'],
      '10': ['text-danger', 'Valutazione della complessità: molto debole'],
      '20': ['text-warning', 'Valutazione della complessità: debole'],
      '30': ['text-info', 'Valutazione della complessità: buona'],
      '40': ['text-success', 'Valutazione della complessità: molto buona'],
      '50': ['text-success', 'Valutazione della complessità: ottima']
    }
  };

  Password.prototype.init = function () {
    var self = this;

    /*this.$element.wrap('<div style="position:relative" />');*/

    /*this.$text = $('<input type="text" />')
      .insertAfter(this.$element)
      .attr('class', this.$element.attr('class'))
      .attr('placeholder', this.$element.attr('placeholder'))
      .css('display', this.$element.css('display'))
      .val(this.$element.val()).hide();

    this.$icon = $([
      '<span tabindex="100" title="' + this.options.message + '" style="cursor: pointer;position: absolute;top: 7px;right: 10px;"><i class="fa fa-eye-slash"></i></span>'
    ].join('')).insertAfter(this.$text);*/

    /*this.$text.off('keyup').on('keyup', $.proxy(function () {
      this.$element.val(this.$text.val());
    }, this));

    this.$icon.off('click').on('click', $.proxy(function () {
      this.$text.val(this.$element.val());
      this.toggle();
    }, this));*/

    if (this.options.strengthMeter) {
      self.$infoContainer.parent().css('position', 'relative');

      this.$element.strengthMeter('text', {
        container: self.$infoContainer.find('.meter'),
        hierarchy: self.options.hierarchy
      });

      var setValid = function ($element) {
        $element.addClass(self.options.validCssClass).removeClass(self.options.invalidCssClass);
        $element.find('i').removeClass('fa-times').addClass('fa-check');
      };

      var setInvalid = function ($element) {
        $element.removeClass(self.options.validCssClass).addClass(self.options.invalidCssClass);
        $element.find('i').addClass('fa-times').removeClass('fa-check');
      };

      var validate = function () {
        var pswd = self.$element.val();
        if (pswd.length < self.options.minLength) {
          setInvalid(self.$infoContainer.find('.length'));
        } else {
          setValid(self.$infoContainer.find('.length'));
        }
        if (pswd.match(/[A-z]/)) {
          setValid(self.$infoContainer.find('.letter'));
        } else {
          setInvalid(self.$infoContainer.find('.letter'));
        }
        if (pswd.match(/[A-Z]/)) {
          setValid(self.$infoContainer.find('.capital'));
        } else {
          setInvalid(self.$infoContainer.find('.capital'));
        }
        if (pswd.match(/\d/)) {
          setValid(self.$infoContainer.find('.number'));
        } else {
          setInvalid(self.$infoContainer.find('.number'));
        }
      };

      this.$element.keyup(function () {
        validate();
      }).change(function () {
          validate();
      }).focus(function () {
        self.$infoContainer.show();
      }).blur(function () {
        self.$infoContainer.hide();
      });
      /*this.$text.keyup(function () {
        validate();
      }).focus(function () {
        self.$infoContainer.show();
      }).blur(function () {
        self.$infoContainer.hide();
      });*/
    }
  };

  /*Password.prototype.toggle = function (_relatedTarget) {
    this[!this.isShown ? 'show' : 'hide']();
  };

  Password.prototype.show = function (_relatedTarget) {
    var e = $.Event('show.bs.password', {relatedTarget: _relatedTarget});
    this.$element.trigger(e);

    this.isShown = true;
    this.$element.hide();
    var value = this.$text.val();
    this.$text.val(value.replace('_ezpassword', ''));
    this.$text.show();
    this.$icon.find('i')
      .removeClass('fa-eye-slash')
      .addClass('fa-eye');

    this.$element.before(this.$text);
  };

  Password.prototype.hide = function (_relatedTarget) {
    var e = $.Event('hide.bs.password', {relatedTarget: _relatedTarget});
    this.$element.trigger(e);

    this.isShown = false;
    this.$element.show();
    this.$text.hide();
    this.$icon.find('i')
      .removeClass('fa-eye')
      .addClass('fa-eye-slash');

    this.$text.before(this.$element);
  };*/


  // PASSWORD PLUGIN DEFINITION
  // =======================

  var old = $.fn.password;

  $.fn.password = function (option, _relatedTarget) {
    return this.each(function () {
      var $this = $(this),
        data = $this.data('bs.password'),
        options = $.extend({}, Password.DEFAULTS, $this.data(), typeof option === 'object' && option);

      if (!data) {
        $this.data('bs.password', (data = new Password(this, options)));
      }

      if (typeof option === 'string') {
        data[option](_relatedTarget);
      }
    });
  };

  $.fn.password.Constructor = Password;

  $.fn.password.noConflict = function () {
    $.fn.password = old;
    return this;
  };

}(window.jQuery);
