FROM php:8.2-cli

RUN apt-get update

RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

RUN apt-get install -y git zip unzip

WORKDIR /app

COPY ./ /app

RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"
