FROM phpdockerio/php:8.5-fpm

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www

RUN apt-get update && \
    apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && composer init -n \
    && composer dump-autoload --optimize -n