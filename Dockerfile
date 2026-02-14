# Ultra-light PHP 8.2 with Laravel's built-in server
FROM php:8.2-cli-alpine

# Install only what Laravel NEEDS
RUN apk --no-cache add \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install \
    pdo_mysql \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Expose Laravel's default port
EXPOSE 8000

# Start Laravel's built-in server
CMD php artisan serve --host=0.0.0.0 --port=8000