FROM mcr.microsoft.com/devcontainers/php:8.2

# نصب پکیج‌های مورد نیاز لاراول
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# نصب Composer (رسمی)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
