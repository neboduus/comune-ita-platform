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
    this.meeting = null;
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
    return super.render(`<div id="calendar-container-${this.id}" class="slot-calendar d-print-none d-preview-calendar-none"><div class="row"><div class="col-12 col-md-6"><h6>${this.component.label}</h6>
<div class="date-picker"></div></div><div class="col-12 col-md-6"><div class="row" id="slot-picker"></div></div></div></div>${content}
<div id="date-picker-print" class="mt-3 d-print-block d-preview-calendar"></div>`);
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
              minDate: new Date(data.sort((a, b) => a.date.localeCompare(b.date))[0]),
              firstDay: 1,
              dateFormat: 'dd-mm-yy',
              onSelect: function(dateText) {
                if (dateText !== self.date) {
                  // If date changed, reset slot choice
                  self.slot = false;
                  self.updateValue();
                }
                self.date = dateText;
                self.getDaySlots();

                let slotText = self.slot ? ' alle ore '+ self.slot : '';
                $('#date-picker-print').html('<b>Giorno selezionato per l\'appuntamento: </b> '+ self.date +' '+ slotText)

              },
              beforeShowDay: function(date){
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                if(data.some(e => e.available === false && e.date === string)){
                  return [ data.some(e => e.date === string) , 'not-available']
                }
                return [ (data.some(e => e.date === string)) ]
              },
            });

            if (self.date) {
              let parsedDate = moment(self.date, 'DD-MM-YYYY');
              self.calendar.datepicker("setDate", parsedDate.toDate());
              self.getDaySlots();
            }
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
          }, complete: function () {
            //Auto-click current selected day
            var dayActive = $('a.ui-state-active');
            if(!self.date && dayActive.length > 0){
              dayActive.click();
            }

            if(self.date && self.slot)
            $('#date-picker-print').html('<b>Giorno selezionato per l\'appuntamento: </b> '+ self.date +' alle ore '+ self.slot)
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
    if (!(this.date && this.slot)) {
      // Unset value (needed for calendars with 'required' option"
      return null;
    }
    let meeting_id = this.meeting ? this.meeting : "";
    return this.date.replace(/-/g, "/") + ' @ ' + this.slot + ' (' + this.component.calendarId + '#' + meeting_id + ')';
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
    let explodedCalendar = explodedValue[2].split("#");
    this.date = explodedValue[0];
    this.slot = explodedValue[1];
    this.calendar = explodedCalendar[0];
    this.meeting = explodedCalendar[1];

    if(this.date && this.slot){
      $('#date-picker-print').html('<b>Giorno selezionato per l\'appuntamento: </b> '+ this.date +' alle ore '+ this.slot)
    }
  }


  getDaySlots(){
    let self = this,
        calendarID = this.component.calendarId,
        html = '',
        location = window.location,
        explodedPath = location.pathname.split("/"),
        parsedDate = moment(self.date, 'DD-MM-YYYY');

    this.container.find('#slot-picker').html(html);
    let url = location.origin + '/' + explodedPath[1] + '/api/calendars/' + calendarID + '/availabilities/' + parsedDate.format('YYYY-MM-DD');
    if (self.meeting) {
      // Exclude saved meeting from unavailabilities
      url = `${url}?exclude=${self.meeting}`
    }
    $.ajax(url,
      {
        dataType: 'json', // type of response data
        beforeSend: function(){
          self.container.find('#slot-picker').append('<div class="col-12">' + self.loaderTpl + '</div>');
        },
        success: function (data, status, xhr) {   // success callback function
          $('#loader').remove();
          var countAllElmAvailables = data.filter(function(item){
            return item.availability;
          }).length;

          if(countAllElmAvailables === 0){
            self.container.find('#slot-picker').html('<div class="callout warning">\n' +
              '  <div class="callout-title"><svg class="icon"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-help-circle"></use></svg>Attenzione</div>\n' +
              '  <p>Non è possibile prendere appuntamento in questa giornata, tutti gli appuntamenti a disposizione sono stati già prenotati</p>\n' +
              '</div>');
          }else{
            $(data).each(function( index, element ) {
              var cssClass = 'available';
              if (!element.availability) {
                cssClass = 'disabled';
              }

              let key = element.start_time + '-' + element.end_time;
              if (key === self.slot) {
                  cssClass = cssClass + ' active';
              }

              html = html.concat('<div class="col-6"><button type="button" data-slot="' + key +'" class="btn btn-ora p-0 '+  cssClass +'">'+ key +'</button></div>');

            });
            self.container.find('#slot-picker').html('<div class="col-12"><h6>Orari disponibili il ' + self.date + '</h6></div>' + html);

            $('.btn-ora.available').on('click', function (e) {
              e.preventDefault();
              $('.btn-ora.active').removeClass('active');
              $(this).addClass('active');
              self.slot = $(this).data('slot');

              self.createOrUpdateMeeting();
              self.updateValue()

              $('#date-picker-print').addClass('d-preview-calendar-none');
            })
          }

        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
        }, complete: function (){
          //Click available hour button only is visible for auto selection
          var btnHourActive = $('.btn-ora.available.active');
          if(btnHourActive.length > 0 && btnHourActive.is(":visible")){
            btnHourActive.click();
          }
        }
      });
  }

  createOrUpdateMeeting(){
    let self = this,
        location = window.location,
        explodedPath = location.pathname.split("/");

    $.ajax(location.origin + '/' + explodedPath[1] + '/meetings/new-draft',
        {
          method: "POST",
          data: {"date": self.date, "slot": self.slot, "calendar":this.component.calendarId, "meeting": self.meeting},
          dataType: 'json', // type of response data
          success: function (data, status, xhr) {   // success callback function
            self.meeting = data;
            self.updateValue();
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            alert("Si è verificato un errore, si prega di riprovare");
            // Reinitialize
            self.slot = null;
            self.meeting = null;
            self.updateValue();
            self.getDaySlots();
          },
          complete: function () {
              let slotText = self.slot ? ' alle ore '+ self.slot : '';
              $('#date-picker-print').html('<b>Giorno selezionato per l\'appuntamento: </b> '+ self.date +' '+ slotText)
          }
        });
  }
}
