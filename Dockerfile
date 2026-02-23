FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libonig-dev libxml2-dev libzip-dev \
    libsqlite3-dev sqlite3 pkg-config nodejs npm

RUN docker-php-ext-install pdo pdo_mysql mbstring zip pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN mkdir -p database && touch database/database.sqlite

RUN composer install --no-dev --optimize-autoloader

RUN php artisan config:clear || true
RUN php artisan cache:clear || true
RUN php artisan view:clear || true
RUN php artisan storage:link || true

RUN npm install || true
RUN npm run build || true

RUN chmod -R 777 storage bootstrap/cache

EXPOSE 10000

# Render provides $PORT; fall back to 10000 for local runs.
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
