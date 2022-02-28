# Come aggiungere un servizio

Per aggiungere un nuovo servizio servono vari step, alcuni di configurazione, altri di intervento sul codice

## Presupposti
La lista dei campi necessari al servizio è pronta, assieme ai relativi testi di aiuto
 
## Procedura
 1. Sul foglio google caricare i testi del servizio in oggetto
 
 2. Da console richiamare il comando `bin/console ocsdc:crea-servizio` che vuole i seguenti argomenti:
   * slug del servizio (sarà usato anche per le rotte)
   * Il nome del servizio (testo libero)
   * handler
   * area
   * descrizione
   * istruzioni
   * L'FQCN della classe persistita che rappresenta il servizio (quella in /Entity/...)
   * Il nome del servizio che rappresenta il flow   * 
   * Il nome del servizio che rappresenta il flow operatore
 
 3. fatto questo bisogna creare la classe che rappresenta il servizio. Questa classe deve estendere 
 `AppBundle/Entity/Pratica`, deve essere marcata come `@Entity` e deve mappare i campi necessari.
 Dei campi presenti nella classe base non va fatto override
  
 * Una volta finito di fare la classe nuova va lanciato `bin/console doctrine:migrations:diff` che genererà
 la migrazione. Questa va controllata e se necessario modificata in modo che possa essere eseguita su un db popolato
 il caso tipico è che venga aggiunto un campo not null e che quindi vada definita la strategia per popolare
 il campo nelle righe già presenti
 
 * TODO: documentazione per lo script di importazione/aggiornamento
 
 * Lo step successivo è la definizione del servizio del flow
Va creata una classe prendendo come spunto quelle già esistenti.
In `AppBundle/Form` creare una cartella col nome della classe del servizio, dentro creare una classe 
`NomeServizioFlow` che estenda da `AppBundle/Form/Base/PraticaFlow`
Nel flusso vanno definiti gli step necessari per la pratica. Prendere spunto dai flussi già definiti
 

