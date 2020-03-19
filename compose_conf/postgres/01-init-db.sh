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

pg_query "CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';"
pg_query "CREATE DATABASE sdc_multi;"
pg_query "GRANT ALL PRIVILEGES ON DATABASE sdc_multi TO $DB_USER;"

if [[ -n $CONSUL_PREFIX ]]; then

        tenants=$(consul kv get -keys ${consul_prefix}/ | sed "s#${consul_prefix}/##" | sed 's#/$##')
        for tenant in $tenants; do
                # db name can be specified in consul, if not specified a default name is used
                db_name=$(consul kv get ${consul_prefix}/${tenant}/config/content/parameters/database_name)
                [[ -z $db_name ]] && db_name=$(echo $tenant | sed 's/-//g')

                echo "==> Configuring tenant ${tenant}..."
                pg_query "CREATE DATABASE ${db_name};"
		pg_query "GRANT ALL PRIVILEGES ON DATABASE ${db_name} TO $DB_USER;"
        done

else

  psql -v ON_ERROR_STOP=1 \
        --username "$POSTGRES_USER" \
        --dbname "$POSTGRES_DB" <<-EOSQL

	CREATE DATABASE sdc_multi;
        GRANT ALL PRIVILEGES ON DATABASE sdc_multi TO $DB_USER;

        CREATE DATABASE sdc_rovereto;
        GRANT ALL PRIVILEGES ON DATABASE sdc_rovereto TO $DB_USER;

        CREATE DATABASE sdc_treville;
        GRANT ALL PRIVILEGES ON DATABASE sdc_treville TO $DB_USER;

        CREATE DATABASE sdc_vallelaghi;
        GRANT ALL PRIVILEGES ON DATABASE sdc_vallelaghi TO $DB_USER;

        CREATE DATABASE sdc_cavedine;
        GRANT ALL PRIVILEGES ON DATABASE sdc_cavedine TO $DB_USER;

        CREATE DATABASE sdc_ala;
        GRANT ALL PRIVILEGES ON DATABASE sdc_ala TO $DB_USER;

        CREATE DATABASE sdc_fem;
        GRANT ALL PRIVILEGES ON DATABASE sdc_fem TO $DB_USER;

        CREATE DATABASE sdc_folgaria;
        GRANT ALL PRIVILEGES ON DATABASE sdc_folgaria TO $DB_USER;

        CREATE DATABASE sdc_luserna;
        GRANT ALL PRIVILEGES ON DATABASE sdc_luserna TO $DB_USER;

        CREATE DATABASE sdc_lavarone;
        GRANT ALL PRIVILEGES ON DATABASE sdc_lavarone TO $DB_USER;

        CREATE DATABASE sdc_bugliano;
        GRANT ALL PRIVILEGES ON DATABASE sdc_bugliano TO $DB_USER;

        CREATE DATABASE sdc_borgolares;
        GRANT ALL PRIVILEGES ON DATABASE sdc_borgolares TO $DB_USER;

        CREATE DATABASE sdc_canalsanbovo;
        GRANT ALL PRIVILEGES ON DATABASE sdc_canalsanbovo TO $DB_USER;

        CREATE DATABASE sdc_imer;
        GRANT ALL PRIVILEGES ON DATABASE sdc_imer TO $DB_USER;

        CREATE DATABASE sdc_maniago;
        GRANT ALL PRIVILEGES ON DATABASE sdc_maniago TO $DB_USER;

        CREATE DATABASE sdc_mezzano;
        GRANT ALL PRIVILEGES ON DATABASE sdc_mezzano TO $DB_USER;

        CREATE DATABASE sdc_mori;
        GRANT ALL PRIVILEGES ON DATABASE sdc_mori TO $DB_USER;
        
	CREATE DATABASE sdc_borgolares;
        GRANT ALL PRIVILEGES ON DATABASE sdc_borgolares TO $DB_USER;
EOSQL

fi
