import datepickerFactory from "jquery-datepicker";
import Base from "formiojs/components/_classes/component/Component";
import editForm from "./Calendar/Calendar.form";
import moment from "moment";
import { i18nDatepicker } from "./translations/i18n-datepicker";
import { CountDownTimer } from "./components/Countdown";

datepickerFactory($);

export default class FormioCalendar extends Base {
  constructor(component, options, data) {
    super(component, options, data);
    this.date = false;
    this.slot = false;
    this.container = false;
    this.calendar = null;
    this.meeting = null;
    this.meeting_expiration_time = null;
    this.opening_hour = null;
    this.first_available_date = null;
    this.first_available_start_time = null;
    this.first_available_end_time = null;
    this.first_availability_updated_at = null;
    this.$language = document.documentElement.lang.toString();
    this.countdown = false;
    this.loaderTpl = `<div id="loader" class="text-center"><i class="fa fa-circle-o-notch fa-spin fa-lg fa-fw"></i><span class="sr-only">${Translator.trans(
      "loading",
      {},
      "messages",
      this.$language
    )}</span></div>`;
  }

  static schema() {
    return Base.schema({
      type: "calendar",
    });
  }

  static builderInfo = {
    title: "Calendar",
    group: "basic",
    icon: "calendar",
    weight: 70,
    schema: FormioCalendar.schema(),
  };

  static editForm = editForm;

