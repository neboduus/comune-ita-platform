#!/bin/bash

for instance in $(./bin/tenants); do
   echo "Execute scheduled actions for tenant: $instance"
   php bin/console --no-interaction --instance $instance ocsdc:scheduled_action:execute
done
