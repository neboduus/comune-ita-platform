FROM ubuntu:18.04

RUN apt-get update
RUN apt-get install -y software-properties-common
RUN add-apt-repository ppa:ondrej/php
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y acl php7.3 php7.3-fpm php7.3-pgsql php7.3-dom wkhtmltopdf php7.3-curl php7.3-mbstring php7.3-gd php7.3-zip ghostscript curl git unzip python make g++ gnupg2
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y yarn
RUN ln -snf /usr/share/zoneinfo/Europe/Rome /etc/localtime
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer selfupdate 1.7.2
RUN curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.11/install.sh | bash

COPY . /docroot
WORKDIR /docroot

RUN ln -s /docroot/bin/console /docroot/app
RUN composer global require hirak/prestissimo && composer install

RUN yarn install
RUN yarn encore production

# Necessario per evitare: Unable to create the PID file (/run/php/php7.3-fpm.pid).: No such file or directory
# Da verificare
RUN mkdir /run/php

RUN chown www-data /docroot/var -R


CMD [ "php-fpm7.3" , "-R", "-F" ]


