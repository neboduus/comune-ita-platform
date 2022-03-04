# prepare assets for symfony
FROM node:10.22.1 as assets

RUN mkdir -p /home/node/app
WORKDIR /home/node/app

COPY package.json package.json yarn.lock yarn.lock webpack.config.js ./

RUN yarn install

COPY assets ./assets
RUN yarn encore production
RUN ls -l web

# prepare the vendor dir for symfony
FROM wodby/php:${wodby_version:-7.3} as builder

USER root

WORKDIR /var/www/html

COPY ./composer.json ./composer.lock ./

# app dir is required for classmaps entry in composer.json
COPY app ./app

RUN composer install --no-scripts --prefer-dist --no-suggest

# prepare the final image
FROM wodby/php:${wodby_version:-7.3} 

USER root
RUN apk add --no-cache jq httpie

# allow php to terminate gracefully during deployments
# https://www.goetas.com/blog/traps-on-the-way-of-blue-green-deployments/
RUN sed -i 's/;process_control_timeout = 0/process_control_timeout = 1m/' /usr/local/etc/php-fpm.conf
RUN sed -i 's/^access.log =(.*)/access.log = \/dev\/null/' /usr/local/etc/php-fpm.d/docker.conf

USER wodby

COPY --from=builder /var/www/html/vendor /var/www/html/vendor

#ARG SYMFONY_ENV=prod
ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

COPY --chown=wodby:wodby ./ .
COPY --chown=wodby:wodby --from=assets /home/node/app/web /var/www/html/web

# Add version file
ARG CI_COMMIT_REF_NAME=no-branch
ARG CI_COMMIT_SHORT_SHA=1234567
ARG CI_COMMIT_TAG
RUN chmod 755 ./compose_conf/scripts/*.sh
RUN ./compose_conf/scripts/get-version.sh > /var/www/html/web/VERSION

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin
RUN chmod 755 ./bin/*.sh

RUN mkdir -p var/uploads && chown wodby:wodby var -R

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

# Add utility to check healthness of php-fpm
ENV FCGI_STATUS_PATH=/php-status PHP_FPM_PM_STATUS_PATH=/php-status PHP_FPM_CLEAR_ENV=no
RUN curl https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck > /usr/local/bin/php-fpm-healthcheck && \
    chmod +x /usr/local/bin/php-fpm-healthcheck
HEALTHCHECK --interval=1m --timeout=3s \
        CMD /usr/local/bin/php-fpm-healthcheck

# lo script richiede che il file dei parametri sia già al suo posto
RUN composer run-script post-docker-install-cmd

# il container wodby/php prevede una dir dove mettere script lanciati prima
# di far partire PHP-FPM
COPY compose_conf/php/init.d/*.sh /docker-entrypoint-init.d/

RUN curl https://raw.githubusercontent.com/vishnubob/wait-for-it/master/wait-for-it.sh> /usr/local/bin/wait-for-it && \
    chmod +x /usr/local/bin/wait-for-it

ENV LOGS_PATH=php://stderr PHP_DATE_TIMEZONE=Europe/Rome

