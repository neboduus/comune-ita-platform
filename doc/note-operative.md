# Note operative

## Caricamento dei servizi
Nella cartella `servizi_config_templates` si trovano dei file standard di configurazione dei servizi, pronti per essere adattati per il singolo tenant e  caricati nella sua istanza tramite

`bin/console ocsdc:crea-servizi -f nome_del_file_del_servizio.json -i slug-istanza-tenant`

Prestare attenzione in particolare alla configurazione MyPay se necessaria, all'url del modulo principale se presente e allo stato del servizio:

 * 0 = disattivo
 * 1 = attivo
 * 2 = sospeso

## Deploy

### Prod

Indirizzo: *https://www.stanzadelcittadino.it*

In questo ambiente il deploy avviene con git e sono necessarie alcune operazioni per lo svuotamente della
cache di symfony che spesso non si aggiorna come si deve. Una volta allineato il codice con git eseguire
la pulizia della cache sul container dell'app:

```
[developer@docker ocsdc]$ docker-compose exec ocsdc bin/console cache:clear -e prod
[developer@docker ocsdc]$ docker-compose exec ocsdc bin/console cache:clear -i comune-di-treville -e prod
[developer@docker ocsdc]$ docker-compose exec ocsdc bin/console cache:clear -i comune-di-vallelaghi -e prod
[developer@docker ocsdc]$ docker-compose exec ocsdc bin/console cache:clear -i comune-di-rovereto -e prod
[developer@docker ocsdc]$ docker-compose exec ocsdc bin/console cache:clear -i comune-di-cavedine -e prod
```

Stessa operazione sul container del cron:

```
[developer@docker ocsdc]$ docker-compose exec cron bin/console cache:clear -e prod
[developer@docker ocsdc]$ docker-compose exec cron bin/console cache:clear -i comune-di-treville -e prod
[developer@docker ocsdc]$ docker-compose exec cron bin/console cache:clear -i comune-di-vallelaghi -e prod
[developer@docker ocsdc]$ docker-compose exec cron bin/console cache:clear -i comune-di-rovereto -e prod
[developer@docker ocsdc]$ docker-compose exec cron bin/console cache:clear -i comune-di-cavedine -e prod
```

### Demo

Indirizzo: *https://demosdc.stanzadelcittadino.it*

L'ambiente demo ha un deploy semplificato rispetto alla produzione, effettuato usando docker: questo rende
le dipendenze dal sistema di hosting molto ridotte, ma ci sono ancora dei dettagli da fixare per renderlo
un deploy pulito.

I container *app* e *apache* sono interdipendenti, per la precisione il container *apache* deve avere 
la directory `/var/www/html/web` identica a quella dell'immagine del container php. Questo non è risolto
durante la build ma a runtime, e comporta delle operazioni manuali durante il deploy. Per aggiornare
questi componenti eseguire i seguenti step:

    docker-compose pull apache php
    docker-compose stop apache php
    docker-compose rm -f apache php
    docker volume rm sdc_app
    docker-compose up -d




