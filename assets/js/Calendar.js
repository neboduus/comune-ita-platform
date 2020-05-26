import "jquery-ui/ui/widgets/datepicker"
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
    this.loaderTpl = '<div id="loader" class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-lg fa-fw"></i><span class="sr-only">Loading...</span></div>';
  }

  static schema() {
    return Base.schema({
      type: 'calendar'
    });
  }

  static builderInfo = {
    title: 'Calendar',
    group: 'basic',
    icon: 'calendar',
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
    return super.render(`<div id="calendar-container-${this.id}" class="slot-calendar"><div class="row"><div class="col-12 col-md-6"><h6>${this.component.label}</h6>
<div  class="date-picker"></div></div><div class="col-12 col-md-6"><div class="row" id="slot-picker"></div></div></div></div>${content}`);
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
        calendarID = this.component.calendarId,
        location = window.location,
        html = '',
        explodedPath = location.pathname.split("/");

    this.container = $('#calendar-container-' + this.id);

    $.datepicker.regional['it'] = {
      closeText: 'Chiudi', // set a close button text
      currentText: 'Oggi', // set today text
      monthNames: ['Gennaio','Febbraio','Marzo','Aprile','Maggio','Giugno',   'Luglio','Agosto','Settembre','Ottobre','Novembre','Dicembre'], // set month names
      monthNamesShort: ['Gen','Feb','Mar','Apr','Mag','Giu','Lug','Ago','Set','Ott','Nov','Dic'], // set short month names
      dayNames: ['Domenica','Luned&#236','Marted&#236','Mercoled&#236','Gioved&#236','Venerd&#236','Sabato'], // set days names
      dayNamesShort: ['Dom','Lun','Mar','Mer','Gio','Ven','Sab'], // set short day names
      dayNamesMin: ['Do','Lu','Ma','Me','Gio','Ve','Sa'], // set more short days names
      nextText: '',
      prevText: ''
      //dateFormat: 'dd-mm.-yy' // set format date
    };

    $.datepicker.setDefaults($.datepicker.regional['it']);

    if (calendarID !== '' && calendarID != null) {
      $.ajax(location.origin + '/' + explodedPath[1] + '/api/calendars/' + calendarID + '/availabilities',
        {
          dataType: 'json', // type of response data
          beforeSend: function(){
            self.container.find('.date-picker').append(self.loaderTpl);
          },
          success: function (data, status, xhr) {   // success callback function

            $('#loader').remove();
            self.calendar = self.container.find('.date-picker').datepicker({
              minDate: new Date(data.sort()[0]),
              firstDay: 1,
              dateFormat: 'dd-mm-yy',
              onSelect: function(dateText) {
                self.date = dateText;
                self.getDaySlots();
              },
              beforeShowDay: function(date){
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                return [ data.indexOf(string) !=  -1 ]
              }
            });

            if (self.date) {
              let parsedDate = moment(self.date, 'DD-MM-YYYY');
              self.calendar.datepicker("setDate", parsedDate.toDate());
              self.getDaySlots();
            }

          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
            console.log(errorMessage);
          }
        });
    }

    // Allow basic component functionality to attach like field logic and tooltips.
    return super.attach(element);
  }

  /**
   * Get the value of the component from the dom elements.
   *
   * @returns {String}
   */
  getValue() {
    return this.date.replace(/-/g, "/") + ' @ ' + this.slot + ' (' + this.component.calendarId +')';
  }

  /**
   * Set the value of the component into the dom elements.
   *
   * @param value
   * @returns {boolean}
   */
  setValue(value) {
    if (!value) {
      return;
    }
    let explodedValue = value.replace(")", "").replace(' (', " @ ").replace(/\//g, "-").split(" @ ");
    this.date = explodedValue[0];
    this.slot = explodedValue[1];
  }

  getDaySlots(){
    let self = this,
        calendarID = this.component.calendarId,
        html = '',
        location = window.location,
        explodedPath = location.pathname.split("/"),
        parsedDate = moment(self.date, 'DD-MM-YYYY');

    this.container.find('#slot-picker').html(html);
    $.ajax(location.origin + '/' + explodedPath[1] + '/api/calendars/' + calendarID + '/availabilities/' + parsedDate.format('YYYY-MM-DD'),
      {
        dataType: 'json', // type of response data
        beforeSend: function(){
          self.container.find('#slot-picker').append('<div class="col-12">' + self.loaderTpl + '</div>');
        },
        success: function (data, status, xhr) {   // success callback function
          $('#loader').remove();
          $(data).each(function( index, element ) {
            var cssClass = 'available';
            if (!element.availability) {
              cssClass = 'disabled';
            }

            let key = element.start_time + '-' + element.end_time;
            if (key == self.slot) {
              cssClass = cssClass + ' active';
            }

            html = html.concat('<div class="col-6"><button type="button" data-slot="' + key +'" class="btn btn-ora '+  cssClass +'">'+ key +'</button></div>');

          });
          self.container.find('#slot-picker').html('<div class="col-12"><h6>Orari disponibili il ' + self.date + '</h6></div>' + html);

          $('.btn-ora.available').on('click', function (e) {
            e.preventDefault();
            $('.btn-ora.active').removeClass('active');
            $(this).addClass('active');
            self.slot = $(this).data('slot');
            self.updateValue();
          })
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
          console.log(errorMessage);
        }
      });
  }
}
