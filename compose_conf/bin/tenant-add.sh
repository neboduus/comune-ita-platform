#!/bin/bash

prefix=${CONSUL_PREFIX:?"Missing env var CONSUL_PREFIX"}

tenant=${1:?"Missing parameter <TENANT> (ex: comune-di-bugliano)"}

tenant_keys='config/protocollo
content/parameters/database_name
content/parameters/codice_meccanografico
content/parameters/prefix'

echo -e "Configuring tenant ${tenant}"

for key in $tenant_keys; do
        echo -e "\n\nEnter the value for $key"
        full_key="${prefix}/${tenant}/${key}"
        read value
        consul kv put "$full_key" $value
        if [[ $? -gt 0 ]]; then
                echo "ERROR, trying to set '$full_key' to '$value'"
        fi
done

