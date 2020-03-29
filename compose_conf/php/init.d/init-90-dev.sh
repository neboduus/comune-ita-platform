#!/bin/bash

if [[ $ENV == 'dev' ]]; then

  echo "==> ENV=dev"
  echo "Use single entrypoint 'app_dev.php'"
  if [[ -f web/app_dev.php ]]; then
    mv -v web/app.php web/app_prod.php
    mv -v web/app_dev.php web/app.php
  fi

  if [[ -n ${CONSUL_PREFIX} ]]; then

    echo "At least the tenant 'bugliano' must be configured in consul"

    wait-for-it ${CONSUL_HTTP_ADDR:-'consul:8500'} --timeout=0 --strict

    tenant='comune-di-bugliano'
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/env || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/env dev
    
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/protocollo || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/protocollo dummy 
    
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/email || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/email ${tenant}@stanzadelcittadino.localtest.me
    
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name sdc_bugliano
    
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/codice_meccanografico || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/codice_meccanografico c_bug
    
    consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/prefix || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/prefix ${tenant}    
  fi

else

  if [[ -f web/app_prod.php ]]; then
    echo "==> ENV != dev, restoring 'app.php'"
    mv -v web/app.php web/app_dev.php
    mv -v web/app_prod.php web/app.php
  fi

fi
