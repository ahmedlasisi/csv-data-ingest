# Use official PHP 8.2 + Apache image
FROM php:8.2-apache

# Install dependencies

RUN apt-get update && apt-get install -y \
    libxml2-dev \
    libpq-dev \
    libpq-dev \
    libonig-dev \
    libzip-dev \
    git \
    unzip \
    mariadb-client \ 
    && docker-php-ext-install pdo pdo_mysql zip

# Set working directory
WORKDIR /var/www/symfony

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy entrypoint script
COPY /docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh

# Enable Apache Rewrite Module
RUN a2enmod rewrite

# Set Apache DocumentRoot
RUN sed -i 's|/var/www/html|/var/www/symfony/public|g' /etc/apache2/sites-available/000-default.conf

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf


# Ensure permissions
RUN mkdir -p /var/www/symfony/var/cache /var/www/symfony/var/logs \
    && chown -R www-data:www-data /var/www/symfony/var \
    && chmod -R 775 /var/www/symfony/var


RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Expose port 8000
EXPOSE 8000

# Set entrypoint script
CMD ["public/index.php"]
