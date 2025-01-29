# Use official PHP 8.2 + Apache image
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Set working directory
WORKDIR /var/www/symfony

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Expose port 8000
EXPOSE 8000

# Set entrypoint script
CMD ["apache2-foreground"]
