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

COPY --chown=wodby:wodby ./ .

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin
RUN chmod 755 ./bin/*.sh

RUN ./compose_conf/php/init-uploads.sh

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

# lo script richiede che il file dei parametri sia già al suo posto
RUN composer run-script post-docker-install-cmd

COPY compose_conf/php/init*.sh /docker-entrypoint-init.d/

RUN bin/console cache:warmup
