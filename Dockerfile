# PHPWeave Production Dockerfile
# Optimized for performance with APCu caching

FROM php:8.4-apache

# Install system dependencies for multiple database drivers
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    libsqlite3-dev \
    unixodbc-dev \
    freetds-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions for multiple database support
# MySQL/MariaDB
RUN docker-php-ext-install pdo pdo_mysql mysqli

# PostgreSQL
RUN docker-php-ext-install pdo_pgsql

# SQLite (built-in, just enable PDO)
RUN docker-php-ext-install pdo_sqlite

# SQL Server (FreeTDS/ODBC)
RUN docker-php-ext-install pdo_dblib pdo_odbc

# Additional extensions
RUN docker-php-ext-install zip

# Install and configure APCu for route caching
RUN pecl install apcu && \
    docker-php-ext-enable apcu && \
    echo "apc.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini && \
    echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Create cache and storage directories with proper permissions
RUN mkdir -p cache storage storage/queue && \
    chown -R www-data:www-data cache storage && \
    chmod 755 cache storage storage/queue

# Set proper permissions for the application
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Configure Apache document root to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Set environment variables
ENV DOCKER_ENV=production
ENV PHPWEAVE_ENV=production

# Expose port 80
EXPOSE 80

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/health.php || exit 1

# Start Apache
CMD ["apache2-foreground"]
