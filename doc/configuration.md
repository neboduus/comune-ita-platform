# Configurazione

## Environment variables

|           Nome               | Required |     Default     | Note        |
|------------------------------|----------|-----------------|-------------|
| OCSDC_SCHEME                 | No       | https           | Usato per i link inseriti nelle email transazionali |
| OCSDC_HOST                   | No       | localtest.me | Usato per i link inseriti nelle email transazionali |
| DB_DRIVER		       | No       | pdo_pgsql       | Database configuration |
| DB_HOST                      | No       | postgres        | |
| DB_PORT                      | No       | 5432            | |
| DB_NAME                      | No       | sdc_multi       | |
| DB_USER                      | No       | sdc             | |
| DB_PASSWORD                  | No       | sdc             | |
| MAILER_TRANSPORT    	       | No       | smtp            | Configurazione server per l'invio di messaggi transazionali |
| MAILER_HOST                  | Yes      | 127.0.0.1       | |
| MAILER_PORT                  | No       | 25              | |
| MAILER_USER                  | No       | null            | |
| MAILER_PASSWORD              | No       | null            | |
| PEC_MAILER_TRANSPORT         | No       | smtp            | Configurazione server per l'invio di messaggi ai sistemi di protocollo che accettano PEC
| PEC_MAILER_HOST              | Yes      | smtp.pec.host   |
| PEC_MAILER_PORT              | No       | 465             |
| PEC_MAILER_USER              | No       | null            |
| PEC_MAILER_PASSWORD          | No       | null            |
| PEC_DELIVERY_ADDRESS         | Yes      | pec.stanzadelcittadino@localtest.me |
| SECRET                       | Yes      | ThisTokenIsNotSoSecretChangeIt  | |
| DEFAULT_FROM_EMAIL_ADDRESS   | No       | stanzadelcittadino@localtest.me |  |
| WKHTMLTOPDF_SERVICE          | Yes      | wkhtmltopdf     | Url dell'API del servizio Gotemberg |
| EZ_PASSWORD                  | No       | ez              |  |
| PASSWORD_LIFE_TIME           | No       | 365             | Durata in giorni della password per amministratori e operatori: dopo questa data l'account resta attivo ma al primo login verra' richiesto il cambio password |
| INACTIVE_USER_LIFE_TIME      | No       | 545             | Numero massimo di giorni dopo il quale in assenza di login l'account viene bloccato |
| LOGS_PATH                    | No       | %kernel.logs_dir%/%kernel.environment%.log | |
| FORMSERVER_PRIVATE_URL       | Yes      | http//formserver | URL of the formserver used by PHP Process to update forms |
| FORMSERVER_PUBLIC_URL        | Yes      | http://formserver.localtest.me | URL of the formserver used by application users and operators to render forms
| FORMSERVER_ADMIN_URL         | Yes      | http://formserver.localtest.me | URL of the formserver used by administrators to render forms
| HASH_VALIDITY                | No       | 1 |
| TOKEN_TTL                    | No       | 3600 | Durata del token di autenticazione in secondi |
| RECAPTCHA_KEY                | Yes      |   | Credenziali del recaptcha usato sulle pratiche anonime
| RECAPTCHA_SECRET             | Yes      |   | Credenziali del recaptcha usato sulle pratiche anonime
| SENTRY_DSN                   | No       |   | Se configurato abilita l'integrazione con un server [Sentry](https://sentry.io) |
| LOGIN_ROUTE                  | No       | login_pat | Autenticazione di default, sovrascribile per tenant
| SINGLE_LOGOUT_URL            | No       | /Shibboleth.sso/Logout | Url a cui rendirizzare dopo il logout
| BROWSERS_RESTRICTIONS        | No       | null            |
| CACHE_MAX_AGE                | No       | 0               | Valore degli header `cache-control` restituiti dai path che consentono il caching
| METRICS_TYPE                 | No       | in_memory       | Alternativa: redis
| METRICS_REDIS_HOST           | No       | redis           |
| METRICS_REDIS_PORT           | No       | 6379            |
| METRICS_REDIS_PASSWORD       | No       | ~               |
| UPLOAD_DESTINATION           | No       | local_filesystem | Destinazione dei file caricati dagli utenti del sistema. Alternativa: s3_filesystem e allora richiede le variabili S3_** per dettagliare le configurazioni |
| S3_REGION     	       | No       | eu-west-1       |
| S3_KEY                       | No       |                 |
| S3_SECRET                    | No       |                 |
| S3_BUCKET                    | No       | test            |
| KAFKA_BROKERS		       | No       |                 | Lista dei broker di kafka, necessaria per i check sui pagamenti
| KAFKA_URL                    | No       | null            | Se configurato, gli eventi delle entità dell'applicativo vengono inviati al server Kafka
| KAFKA_EVENT_VERSION          | No       | 1               | Versione dell'API che determina il formato dell'evento Kafka |
| KAFKA_TOPIC_APPLICATIONS     | No       | applications    | Nome del topic a cui vengono inviati gli eventi relativi alle Pratiche |
| KAFKA_TOPIC_SERVICES         | No       | services        | Nome del topic a cui vengono inviati gli eventi relativi ai Servizi |
| KAFKA_TOPIC_PAYMENTS         | No       | payments        | Nome del topic a cui vengono inviati gli eventi relativi ai Payments |
| API_VERSION                  | No       | 1               | Versione delle API service in caso non venga specificato
| SKIP_CACHE_WARMUP	       | No       | false           | Salta la creazione anticipata della cache all'avvio del container

