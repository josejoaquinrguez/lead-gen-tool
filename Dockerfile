FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-install curl pdo_mysql mysqli \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . /var/www/html

RUN mkdir -p storage/cache storage/cache/websites storage/debug \
    && chown -R www-data:www-data storage

EXPOSE 80
