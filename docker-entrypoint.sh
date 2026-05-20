#!/bin/bash
set -e

# If the database folder is empty (e.g., first mount), copy the default databases from the backup location
if [ ! -f /var/www/html/api/.anspanel.db ]; then
    echo "First run: Initializing database files from default templates..."
    mkdir -p /var/www/html/api
    cp -rp /var/www/html/api_backup/. /var/www/html/api/
fi

# If the uploads folder is empty, copy the default uploads from the backup location
if [ ! -d /var/www/html/uploads/themes ] || [ ! -d /var/www/html/uploads/splash ]; then
    echo "First run: Initializing upload directories from default templates..."
    mkdir -p /var/www/html/uploads
    cp -rp /var/www/html/uploads_backup/. /var/www/html/uploads/
fi

# Ensure correct permissions for Apache
echo "Ensuring correct permissions for Apache..."
chown -R www-data:www-data /var/www/html
chmod -R 775 /var/www/html

# Run the default Apache command
exec apache2-foreground