## Grafica generale

|           Nome               | Required |     Default     | Note        |
|------------------------------|----------|-----------------|-------------|
| HEADER_TOP_TITLE             | No       | Provincia autonoma di Trento | FixMe |
| HEADER_TOP_LINK              | No       | http://www.provincia.tn.it/ | FixMe |

## Integrazioni terze parti

|           Nome               | Required |     Default     | Note        |
|------------------------------|----------|-----------------|-------------|
| PITRE_ADAPTER_URL            | No       | http://pitre    | Indirizzo del Pitre Soap Proxy |
| GISCOM_ADAPTER_URL           | No       | https://www.giscom.cloud/WebAPI/ |
| GISCOM_PASSWORD              | No       | giscom          |
| GISCOM_ADAPTER_USERNAME      | No       | pippo           |
| GISCOM_ADAPTER_PASSWORD      | No       | passw           |
| QUEUEIT_CUSTOMER_ID          | No       |                 | Integrazione con servizio [queue-it](https://queue-it.com/) |
| QUEUEIT_SECRET               | No       |                 | Your 72 char secret key as specified in Go Queue-it self-service platform |
| QUEUEIT_CONFIG_FILE          | No       |                 | Absolute path of [Queue-it configuration file](https://github.com/queueit/KnownUser.V3.PHP/blob/master/Documentation/README.md)
| MYPAY_ADAPTER_URL            | No       |                 | Indirizzo del MyPay Soap Proxy
| IO_API_URL                   | No       | https://api.io.italia.it/api/v1 | Url a cui effettuare le chiamate per l'App.IO: e' possibile con questa variabile indirizzare le chiamate a un proprio proxy interno |

## Configurazioni di PHP e PHP-FPM

Il container di base utilizzato per la creazione dell'immagine PHP supporta numerosi parametri di configurazione,
si consiglia di fare riferimento alla pagina del progetto per vedere in dettaglio quali parametri e' possibile
personalizzare: https://github.com/wodby/php

## Configurazione tenants

Il sistema è _multitenant-multiple-databases_, i tenant configurati sono nel file `app/instances_${APP_ENV}.yml`

E' possibile sovrascrivere il file dei tenant con alcune variabili d'ambiente:

|           Nome               | Required |     Default     | Note        |
|------------------------------|----------|-----------------|-------------|
| INSTANCE_OVERRIDE            | No       | false           | Impostare a true per abilitare la funzionalità |
| INSTANCE_address             | No       |                 | Indirizzo completo dell'applicazione, es: stanzadelcittadino.localtest.me/comune-di-bugliano  |
| INSTANCE_identifier          | No       |                 | Identificativo dell'ente sul database, es: comune-di-bugliano |
| INSTANCE_database: 	       | No       |                 | Nome del database dell'istanza, es: sdc_bugliano |
| INSTANCE_codice_meccanografico | No     |                 | Codice meccanografico, es: c_cbug (puo' essere un codice di fantasia) |

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

- Calendari con appuntamenti a intervalli dinamici, si abilita tramite la variabile d'ambiente `FEATURE_CALENDAR_TYPE`:
  aggiunge la possibilità di modificare la tipologia di appuntamenti di un calendario aggiungendo la possibilità
  di gestire prenotazioni ad intervalli variabilo

- Interfaccia per operatori e admin, si abilita tramite la variabile d'ambiente `FEATURE_ANALYTICS`:
  abilita la pagina operatori/analytics mostrando dati statistici della stanza.

- Identificativo univoco del servizio, si abilita/disabilita tramite la variabile d'ambiente `FEATURE_SERVICE_IDENTIFIER`:
  Consente all'amministratore di definire ed editare per ogni servizio un identificativo univoco.
