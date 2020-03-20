#!/bin/bash

wait-for-it postgres:5432 -t 60 -- php bin/console --no-interaction doctrine:migrations:migrate

for instance in $(./bin/tenants); do
   php bin/console --no-interaction --instance $instance doctrine:migrations:migrate
done
