#!/bin/bash
[[ -n $DEBUG ]] && set -x

consul_prefix=${CONSUL_PREFIX:?"Missing env var CONSUL_PREFIX"}

pg_query() {
  cmd=$1
  psql -v ON_ERROR_STOP=1 \
        --username "$POSTGRES_USER" \
        --dbname "$POSTGRES_DB" \
        --command "$cmd"
}

ensure_db()
{
  name=$1
  if psql -U "$POSTGRES_USER" -tc "SELECT 1 FROM pg_database WHERE datname = '${name}'" | grep -q 1; then
    echo "Db ${name} already exists"
  else
    echo "Creating db ${name} ..."
    psql -U "$POSTGRES_USER" -c "CREATE DATABASE ${name}"
    pg_query "GRANT ALL PRIVILEGES ON DATABASE ${name} TO $POSTGRES_USER;"
  fi
}

#pg_query "CREATE USER $POSTGRES_USER WITH PASSWORD '$POSTGRES_PASSWORD';"

wait-for-it ${CONSUL_HTTP_ADDR:-'consul:8500'} --timeout=0 --strict

ensure_db sdc_multi

if [[ -n $CONSUL_PREFIX ]]; then

        tenants=$(consul kv get -keys ${consul_prefix}/ | sed "s#${consul_prefix}/##" | sed 's#/$##')
        for tenant in $tenants; do
                # db name can be specified in consul, if not specified a default name is used
                db_name=$(consul kv get ${consul_prefix}/${tenant}/config/content/parameters/database_name)
                [[ -z $db_name ]] && db_name=$(echo $tenant | sed 's/-//g')

                ensure_db $db_name
        done
else

databases="sdc_multi
sdc_rovereto
sdc_treville
sdc_vallelaghi
sdc_cavedine
sdc_cavedine
sdc_ala
sdc_fem
sdc_fem
sdc_folgaria
sdc_luserna
sdc_lavarone
sdc_bugliano
sdc_borgolares
sdc_canalsanbovo
sdc_imer
sdc_maniago
sdc_mezzano
sdc_mori"

  for item in $databases; do
    ensure_db $item
  done

fi

