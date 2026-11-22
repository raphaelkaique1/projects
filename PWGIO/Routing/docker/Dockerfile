FROM php:8.6.0-fpm

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www

RUN apt-get update && \
    apt-get install -y libonig-dev && \
    docker-php-ext-install mbstring && \
    composer init -n && \
    composer dump-autoload --optimize -n