#!/bin/bash

for instance in $(php bin/console tenants); do
   echo "Execute scheduled actions for tenant: $instance"
   php bin/console --no-interaction ocsdc:scheduled_action:execute --instance $instance
done
