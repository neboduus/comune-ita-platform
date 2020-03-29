#!/bin/bash
[[ -n $DEBUG ]] && set -x

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

if [[ -n $CONSUL_PREFIX ]]; then
        tenants=$(consul kv get -keys ${CONSUL_PREFIX}/ | sed "s#${CONSUL_PREFIX}/##" | sed 's#/$##')
        for tenant in $tenants; do
                # db name can be specified in consul, if not specified a default name is used
                db_name=$(consul kv get ${CONSUL_PREFIX}/${tenant}/config/content/parameters/database_name)
                [[ -z $db_name ]] && db_name=$(echo $tenant | sed 's/-//g')

                ensure_db $db_name
        done
fi