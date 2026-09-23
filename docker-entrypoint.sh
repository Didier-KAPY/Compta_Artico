#!/bin/sh
set -e

APP_PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${APP_PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${APP_PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan optimize:clear
php artisan migrate --force
php artisan config:cache
php artisan view:cache

exec apache2-foreground
