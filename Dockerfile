FROM php:8.4-fpm AS base

# تنظیم دایرکتوری کاری
WORKDIR /var/www/html

# نصب وابستگی‌های سیستم [cite: 1]
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev \
    libzip-dev zip unzip libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# نصب اکستنشن‌های PHP [cite: 1, 2]
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache intl

# نصب و فعال‌سازی Redis [cite: 2]
RUN pecl install redis && docker-php-ext-enable redis

# نصب Composer [cite: 2]
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# تنظیم دسترسی کاربر [cite: 2]
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# کپی فایل‌های پروژه [cite: 3]
COPY --chown=www-data:www-data . /var/www/html

# نصب پکیج‌های لاراول [cite: 3]
RUN composer install --no-dev --optimize-autoloader --no-interaction

# تنظیم پرمیشن‌های لاراول [cite: 3]
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm"]