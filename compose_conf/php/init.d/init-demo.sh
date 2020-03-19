#!/bin/bash

if [[ $ENV == 'DEMO' ]]; then
  echo "==> ENV=DEMO, loading doctrine fixtures to create a default environment"
  php bin/console --no-interaction doctrine:fixtures:load
  for instance in $(./bin/tenants); do
    php bin/console --no-interaction --instance ${instance} doctrine:fixtures:load
  done

  if [[ -n ${CONSUL_PREFIX} ]]; then
    echo "==> ENV=DEMO, loading consul configuration for bugliano and vallelaghi"
    for tenant in comune-di-bugliano comune-di-vallelaghi; do
      db_name=$(echo $tenant | sed 's/-//g')
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/env prod
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/protocollo dummy
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/email ${tenant}@demo.stanzadelcittadino.it
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name ${db_name}
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/codice_meccanografico 1234
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/prefix ${tenant}
    done
  fi
fi
