FROM php:8.3-cli-alpine

# Install system dependencies and PostgreSQL development libraries
RUN apk update && apk add --no-cache \
    postgresql-dev \
    postgresql-client \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    linux-headers

# Install PHP extensions required for Laravel 13 & PostgreSQL 18
RUN docker-php-ext-install pdo pdo_pgsql zip bcmath pcntl

# Install latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 8000

# Default command to serve Laravel application safely without re-seeding on restart
CMD ["sh", "-c", "composer install --no-interaction && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8000"]
