#!/bin/bash

if [[ $ENV == 'DEMO' ]]; then

	echo "==> ENV=DEMO, loading doctrine fixtures to create a default environment"

	php bin/console -q --no-interaction doctrine:database:create --if-not-exists
	php bin/console -q --no-interaction doctrine:migrations:migrate --em=main --configuration=src/Migrations/Main/configuration.php
	php bin/console -q --no-interaction doctrine:fixtures:load --env=dev

	for instance in $(php bin/console tenants); do
   		echo "              create db and loading doctrine fixtures for tenant: $instance"
   		#php bin/console -q --no-interaction --instance=$instance doctrine:database:create --if-not-exists
   		php bin/console tenants --create-db=$instance
   		php bin/console -q --no-interaction --instance=$instance doctrine:migrations:migrate
   		php bin/console -q --no-interaction --instance=$instance doctrine:fixtures:load --env=dev
	done

#	for instance in $(php bin/console tenants); do
#   		echo "==> Drop tenant: $instance"
#   		php bin/console --no-interaction doctrine:database:drop --force -q -i $instance
#	done

fi


