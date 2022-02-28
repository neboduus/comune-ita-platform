# Note operative

## Caricamento dei servizi
Nella cartella `servizi_config_templates` si trovano dei file standard di configurazione dei servizi, pronti per essere adattati per il singolo tenant e  caricati nella sua istanza tramite

`bin/console ocsdc:crea-servizi -f nome_del_file_del_servizio.json -i slug-istanza-tenant`

Prestare attenzione in particolare alla configurazione MyPay se necessaria, all'url del modulo principale se presente e allo stato del servizio:

 * 0 = disattivo
 * 1 = attivo
 * 2 = sospeso

## Deploy

### Release

Per effettuare una release si utilizza una procedura automatica che sfrutta:

 * release-it  (https://www.npmjs.com/package/release-it)
 * auto-changelog (https://www.npmjs.com/package/auto-changelog)

Le procedura per effettuare una release è cos' composta:

* Approvare tutte le eventuali mr che vogliamo facciano parte della release
* Posizionarsi sulla root del progetto, branch master aggiornato
* Eseguire il comando `release-it`
* Il sistema mostrerà il changelog e proporrà una sceata tra le possibili prossime versioni (patch, minor, major, prepatch, preminor, premajor...), fare riferimento a https://semver.org/lang/it/ per i dettagli sulle versioni
* Una volta scelta la versione il repository avrà come modifiche il file di changelog generato già aggiunto nello stage
* Aggiornare manualmente il file publicode.yml modificando nuova versione e data di aggiornamento a aggiungerlo nello stage tramite `git add publicode.yml`
* Rispondere si (Y) a tutte le opzioni che release-it propone in seguito:
    - Commit (Release x.x.x)?
    - Tag (x.x.x)?
    - Push?
    - Create a release on GitLab (Release x.x.x)?

è possibile testare la procedura di release senza apportare modifiche con il comando `release-it --dry-run`


