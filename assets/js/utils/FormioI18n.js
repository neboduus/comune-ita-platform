
class FormIoI18n {

  static languages() {
    return  {
      en: {},
      de: {},
      it: {
        complete: 'Invio Completato',
        error: 'Per favore correggi i seguenti errori prima di inviare.',
        array: '{{field}} deve essere una lista',
        array_nonempty: '{{field}} deve contenere almeno un valore',
        nonarray: '{{field}} deve essere una lista',
        select: '{{field}} contiene una scelta non valida',
        pattern: '{{field}} non corrisponde al pattern {{pattern}}',
        minLength: '{{field}} deve contenere almeno {{length}} caratteri.',
        maxLength: '{{field}} non può contenere più di {{length}} caratteri.',
        minWords: '{{field}} deve contenere almeno {{length}} parole.',
        maxWords: '{{field}} non può contenere più di {{length}} parole.',
        min: '{{field}} non può essere inferiore a {{min}}.',
        max: '{{field}} non può essere superiore a {{max}}.',
        maxDate: '{{field}} non può essere una data successiva a {{- maxDate}}',
        minDate: '{{field}} non può essere una data precedente a {{- minDate}}',
        maxYear: '{{field}} non può contenere un anno superiore a {{maxYear}}',
        minYear: '{{field}} non può contenere un anno inferiore a {{minYear}}',
        invalid_url: '{{field}} deve essere un indirizzo valido.',
        invalid_regex: '{{field}} non corrisponde al pattern {{regex}}.',
        invalid_date: '{{field}} non è una data valisa.',
        invalid_day: '{{field}} non è un giorno valido.',
        mask: '{{field}} non corrisponde alla maschera.',
        month: 'Mese',
        day: 'Giorno',
        year: 'Anno',
        january: 'Gennaio',
        february: 'Febbraio',
        march: 'Marzo',
        april: 'Aprile',
        may: 'Maggio',
        june: 'Giugno',
        july: 'Luglio',
        august: 'Agosto',
        september: 'Settembre',
        october: 'Ottobre',
        november: 'Novembre',
        december: 'Dicembre',
        confirmCancel: 'Sei sicuro di voler annullare?',
        time: 'Tempo non valido',
        Month: 'Mese',
        Day: 'Giorno',
        Year: 'Anno',
        January: 'Gennaio',
        February: 'Febbraio',
        March: 'Marzo',
        April: 'Aprile',
        May: 'Maggio',
        June: 'Giugno',
        July: 'Luglio',
        August: 'Agosto',
        September: 'Settembre',
        October: 'Ottobre',
        November: 'Novembre',
        December: 'Dicembre',
        next: 'Successivo',
        previous: 'Precedente',
        cancel: 'Annulla',
        submit: 'Successivo',
        required: 'Campo richiesto',
        invalid_email: "Indirizzo email non valido",
        "Add Another": "Aggiungi",
        "No storage has been set for this field. File uploads are disabled until storage is set up.": "Non è possibile utilizzare il caricamento di documenti a causa di un errore di configurazione (manca una destinazione dei documenti caricati nella definizione del campo",
        "Drop files to attach,": "Trascina qui il file da caricare,",
        "or": "oppure",
        "browse": 'cercalo nel tuo PC',
        "File Name": 'Nome del file',
        "Size": "Dimensione",
        "Type": "Tipo",
        "Field Set": "Elementi",
        "Type to search": "Digita per cercare",
        "File is too big; it must be at most {{ size }}": "La dimensione del file selezionato supera la dimensione massima consentita di {{ size }}.",
        "File is too small; it must be at least {{ size }}": "La dimensione del file selezionato è inferiore alla dimensione minima consentita di {{ size }}.",
        "File is the wrong type; it must be {{ pattern }}": "Il formato del file selezionato non è corretto: deve essere {{ pattern }}",
        "File with the same name is already uploaded": "È già stato caricato un file con lo stesso nome"
      }
    }
  }
}

export default FormIoI18n;
