#!/bin/sh
set -e

# Default Render port to 10000 if not provided
PORT="${PORT:-10000}"
export PORT

echo "==> Starting OmPraMani Laravel 13 on Render (Port: ${PORT})..."

# Substitute PORT in Nginx configuration
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Ensure writable directories for Laravel
mkdir -p /var/www/html/storage/framework/cache/data \
         /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Production optimizations if APP_KEY is present
if [ -n "$APP_KEY" ]; then
    echo "==> Warming up Laravel caches..."
    php artisan package:discover --ansi
    php artisan config:cache --ansi || true
    php artisan route:cache --ansi || true
    php artisan view:cache --ansi || true
fi

# Ensure storage link exists
php artisan storage:link --force || true

echo "==> Starting PHP-FPM..."
php-fpm -D

echo "==> Starting Nginx..."
exec nginx -g 'daemon off;'
