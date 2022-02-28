# Gsuit SDK

Foglio di lavoro, progettato per facilitare la reportistica e uso dei dati recuperati da uno o più servizi
Template del foglio è utilizzabile solo tramite lo strumento [Google Spreadsheets](https://docs.google.com/spreadsheets)

## Descrizione
Un'utente abilitato può recuperare i dati di uno o più servizi dopo aver eseguito correttamente la prima configurazione.
Il foglio è suddiviso in più sezioni personalizzabili posizionale in basso alla pagina, le principali sono Applications e Config.
- Applications, sezione dove verranno importati tramite API tutti i dati del servizio/i configurati.
- Config, sezione dove verranno configurati tutti i parametri per l' accesso alle API e servizi richiesti
- Stats, utilizzato per statistiche dei dati, configurata manualmente in base alle esigenze

## Funzionalità principali

- Importazione dei dati nella sezione Applications
- Aggiornamento degli stati delle pratiche già importate
- Importazione per gruppo di servizi

## Altri riferimenti

* [API](https://www2.stanzadelcittadino.it/comune-di-trento/api/doc)
* [Template](https://docs.google.com/spreadsheets/d/1ZcLw96qsohswbvRB_ShGn2o8r-WAagogXuDERDNeC5A/edit?usp=sharing)

## Requisiti

* Google Spreadsheets
* Account operatore del proprio ente - Stanza del cittadino

## Installazione

Non necessita di installazione

## Prima Configurazione

Necessaria per una prima configurazione andare nella sezione CONFIG configurando i seguenti parametri:
- **Username** dell' account operatore dell' ente **(obbligatorio)**
- **Password** dell' account operatore dell' ente **(obbligatorio)**
- **Host** host API per recupero dati default https://www.stanzadelcittadino.it **(obbligatorio)**
- **Tenant** slug dell'ente richiesto,  ES: comune-di-trento **(obbligatorio)**
- **Servizio** slug del servizio richiesto, ES: bonus-trento **(obbligatorio)**
- **Group** per importazione dati servizi appartenenti a un gruppo, aggiungere gli slug dei servizi separati per "," senza spazi. Es. slug-servizio-1,slug-servizio-2.
**_Se popolato il campo Servizio viene ignorato_**
- **LastDateUpdate** data ultimo aggiornamento dei dati, popolato automaticamente
- **LastLineChecked** ultima riga aggiornata sul foglio popolato automaticamente
- **LastUrl** ultimo endpoint richiesto da API, popolato automaticamente
- **Ver. API** Versione API utilizzate
- **IsRunning** TRUE o FALSE, valorizzato automaticamente, verifica se ci sono script in esecuzione, prevenendone l' esecuzione di script simultanei


## Importazione dei dati

Andare nella barra degli strumenti, sul tab **Stanza del cittadino**
e cliccare su **Importa dati**.
Verranno importati i dati delle pratiche nella sezione **Applications**
Alla prima importazione è consigliato verificare che Applications si vuoto, se non lo è, eliminare tutti dati presenti.
Al termine dello script un alert mostrerà l'esito dell'importazione

Con lo stesso procedimento è possibile aggiornare gli stati di pratiche già importate eseguendo **Aggiorna stati** dal tab Stanza del cittadino.

## Ordinamento dei dati

Dopo la prima importazione è possibile nascondere o filtrare i dati utilizzando l'header in Applications
Nelle successive importazioni, verranno aggiunti o aggioranti i dati in base all'header configurato

## Importazioni con scheduler

E' possibile eseguire le importazioni in modo automatico mediante gli attivatori

 **Come configurare gli attivatori**
- Andare nella sezione Editor script sotto la voce Strumenti
- Nella pagina appena aperta andare in Trigger del progetto corrente in Modifica
- Si aprirà una nuova pagina e cliccare sul pulsante Aggiungi attivatore in basso a destra
- Selezionare la funzione getTenantApplication per schedulare Importa dati, selezione un origine dell'evento e salva
- Selezionare la funzione updateApplication per schedulare Importa Aggiorna stati, selezione un origine dell'evento e salva

Verranno eseguiti gli attivatori in base allo scheduler impostato

## Codice scripts

Gli script utilizzati per le funzionalità del foglio di lavoro sono disponibili
nella sezione **Editor script** sotto la voce **Strumenti**


## Copyright

Copyright (C) 2016-2020  Opencontent SCARL

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

