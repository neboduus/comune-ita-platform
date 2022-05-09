#!/usr/bin/env bash

[[ $DEBUG ]] && set -x
[[ $VERBOSE ]] && echo "Set verbose output"

[[ -z $KAFKA_BROKERS ]] && echo "Error, missing KAFKA_BROKERS variable to get updates on payments" && exit 1

if [[ -z $KAFKA_TOPIC_PAYMENTS ]]; then
  echo "Warning, missing KAFKA_TOPIC_PAYMENTS variable, using default 'payments'"
  readonly topic='payments'
else
  readonly topic=${KAFKA_TOPIC_PAYMENTS}
fi

# si puo' mettere a oldest per leggere dall'inizio
offset=${TOPIC_OFFSET:-'newest'}

payment_info=$(/usr/bin/kaf --brokers "$KAFKA_BROKERS" consume $topic  --offset $offset | jq -r ".id,.tenant_id,.status,.remote_id")

IFS='\n' read -d'' -ra payment <<< "$payment_info"

payment_id=${payment[0]}
tenant_id=${payment[1]}       # <- qui abbiamo il solito problema degli ID e dei nomi dei tenant
status_name=${payment[2]}
application_id=${payment[3]}

# cablo il tenant_name per superare il problema che non sappiamo ancora convertirlo
tenant_slug="comune-di-verbania"

case $payments_status in

  COMPLETE)
    ./bin/console ocsdc:application:change-status --instance $tenant_slug --id $application_id --status 1520
  ;;

  PAYMENT_FAILED)
    ./bin/console ocsdc:application:change-status --instance $tenant_slug --id $application_id --status 1530
  ;;


  *)
    [[ $VERBOSE ]] && echo "debug: nothing to do on payments $payment_id for $application_id of tenant $tenant_id ($tenant_slug)"
  ;;

esac
