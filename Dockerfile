# prepare assets for symfony
FROM node:14.21.2-slim as assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app

COPY package.json package.json yarn.lock yarn.lock webpack.config.js ./

RUN yarn install

COPY assets ./assets
RUN npx browserslist@latest --update-db && \
    yarn encore production
RUN ls -l public

# prepare the vendor dir for symfony
FROM wodby/php:${wodby_version:-7.4} as builder

USER root

WORKDIR /var/www/html

COPY ./composer.json ./composer.lock ./
RUN composer install --no-scripts --prefer-dist --no-suggest

# prepare the final image
FROM registry.gitlab.com/opencontent/php-caddy-prometheus:7.4-4.33.4

COPY Caddyfile /etc/caddy/Caddyfile

USER root
RUN apk add --no-cache jq httpie

# allow php to terminate gracefully during deployments
# https://www.goetas.com/blog/traps-on-the-way-of-blue-green-deployments/
RUN sed -i 's/;error_log = log\/php-fpm.log/error_log = \/proc\/self\/fd\/2/' /usr/local/etc/php-fpm.conf
RUN sed -i 's/;process_control_timeout = 0/process_control_timeout = 1m/' /usr/local/etc/php-fpm.conf
RUN sed -i 's/^access.log = \/proc\/self\/fd\/2/access.log = \/dev\/null/' /usr/local/etc/php-fpm.d/docker.conf

# Add utility to check healthness of php-fpm
ENV FCGI_STATUS_PATH=/php-status PHP_FPM_PM_STATUS_PATH=/php-status PHP_FPM_CLEAR_ENV=no
RUN curl https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck > /usr/local/bin/php-fpm-healthcheck && \
    chmod +x /usr/local/bin/php-fpm-healthcheck

RUN curl https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh> /usr/local/bin/wait-for-it && \
    chmod +x /usr/local/bin/wait-for-it

RUN curl https://raw.githubusercontent.com/birdayz/kaf/master/godownloader.sh | BINDIR=/usr/bin bash

USER wodby

COPY --from=builder /var/www/html/vendor /var/www/html/vendor

#ARG APP_ENV=prod
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

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin/
RUN chmod 755 ./bin/*.sh

RUN mkdir -p var/uploads && chown wodby:wodby var -R

#RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

HEALTHCHECK --interval=1m --timeout=3s \
        CMD /usr/local/bin/php-fpm-healthcheck

# lo script richiede che il file dei parametri sia già al suo posto
# RUN composer run-script post-install-cmd
RUN php bin/console assets:install public --no-cleanup

# il container wodby/php prevede una dir dove mettere script lanciati prima
# di far partire PHP-FPM
COPY compose_conf/php/init.d/*.sh /docker-entrypoint-init.d/

ENV PHP_DATE_TIMEZONE=Europe/Rome

# generate js transaltions file
RUN php bin/console bazinga:js-translation:dump public --format=js --pattern=/translations/{domain}.{_format}


