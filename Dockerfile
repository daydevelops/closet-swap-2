FROM php:8.2-fpm-alpine

ARG APP_ENV=staging

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    zip \
    unzip \
    git \
    curl \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql exif pcntl bcmath gd

# PHP config
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini

# Install Composer
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN mkdir -p bootstrap/cache storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data bootstrap/cache storage \
    && chmod -R 775 bootstrap/cache storage \
    && if [ "$APP_ENV" = "production" ]; then \
         composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev; \
       else \
         composer install --no-interaction --prefer-dist --optimize-autoloader; \
       fi

EXPOSE 9000

CMD ["php-fpm"]
