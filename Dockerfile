FROM wodby/php:7.3

USER root

# Installo node e yarn per webpack encore
RUN apk add --update nodejs npm
RUN apk add --update yarn

USER wodby

# configurazione di composer
RUN composer global require hirak/prestissimo

#ARG SYMFONY_ENV=prod
ENV PHP_FPM_USER=wodby
ENV PHP_FPM_GROUP=wodby

WORKDIR /var/www/html

# prendiamo i file dal primo container creati in /srv/ e li metto in $WORKDIR
#COPY --from=builder /srv/. ./

# FIXME: per non ripetere ogni volta il download delle dipendenze del progetto
# il composer install dovrebbe avvenire prima della copia del codice, ma
# facendolo si ottiene un errore, probabilmente c'e' qualcosa nel composer
# install che dipende dall'applicazione. Scommentare per credere.
#COPY composer.json composer.lock ./ 
#RUN composer install --no-scripts

COPY --chown=wodby:wodby ./ .

RUN cp app/config/parameters.tpl.yml app/config/parameters.yml

#RUN sleep 3600;

RUN composer install --no-scripts

# lo script richiede che il file dei parametri sia già al suo posto
RUN composer run-script post-docker-install-cmd

# Compilo css e js
RUN yarn install
RUN yarn encore production


COPY compose_conf/php/init-db.sh /docker-entrypoint-init.d/
COPY compose_conf/php/init-demo.sh /docker-entrypoint-init.d/

