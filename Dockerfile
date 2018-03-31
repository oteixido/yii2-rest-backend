FROM php:7.1-cli
RUN apt-get update && \
    apt-get install -y git unzip libcurl4-gnutls-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    docker-php-ext-install curl
