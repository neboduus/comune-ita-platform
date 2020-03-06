# Form.io

Linee guida da seguire per il corretto funzionamento delle form create tramite form.io

## Componenti

### Select popolato via API

* [`SHOULD`] Quando si inserisce un componente di tipo **select** è buona norma popolare i campi `Data Path` (se presente)
 e `Value Property` in modo da popolare anche il campo `value` della select.

### Nested form

* [`MUST`] Quando si inserisce un componente di tipo **Nested Form** è necessario ricordarsi di togliere la spunta
al checkbox **Save as reference** presente nel tab **Form**

* [`MUST`] Non inserire componenti di tipo **Nested Form** all'interno di elementi di layout per il corretto popolamento
automatico dei campi

## Integrazioni

### Servizi a sottoscrizione

Per agganciare un servizio form.io al backoffice dei servizi a sottoscrizione la form dovrà contenere almeno i seguenti campi:

* Subform `applicant`: dati anagrafici del richiedente
* Campo `code`: codice del servizio a sottoscrizione
