FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip libonig-dev libxml2-dev libzip-dev

RUN docker-php-ext-install pdo pdo_mysql mbstring zip pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN touch database/database.sqlite

RUN composer install --no-dev --optimize-autoloader
RUN php artisan key:generate || true
RUN php artisan migrate || true

EXPOSE 10000
CMD php artisan serve --host 0.0.0.0 --port 10000