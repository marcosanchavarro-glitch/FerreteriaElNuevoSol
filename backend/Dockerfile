FROM php:8.4-apache-bookworm AS php-base
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev \
    libicu-dev libonig-dev libzip-dev unzip git ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql pdo_mysql gd intl mbstring zip opcache \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

FROM php-base AS dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress

FROM php-base AS tests
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist --no-progress
COPY . .
RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-interaction \
    && php artisan test --compact

FROM php-base AS production
COPY . .
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY deploy/apache.conf /etc/apache2/sites-available/000-default.conf
COPY deploy/php.ini /usr/local/etc/php/conf.d/production.ini
RUN printf 'Listen ${PORT}\n' > /etc/apache2/ports.conf \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && composer dump-autoload --no-dev --classmap-authoritative --no-interaction \
    && chmod +x deploy/start.sh \
    && chown -R www-data:www-data storage bootstrap/cache
ENV PORT=10000
EXPOSE 10000
CMD ["sh", "deploy/start.sh"]
