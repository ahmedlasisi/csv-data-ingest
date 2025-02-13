# Use the Bref PHP 8.2 FPM development image
FROM bref/php-82-fpm-dev:latest

# Install MySQL client and any other tools
RUN apt-get update && \
    apt-get install -y default-mysql-client && \
    apt-get clean

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash && \
    mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# Set the working directory
WORKDIR /var/task

# Copy the application files to /var/task (this is where Bref expects the app)
COPY . /var/task

# Ensure cache and log directories are writable by any user (useful for local development)
RUN mkdir -p /var/task/var/cache /var/task/var/log && \
    chmod -R 777 /var/task/var/cache /var/task/var/log

# Expose port 9000
EXPOSE 9000

# Use the default PHP-FPM entrypoint
CMD ["php-fpm"]