  /**
   * Render returns a html string of the fully rendered component.
   *
   * @param children - If this class is extended, the sub string is passed as children.
   * @returns {string}
   */
  render(children) {
    // To make this dynamic, we could call this.renderTemplate('templatename', {}).
    let calendarClass = "";
    let content = this.renderTemplate("input", {
      input: {
        type: "input",
        ref: `${this.component.key}-selected`,
        attr: {
          id: `${this.component.key}`,
          class: "form-control",
          type: "hidden",
        },
      },
    });
    // Calling super.render will wrap it html as a component.
    return super.render(`
    <div id="calendar-container-${this.id}" class="slot-calendar d-print-none d-preview-calendar-none">
<div class="row"><div class="col-12 col-md-6"><h6>${this.component.label}</h6>
<div class="date-picker"></div></div><div class="col-12 col-md-6"><div class="row" id="slot-picker"></div></div></div></div>${content}
<div id="date-picker-print" class="mt-3 d-print-block d-preview-calendar"></div><div class="mt-3 d-print-none d-preview-none" id="draft-expiration-container"><div id="draft-expiration" class="d-print-none d-preview-none"></div><span id="draft-expiration-countdown" class="font-weight-bolder"></span></div>`);
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
      openingHours = this.component.select_opening_hours
        ? this.component.opening_hours
        : [],
      location = window.location,
      html = "",
      explodedPath = location.pathname.split("/");

    this.container = $(`#calendar-container-${this.id}`);

    // override default values calendar
    $.datepicker.regional[self.$language] = i18nDatepicker[self.$language];
    $.datepicker.setDefaults($.datepicker.regional[self.$language]);

    if (calendarID !== "" && calendarID != null && !this.disabled) {
      let url = `${location.origin}/${explodedPath[1]}/api/calendars/${calendarID}/availabilities`;
      if (selectOpeningHours && openingHours) {
        url = `${url}?opening_hours=${openingHours.join()}`;
      }
      $.ajax(url, {
        dataType: "json", // type of response data
        beforeSend: function () {
          self.container.find(".date-picker").append(self.loaderTpl);
        },
        success: function (data, status, xhr) {
          self.getFirstAvailableSlot(data);

          $("#loader").remove();
          self.calendar = self.container.find(".date-picker").datepicker({
            minDate: new Date(
              data.sort((a, b) => a.date.localeCompare(b.date))[0]
            ),
            firstDay: 1,
            dateFormat: "dd-mm-yy",
            onSelect: function (dateText) {
              if (dateText !== self.date) {
                // If date changed, reset slot choice
                self.slot = false;
                self.opening_hour = false;
                self.updateValue();
              }
              self.date = dateText;
              self.getDaySlots();

              let slotText = self.slot
                ? ` ${Translator.trans(
                    "calendar_formio.at_hours",
                    {},
                    "messages",
                    self.$language
                  )} ${self.slot}`
                : "";
              $("#date-picker-print").html(
                `<b>${Translator.trans(
                  "calendar_formio.day_selected",
                  {},
                  "messages",
                  self.$language
                )}: </b> ${self.date} ${slotText}`
              );

              if (self.meeting_expiration_time) {
                let expiration = `${self.meeting_expiration_time.format(
                  "DD-MM-YYYY"
                )} ${Translator.trans(
                  "calendar_formio.at_hours",
                  {},
                  "messages",
                  self.$language
                )} ${self.meeting_expiration_time.format("HH:mm")}`;
                $("#draft-expiration").html(
                  `<i>${Translator.trans(
                    "calendar_formio.draft_expiration_text",
                    {},
                    "messages",
                    self.$language
                  )} ${self.meeting_expiration_time.format("HH:mm")}</i>`
                );
                $("#draft-expiration-container").addClass("alert alert-info");
                CountDownTimer(
                  self.meeting_expiration_time,
                  "draft-expiration-countdown"
                );
              }
            },
            beforeShowDay: function (date) {
              var string = jQuery.datepicker.formatDate("yy-mm-dd", date);
              if (
                data.some((e) => e.available === false && e.date === string)
              ) {
                return [
                  false,
                  "disabled not-available",
                  Translator.trans(
                    "calendar_formio.unavailable",
                    {},
                    "messages",
                    self.$language
                  ),
                ];
              } else {
                return [data.some((e) => e.date === string)];
              }
            },
          });

          if (self.date) {
            let parsedDate = moment(self.date, "DD-MM-YYYY");
            self.calendar.datepicker("setDate", parsedDate.toDate());
            self.getDaySlots();
          }
        },
        error: function (jqXhr, textStatus, errorMessage) {
          // error callback
          alert(
            `${Translator.trans(
              "calendar_formio.availability_error",
              {},
              "messages",
              self.$language
            )}`
          );
        },
        complete: function () {
          //Auto-click current selected day
          var dayActive = $("a.ui-state-active");
          if (!self.date && dayActive.length > 0) {
            dayActive.click();
          }

          if (self.date && self.slot) {
            $("#date-picker-print").html(
              `<b>${Translator.trans(
                "calendar_formio.day_selected",
                {},
                "messages",
                self.$language
              )}: </b> ${self.date} ${Translator.trans(
                "calendar_formio.at_hours",
                {},
                "messages",
                self.$language
              )} ${self.slot}`
            );
          }
          if (self.meeting_expiration_time) {
            $("#draft-expiration").html(
              `<i>${Translator.trans(
                "calendar_formio.draft_expiration_text",
                {},
                "messages",
                self.$language
              )}</i>`
            );
          }
        },
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
    return `${this.date.replace(/-/g, "/")} @ ${this.slot} (${
      this.component.calendarId
    }#${meeting_id}#${opening_hour})`;
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
    let explodedValue = value
      .replace(")", "")
      .replace(" (", " @ ")
      .replace(/\//g, "-")
      .split(" @ ");
    let explodedCalendar = explodedValue[2].split("#");
    this.date = explodedValue[0];
    this.slot = explodedValue[1];
    this.calendar = explodedCalendar[0];
    this.meeting = explodedCalendar[1];
    this.meeting_expiration_time = null;
    this.opening_hour =
      explodedCalendar.length === 3 ? explodedCalendar[2] : "";

    if (this.date && this.slot) {
      $("#date-picker-print").html(
        `<b>${Translator.trans(
          "calendar_formio.day_selected",
          {},
          "messages",
          self.$language
        )}: </b> ${this.date} ${Translator.trans(
          "calendar_formio.at_hours",
          {},
          "messages",
          self.$language
        )} ${this.slot}`
      );
    }
    if (self.meeting_expiration_time) {
      let expiration = `${self.meeting_expiration_time.format(
        "DD-MM-YYYY"
      )} ${Translator.trans(
        "calendar_formio.at_hours",
        {},
        "messages",
        self.$language
      )} ${self.meeting_expiration_time.format("HH:mm")}`;
      $("#draft-expiration").html(
        `<i>${Translator.trans(
          "calendar_formio.draft_expiration_text",
          {},
          "messages",
          self.$language
        )}</i>`
      );
      $("#draft-expiration-container").addClass("alert alert-info");
    }
  }

  /**
   * Get first available slot from calendar
   * @param availabilities
   */
  getFirstAvailableSlot(availabilities) {
    availabilities = availabilities.sort((a, b) =>
      a.date.localeCompare(b.date)
    );
    $(availabilities).each((i, e) => {
      if (e.available) {
        this.first_available_date = e.date;
        return false;
      }
    });
    if (this.first_available_date !== null) {
      let self = this,
        calendarID = this.component.calendarId,
        location = window.location,
        explodedPath = location.pathname.split("/");

      $.ajax(
        `${location.origin}/${explodedPath[1]}/api/calendars/${calendarID}/availabilities/${this.first_available_date}?available=true`,
        {
          dataType: "json",
          success: function (data, status, xhr) {
            // success callback function
            let slot = data[0];
            self.first_available_start_time = slot.start_time;
            self.first_available_end_time = slot.end_time;
            self.first_availability_updated_at = moment().format();
          },
          error: function (jqXhr, textStatus, errorMessage) {
            // error callback
            console.log("Impossibile selezionare prima disponibilità");
          },
        }
      );
    }
  }

  getDaySlots() {
    let self = this,
      calendarID = this.component.calendarId,
      selectOpeningHours = this.component.select_opening_hours,
      openingHours = this.component.select_opening_hours
        ? this.component.opening_hours
        : [],
      html = "",
      location = window.location,
      explodedPath = location.pathname.split("/"),
      parsedDate = moment(self.date, "DD-MM-YYYY");

    this.container.find("#slot-picker").html(html);
    let url = `${location.origin}/${
      explodedPath[1]
    }/api/calendars/${calendarID}/availabilities/${parsedDate.format(
      "YYYY-MM-DD"
    )}`;
    let queryParameters = [];
    if (self.meeting) {
      // Exclude saved meeting from unavailability
      queryParameters.push(`exclude=${self.meeting}`);
    }
    if (selectOpeningHours && openingHours) {
      // Select specific opening hours
      queryParameters.push(`opening_hours=${openingHours.join()}`);
    }

    if (queryParameters) {
      url = `${url}?${queryParameters.join("&")}`;
    }

    $.ajax(url, {
      dataType: "json", // type of response data
      beforeSend: function () {
        self.container
          .find("#slot-picker")
          .append(`<div class="col-12">${self.loaderTpl}</div>`);
      },
      success: function (data, status, xhr) {
        // success callback function
        $("#loader").remove();
        var countAllElmAvailables = data.filter(function (item) {
          return item.availability;
        }).length;

        if (countAllElmAvailables === 0) {
          self.container.find("#slot-picker")
            .html(`<div class="callout warning">
                            <div class="callout-title"><svg class="icon"><use xlink:href="/bootstrap-italia/dist/svg/sprite.svg#it-help-circle"></use></svg>${Translator.trans(
                              "warning",
                              {},
                              "messages",
                              self.$language
                            )}</div>
                            <p>${Translator.trans(
                              "calendar_formio.no_availability_error",
                              {},
                              "messages",
                              self.$language
                            )}</p>
                            </div>`);
        } else {
          $(data).each(function (index, element) {
            var cssClass = "available";
            var ariaDisabled = false;
            if (!element.availability) {
              cssClass = "disabled";
              ariaDisabled = true;
            }

            let key = `${element.start_time}-${element.end_time}`;
            if (key === self.slot) {
              cssClass = `${cssClass} active`;
            }
            let op_hour = element.opening_hour;
            html = html.concat(
              `<div class="col-6"><button type="button" data-slot="${key}" data-opening_hour="${op_hour}" class="btn btn-ora p-0 ${cssClass}" ${
                ariaDisabled ? 'tabindex="-1"' : ""
              } aria-disabled="${ariaDisabled}">${key}</button></div>`
            );
          });
          self.container
            .find("#slot-picker")
            .html(
              `<div class="col-12"><h6> ${Translator.trans(
                "calendar_formio.availability_hours",
                {},
                "messages",
                self.$language
              )} ${self.date}</h6></div>${html}`
            );

          $(".btn-ora.available").on("click", function (e) {
            e.preventDefault();
            $(".btn-ora.active").removeClass("active");
            $(this).addClass("active");
            self.slot = $(this).data("slot");
            self.opening_hour = $(this).data("opening_hour");

            self.createOrUpdateMeeting();
            self.updateValue();

            $("#date-picker-print").addClass("d-preview-calendar-none");
          });
        }
      },
      error: function (jqXhr, textStatus, errorMessage) {
        // error callback
        alert(
          `${Translator.trans(
            "calendar_formio.availability_error",
            {},
            "messages",
            self.$language
          )}`
        );
      },
      complete: function () {
        //Click available hour button only is visible for auto selection
        var btnHourActive = $(".btn-ora.available.active");
        if (btnHourActive.length > 0 && btnHourActive.is(":visible")) {
          btnHourActive.click();
        }
      },
    });
  }

  createOrUpdateMeeting() {
    let self = this,
      location = window.location,
      explodedPath = location.pathname.split("/");

    $.ajax(
      `${location.origin}/${explodedPath[1]}/${explodedPath[2]}/meetings/new-draft`,
      {
        method: "POST",
        data: {
          date: self.date,
          slot: self.slot,
          calendar: this.component.calendarId,
          opening_hour: self.opening_hour,
          meeting: self.meeting,
          first_available_date: self.first_available_date,
          first_available_start_time: self.first_available_start_time,
          first_available_end_time: self.first_available_end_time,
          first_availability_updated_at: self.first_availability_updated_at,
        },
        dataType: "json", // type of response data
        success: function (data, status, xhr) {
          // success callback function
          self.meeting = data["id"];
          self.meeting_expiration_time = moment(
            data["expiration_time"],
            "YYYY-MM-DD h:mm"
          );
          self.updateValue();
        },
        error: function (jqXhr, textStatus, errorMessage) {
          // error callback
          alert(
            `${Translator.trans(
              "calendar_formio.availability_error",
              {},
              "messages",
              self.$language
            )}`
          );
          // Reinitialize
          self.slot = null;
          self.meeting = null;
          self.meeting_expiration_time = null;
          self.updateValue();
          self.getDaySlots();
        },
        complete: function () {
          let slotText = self.slot
            ? ` ${Translator.trans(
                "calendar_formio.at_hours",
                {},
                "messages",
                self.$language
              )} ${self.slot}`
            : "";
          $("#date-picker-print").html(
            `<b>${Translator.trans(
              "calendar_formio.day_selected",
              {},
              "messages",
              self.$language
            )}: </b> ${self.date} ${slotText}`
          );
          if (self.meeting_expiration_time) {
            $("#draft-expiration").html(
              `<i>${Translator.trans(
                "calendar_formio.draft_expiration_text",
                {},
                "messages",
                self.$language
              )}</i>`
            );
            $("#draft-expiration-container").addClass("alert alert-info");
            if (!self.countdown) {
              self.countdown = true;
              CountDownTimer(
                self.meeting_expiration_time,
                "draft-expiration-countdown"
              );
            }
          }
        },
      }
    );
  }
}
