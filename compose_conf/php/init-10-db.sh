#!/bin/bash

[[ $DEBUG == 1 ]] && set -x

if [[ $ENABLE_MIGRATIONS == 'true' ]]; then

  # migrations on default db 'sdc_multi'
  bin/console --no-interaction doctrine:migrations:migrate

  # initialization && migrations on municipalities
  for identifier in $(./bin/tenants); do

    bin/console doctrine:database:create -i "$identifier" --if-not-exists
    bin/console doctrine:migrations:migrate -i "$identifier" --no-interaction
    bin/console ocsdc:configure-instance -i "$identifier" --no-interaction --name="${identifier}" --code_adm="C_123" --siteurl="https://${identifier}.it" --admin_name="Amministratore" --admin_lastname="Servizi" --admin_email="admin@localtest.me" --admin_username="admin" --admin_password="changeme"

  done
fi
