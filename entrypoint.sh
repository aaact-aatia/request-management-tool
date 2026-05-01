#!/bin/bash
set -e

# Install dependencies if vendor/ doesn't exist
if [ ! -d /var/www/html/vendor ]; then
  composer install --no-dev --no-interaction --working-dir=/var/www/html
fi

# Start Apache
exec docker-php-entrypoint apache2-foreground
