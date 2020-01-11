#!/bin/bash
set -e

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
        CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';

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
EOSQL
