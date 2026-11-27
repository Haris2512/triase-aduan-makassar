#!/usr/bin/env bash
set -e

PORT="${PORT:-7860}"

if [ "${PORT}" != "7860" ] && [ -w /etc/apache2/ports.conf ]; then
    sed -i "s/Listen 7860/Listen ${PORT}/g" /etc/apache2/ports.conf
    sed -i "s/:7860>/:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
fi

if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground
