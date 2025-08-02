# Composer stage
FROM composer:2 as composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Application stage
FROM php:8.1-fpm-alpine

WORKDIR /var/www

# Install required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Copy application files
COPY . /var/www
COPY --from=composer /app/vendor/ /var/www/vendor/

# Set permissions
RUN chown -R www-data:www-data /var/www/var

EXPOSE 9000
