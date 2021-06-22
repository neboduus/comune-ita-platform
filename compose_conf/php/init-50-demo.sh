#!/bin/bash

if [[ $ENV == 'DEMO' ]]; then

	echo "==> ENV=DEMO, Environment demo is not available at the moment"
	#echo "==> ENV=DEMO, loading doctrine fixtures to create a default environment"

	#php bin/console --no-interaction doctrine:fixtures:load

  #instance=comune-di-bugliano
	#php bin/console --no-interaction --instance $instance doctrine:fixtures:load

fi


