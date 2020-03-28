#!/bin/bash

if [[ -n $CONSUL_PREFIX ]]; then
    echo "==> Getting tenants configuration from Consul @ ${CONSUL_HTTP_ADDR}"

    wait-for-it ${CONSUL_HTTP_ADDR:-'consul:8500'} --strict --timeout=0 -- /bin/tenants-config.sh
    consul watch -type keyprefix -prefix ${CONSUL_PREFIX} /bin/tenants-config.sh &
fi
