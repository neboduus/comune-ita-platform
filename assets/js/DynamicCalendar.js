import "jquery-ui/ui/widgets/datepicker"
import ionRangeSlider from "ion-rangeslider"

import Base from 'formiojs/components/_classes/component/Component';
import editForm from './DynamicCalendar/DynamicCalendar.form'
import moment from 'moment'


export default class FormioCalendar extends Base {
  constructor(component, options, data) {
    super(component, options, data);
    this.date = false;
    this.slot = false;
    this.available_slot = false;
    this.container = false;
    this.calendar = null;
    this.meeting = null;
    this.meeting_expiration_time = null;
    this.opening_hour = null;
    this.min_duration = null;
    this.loaderTpl = '<div id="loader" class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-lg fa-fw"></i><span class="sr-only">Loading...</span></div>';
  }

  static schema() {
    return Base.schema({
      type: 'dynamic_calendar'
    });
  }

  static builderInfo = {
    title: 'Dynamic Calendar',
    group: 'basic',
    icon: 'calendar-plus-o',
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
    return super.render(`
        <div id="calendar-container-${this.id}" class="slot-calendar d-print-none d-preview-calendar-none">
            <div class="row">
                <div class="col-12 col-md-6">
                    <h6>${this.component.label}</h6>
                    <div class="date-picker"></div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="row" id="slot-picker"></div>
                </div>
            </div>
        </div>

        <div id="slot-picker" class="mt-3 d-print-block d-preview-calendar"></div>
        <div id="range-picker" class="my-5"></div>
        ${content}
        <div id="date-picker-print" class="mt-3 d-print-block d-preview-calendar"></div>
        <div id="draft-expiration" class="mt-3 d-print-none d-preview-none"></div>
        `);
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
      selectOpeningHours = this.component.select_opening_hours,
      openingHours = this.component.select_opening_hours ? this.component.opening_hours : [],
      location = window.location,
      html = '',
      explodedPath = location.pathname.split("/");

    this.container = $(`#calendar-container-${this.id}`);

    $.datepicker.regional['it'] = {
      closeText: 'Chiudi', // set a close button text
      currentText: 'Oggi', // set today text
      monthNames: ['Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno', 'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'], // set month names
      monthNamesShort: ['Gen', 'Feb', 'Mar', 'Apr', 'Mag', 'Giu', 'Lug', 'Ago', 'Set', 'Ott', 'Nov', 'Dic'], // set short month names
      dayNames: ['Domenica', 'Luned&#236', 'Marted&#236', 'Mercoled&#236', 'Gioved&#236', 'Venerd&#236', 'Sabato'], // set days names
      dayNamesShort: ['Dom', 'Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab'], // set short day names
      dayNamesMin: ['Do', 'Lu', 'Ma', 'Me', 'Gio', 'Ve', 'Sa'], // set more short days names
      nextText: '',
      prevText: ''
      //dateFormat: 'dd-mm.-yy' // set format date
    };

    $.datepicker.setDefaults($.datepicker.regional['it']);

    if (calendarID !== '' && calendarID != null && !this.disabled) {
      let url = `${location.origin}/${explodedPath[1]}/api/calendars/${calendarID}/availabilities`;
      if (selectOpeningHours && openingHours) {
        url = `${url}?opening_hours=${openingHours.join()}`;
      }
      $.ajax(url,
        {
          dataType: 'json', // type of response data
          beforeSend: function () {
            self.container.find('.date-picker').append(self.loaderTpl);
          },
          success: function (data, status, xhr) {   // success callback function
            $('#loader').remove();
            self.calendar = self.container.find('.date-picker').datepicker({
              minDate: new Date(data.sort((a, b) => a.date.localeCompare(b.date))[0]),
              firstDay: 1,
              dateFormat: 'dd-mm-yy',
              onSelect: function (dateText) {
                if (dateText !== self.date) {
                  // If date changed, reset slot choice
                  self.available_slot = false;
                  self.slot = false;
                  self.opening_hour = false;
                  self.min_duration = false;
                  self.updateValue();
                }
                self.date = dateText;
                self.getDaySlots();

                $('#range-picker').html('')
                let slotText = self.slot ? ` alle ore ${self.slot}` : '';
                $('#date-picker-print').html(`<b>Giorno selezionato per la prenotazione: </b> ${self.date} ${slotText}`);

                if (self.meeting_expiration_time) {
                  let expiration = `${self.meeting_expiration_time.format("DD/MM/YYYY")} alle ore ${self.meeting_expiration_time.format("HH:mm")}`;
                  $('#draft-expiration').html(`<i>Ti è stata riservata una prenotazione in bozza all'orario sopra indicato valido fino al giorno ${expiration}. Procedi con l'invio della domanda prima della scadenza per confermare la prenotazione e non perdere la priorità per il giorno e l'orario selezionati</i>`)
                }

              },
              beforeShowDay: function (date) {
                var string = jQuery.datepicker.formatDate('yy-mm-dd', date);
                if (data.some(e => e.available === false && e.date === string)) {
                  return [data.some(e => e.date === string), 'not-available']
                }
                return [(data.some(e => e.date === string))]
              },
            });

            if (self.date) {
              let parsedDate = moment(self.date, 'DD/MM/YYYY');
              self.calendar.datepicker("setDate", parsedDate.toDate());
              self.getDaySlots();
            }
          },
          error: function (jqXhr, textStatus, errorMessage) { // error callback
            alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
          }, complete: function () {
            //Auto-click current selected day
            var dayActive = $('a.ui-state-active');
            if (!self.date && dayActive.length > 0) {
              dayActive.click();
            }

            if (self.date && self.slot) {
              $('#date-picker-print').html(`<b>Giorno selezionato per la prenotazione: </b> ${self.date} alle ore ${self.slot}`)
            }
            if (self.meeting_expiration_time) {
              let expiration = `${self.meeting_expiration_time.format("DD/MM/YYYY")} alle ore ${self.meeting_expiration_time.format("HH:mm")}`;
              $('#draft-expiration').html(`<i>Ti è stata riservata una prenotazione in bozza all'orario sopra indicato valido fino al giorno ${expiration}. Procedi con l'invio della domanda prima della scadenza per confermare la prenotazione e non perdere la priorità per il giorno e l'orario selezionati</i>`)
            }
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
    let opening_hour = this.opening_hour ? this.opening_hour : "";
    return `${this.date.replace(/-/g, "/")} @ ${this.slot} (${this.component.calendarId}#${meeting_id}#${opening_hour})`;
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
    this.meeting_expiration_time = null;
    this.opening_hour = explodedCalendar.length === 3 ? explodedCalendar[2] : "";

    if (this.date && this.slot) {
      $('#date-picker-print').html(`<b>Giorno selezionato per la prenotazione: </b> ${this.date} alle ore ${this.slot}`)
    }
    if (self.meeting_expiration_time) {
      let expiration = `${self.meeting_expiration_time.format("DD/MM/YYYY")} alle ore ${self.meeting_expiration_time.format("HH:mm")}`;
      $('#draft-expiration').html(`<i>Ti è stata riservata una prenotazione in bozza all'orario sopra indicato valido fino al giorno ${expiration}. Procedi con l'invio della domanda prima della scadenza per confermare la prenotazione e non perdere la priorità per il giorno e l'orario selezionati</i>`)
    }
  }


  getDaySlots() {
    let self = this,
      calendarID = this.component.calendarId,
      selectOpeningHours = this.component.select_opening_hours,
      openingHours = this.component.select_opening_hours ? this.component.opening_hours : [],
      step = this.component.range_step ? this.component.range_step : 1,
      html = '',
      location = window.location,
      explodedPath = location.pathname.split("/"),
      parsedDate = moment(self.date, 'DD-MM-YYYY');

    this.container.find('#slot-picker').html(html);
    let url = `${location.origin}/${explodedPath[1]}/api/calendars/${calendarID}/availabilities/${parsedDate.format('YYYY-MM-DD')}`;

    let queryParameters = []
    if (self.meeting) {
      // Exclude saved meeting from unavailabilities
      queryParameters.push(`exclude=${self.meeting}`)
    }
    if (selectOpeningHours && openingHours) {
      // Select specific opening hours
      queryParameters.push(`opening_hours=${openingHours.join()}`)
    }

    if (queryParameters) {
      url = `${url}?${queryParameters.join('&')}`;
    }


    $.ajax(url,
      {
        dataType: 'json', // type of response data
        beforeSend: function () {
          self.container.find('#slot-picker').append(`<div class="col-12">${self.loaderTpl}</div>`);
          self.container.find('#range-picker').append(`<div class="col-12">${self.loaderTpl}</div>`);

        },
        success: function (data, status, xhr) {   // success callback function
          $('#loader').remove();
          var countAllElmAvailables = data.filter(function (item) {
            return item.availability;
          }).length;

          if (countAllElmAvailables === 0) {
            self.container.find('#slot-picker').html(`<div class="callout warning">
                            <div class="callout-title"><svg class="icon"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-help-circle"></use></svg>Attenzione</div>
                            <p>Non è possibile prenotare in questa giornata, tutte le fasce orarie a disposizione sono già prenotate</p>
                            </div>`);
          } else {
            $(data).each(function (index, element) {
              var cssClass = 'available';
              var ariaDisabled = false
              if (!element.availability) {
                cssClass = 'disabled';
                ariaDisabled = true;
              }

              let key = `${element.start_time}-${element.end_time}`;
              if (key === self.available_slot) {
                cssClass = `${cssClass} active`;
              }
              let op_hour = element.opening_hour;
              let min_duration = element.min_duration;

              html = html.concat(`<div class="col-6"><button type="button" data-available_slots="${key}" data-opening_hour="${op_hour}" data-min_duration="${min_duration}" class="btn btn-ora p-0 ${cssClass}">${key}</button></div>`);

            });
            self.container.find('#slot-picker').html(`<div class="col-12"><h6>Seleziona la fascia oraria per il giorno ${self.date}</h6></div>${html}`);


            $('.btn-ora.available').on('click', function (e) {
              e.preventDefault();
              $('.btn-ora.active').removeClass('active');
              $(this).addClass('active');
              self.available_slot = $(this).data('available_slots');
              self.opening_hour = $(this).data('opening_hour');
              self.min_duration = $(this).data('min_duration');

              let slots = self.available_slot.split('-');
              let start = slots[0].replace(":", "")
              let end = slots[1].replace(":", "")


              $('#range-picker').html(
                `<div class="my-2">
                                   <label for="range">Se preferisci puoi utilizzare il controllo che segue per accorciare la durata della prenotazione</label>
                                   <input type="text" class="mt-2" id="range" value=""/>
                                </div>
                                <div class="row mt-3">
                                   <div class="col-6">
                                       <label for="from-range">Dalle</label>
                                       <input id="from-range" type="time" value="${slots[0]}">
                                   </div>
                                   <div class="col-6">
                                       <label for="from-range">Alle</label>
                                       <input id="to-range" type="time" value="${slots[1]}">
                                   </div>
                                </div>`
              )

              let range = $("#range")
              range.ionRangeSlider({
                skin: "flat",
                type: "double",
                grid: false,
                drag_interval: true,
                force_edges: true,
                step: step * 60000,
                min_interval: self.min_duration * 60 * 1000,
                min: moment(start, "HHmm").valueOf(),
                max: moment(end, "HHmm").valueOf(),
                from: moment(start, "HHmm").valueOf(),
                to: moment(end, "HHmm").valueOf(),
                prettify: function (num) {
                  return moment(num).format('HH:mm');
                },
                onStart: function (data) {
                  // fired on pointer release
                  self.slot = `${data.from_pretty}-${data.to_pretty}`;
                  $('#from-range').val(data.from_pretty)
                  $('#to-range').val(data.to_pretty)
                  self.createOrUpdateMeeting();

                },
                onUpdate: function (data) {
                  self.slot = `${data.from_pretty}-${data.to_pretty}`;
                  self.createOrUpdateMeeting()
                },
                onFinish: function (data) {
                  // fired on pointer release
                  self.slot = `${data.from_pretty}-${data.to_pretty}`;
                  $('#from-range').val(data.from_pretty)
                  $('#to-range').val(data.to_pretty)
                  self.createOrUpdateMeeting()
                },
              });

              var instance = range.data("ionRangeSlider");
              $('#from-range').on("blur", function () {
                if ($(this).val() < slots[0] || $(this).val() > slots[1]) {
                  alert(`Il valore ${$(this).val()} non è valido`)
                  $(this).val(slots[0])
                } else {
                  instance.update({
                    from: moment($(this).val(), "HHmm").valueOf()
                  });
                }
              });

              $('#to-range').on("blur", function () {
                if ($(this).val() < slots[0] || $(this).val() > slots[1]) {
                  alert(`Il valore ${$(this).val()} non è valido`)
                  $(this).val(slots[1])
                } else {
                  instance.update({
                    to: moment($(this).val(), "HHmm").valueOf()
                  });
                }
              });

              self.updateValue()

              $('#date-picker-print').addClass('d-preview-calendar-none');
            })
          }

        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore nel recupero delle disponibilità, si prega di riprovare");
        }, complete: function () {
          //Click available hour button only is visible for auto selection
          var btnHourActive = $('.btn-ora.available.active');
          if (btnHourActive.length > 0 && btnHourActive.is(":visible")) {
            btnHourActive.click();
          }
        }
      });
  }

  createOrUpdateMeeting() {
    let self = this,
      location = window.location,
      explodedPath = location.pathname.split("/");

    $.ajax(`${location.origin}/${explodedPath[1]}/${explodedPath[2]}/meetings/new-draft`,
      {
        method: "POST",
        data: {
          "date": self.date,
          "slot": self.slot,
          "calendar": this.component.calendarId,
          "opening_hour": self.opening_hour,
          "meeting": self.meeting
        },
        dataType: 'json', // type of response data
        success: function (data, status, xhr) {   // success callback function
          self.meeting = data["id"];
          self.meeting_expiration_time = moment(data["expiration_time"], "YYYY-MM-DD HH:mm")
          self.updateValue();
        },
        error: function (jqXhr, textStatus, errorMessage) { // error callback
          alert("Si è verificato un errore, si prega di riprovare");
          // Reinitialize
          self.slot = self.available_slot;
          self.meeting = null;
          self.meeting_expiration_time = null;

          $('#range-picker').html('')

          self.updateValue();
          self.getDaySlots();
        },
        complete: function () {
          let slotText = self.slot ? ` alle ore ${self.slot}` : '';
          $('#date-picker-print').html(`<b>Giorno selezionato per la prenotazione: </b> ${self.date} ${slotText}`);
          if (self.meeting_expiration_time) {
            let expiration = `${self.meeting_expiration_time.format("DD/MM/YYYY")} alle ore ${self.meeting_expiration_time.format("HH:mm")}`;
            $('#draft-expiration').html(`<i>Ti è stata riservata una prenotazione in bozza all'orario sopra indicato valido fino al giorno ${expiration}. Procedi con l'invio della domanda prima della scadenza per confermare la prenotazione e non perdere la priorità per il giorno e l'orario selezionati</i>`)
          }
        }
      });
  }
}
