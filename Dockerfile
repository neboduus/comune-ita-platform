# prepare assets for symfony
FROM node:10.15.0 as assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app

COPY package.json yarn.lock webpack.config.js ./

RUN yarn install

COPY assets ./assets
RUN yarn encore production
RUN ls -l public


# install dependencies
FROM composer:1.8.4 as composer

RUN rm -rf /var/www && mkdir /var/www
WORKDIR /var/www

COPY composer.* /var/www/
COPY symfony.lock /var/www/

ARG APP_ENV=prod

RUN set -xe \
    #&& if [ "$APP_ENV" = "prod" ]; then export ARGS="--no-dev"; fi \
    && composer install --prefer-dist --no-scripts --no-progress --no-suggest --no-interaction $ARGS

COPY . /var/www

RUN composer dump-autoload --classmap-authoritative


# build
FROM wodby/php:7.3

ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

ARG APP_ENV=prod
ARG APP_DEBUG=0

ENV APP_ENV $APP_ENV
ENV APP_DEBUG $APP_DEBUG

COPY --chown=wodby:wodby ./.env /var/www/html/.env
COPY --chown=wodby:wodby ./assets /var/www/html/assets
COPY --chown=wodby:wodby ./bin /var/www/html/bin
COPY --chown=wodby:wodby ./config /var/www/html/config
COPY --chown=wodby:wodby ./src /var/www/html/src
COPY --chown=wodby:wodby ./templates /var/www/html/templates
COPY --chown=wodby:wodby ./translations /var/www/html/translations
#COPY --chown=wodby:wodby ./var /var/www/html/var
COPY --chown=wodby:wodby ./public/* /var/www/html/public/
COPY --chown=wodby:wodby --from=composer /var/www/vendor /var/www/html/vendor
COPY --chown=wodby:wodby --from=assets /home/node/app/public /var/www/html/public

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin
RUN chmod 755 ./bin/*.sh

COPY compose_conf/php/init*.sh /docker-entrypoint-init.d/
