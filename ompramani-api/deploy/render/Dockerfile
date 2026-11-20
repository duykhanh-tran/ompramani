# ==============================================================================
# OMPRAMANI LARAVEL 13 BACKEND — RENDER DOCKERFILE
# Multi-stage production build (PHP 8.4-fpm-alpine + Nginx)
# Compatible with Render Web Services, Neon PostgreSQL & Cloudflare R2
# ==============================================================================

# ------------------------------------------------------------------------------
# STAGE 1: COMPOSER BUILDER
# ------------------------------------------------------------------------------
FROM composer:2.8 AS composer-stage

WORKDIR /app

# Copy dependency definition files first for optimal layer caching
COPY composer.json composer.lock ./

# Install production dependencies without running scripts
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-scripts \
    --no-autoloader \
    --ignore-platform-req=ext-gd \
    --ignore-platform-req=ext-intl \
    --ignore-platform-req=ext-zip \
    --ignore-platform-req=ext-bcmath \
    --ignore-platform-req=ext-pdo_pgsql \
    --ignore-platform-req=ext-pgsql

# Copy application source code
COPY . .

# Ensure storage framework directories exist before generating classmap
RUN mkdir -p storage/framework/cache/data \
             storage/framework/sessions \
             storage/framework/views \
             storage/logs \
             bootstrap/cache

# Generate optimized autoload classmap without running scripts in builder
# (Artisan scripts and Filament asset compilation run in Stage 2 with full PHP 8.4 extensions)
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ------------------------------------------------------------------------------
# STAGE 2: PRODUCTION RUNTIME
# ------------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS runtime

# Install system dependencies & build packages
RUN apk add --no-cache \
    nginx \
    gettext \
    curl \
    ca-certificates \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        gd \
        zip \
        opcache \
        exif \
        pcntl

# Production PHP & Opcache configuration
RUN { \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=10000'; \
        echo 'opcache.revalidate_freq=0'; \
        echo 'opcache.validate_timestamps=0'; \
        echo 'opcache.enable_cli=1'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini \
    && { \
        echo 'upload_max_filesize=32M'; \
        echo 'post_max_size=32M'; \
        echo 'memory_limit=256M'; \
        echo 'max_execution_time=60'; \
        echo 'expose_php=Off'; \
    } > /usr/local/etc/php/conf.d/production.ini

WORKDIR /var/www/html

# Copy application from builder stage
COPY --from=composer-stage /app /var/www/html

# Copy Nginx template and entrypoint script
COPY deploy/render/nginx.conf /etc/nginx/templates/default.conf.template
COPY deploy/render/entrypoint.sh /usr/local/bin/entrypoint.sh

# Create all writable storage and cache directories
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/bootstrap/cache \
             /var/www/html/public/css/filament \
             /var/www/html/public/js/filament \
             /var/www/html/public/fonts/filament \
             /var/www/html/public/vendor/livewire \
    && chmod +x /usr/local/bin/entrypoint.sh \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run Laravel package discovery, Filament 5 & Livewire 4 asset publishing inside full PHP 8.4 runtime
# (This bakes all compiled Filament CSS, JS, fonts, and Livewire frontend assets into the final image)
RUN php artisan package:discover --ansi \
    && php artisan filament:upgrade \
    && php artisan livewire:publish --assets \
    && chown -R www-data:www-data /var/www/html/public

# Render default port
EXPOSE 10000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
