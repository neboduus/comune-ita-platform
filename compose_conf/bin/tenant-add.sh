#!/bin/bash

prefix=${CONSUL_PREFIX:?"Missing env var CONSUL_PREFIX"}

tenant=${1:?"Missing parameter <TENANT> (ex: comune-di-bugliano)"}
db_name=$(echo $tenant | sed 's/-//g')
# keys needed and respective default value
# leave empty a value to make it a free but required key
declare -A tenant_keys
tenant_keys['config/env']=prod
tenant_keys['config/protocollo']=pitre
tenant_keys['config/email']=no-reply@opencontent.it
tenant_keys['config/content/parameters/database_name']=$db_name
tenant_keys['config/content/parameters/codice_meccanografico']=
tenant_keys['config/content/parameters/prefix']=$tenant

echo -e "\n==> Configuring tenant ${tenant}"

for key in "${!tenant_keys[@]}"; do
        echo -e "\n\nEnter the value for $key [default: ${tenant_keys[$key]}]"
        full_key="${prefix}/${tenant}/${key}"
        read value
	[[ -z $value ]] && value=${tenant_keys[$key]}
	if [[ -z $value ]]; then
		echo "Key ${key} cannot be empty, sorry cannot continue"
		exit 1
	fi
	
        consul kv put "$full_key" $value
        if [[ $? -gt 0 ]]; then
                echo "ERROR, trying to set '$full_key' to '$value'"
		exit 2
        fi
done

