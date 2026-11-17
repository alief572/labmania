FROM node:24-bookworm-slim AS frontend
WORKDIR /build
COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts
COPY . .
ARG VITE_BASE=
RUN VITE_BASE="$VITE_BASE" npm run build

FROM php:8.5-apache
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libicu-dev libonig-dev \
    libcurl4-openssl-dev libxml2-dev default-mysql-client unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl mbstring pdo_mysql zip bcmath curl xmlwriter exif \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/laravel
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/app/private/documents storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache
COPY --from=frontend /build/public/build ./public/build
COPY rehearsal/apache.conf /etc/apache2/sites-available/000-default.conf
