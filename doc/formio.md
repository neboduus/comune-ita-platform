# Form.io

Linee guida da seguire per il corretto funzionamento delle form create tramite form.io

## Pagamenti
Per poter inserire un pagamneto in un form va inserito un campo di tipo text e rispettare le seguenti regole:
* [`MUST`] Il name del campo deve essere `payment_amount`

## Componenti

### Wizard
* [`MUST`] Verificare nelle impostazioni delle pagine che non sia spuntato il campo Collapsible

### Select popolato via API

* [`SHOULD`] Quando si inserisce un componente di tipo **select** è buona norma popolare i campi `Data Path` (se presente)
 e `Value Property` in modo da popolare anche il campo `value` della select.

### Nested form

* [`MUST`] Quando si inserisce un componente di tipo **Nested Form** è necessario ricordarsi di togliere la spunta
al checkbox **Save as reference** presente nel tab **Form**

* [`MUST`] Non inserire componenti di tipo **Nested Form** all'interno di elementi di layout per il corretto popolamento
automatico dei campi

### Uploade dei file
Il componente per l'upload del file è utilizzabile cone le seguenti impostazioni:
*  Storage va impostato a `Url`
*  Va specificata un url così composto `https://{host}/{instance}/allegati`
   Es.`http://stanzadelcittadino.it/comune-di-bugliano/allegati`

### Bilancio
Il componente bilancio serve per poter dividere l'ammontare dei pagamenti in sottovoci.
Per ogni riga di bilancio possone essere specificati:
* Codice del capitolo [`MUST`]
* Codide dell'ufficio [`MUST`]
* Codice di accertamento
* Importo [`MUST`]

Il componete di bilancio è completamente trasparente all'utente finale, deve essere quindi impostato come `hidden` e deve rispettare le seguneti regole:
* [`MUST`] Il name del campo deve essere `payment_financial_report`
* [`MUST`] La somma degli importi della componente di bilancio deve essere uguale al `payment_amount` specificato

Nel caso di componeti di bilancio complesse dipendenti da altri campi del form va inserito il valore delle compomenti di
 bilancio dinamicamente tramite funzione js.

Nel `Calculated value` del campo Bilancio va inserita una funzione come l'esempio sottostante, tale funzione viene attivata al cambiamento (redraw)
del campo collegato (tipologia).

Es.
```
if (data.tipologia === 'value') {
 	value = [
 		{codAccertamento: "", codCapitolo: "cap1", codUfficio: "uff1", importo: "2"},
 		{codAccertamento: "", codCapitolo: "cap2", codUfficio: "uff2", importo: "8"}
 	];
 } else {
 	value = [
 		{codAccertamento: "", codCapitolo: "cap1", codUfficio: "uff1", importo: "5"},
 		{codAccertamento: "", codCapitolo: "cap2", codUfficio: "uff2", importo: "5"}
 	];
 }
```
I


## Integrazioni

### Servizi a sottoscrizione

Per agganciare un servizio form.io al backoffice dei servizi a sottoscrizione la form dovrà contenere almeno i seguenti campi:

* Subform `applicant`: dati anagrafici del richiedente
* Campo `code`: codice del servizio a sottoscrizione

Oppure nel caso di iscrizione per conto di altri:

* Subform `subscriber`: dati anagrafici del richiedente
* Campo `code`: codice del servizio a sottoscrizione

### Prenotazione appuntamenti

Per aggangiare un servizio form.io al backoffice della prenotazione appuntamenti la form dovrà contenere almeno i seguenti campi:

*  Subform `applicant`: dati anagrafici del richiedente: Non sono richiesti tutti i campi presenti nel componente/subform
`anagrafica`, ma è sufficiente utilizzare la subform `anagrafica-lite` (`name`, `surname`, `email_address`, `phone_number` e `fiscal_code`)
* Campo `calendar`: il calendario per la scelta del giorno e dello slot disponibile. La compilazione di questo campo restituirà una stringa del tipo
`d/m/Y @ H:i-H:i (calendarId)`
* Campo `user_message`: il messaggio descrittivo dell'utente che prenota l'appuntamento

Si consiglia di utilizzare il form `Anagrafica-lite` a tale scopo.
