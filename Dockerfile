
FROM ubuntu:18.04
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y php php-pgsql php-dom wkhtmltopdf php-curl php-mbstring php-gd php-zip ghostscript curl git unzip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
COPY . /usr/src/myapp
WORKDIR /usr/src/myapp
RUN composer install
CMD [ "php", "bin/phpunit" ]
