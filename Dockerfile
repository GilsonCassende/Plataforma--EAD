FROM composer:2 AS composer_deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM node:22-bookworm-slim AS node_deps

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --omit=dev

FROM php:8.2-cli

WORKDIR /app

RUN apt-get update && apt-get install -y \
    chromium \
    nodejs \
    ffmpeg \
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
COPY --from=node_deps /app/node_modules /app/node_modules

RUN mkdir -p /app/bootstrap_uploads /app/public/uploads /app/storage /app/storage/backups /app/logs /tmp/plataforma-ead-lesson-audio \
    && cp -a /app/public/uploads/. /app/bootstrap_uploads/ 2>/dev/null || true \
    && chmod -R 775 /app/public/uploads /app/storage /app/logs /tmp/plataforma-ead-lesson-audio

ENV CHROME_BIN=/usr/bin/chromium
ENV NODE_BIN=/usr/bin/node
ENV STORAGE_DISK=local
ENV STORAGE_LOCAL_ROOT=/app/storage/backups
ENV LESSON_AUDIO_TEMP_DIR=/tmp/plataforma-ead-lesson-audio

EXPOSE 8080

CMD ["sh", "-lc", "PHP_CLI_SERVER_WORKERS=${PHP_CLI_SERVER_WORKERS:-4} php -S 0.0.0.0:${PORT:-8080} -t public public/router.php"]
