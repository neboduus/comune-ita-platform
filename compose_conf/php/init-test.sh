#!/bin/bash

[[ $DEBUG == 1 ]] && set -x

echo "==> Checking database availability"
wait-for-it ${DB_HOST}:${DB_PORT}

echo "==> Running migrations"
echo " * Running migrations on comune-di-test"
bin/console doctrine:database:create -e test -i comune-di-test --if-not-exists
bin/console doctrine:migrations:migrate -e test -i comune-di-test --no-interaction

echo "==> First-time instance initialization"
echo " * admin user creation on comune-di-test"
bin/console ocsdc:configure-instance -e test -i "comune-di-test" --no-interaction --name="comune-di-test" --code_adm="C_123" --siteurl="https://comune-di-test.it" --admin_name="Amministratore" --admin_lastname="Servizi" --admin_email="admin@localtest.me" --admin_username="admin" --admin_password="changeme"
