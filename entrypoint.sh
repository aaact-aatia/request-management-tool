#!/bin/sh
set -eu

UPLOAD_PATH="${FILE_STORAGE_LOCAL_PATH:-/var/uploads/rmt}"
mkdir -p "$UPLOAD_PATH"
chown -R www-data:www-data "$UPLOAD_PATH" 2>/dev/null || true
chmod -R u+rwX,g+rwX "$UPLOAD_PATH" 2>/dev/null || true

exec docker-php-entrypoint apache2-foreground
