#!/bin/bash

if [[ $ENV == 'DEMO' ]]; then

	echo "==> ENV=DEMO, loading doctrine fixtures to create a default environment"

	php bin/console --no-interaction doctrine:fixtures:load

	for instance in $(ls -1 app/config/|grep comune); do
   		php bin/console --no-interaction --instance $instance doctrine:fixtures:load
	done

fi


