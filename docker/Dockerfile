FROM php:8.2-fpm-alpine

WORKDIR /var/www

RUN apk add --no-cache \
    nginx \
    curl \
    git \
    unzip \
    libzip-dev \
    postgresql-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev

RUN docker-php-ext-install pdo pdo_pgsql pgsql zip intl bcmath

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN mkdir -p /var/www/var /var/www/public /run/nginx /var/cache/nginx/client_temp

COPY nginx/default.conf /etc/nginx/http.d/default.conf

RUN chown -R www-data:www-data /var/www /var/cache/nginx /run/nginx

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s --start-period=10s --retries=3 \
    CMD curl -f http://localhost/_health || exit 1

COPY --chown=www-data:www-data . /var/www

RUN composer install --no-dev --optimize-autoloader || true

USER www-data

CMD ["php-fpm"]