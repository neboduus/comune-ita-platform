#!/bin/bash

php bin/console --no-interaction doctrine:migrations:migrate

for instance in $(ls -1 app/config/|grep comune); do
   php bin/console --no-interaction --instance $instance doctrine:migrations:migrate
done
