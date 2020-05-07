#!/bin/bash

if [[ $ENABLE_MIGRATIONS == 'true' ]]; then

  php bin/console --no-interaction doctrine:migrations:migrate --em=main --configuration=src/Migrations/Main/configuration.php

  for instance in $(php bin/console tenants); do
     php bin/console --no-interaction doctrine:migrations:migrate -i $instance
  done

fi


