#!/bin/bash

for instance in $(./bin/tenants); do
  echo "Execute scheduled actions for tenant: $instance"
  php bin/console --no-interaction --instance $instance ocsdc:scheduled_action:execute -o 60 --max-retry 10 --count 50
  echo "Disable users for tenant: $instance"
  php bin/console --no-interaction --instance $instance ocsdc:user-secure:execute
  echo "Delete draft meetings for tenant: $instance"
  php bin/console --no-interaction --instance $instance ocsdc:delete-draft-meetings
  echo "Create subscription payments draft for tenant: $instance"
  php bin/console --no-interaction --instance $instance ocsdc:create_subscription_payment_drafts
done
