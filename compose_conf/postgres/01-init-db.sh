#!/bin/bash
set -e

# Non e' più usato, il db viene creato all'avvio del postgres

psql -v ON_ERROR_STOP=0 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
        CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';

        CREATE DATABASE sdc_bugliano;
        GRANT ALL PRIVILEGES ON DATABASE sdc_bugliano TO $DB_USER;
EOSQL
