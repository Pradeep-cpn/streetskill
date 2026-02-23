FROM php:8.2-cli

# Install system deps (IMPORTANT)
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    sqlite3 \
    pkg-config

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring zip pdo_sqlite

# Install composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# create sqlite file
RUN mkdir -p database && touch database/database.sqlite

# install deps
RUN composer install --no-dev --optimize-autoloader

RUN php artisan key:generate || true
RUN php artisan migrate || true

EXPOSE 10000

CMD php artisan serve --host 0.0.0.0 --port 10000