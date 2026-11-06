FROM php:8.5.12-debian13-fpm-fips

RUN apt-get update && \
    apt-get install -y \
    git \
    curl \
    zip \
    unzip

WORKDIR /var/www