#!/bin/bash

for instance in $(./bin/tenants); do
   php bin/console --no-interaction --instance $instance ocsdc:scheduled_action:execute;
   php bin/console --no-interaction --instance $instance ocsdc:delete-draft-meetings;
done
