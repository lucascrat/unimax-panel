FROM php:8.1-apache

# Install SQLite development packages and system utilities
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    unzip \
    && docker-php-ext-install pdo pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache Mod Rewrite
RUN a2enmod rewrite

# Configure Apache to allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy the application code
COPY htvv/ /var/www/html/

# Create backups of database and uploads directories to seed persistent volumes on first run
RUN mkdir -p /var/www/html/api_backup /var/www/html/uploads_backup \
    && cp -rp /var/www/html/api/. /var/www/html/api_backup/ \
    && cp -rp /var/www/html/uploads/. /var/www/html/uploads_backup/

# Create a symlink from htvv to the root (.) so both root domain and subfolder /htvv work perfectly
RUN ln -s . htvv

# Copy and setup the entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Ensure correct permissions for Apache
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Expose port 80
EXPOSE 80

# Use the custom entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
