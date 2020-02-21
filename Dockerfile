FROM wodby/php:7.3

USER root

# Installo node e yarn per webpack encore
RUN apk add --no-cache --update nodejs npm yarn

USER wodby

# configurazione di composer
RUN composer global require hirak/prestissimo

#ARG SYMFONY_ENV=prod
ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

COPY --chown=wodby:wodby ./ .

COPY --chown=wodby:wodby ./compose_conf/bin/*.sh ./bin
RUN chmod 755 ./bin/*.sh

RUN ./compose_conf/php/init-uploads.sh

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

RUN composer install --no-scripts

#RUN bin/console cache:warmup

# lo script richiede che il file dei parametri sia già al suo posto
RUN composer run-script post-docker-install-cmd

# Compilo css e js
RUN yarn install && \
    yarn encore production

COPY compose_conf/php/init*.sh /docker-entrypoint-init.d/
