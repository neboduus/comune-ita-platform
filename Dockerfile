
FROM ubuntu:18.04
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y acl php php7.2-fpm php-pgsql php-dom wkhtmltopdf php-curl php-mbstring php-gd php-zip ghostscript curl git unzip python make g++
RUN ln -snf /usr/share/zoneinfo/Europe/Rome /etc/localtime
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer selfupdate 1.7.2
RUN curl -o- https://raw.githubusercontent.com/creationix/nvm/v0.33.11/install.sh | bash

COPY . /docroot
WORKDIR /docroot

RUN ln -s /docroot/bin/console /docroot/app
RUN composer global require hirak/prestissimo && composer install

CMD [ "php-fpm7.2" , "-R", "-F" ]
