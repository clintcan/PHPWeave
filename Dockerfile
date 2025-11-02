# PHPWeave Production Dockerfile
# Optimized for performance with APCu caching
# Security: Multi-stage build with vulnerability patching

FROM php:8.4-apache

# Security: Update package lists and upgrade all packages to patch vulnerabilities
RUN apt-get update && apt-get upgrade -y && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    libpq-dev \
    libsqlite3-dev \
    unixodbc \
    unixodbc-dev \
    freetds-dev \
    freetds-bin \
    # Security tools
    ca-certificates \
    curl \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get clean

# Install PHP extensions for multiple database support
# Core PDO
RUN docker-php-ext-install pdo

# MySQL/MariaDB
RUN docker-php-ext-install pdo_mysql mysqli

# PostgreSQL
RUN docker-php-ext-install pdo_pgsql

# SQLite (built-in, just enable PDO)
RUN docker-php-ext-install pdo_sqlite

# SQL Server via FreeTDS (dblib)
RUN docker-php-ext-install pdo_dblib

# ODBC with unixODBC configuration
RUN docker-php-ext-configure pdo_odbc --with-pdo-odbc=unixODBC,/usr && \
    docker-php-ext-install pdo_odbc

# Additional extensions
RUN docker-php-ext-install zip

# Install and configure APCu for route caching
RUN pecl install apcu && \
    docker-php-ext-enable apcu && \
    echo "apc.enabled=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini && \
    echo "apc.enable_cli=1" >> /usr/local/etc/php/conf.d/docker-php-ext-apcu.ini

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Security: Configure Apache security headers
RUN a2enmod headers && \
    echo "ServerTokens Prod" >> /etc/apache2/conf-available/security.conf && \
    echo "ServerSignature Off" >> /etc/apache2/conf-available/security.conf && \
    echo "TraceEnable Off" >> /etc/apache2/conf-available/security.conf && \
    echo "<IfModule mod_headers.c>" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "    Header always set X-Frame-Options \"DENY\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "    Header always set X-Content-Type-Options \"nosniff\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "    Header always set X-XSS-Protection \"1; mode=block\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "    Header always set Referrer-Policy \"strict-origin-when-cross-origin\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "    Header always set Permissions-Policy \"geolocation=(), microphone=(), camera=()\"" >> /etc/apache2/conf-available/security-headers.conf && \
    echo "</IfModule>" >> /etc/apache2/conf-available/security-headers.conf && \
    a2enconf security-headers

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Configure Apache document root to public directory
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create cache, storage, and logs directories with proper permissions
RUN mkdir -p cache storage storage/queue logs && \
    chown -R www-data:www-data cache storage logs && \
    chmod 755 cache storage storage/queue logs

# Set proper permissions for the application
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

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
