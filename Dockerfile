# Build stage
FROM php:8.4-fpm AS base

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip unzip libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache intl

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set proper permissions
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Copy application files
COPY --chown=www-data:www-data . /var/www/html

# Switch to www-data user
USER www-data

# Expose port 9000
EXPOSE 9000

CMD ["php-fpm"]

# Production stage
FROM base AS production
RUN composer install --no-dev --optimize-autoloader --no-interaction