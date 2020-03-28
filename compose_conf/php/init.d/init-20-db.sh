#!/bin/bash

DB_HOST=${DB_HOST:-postgres}
DB_PORT=${DB_PORT:-5432}

wait-for-it ${DB_HOST}:${DB_PORT} --timeout=0 --strict -- php bin/console --no-interaction doctrine:migrations:migrate

for instance in $(./bin/tenants); do
   wait-for-it ${DB_HOST}:${DB_PORT} --timeout=0 --strict -- php bin/console --no-interaction --instance ${instance} doctrine:migrations:migrate
done
