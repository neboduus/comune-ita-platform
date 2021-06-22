#!/bin/bash
set -e

psql -v ON_ERROR_STOP=0 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
        CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';

        CREATE DATABASE sdc_multi;
        GRANT ALL PRIVILEGES ON DATABASE sdc_multi TO $DB_USER;

        CREATE DATABASE sdc_vallelaghi;
        GRANT ALL PRIVILEGES ON DATABASE sdc_vallelaghi TO $DB_USER;

        CREATE DATABASE sdc_bugliano;
        GRANT ALL PRIVILEGES ON DATABASE sdc_bugliano TO $DB_USER;
EOSQL
