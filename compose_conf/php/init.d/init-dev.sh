#!/bin/bash

if [[ $ENV == 'dev' ]]; then
  echo "==> ENV=dev, using 'app_dev.php'"
  if [[ -f web/app_dev.php ]]; then
    mv -v web/app.php web/app_prod.php
    mv -v web/app_dev.php web/app.php
  fi
else
  if [[ -f web/app_prod.php ]]; then
    echo "==> ENV!=dev, restoring 'app.php'"
    mv -v web/app.php web/app_dev.php
    mv -v web/app_prod.php web/app.php
  fi

  if [[ -n ${CONSUL_PREFIX} ]]; then
    echo "==> ENV=DEV, at lease the tenant bugliano must be configured in consul"
	
    wait-for-it $CONSUL_HTTP_ADDR -t 60

    tenant='comune-di-bugliano'
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/env || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/env dev
    
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/protocollo || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/protocollo dummy 
    
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/email || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/email ${tenant}@stanzadelcittadino.localtest.me
    
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name sdc_bugliano
    
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/codice_meccanografico || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/codice_meccanografico c_bug
    
    test consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/prefix || \
      consul kv put ${CONSUL_PREFIX}/${tenant}/config/content/parameters/prefix ${tenant}
    
  fi

fi