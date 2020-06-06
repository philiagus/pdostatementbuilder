FROM php:7.2-cli

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update

RUN pecl install xdebug-2.7.0 \
    && docker-php-ext-enable xdebug

RUN apt-get install -y git zip unzip

WORKDIR /app

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === 'c5b9b6d368201a9db6f74e2611495f369991b72d9c8cbd3ffbc63edff210eb73d46ffbfce88669ad33695ef77dc76976') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php  --install-dir=/usr/bin --filename=composer
RUN php -r "unlink('composer-setup.php');"


COPY ./ /app

RUN composer install --no-interaction