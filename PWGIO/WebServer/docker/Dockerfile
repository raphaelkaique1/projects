FROM phpdockerio/php:8.5-fpm

RUN apt-get update && \
    apt-get install -y \
    git \
    curl \
    zip \
    unzip

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

WORKDIR /var/www