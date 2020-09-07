let outdatedBrowserRework = require("outdated-browser-rework");

$(document).ready(function () {
  outdatedBrowserRework({
    browserSupport: {
      Chrome: 57, // Includes Chrome for mobile devices
      Edge: 39,
      Safari: 10,
      "Mobile Safari": 10,
      Firefox: 50,
      Opera: 50,
      Vivaldi: 1,
      Yandex: 10,
      IE: false
    },
    fullscreen:true,
    messages: {
      it: {
        outOfDate: "Il tuo browser è obsoleto!",
        unsupported: "Aggiorna il tuo browser per poter accedere al servizio e compilare correttamente il form",
        update: {
          web: "<p style='font-size: 18px'>Aggiorna il tuo browser per poter accedere al servizio e compilare correttamente il form</p> <p style=\"font-size: 20px;line-height: 1;text-transform: none;margin-top: 25px\">\n" +
            "        Si raccomanda l'utilizzo di Firefox o Chrome\n" +
            "      </p>",
          googlePlay: "Installa Chrome da Google Play",
          appStore: "Aggiorna il browser dall\' Apple Store"
        },
        url: "https://bestvpn.org/outdatedbrowser/en",
        callToAction: "Aggiorna",
        close: 'Chiudi'
      }
    },
    backgroundColor:'#3478bd',
    requireChromeOnAndroid: false,
    isUnknownBrowserOK: false
  });
});
