# La Stanza Del Cittadino

![La stanza del Cittadino - Area Personale][sdc1]

L’area personale del cittadino, progettata con il [Team Digitale](https://teamdigitale.governo.it) utilizzando lo [starter KIT per il sito di un comune](https://designers.italia.it/progetti/siti-web-comuni/)

## Descrizione

Un’area personale con cui il cittadino può inviare istanze (es. iscrizioni asilo nido), verificare lo stato delle pratiche, ricevere comunicazioni da parte dell’ente (messaggi, scadenze, ...) ed effettuare pagamenti. Progettata con lo starter KIT per il sito di un comune insieme al Team per la Trasformazione Digitale, permette di creare nuovi servizi digitali secondo un flusso definito nelle Linee Guida di Design e integrato con le piattaforme abilitanti. Si integra facilmente con le applicazioni presenti presso l’ente (interoperabile, separando back end e front end); il profilo del cittadino si arricchisce ad ogni interazione e riduce la necessità di chiedere più volte le stesse informazioni (once only). Una soluzione pratica per gestire il processo di trasformazione digitale in linea col Piano Triennale.
Nasce da un progetto condiviso con il Consorzio Comuni Trentini - ANCI Trentino, che accompagna i comuni nella revisione dei servizi al cittadino in un’ottica digitale (digital first)

## Funzionalità principali

- Interfaccia responsive (Bootstrap Italia), conforme alle [Linee Guida di design per i servizi web della PA](https://designers.italia.it/guide/)
- Autenticazione con [SPID](https://www.spid.gov.it/)
- Integrazione con [PagoPA](https://teamdigitale.governo.it/it/projects/pagamenti-digitali.htm) attraverso MyPAY
- Meccanismo di compilazione on-line dei moduli a step
- Gestione completa dell’iter di un’istanza: invio, presa in carico, risposta al cittadino, richiesta integrazioni
- Controllo automatico della validità di firme digitali e formati dei file, ai fini della conservazione sostitutiva
- Generazione automatica delle ricevuta di invio dell’istanza, in formato pdf
- Integrazione con il [sito comunale Comunweb](https://developers.italia.it/it/software/c_a116-opencontent-opencity): prossime scadenze e descrizione dei servizi
- Integrazione con alcuni dei sistemi di protocollazione più diffusi presso i comuni (tra cui, [PiTre](https://www.pi3.it))
- Integrazione con Shibboleth (SAML 2.0)


## Altri riferimenti

* [Manuale d'uso](https://manuale-stanza-del-cittadino.readthedocs.io/)
* [API](https://documenter.getpostman.com/view/7046499/S17wM5XS)
* [Demo](https://devsdc.opencontent.it/comune-di-ala)

## Screenshots

![sdc2]
![sdc3]
![sdc4]
![sdc5]

## Struttura del progetto

Il software è composto da una componente principale sviluppata con Symfony e da varie componenti accessorie, che sono 
facilmente sostituibili in caso di necessità:

* symfony-app: costituisce l'interfaccia principale dell'applicativo, sia di front-end che di backend (utilizza postgresql come layer di persistenza) ed e' il software presente in questo repository
* apache+shibboleth: il webserver apache distribuito nel presente repository contiene anche shibboleth per l'autenticazione mediante SPID, questo componente può essere sostituito facilmente da un altro sistema utilizzabile per l'autenticazione.
* [wkhtmltopdf](https://hub.docker.com/r/traumfewo/docker-wkhtmltopdf-aas): questo microservizio è necessario per creazione di PDF, il formato con cui vengono creati e protocollati i documenti durante l'esecuzione delle pratiche del cittadino,
* mypay-proxy e pitre-proxy: due semplice proxy che espongono una semplice interfaccia ReST verso l'applicativo e inoltrano le chiamate ai servizi MyPAY e PiTRE mediante protocollo SOAP; questi due proxy sono in fase di rilascio

## Requisiti

Lo stack applicativo è composto da:
  
* [Symfony 3.4](https://symfony.com/what-is-symfony) per lo sviluppo della componente principale che richiede quindi PHP 7.2.x
* [Express](https://expressjs.com) per lo sviluppo del proxy MyPay, che richiede NodeJS
* Java per lo sviluppo del proxy MyPay, che richiede quindi OpenJDK 8.x
* PostgreSQL 10.x come database principale

La distribuzione di questi componenti avviene mediante immagini [Docker](https://it.wikipedia.org/wiki/Docker),  
non si tratta di un requisito, i singoli applicativi possono essere installati anche in assenza
di esso, su un qualunque server Linux.

## Installazione

I `Dockerfile` presenti nel repository principale e in quelli secondari possono essere utilizzati
per dedurre quali sono i requisiti a livello di sistema operativo di ogni componente, per questo
motivo non viene fornita documentazione in merito, ma in caso di necessità è possibile aprire una
issue per richiedere chiarimenti.

## Build system, CI e test automatici

Mediante il servizio di CI di GitLab vengono preparate le immagini docker di ogni componente.
Durante la build vengono inoltre effettuati:

* il test di sicurezza dinamica Owasp, mediante il tool `ZAP`
* il test della validità del `publiccode.yml` del repository

## Deploy

Dal repository stesso è possibile fare il deploy di un *ambiente demo* del servizio. Per farlo
è sufficiente utilizzare il docker-compose.yml del progetto e configurare le variabili
necesasarie mediante un file `.env` (viene fornito un file `env.dist` come riferimento.

E' possibile prelevare il singolo file dal repository e avviare i servizi a partire dalle immagini
docker rilasciate, mendiante il comando:

    docker-compose up -d

In alternativa e' possibile fare il deploy di un *ambiente di sviluppo*, facendo il
clone del repository ed effettuando la build localmente al proprio computer:

    docker-compose up --build -d


## Project status 

Il progetto e' stabile e usato in produzione da alcune amministrazioni pubbliche.

Sebbene lo sviluppo sia stato orientato fin dall'inizio alla modularità e alla configurabilità
delle feature, molti aspetti di dettaglio e le interfacce di comunicazione con i proxy non sono
abbastanza maturi per essere sostituiti con una semplice configurazione, in alcuni casi 
pu' rendersi necessario la modifica del codice sorgente. Sono quindi graditi i contributi
al codice per il supporto di altri sistemi di protocollo o di pagamento, purché siano fatte
in modo modulare e configurabile, in modo da non pregiudicare la flessibilità del progetto.

Altri limiti:
- i servizi sono codificati a codice mediante classi PHP, stiamo gia' progettando
  l'estensione del progetto con un sistema piu' generico, che consenta la creazione di servizi
  da una interfaccia di backend
- la gestione degli operatori e' poco flessibile e non esiste un backend per la loro creazione: 
  al momento la loro gestione e' effettuata mediante la CLI di symfony: è in corso la 
  sperimentazione di AWS Cognito per la gestione degli operatori.

## Copyright

Copyright (C) 2016-2019  Opencontent SCARL

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

[sdc1]: images/sdc001.jpeg "pagina principale dell'area personale"
[sdc2]: images/sdc002.jpeg "elenco dei servizi"
[sdc3]: images/sdc003.jpeg "esempio di pratica compilata online"
[sdc4]: images/sdc004.jpeg "esempio di pratica inviata all'ente"
[sdc5]: images/sdc005.jpeg "elenco delle pratiche sul backend dell'operatore"

