#!/bin/bash
set -e

# Wait for the database to be ready
dockerize -wait tcp://db:3306 -timeout 60s

if [ "$APP_ENV" == "prod" ]; then
    php artisan migrate --force
else
    php artisan migrate:fresh
    php artisan db:seed --force
fi

# Start PHP-FPM
php-fpm
