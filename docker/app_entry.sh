#!/bin/sh
set -e

# Install dependencies if vendor directory is missing (e.g. first run with volume mount)
if [ ! -d "vendor" ]; then
    composer install --no-interaction --prefer-dist
fi

# Wait for the database to be ready
echo "Waiting for database..."
until nc -z "${DB_HOST:-db}" 3306; do
  sleep 1
done
echo "Database ready."

if [ "$APP_ENV" = "production" ]; then
    php artisan migrate --force
else
    php artisan migrate:fresh
    php artisan db:seed --force
fi

# Start PHP-FPM
php-fpm
