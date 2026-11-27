FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsqlite3-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_sqlite zip opcache \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && cp .env.example .env \
    && sed -i 's/^APP_NAME=.*/APP_NAME="TriasePublik Makassar"/' .env \
    && sed -i 's/^APP_ENV=.*/APP_ENV=production/' .env \
    && sed -i 's/^APP_DEBUG=.*/APP_DEBUG=false/' .env \
    && sed -i 's/^APP_LOCALE=.*/APP_LOCALE=id/' .env \
    && sed -i 's/^SESSION_DRIVER=.*/SESSION_DRIVER=cookie/' .env \
    && sed -i 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env \
    && sed -i 's/^LOG_CHANNEL=.*/LOG_CHANNEL=stderr/' .env \
    && php artisan key:generate --force \
    && mkdir -p database storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && php artisan migrate --force \
    && php artisan db:seed --force \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod 666 /var/www/html/.env /var/www/html/database/database.sqlite

# Configure Apache DocumentRoot, AllowOverride All, and default port 7860
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf \
    && sed -i 's/Listen 80/Listen 7860/g' /etc/apache2/ports.conf \
    && sed -i 's/:80>/:7860>/g' /etc/apache2/sites-available/000-default.conf \
    && chmod -R 777 /var/run/apache2 /var/lock/apache2 /var/log/apache2 /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 7860

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
