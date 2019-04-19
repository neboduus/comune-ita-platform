# OpenContent - Stanza Del Cittadino

## Caricamento dei servizi
Nella cartella `servizi_config_templates` si trovano dei file standard di configurazione dei servizi, pronti per essere adattati per il singolo tenant e  caricati nella sua istanza tramite

`bin/console ocsdc:crea-servizi -f nome_del_file_del_servizio.json -i slug-istanza-tenant`

Prestare attenzione in particolare alla configurazione MyPay se necessaria, all'url del modulo principale se presente e allo stato del servizio:

 * 0 = disattivo
 * 1 = attivo
 * 2 = sospeso
