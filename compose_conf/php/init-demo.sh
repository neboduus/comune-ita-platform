#!/bin/bash

if [[ $ENV == 'DEMO' ]]; then

	php bin/console --no-interaction doctrine:fixtures:load

	for instance in $(ls -1 app/config/|grep comune); do
   		php bin/console --no-interaction --instance $instance doctrine:fixtures:load
	done

fi


