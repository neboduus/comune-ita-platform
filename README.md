# La Stanza Del Cittadino

La Stanza del Cittadino è una piattaforma per creare e gestire i servizi digitali e l'area personale del cittadino, progettati secondo i KIT di [Designers Italia](https://designers.italia.it/kit/), il modello di [sito e servizi digitali dei Comuni italiani](https://designers.italia.it/modello/comuni/) e pubblicata su [Developers Italia](https://developers.italia.it/it/software/opencontent-stanzadelcittadino-cf016d).

Supporta il *multilinguismo*, per favorire l'effettivo accesso ai servizi digitali da parte dei cittadini europei, ed altre raccomandazioni indicate nell'[eGovernment Benchmark Method Paper 2020-2023](https://op.europa.eu/it/publication-detail/-/publication/333fe21f-4372-11ec-89db-01aa75ed71a1).

E' una delle componenti della soluzione [OpenCity Italia](https://opencityitalia.it/), che insieme al sito web istituzionale è disponibile anche in versione [SaaS in cloud qualificato AgID](https://catalogocloud.agid.gov.it/service/525).





## Descrizione

Un’area personale con cui il cittadino può inviare istanze (es. iscrizioni asilo nido), verificare lo stato delle pratiche, ricevere comunicazioni da parte dell’ente (messaggi, scadenze, ...) ed effettuare pagamenti. Progettata con lo starter KIT per il sito di un comune insieme al Team per la Trasformazione Digitale, permette di creare nuovi servizi digitali secondo un flusso definito nelle Linee Guida di Design e integrato con le piattaforme abilitanti. Si integra facilmente con le applicazioni presenti presso l’ente (interoperabile, separando back end e front end); il profilo del cittadino si arricchisce ad ogni interazione e riduce la necessità di chiedere più volte le stesse informazioni (once only). Una soluzione pratica per gestire il processo di trasformazione digitale in linea col Piano Triennale.
Nasce da un progetto condiviso con il Consorzio Comuni Trentini - ANCI Trentino, che accompagna i comuni nella revisione dei servizi al cittadino in un’ottica digitale (digital first)

## Funzionalità principali

- Creazione dei moduli da backend, grazie all'integrazione con [Form.IO](https://www.form.io/) un potente sistema di creazione di form dinamiche opensource
- Interfaccia responsive (Bootstrap Italia), conforme alle [Linee Guida di design per i servizi web della PA](https://designers.italia.it/guide/)
- Autenticazione con [SPID](https://www.spid.gov.it/)
- Integrazione con [PagoPA](https://teamdigitale.governo.it/it/projects/pagamenti-digitali.htm) attraverso MyPAY, PiemontePay, E-fil, IRIS
- Meccanismo di compilazione on-line dei moduli a step
- Gestione completa dell’iter di un’istanza: invio, presa in carico, risposta al cittadino, richiesta integrazioni
- Controllo automatico della validità di firme digitali e formati dei file, ai fini della conservazione sostitutiva
- Generazione automatica delle ricevuta di invio dell’istanza, in formato PDF
- Integrazione con il [sito comunale Comunweb](https://developers.italia.it/it/software/c_a116-opencontent-opencity): prossime scadenze e descrizione dei servizi
- Integrazione con alcuni dei sistemi di protocollazione più diffusi presso i comuni (tra cui, [PiTre](https://www.pi3.it), Infor/Municipia, Sicraweb, Datagraph, Civilia, Halley)
- Integrazione con Shibboleth (SAML 2.0)


## Altri riferimenti

* [Manuale d'uso](https://link.opencontent.it/sdc-manuale)
* [API](https://link.opencontent.it/sdc-apidoc)
* [Demo](https://link.opencontent.it/sdc-demo)

## Struttura del progetto

Il software è realizzato da una componente principale sviluppata con
[Symfony](https://symfony.com/) e da varie componenti accessorie, che sono
facilmente sostituibili in caso di necessità:

* [symfony-core](https://gitlab.com/opencontent/stanza-del-cittadino/core): costituisce l'interfaccia principale dell'applicativo, sia di front-end che di backend (utilizza postgresql come layer di persistenza) ed e' il software presente in questo repository
* [form-server](https://gitlab.com/opencontent/stanza-del-cittadino/form-server): tutti i moduli presentati ai cittadini sono realizzati mediante Form.IO di cui questo componente è la parte server. Ci siamo basati sulla versione opensource del prodotto per creare una nostra versione del server, compatibile 1:1 con la versione ufficiale. Abbiamo esteso la versione opensource con funzionalità specifiche necessarie al nostro prodotto, in particolare abbiamo lavorato sul versionamento dei moduli e sul supporto multilingua.
* apache+shibboleth: il webserver apache distribuito nel presente repository contiene anche shibboleth per l'autenticazione mediante SPID, questo componente può essere sostituito facilmente da un altro sistema utilizzabile per l'autenticazione. Per una demo funzionante non è necessario attivare anche apache, l'autenticazione può essere efficacemente simulato.
* [gotemberg](https://gotenberg.dev/): questo microservizio è necessario per creazione di PDF, il formato con cui vengono creati e protocollati i documenti durante l'esecuzione delle pratiche del cittadino,
* per l'integrazione con sistemi di protocollo, di pagamento e di autenticazione ci sono poi una serie di componenti specifici che vengono attivati a seconda del territorio a cui appartiene l'Ente.
  * [mypay-proxy](https://gitlab.com/opencontent/mypay-wrapper) e [pitre-proxy](https://gitlab.com/opencontent/stanza-del-cittadino/pitre-soap-proxy): due proxy che espongono una semplice interfaccia ReST verso l'applicativo e inoltrano le chiamate ai servizi MyPAY e PiTRE mediante protocollo SOAP; questi due proxy sono in fase di rilascio
  * [piemontepay](https://gitlab.com/opencontent/stanza-del-cittadino/piemontepay) per l'integrazione con l'omonimo sistema di pagamento del Piemonte
  * [traefik-forward-auth](https://github.com/thomseddon/traefik-forward-auth) usato per la comodissima integrazione con SPID/CIE/Eidas in Toscana, grazie al sistema [ARPA](https://www.regione.toscana.it/arpa)
  * CAS Autentication

Altre dipendenze:
  * il database principale utilizzato è *PostgreSQL*, versione 10 (ma supporta fino alla 12 senza problemi). E' compatibile anche
    con il servizio [Aurora Serverless v1 di AWS](https://aws.amazon.com/it/rds/aurora/serverless/) che usiamo con soddisfazione
    in produzione sulla nostra installazione principale.
  * secondariamente sono usati anche *MongoDB* (persistenza principale di form-server) e *Redis* (usato per la condivisione delle
    sessioni PHP, per la cache di diversi componenti.

La distribuzione di questi componenti avviene mediante immagini [Docker](https://it.wikipedia.org/wiki/Docker),
non si tratta di un requisito, i singoli applicativi possono essere installati anche in assenza di esso, su un
qualunque server Linux.

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

$ http get https://www.stanzadelcittadino.it/comune-di-ala/api/applications "Authorization: Bearer $TOKEN"
```

### Versionamento delle API

È possibile specificare la versione delle Api che si vanno ad interrogare, questo è possibile in 2 modi differenti:

  * tramite parametro `version` da passare come parametro delle GET
  * tramite parametro `X-Accept-Version` da specificare nell' header della richiesta

La versione di default è per retrocompatibilità la *1*

Dalla versione *2* le chiavi dei valori del campo `data` delle `applications` non sono più restituite
come un insieme di strighe piatto separato dal punto (.) ma come campo json con le chiavi esplose.

Nota bene: si raccomanda di specificare sempre la versione di API utilizzata, anche se è il default (1), perche' in futuro
il default potrebbe cambiare.

## Build system, CI e test automatici

Mediante il servizio di CI di GitLab vengono preparate le immagini docker di ogni componente.
Durante la build vengono inoltre effettuati:

* il test di sicurezza dinamica Owasp, mediante il tool `ZAP`
* il test della validità del `publiccode.yml` del repository

## Installazione

### Con l'ausilio di docker

Dal repository stesso è possibile fare il deploy di un *ambiente di sviluppo* del servizio. Per farlo è sufficiente fare il clone del repository sul proprio computer e utilizzare questo [docker-compose.yml](https://gitlab.com/opencontent/stanza-del-cittadino/core/-/blob/master/docker-compose.yml) del progetto.

    git clone git@gitlab.com:opencontent/stanza-del-cittadino/core.git
    cd stanzadelcittadino
    docker-compose up -d postgres
    sleep 10
    docker-compose up --build -d

Al termine della build e dell'inizializzazione del database sarà possibile visitare l'indirizzo:

http://stanzadelcittadino.localtest.me/

Una volta fatto il primo setup, se si inizia a configurare il tenant di prova è consigliabile
impostare a `FALSE` la variabile `ENABLE_INSTANCE_CONFIG` nel `docker-compose.yml`.

In caso di problemi è possibile trovare maggiori infomazioni nel [wiki](https://gitlab.com/opencontent/stanza-del-cittadino/core/-/wikis/Informazioni-per-sviluppatori/Ambiente-di-sviluppo).

#### Integrazione con Kafka
Il software è già pronto all'integrazione con Kafka, Apache Kafka è una piattaforma di data streaming che consente di dare vita a pipeline di dati e ad applicazioni.
Per semplicità di configurazione è stato aggiunto un file già pronto all'abilitazione dell'integrazione con kafka `docker-compose.kafka.yml`.

Aggiungendo questo file in fase di build del progetto partiranno in automatico tutti i microservizi necessari all'integrazione.
Nel file sono già presenti le configurazioni minime per interfacciare i 2 applciativi.
Dopo aver avviato i servizi per l'integrazione con Kafka sarà possibile verificare i messaggi presenti nella coda all'indirizzo:

kowl.localtest.me


### Senza l'ausilio di docker

I `Dockerfile` presenti nel repository principale e in quelli secondari possono essere utilizzati
per dedurre quali sono i requisiti a livello di sistema operativo di ogni componente, per questo
motivo non viene fornita documentazione in merito, ma in caso di necessità è possibile aprire una
issue per richiedere chiarimenti. Si sconsiglia comunque il setup senza docker perché rende molto
oneroso gli aggiornamenti, che ad oggi sono variabili da 1-2 al mese fino a 2-3 a settimana in caso
di bugfix.

## Credenziali d'accesso
Per accedere come admin usare le seguenti credenziali

    user: admin
    password: changeme

## Abilitazione features

Mediante specifiche variabili d'ambiente è possibile abilitare o disabilitare features.

    FEATURE_NOME=true

Feature disponibili:
   - Browser outdated, si abilita tramite la variabile d'ambiente `FEATURE_NEW_OUTDATED_BROWSER`:
   sostituisce il vecchio plugin browser outdated per la verifica di browser obsoleti.
   Migliora la scelta di browser compatibili tramite la versione minima configurata.
   Supporta browser mobile con callback specifiche per Web - Android - IOS.


   - Interfaccia di dettaglio pratica per il cittadino, si abilita tramite la variabile d'ambiente `FEATURE_APPLICATION_DETAIL`:
   sostituisce l'interfaccia di dettaglio ad uso del cittadino, migliorandone la user experience.
   Consente inoltre lo scambio di messaggi tra operatore e cittadino.

   - Interfaccia per operatori e admin, si abilita tramite la variabile d'ambiente `FEATURE_ANALYTICS`:
     abilita la pagina operatori/analytics mostrando dati statistici della stanza.

## Restrizioni di accesso ai servizi

Mediante la variabile d'ambiente `BROWSERS_RESTRICTIONS` è possibile limitare l'accesso di una specifica versione di un browser ai servizi.

La sintassi della variabile è cosi definita:  `nome browser,operatore logico, versione|nome browser,operatore logico, versione`

Es. `Maxthon,<,4.0.5`

Il componente utilizzato è [Parser-PHP](https://github.com/WhichBrowser/Parser-PHP) al quale si rimanda per uteriori informazioni.

## Project status

Il progetto e' stabile e usato in produzione da oltre 200 amministrazioni pubbliche.

Sebbene lo sviluppo sia stato orientato fin dall'inizio alla modularità e alla configurabilità
delle feature, molti aspetti di dettaglio e le interfacce di comunicazione con i proxy non sono
abbastanza maturi per essere sostituiti con una semplice configurazione, in alcuni casi
può rendersi necessario la modifica del codice sorgente. Sono quindi graditi i contributi
al codice per il supporto di altri sistemi di protocollo o di pagamento, purché siano fatte
in modo modulare e configurabile, in modo da non pregiudicare la flessibilità del progetto.

Altri limiti:
- la gestione degli operatori è molto limitata al momento e non consente grande flessibilità
  o l'utilizzo di gruppi di operatori per la gestione delle pratiche: i profili di accesso
  sono tre rigidamente separati tra loro: il cittadino (user), l'operatore (il personale
  dell'Ente addetto a gestire le pratiche) e l'amministratore che configura l'istanza
  e i singoli servizi.
- il security log è minimale e intellegibile solo da personale tecnico
- le API coprono attualmente circa il 90% delle funzionalità dell'applicativo

## Copyright

Copyright (C) 2016-2022  Opencontent SCARL

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

