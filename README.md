# La Stanza Del Cittadino

L’area personale del cittadino, progettata con il [Team Digitale](https://teamdigitale.governo.it) utilizzando lo [starter KIT per il sito di un comune](https://designers.italia.it/progetti/siti-web-comuni/)

## Descrizione

Un’area personale con cui il cittadino può inviare istanze (es. iscrizioni asilo nido), verificare lo stato delle pratiche, ricevere comunicazioni da parte dell’ente (messaggi, scadenze, ...) ed effettuare pagamenti. Progettata con lo starter KIT per il sito di un comune insieme al Team per la Trasformazione Digitale, permette di creare nuovi servizi digitali secondo un flusso definito nelle Linee Guida di Design e integrato con le piattaforme abilitanti. Si integra facilmente con le applicazioni presenti presso l’ente (interoperabile, separando back end e front end); il profilo del cittadino si arricchisce ad ogni interazione e riduce la necessità di chiedere più volte le stesse informazioni (once only). Una soluzione pratica per gestire il processo di trasformazione digitale in linea col Piano Triennale.
Nasce da un progetto condiviso con il Consorzio Comuni Trentini - ANCI Trentino, che accompagna i comuni nella revisione dei servizi al cittadino in un’ottica digitale (digital first)

## Funzionalità principali

- Creazione dei moduli da backend, grazie all'integrazione con [Form.IO](https://www.form.io/) un potente sistema di creazione di form dinamiche opensource
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
* [API](https://stanzadelcittadino.it/comune-di-ala/api/doc)
* [Demo](https://demosdc.opencontent.it/comune-di-ala)

## Struttura del progetto

Il software è realizzato da una componente principale sviluppata con [Symfony](https://symfony.com/) e da varie componenti accessorie, che sono
facilmente sostituibili in caso di necessità:

* symfony-app: costituisce l'interfaccia principale dell'applicativo, sia di front-end che di backend (utilizza postgresql come layer di persistenza) ed e' il software presente in questo repository
* apache+shibboleth: il webserver apache distribuito nel presente repository contiene anche shibboleth per l'autenticazione mediante SPID, questo componente può essere sostituito facilmente da un altro sistema utilizzabile per l'autenticazione.
* [wkhtmltopdf](https://hub.docker.com/r/traumfewo/docker-wkhtmltopdf-aas): questo microservizio è necessario per creazione di PDF, il formato con cui vengono creati e protocollati i documenti durante l'esecuzione delle pratiche del cittadino,
* mypay-proxy e pitre-proxy: due proxy che espongono una semplice interfaccia ReST verso l'applicativo e inoltrano le chiamate ai servizi MyPAY e PiTRE mediante protocollo SOAP; questi due proxy sono in fase di rilascio, ma già operativi in produzione.

## Requisiti

Lo stack applicativo è composto da:

* [Symfony 3.4](https://symfony.com/what-is-symfony) per lo sviluppo della componente principale che richiede quindi PHP 7.2.x
* [Express](https://expressjs.com) per lo sviluppo del proxy MyPay, che richiede NodeJS
* Java per lo sviluppo del proxy MyPay, che richiede quindi OpenJDK 8.x
* PostgreSQL 10.x come database principale

La distribuzione di questi componenti avviene mediante immagini [Docker](https://it.wikipedia.org/wiki/Docker),
non si tratta di un requisito, i singoli applicativi possono essere installati anche in assenza di esso, su un
qualunque server Linux.

## Installazione

I `Dockerfile` presenti nel repository principale e in quelli secondari possono essere utilizzati
per dedurre quali sono i requisiti a livello di sistema operativo di ogni componente, per questo
motivo non viene fornita documentazione in merito, ma in caso di necessità è possibile aprire una
issue per richiedere chiarimenti. Si sconsiglia comunque il setup senza docker perché rende molto
oneroso gli aggiornamenti.

## Utilizzo

Oltre al cittadino che accede mediante SPID, posso accedere all'applicativo

- i professionisti che presentano pratiche all'Ente _per conto_ del cittadino (es: pratiche edilizie)
- i gestori delle pratiche, che ricevono notifica delle pratiche da elaborare e possono accettarle
  o rigettarle, allegando messagi o documenti
- gli amministratori dell'Ente che possono impostare le informazioni generali dell'ente, configurare
  i dati relativi al protocollo usato e al sistema di pagamento. Inoltre gli amministratori possono
  creare a pubblicare servizi, progettando i moduli online che i cittadini dovranno compilare.
  gli amministratori hanno infine accesso anche ai log di sicurezza che consentono la verifica dei
  login effettuati da tutti gli utenti e del loro IP di provenienza

## Accesso alle API

Per l'accesso alle API è necessario effettuare l'autenticazione come segue

1. si esegue una `POST` all'endpoint: `https://www.stanzadelcittadino.it/<ENTE>/api/auth` con body

```json
{
  "username": "<nome_utente>",
  "password": "<segreto>"
}
```

2. si ottiene come risposta un token JWT che si inserisce nelle successive chiamate come header
   `Authorization: Bearer <TOKEN>`

Ad esempio, mediante il client da linea di comando [httpie](https://httpie.org/)
e l'utility [jq](https://stedolan.github.io/jq/):

```bash
$ export TOKEN=$(http post https://www.stanzadelcittadino.it/comune-di-ala/api/auth username=XXXXX password=XXXX | jq -raw-output .token)

$ http get https://www.stanzadelcittadino.it/comune-di-ala/api/services "Authorization: Bearer $TOKEN"
```

## Versionamento delle API
È possibile specificare la versione delle Api che si vanno ad interrogare, questo è possibile in 2 modi differenti:
* tramite parametro `version` da passare in get
* tramite parametro `X-Accept-Version` da specificare nell' header della richiesta

La versione di default è per retrocompatibilità la 1.x

Dalla versione 2 le chiavi dei valori del campo data delle `applications` non sono più restituite
come un insieme di strighe piatto separato dal punto (.) ma come campo json con le chiavi esplose.

## Build system, CI e test automatici

Mediante il servizio di CI di GitLab vengono preparate le immagini docker di ogni componente.
Durante la build vengono inoltre effettuati:

* il test di sicurezza dinamica Owasp, mediante il tool `ZAP`
* il test della validità del `publiccode.yml` del repository

## Deploy

Dal repository stesso è possibile fare il deploy di un *ambiente demo* del servizio. Per farlo
è sufficiente utilizzare questo [docker-compose.yml](https://gitlab.com/opencontent/stanzadelcittadino/blob/docker-demo/docker-compose.yml)
del progetto e configurare le variabili
necessarie mediante un file `.env` (viene fornito un file `env.dist` come riferimento.

E' possibile prelevare il singolo file dal repository e avviare i servizi a partire dalle immagini
docker rilasciate, mendiante il comando:

    docker-compose up -d

In alternativa e' possibile fare il deploy di un *ambiente di sviluppo*, facendo il
clone del repository ed effettuando la build localmente al proprio computer:

    git clone git@gitlab.com:opencontent/stanzadelcittadino.git
    git checkout docker-demo
    docker-compose up --build -d

Al termine della build e dell'inizializzazione del database sarà possibile visitare l'indirizzo:

http://stanzadelcittadino.localtest.me

e si dovrebbe vedere una pagina web del tutto simile a https://demosdc.opencontent.it/

## Abilitazione features

Mediante specifiche variabili d'ambiente è possibile abilitare o disabilitare features.

    FEATURE_NOME=true

 Feature disponibili:
   - Nuovo Browser outdated, si abilita tramite la variabile d'ambiente `FEATURE_NEW_OUTDATED_BROWSER`:
   sostituisce il vecchio plugin browser outdated per la verifica di browser obsoleti.
   Migliora la scelta di browser compatibili tramite la versione minima configurata.
   Supporta browser mobile con callback specifiche per Web - Android - IOS


   - Nuova interfaccia di dettaglio pratica per il cittadino, si abilita tramite la variabile d'ambiente `FEATURE_APPLICATION_DETAIL`:
   sostituisce l'interfaccia di dettaglio ad uso del cittadino, migliorandone la user experience.
   Consente inoltre lo scambio di messaggi tra operatore e cittadino.

## Restrizioni di accesso ai servizi

Mediante la variabile d'ambiente `BROWSERS_RESTRICTIONS` è possibile limitare l'accesso di una specifica versione di un browser ai servizi.

La sintassi della variabile è cosi definita:  `nome browser,operatore logico, versione|nome browser,operatore logico, versione`

Es. `Maxthon,<,4.0.5`

Il componente utilizzato è https://github.com/WhichBrowser/Parser-PHP al quale si rimanda per uteriori informazioni.



## Project status

Il progetto e' stabile e usato in produzione da alcune amministrazioni pubbliche.

Sebbene lo sviluppo sia stato orientato fin dall'inizio alla modularità e alla configurabilità
delle feature, molti aspetti di dettaglio e le interfacce di comunicazione con i proxy non sono
abbastanza maturi per essere sostituiti con una semplice configurazione, in alcuni casi
può rendersi necessario la modifica del codice sorgente. Sono quindi graditi i contributi
al codice per il supporto di altri sistemi di protocollo o di pagamento, purché siano fatte
in modo modulare e configurabile, in modo da non pregiudicare la flessibilità del progetto.

Altri limiti:
- la gestione degli operatori è molto limitata al momento e non consente grande flessibilità
  o l'utilizzo di gruppi di operatori per la gestione delle pratiche
- il security log è minimale e intellegibile solo da personale tecnico
- le API coprono attualmente circa il 50% delle funzionalità dell'applicativo
- il test OWASP condotto durante la build è effettuato sull'ambiente di sviluppo e non su un ambiente creato
  appositamente con la versione corrente

## Copyright

Copyright (C) 2016-2020  Opencontent SCARL

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

