# OpenContent - Stanza Del Cittadino

## Server di sviluppo

ssh developer@devsdc.opencontent.it -p222

Vedi https://support.opencontent.it/opencontent/wiki/devsdc

## Servizio di chat
Perché funzioni il servizio di messaggistica interna bisogna che giri il progetto `ocsdc_messaggistica`
Questo a sua volta ha necessità di alcuni requisiti

Nel Parameters vanno messi i puntamenti: 

`messages_backend_url: http://localhost:3000/`

Fare riferimento a quel progetto per il setup specifico

Gli endpoint per postare messaggi sono separati per cittadino e operatore perché mi servono i rispettivi utenti dal firewall di Symfony
