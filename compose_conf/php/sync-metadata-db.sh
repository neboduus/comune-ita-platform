#!/bin/bash

php bin/console --no-interaction doctrine:migrations:sync-metadata-storage

for instance in $(./bin/tenants); do
   php bin/console --no-interaction --instance $instance doctrine:migrations:sync-metadata-storage
done
