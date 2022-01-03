/* global */

const Locales = {
  map: {
    it: {
      drawLocales: {
        draw: {
          toolbar: {
            actions: {
              title: 'Annulla disegno',
              text: 'Annulla',
            },
            finish: {
              title: 'Concludi disegno',
              text: 'Concludi',
            },
            undo: {
              title: 'Elimina ultimo punto disegnato',
              text: 'Elimina ultimo punto',
            },
            buttons: {
              polyline: 'Disegna polilinea',
              polygon: 'Disegna poligono',
              rectangle: 'Disegna rettangolo',
              circle: 'Disegna cerchio',
              marker: 'Aggiungi marker',
              circlemarker: 'Aggiungi marker circolare',
            },
          },
          handlers: {
            circle: {
              tooltip: {
                start: 'Fai click e trascina per disegnare il cerchio.',
              },
              radius: 'raggio',
            },
            circlemarker: {
              tooltip: {
                start: 'Fai click sulla mappa per posizionare il marker.',
              },
            },
            marker: {
              tooltip: {
                start: 'Fai click sulla mappa per posizionare il marker.',
              },
            },
            polygon: {
              tooltip: {
                start: 'Fai click per disegnare la forma.',
                cont: 'Fai click continuare la forma.',
                end: 'Fai click sul primo punto per chiudere la forma.',
              },
            },
            polyline: {
              error: '<strong>Error:</strong> shape edges cannot cross!',
              tooltip: {
                start: 'Fai click per disegnare la linea.',
                cont: 'Fai click continuare la linea.',
                end: "Fai click sull'ultimo punto per chiudere la linea.",
              },
            },
            rectangle: {
              tooltip: {
                start: 'Fai click e trascina per disegnare il rettangolo.',
              },
            },
            simpleshape: {
              tooltip: {
                end: 'Rilascia per completare la forma.',
              },
            },
          },
        },
        edit: {
          toolbar: {
            actions: {
              save: {
                title: 'Salva cambiamenti',
                text: 'Salva',
              },
              cancel: {
                title: 'Annulla modifiche',
                text: 'Annulla',
              },
              clearAll: {
                title: 'Cancella tutti i livelli',
                text: 'Pulisci',
              },
            },
            buttons: {
              edit: 'Modifica livelli',
              editDisabled: 'Nessun livello da modificare',
              remove: 'Elimina livelli',
              removeDisabled: 'Nessun livello da eliminare',
            },
          },
          handlers: {
            edit: {
              tooltip: {
                text: "Trascina le maniglie o i marker per modificare l'elemento.",
                subtext: 'Fai click su "annulla" per annullare le modifiche.',
              },
            },
            remove: {
              tooltip: {
                text: 'Fai click su una elemento per rimuoverlo.',
              },
            },
          },
        },
      },
      locationMarker: {
        popupContent: 'Trascina il marker sulla mappa per identificare il luogo esatto.',
      },
      locate: {
        strings: {
          title: 'Visualizza la tua posizione',
          metersUnit: 'm',
          popup: 'La tua posizione',
          outsideMapBoundsMsg: 'Hmm, la tua posizione è al di fuori dei confini della mappa..',
        },
      },
      fullscreen: {
        title: {
          false: 'Attiva modalità a tutto schermo',
          true: 'Disattiva modalità a tutto schermo',
        },
      },
    },
  },
};

export default Locales;
