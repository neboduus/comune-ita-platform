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

        CREATE DATABASE sdc_castellotesino;
        GRANT ALL PRIVILEGES ON DATABASE sdc_castellotesino TO $DB_USER;

        CREATE DATABASE sdc_ledro;
        GRANT ALL PRIVILEGES ON DATABASE sdc_ledro TO $DB_USER;

        CREATE DATABASE sdc_moena;
        GRANT ALL PRIVILEGES ON DATABASE sdc_moena TO $DB_USER;
EOSQL
