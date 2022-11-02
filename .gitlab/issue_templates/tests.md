Taggare questa issue con label "Test" e Milestone corrente.

## Test generali

Per i test che seguono fare riferimento al documento dei [Test Manuali](https://docs.google.com/spreadsheets/d/1vPZNYSWBDxgIM337GPWvF1oJQ1tFGdm0s8dhKjlZvTw/edit#gid=1226962439).

- [ ] Test PEO, creazione pratica e protocollazione
- [ ] Test PEO, richiesta integrazione
- [ ] Test prenotazione in [calendario a intervalli fissi](https://devsdc.opencontent.it/comune-di-bugliano/it/servizi/prenotazione-calendario-intervalli-fissi)
- [ ] Test prenotazione in [calendario intervalli liberi](https://devsdc.opencontent.it/comune-di-bugliano/it/servizi/prenotazione-calendario-intervalli-liberi)
- [ ] Test invio messaggi a Kafka:
    - testare che le pratiche create nei test precedenti siano state correttamente inviate a kafka
    - testare che in caso di errore di invio a kafka diretto, venga creata la scheduled action necessaria per l'invio successivo.
- [ ] Test importazione di un servizio dalla versione precedente

## Test su nuovi sviluppi

Ogni issue indicata di seguito ha dei casi di test o delle user stories da testare. Testare e spuntare la casella che segue quando tutti i test della issue sono positivi.

- [ ] #numero1 - breve descrizione
- [ ] #numero2 - breve descrizione

## Ambiente di dev

L'ambiente di dev che si può creare dal repository deve essere sempre funzionante: con un clone del repo e un `docker-compose up` si deve sempre ottenere un ambiente minimale ma funzionante.

- [ ] ci sono variabili da aggiungere al docker-compose.yml con un valore di default?
