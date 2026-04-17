# Stage 1: Builder - Install dependencies and build application
FROM php:8.3-fpm-alpine AS builder

WORKDIR /var/www/html

# Install system dependencies with security updates
RUN apk update && apk upgrade && apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    autoconf \
    g++ \
    make

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# Copy Composer from official image
COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

# Copy application code
COPY . .

# Install Composer dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Stage 2: Runtime - Clean production image with only runtime dependencies
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

# Copy PHP extensions from builder
COPY --from=builder /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=builder /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

# Install only runtime dependencies (no build tools) with security updates
RUN apk update && apk upgrade && apk add --no-cache \
    libpng \
    libjpeg-turbo \
    libzip \
    postgresql-libs \
    oniguruma \
    curl \
    && rm -rf /var/cache/apk/*


# Configure OPcache for better performance
RUN { \
    echo 'opcache.memory_consumption=256'; \
    echo 'opcache.max_accelerated_files=7963'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.enable_cli=1'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.file_cache=/tmp'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# Configure PHP-FPM for better performance
RUN { \
    echo '[www]'; \
    echo 'user = www-data'; \
    echo 'group = www-data'; \
    echo 'listen = 9000'; \
    echo 'pm = dynamic'; \
    echo 'pm.max_children = 50'; \
    echo 'pm.start_servers = 5'; \
    echo 'pm.min_spare_servers = 5'; \
    echo 'pm.max_spare_servers = 35'; \
    echo 'pm.process_idle_timeout = 10s'; \
    echo 'pm.max_requests = 500'; \
    } > /usr/local/etc/php-fpm.d/www.conf

# Copy application from builder (only production files, no build tools)
COPY --from=builder /var/www/html /var/www/html

# Set appropriate permissions for storage and cache directories
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html

# Create and set tmp directory permissions
RUN mkdir -p /tmp && chown www-data:www-data /tmp && chmod 1777 /tmp

# Expose port 9000 for PHP-FPM
EXPOSE 9000

# Run as non-root user for better security
USER www-data

# Start PHP-FPM
CMD ["php-fpm"]
