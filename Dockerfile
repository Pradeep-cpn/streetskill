FROM php:8.2-cli

# Install system dependencies (IMPORTANT)
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libsqlite3-dev \
    sqlite3

# Install PHP extensions
RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mbstring \
    zip \
    pdo_sqlite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy project
COPY . .

# Create sqlite DB
RUN mkdir -p database && touch database/database.sqlite

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Generate key (ignore error if exists)
RUN php artisan key:generate || true

# Run migrations (ignore error)
RUN php artisan migrate || true

# Expose port
EXPOSE 10000

# Start Laravel
CMD php artisan serve --host 0.0.0.0 --port 10000