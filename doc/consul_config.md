# Configurazione con Consul

I tenants possono essere configurati esternamente mediante Consul Key-Value Store.

Per abilitare la funzionalità dare un valore appropriato alle seguenti variabili:


| variabile | default | note |
| --------- | ------- | ---- |
| `CONSUL_HTTP_ADDR`     | `consul:8500` | indirizzo delle API di Consul |
| `CONSUL_PREFIX`	 | `sdc/stanzadelcittadino.it/tenants` | prefisso nell'albero del KV Store delle configurazioni dei tenants |
| `TENANTS_CONFIG_PATH`  | `/tmp/tenants` | directory dove devono essere salvati i dati dei tenant (uno per ogni sottodirectory di questa) |
| `CLEANUP_TENANTS_PATH` | `true` | rimuove qualunque cosa sia presente nella directory dei tenants prima di portare li i file di configurazione generati |


## Configurazioni previste

Il sotto albero del tenant dovrebbe contenere:

```
sdc/
	devsdc.opecontent.it/
		comune-di-bugliano/
			config/
				protocollo
			content/
				parameters/
					codice_meccanografico
					database_name
					prefix

		
```

La parte sotto config contiene variabili usate nel file di configurazione in posti specifici

La parte sotto contentn è semplicemente un elenco di configurazioni messe sotto forma di yaml
nel file di configurazione

