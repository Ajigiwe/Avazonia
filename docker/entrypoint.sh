#!/bin/bash
set -e

# Ensure upload directories exist and are writable (bind mounts may be empty)
mkdir -p /var/www/html/public/uploads/products \
         /var/www/html/public/uploads/categories \
         /var/www/html/public/uploads/sliders \
         /var/www/html/public/uploads/videos \
         /var/www/html/backups

# Fix ownership to www-data (Apache user). Ignore errors on Windows bind mounts.
chown -R www-data:www-data /var/www/html/public/uploads /var/www/html/backups 2>/dev/null || true
chmod -R 775 /var/www/html/public/uploads /var/www/html/backups 2>/dev/null || true

# If .env is missing, copy from .env.example (local)
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
  echo "[entrypoint] .env not found â€” copying from .env.example"
  cp /var/www/html/.env.example /var/www/html/.env
fi

exec "$@"
