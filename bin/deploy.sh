#!/bin/bash
git pull
./bin/dockerized_css
docker-compose build
#OCSDC PATH is assumed to be ~
docker run --rm -v ~/ocsdc/web:/docroot/web -it ocsdc_ocsdc:latest /docroot/bin/console assets:install
docker-compose up -d
