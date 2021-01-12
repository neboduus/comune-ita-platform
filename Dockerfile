# prepare assets for symfony
FROM node:10.22.1 as assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app

COPY package.json package.json yarn.lock yarn.lock webpack.config.js ./

RUN yarn install

COPY assets ./assets
RUN yarn encore production
RUN ls -l public

# prepare the vendor dir for symfony
FROM wodby/php:7.3 as builder

USER root

WORKDIR /var/www/html

COPY ./composer.json ./composer.lock ./

# app dir is required for classmaps entry in composer.json
COPY app ./app

RUN composer install --no-scripts --prefer-dist --no-suggest

# prepare the final image
FROM wodby/php:7.3

COPY --from=builder /var/www/html/vendor /var/www/html/vendor

#ARG SYMFONY_ENV=prod
ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

COPY --chown=wodby:wodby ./ .
COPY --chown=wodby:wodby --from=assets /home/node/app/public /var/www/html/public

# Add version file
ARG CI_COMMIT_REF_NAME=no-branch
ARG CI_COMMIT_SHORT_SHA=1234567
ARG CI_COMMIT_TAG
RUN chmod 755 ./compose_conf/scripts/*.sh
RUN ./compose_conf/scripts/get-version.sh > /var/www/html/public/VERSION

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin
RUN chmod 755 ./bin/*.sh

RUN ./compose_conf/php/init-uploads.sh

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

# lo script richiede che il file dei parametri sia già al suo posto
#RUN composer run-script post-docker-install-cmd

COPY compose_conf/php/init*.sh /docker-entrypoint-init.d/

# Todo: da errore di memoria verificare
#RUN bin/console cache:warmup
