#!/bin/bash

php bin/console --no-interaction doctrine:migrations:migrate

for instance in $(./bin/tenants); do
   php bin/console --no-interaction --instance $instance doctrine:migrations:migrate
done
