FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.2-apache

WORKDIR /var/www/html

RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libzip-dev \
    unzip \
    zip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY .docker/apache-vhost.conf /etc/apache2/sites-available/000-default.conf

COPY . /var/www/html
COPY --from=composer_deps /app/vendor /var/www/html/vendor

RUN mkdir -p /var/www/html/public/uploads /var/www/html/storage /var/www/html/logs \
    && chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/storage /var/www/html/logs

EXPOSE 80

