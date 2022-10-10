export function CountDownTimer(date, id) {

  let end = new Date(date);
  const language = document.documentElement.lang.toString();

  let _second = 1000;
  let _minute = _second * 60;
  let _hour = _minute * 60;
  let _day = _hour * 24;
  let timer;

  function showRemaining() {
    let now = new Date();
    let distance = end - now;
    if (distance < 0) {
      clearInterval(timer);
      document.getElementById(id).innerHTML = ` ${Translator.trans('calendar_formio.time_expired', {}, 'messages', language)} `;
      return;
    }

    let days = Math.floor(distance / _day);
    let hours = Math.floor((distance % _day) / _hour);
    let minutes = Math.floor((distance % _hour) / _minute);
    let seconds = Math.floor((distance % _minute) / _second);


      if(days > 1){
        document.getElementById(id).innerHTML = days + ` ${Translator.trans('time.days', {}, 'messages', language)} `;
      }else if (days > 0){
        document.getElementById(id).innerHTML = days + ` ${Translator.trans('time.day', {}, 'messages', language)} `;
      }else{
        document.getElementById(id).innerHTML = ''
     }

    if(hours > 0) {
      if (hours > 1) {
        document.getElementById(id).innerHTML += hours + ` ${Translator.trans('time.hours', {}, 'messages', language)} `;
      }else{
        document.getElementById(id).innerHTML += hours + ` ${Translator.trans('time.hour', {}, 'messages', language)} `;
      }
    }

    if(minutes > 0) {
      if (minutes > 1) {
        document.getElementById(id).innerHTML += minutes + ` ${Translator.trans('time.minutes', {}, 'messages', language)} `;
      }else{
        document.getElementById(id).innerHTML += minutes + ` ${Translator.trans('time.minute', {}, 'messages', language)} `;
      }
    }

    if(seconds > 0) {
      if (seconds > 1) {
        document.getElementById(id).innerHTML += seconds + ` ${Translator.trans('time.seconds', {}, 'messages', language)}`;
      }else{
        document.getElementById(id).innerHTML += seconds + ` ${Translator.trans('time.second', {}, 'messages', language)}`;
      }
    }
  }

  timer = setInterval(showRemaining, 1000);
}
