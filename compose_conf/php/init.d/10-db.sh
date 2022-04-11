#!/bin/bash

[[ $DEBUG == 1 ]] && set -x

echo "==> Checking database availability"
wait-for-it ${DB_HOST}:${DB_PORT}


if [[ $ENABLE_MIGRATIONS == 'true' ]]; then

  echo "==> Running migrations"

  # initialization && migrations on municipalities
  for identifier in $(./bin/tenants); do

    echo " * Running migrations on $identifier"
    bin/console doctrine:database:create -i "$identifier" --if-not-exists
    bin/console doctrine:migrations:migrate -i "$identifier" --no-interaction

  done
fi

# first-time initialization of tenant
if [[ $ENABLE_INSTANCE_CONFIG == 'true' ]]; then

  echo "==> First-time instance initialization"

  for identifier in $(./bin/tenants); do

    echo " * admin user creation on $identifier"

    bin/console ocsdc:configure-instance -i "$identifier" --no-interaction --name="${identifier}" --code_adm="C_123" --siteurl="https://${identifier}.it" --admin_name="Amministratore" --admin_lastname="Servizi" --admin_email="admin@localtest.me" --admin_username="admin" --admin_password="changeme"

  done
fi
