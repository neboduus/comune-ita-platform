# prepare assets for symfony
FROM node:10.15.0 as assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app

COPY package.json package.json yarn.lock yarn.lock webpack.config.js ./

RUN yarn install

COPY assets ./assets
RUN yarn encore production
RUN ls -l web

# prepare the vendor dir for symfony
FROM wodby/php:7.3 as builder

USER root

WORKDIR /var/www/html

RUN composer global require hirak/prestissimo

COPY ./composer.json ./composer.lock ./

# app dir is required for classmaps entry in composer.json
COPY app ./app

RUN composer install --no-scripts --prefer-dist

# prepare the final image
FROM wodby/php:7.3

COPY --from=builder /var/www/html/vendor /var/www/html/vendor

#ARG SYMFONY_ENV=prod
ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

COPY --from=hashicorp/consul-template:alpine /bin/consul-template /bin/consul-template
COPY --from=consul:1.6 /bin/consul /bin/consul

COPY compose_conf/bin/init-consul-watch.sh /docker-entrypoint-init.d/
COPY compose_conf/php/init*.sh /docker-entrypoint-init.d/
COPY compose_conf/bin/*.sh /bin/

COPY --chown=wodby:wodby --from=assets /home/node/app/web /var/www/html/web
COPY --chown=wodby:wodby ./ .

RUN ./compose_conf/php/init-uploads.sh

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

# lo script richiede che il file dei parametri sia già al suo posto
RUN composer run-script post-docker-install-cmd

RUN bin/console cache:warmup
