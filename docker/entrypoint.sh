#!/usr/bin/env bash
set -e

PORT="${PORT:-7860}"

sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

chown -R www-data:www-data /var/www/html/database /var/www/html/storage /var/www/html/bootstrap/cache

if [ -z "${APP_KEY}" ] && ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
    php artisan key:generate --force
fi

php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground
