FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.2-cli

WORKDIR /app

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
    && rm -rf /var/lib/apt/lists/*

COPY . /app
COPY --from=composer_deps /app/vendor /app/vendor

RUN mkdir -p /app/public/uploads /app/storage /app/logs \
    && chmod -R 775 /app/public/uploads /app/storage /app/logs

EXPOSE 8080

CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} -t public public/router.php"]
