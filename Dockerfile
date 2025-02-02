# Use official PHP 8.2 + Apache image
FROM php:8.2-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libonig-dev libxml2-dev default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql

# Set working directory
WORKDIR /var/www/symfony

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Set Apache DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/symfony/public|g' /etc/apache2/sites-available/000-default.conf

# Ensure permissions
RUN mkdir -p /var/www/symfony/var/cache /var/www/symfony/var/logs \
    && chown -R www-data:www-data /var/www/symfony/var \
    && chmod -R 775 /var/www/symfony/var

# Expose port 8000
EXPOSE 9000

# Set entrypoint script
CMD ["apache2-foreground"]
