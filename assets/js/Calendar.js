require('webpack-jquery-ui/datepicker');
import Base from 'formiojs/components/_classes/component/Component';
//import editForm from 'formiojs/components/textfield/TextField.form'
import editForm from './Calendar/Calendar.form'
import moment from 'moment'

export default class FormioCalendar extends Base {
  constructor(component, options, data) {
    super(component, options, data);
    this.date = false;
    this.slot = false;
    this.container = false;
    this.calendar = null;
  }

  static schema() {
    return Base.schema({
      type: 'calendar'
    });
  }

  static builderInfo = {
    title: 'Calendar',
    group: 'basic',
    icon: 'fa fa-calendar',
    weight: 70,
    schema: FormioCalendar.schema()
  }

  static editForm = editForm

  /**
   * Render returns an html string of the fully rendered component.
   *
   * @param children - If this class is extendended, the sub string is passed as children.
   * @returns {string}
   */
  render(children) {
    // To make this dynamic, we could call this.renderTemplate('templatename', {}).
    let calendarClass = '';
    let content = this.renderTemplate('input', {
      input: {
        type: 'input',
        ref: `${this.component.key}-selected`,
        attr: {
          id: `${this.component.key}`,
          class: 'form-control',
          type: 'hidden',
        }
      }
    });

    // Calling super.render will wrap it html as a component.
    return super.render(`<div id="calendar-container-${this.id}" class="slot-calendar"><div class="row"><div class="col-12 col-md-6"><h6>${this.component.label}</h6><div  class="date-picker"></div></div><div class="col-12 col-md-6"><div class="row" id="slot-picker"></div></div></div></div>${content}`);
  }

  /**
   * After the html string has been mounted into the dom, the dom element is returned here. Use refs to find specific
   * elements to attach functionality to.
   *
   * @param element
   * @returns {Promise}
   */
  attach(element) {
    /*const refs = {};
    refs[`${this.component.key}-selected`] = 'multiple';
    this.loadRefs(element, refs);
    this.selected = Array.prototype.slice.call(this.refs[`${this.component.key}-selected`], 0);*/

    let self = this,
        array = ["2020-02-18","2020-02-25","2020-02-29","2020-03-01","2020-03-05","2020-03-09","2020-03-19","2020-03-21","2020-03-22","2020-03-29"],
        html = '';

    this.container = $('#calendar-container-' + this.id);

    $.datepicker.regional['it'] = {
      closeText: 'Chiudi', // set a close button text
      currentText: 'Oggi', // set today text
      monthNames: ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',   'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'], // set month names
      monthNamesShort: ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'], // set short month names
      dayNames: ['Domenica','Luned&#236','Marted&#236','Mercoled&#236','Gioved&#236','Venerd&#236','Sabato'], // set days names
      dayNamesShort: ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'], // set short day names
      dayNamesMin: ['Do','Lu','Ma','Me','Gio','Ve','Sa'], // set more short days names
      //dateFormat: 'dd-mm.-yy' // set format date
    };
    $.datepicker.setDefaults($.datepicker.regional['it']);

    this.calendar = this.container.find('.date-picker').datepicker({
      minDate: new Date(),
      firstDay: 1,
      dateFormat: 'dd-mm-yy',
      onSelect: function(dateText) {
        self.date = dateText;
        self.getDaySlots();
      },
      beforeShowDay: function(date){
        var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
        return [ array.indexOf(string) !=  -1 ]
      }
    });

    // Allow basic component functionality to attach like field logic and tooltips.
    return super.attach(element);
  }

  /**
   * Get the value of the component from the dom elements.
   *
   * @returns {Array}
   */
  getValue() {
    return {
      "date": this.date,
      "slot": this.slot,
    };
  }

  /**
   * Set the value of the component into the dom elements.
   *
   * @param value
   * @returns {boolean}
   */
  setValue(value) {
    console.log('setValue');
    console.log(value);
    if (!value) {
      return;
    }
    this.date = value.date;
    this.slot = value.slot;
    if (this.date) {
      let parsedDate = moment(this.date, 'DD-MM-YYYY');
      console.log(parsedDate);
      this.calendar.datepicker("setDate", parsedDate.toDate());
      this.getDaySlots();
    }
  }

  getDaySlots(){
    let self = this,
        html = '';
    this.container.find('#slot-picker').html(html);
    $.ajax('https://json-server.ship.opencontent.io/meetings',
      {
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          $(data).each(function( index, element ) {
            var cssClass = 'available';
            if (!element.available) {
              cssClass = 'disabled';
            }

            if (element.key == self.slot) {
              cssClass = cssClass + ' active';
            }

            html = html.concat('<div class="col-6"><button type="button" data-slot="' + element.key +'" class="btn btn-ora '+  cssClass +'">'+ element.key +'</button></div>');

          });
          self.container.find('#slot-picker').html('<div class="col-12"><h6>Orari disponibili per il giorno ' + self.date + '</h6></div>' + html);

          $('.btn-ora.available').on('click', function (e) {
            e.preventDefault();
            $('.btn-ora.active').removeClass('active');
            $(this).addClass('active');
            self.slot = $(this).data('slot');
            self.updateValue();
          })
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert(errorMessage);
        }
      });
  }
}
