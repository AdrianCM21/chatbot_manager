# ==========================================
# Stage 1: Build Frontend Assets
# ==========================================
FROM node:20-alpine AS node_builder

WORKDIR /app

COPY package*.json vite.config.js ./
RUN npm install

COPY resources ./resources
COPY public ./public
RUN npm run build

# ==========================================
# Stage 2: Production PHP Runtime (FrankenPHP)
# ==========================================
FROM dunglas/frankenphp:1-php8.3-alpine

# Install system dependencies and PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pcntl \
    bcmath \
    intl \
    zip \
    gd \
    opcache

WORKDIR /app

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Copy application source code
COPY . .

# Copy Vite built frontend assets from Stage 1
COPY --from=node_builder /app/public/build ./public/build

# Dump optimized autoload and publish filament assets
RUN composer dump-autoload --optimize --no-dev

# Setup entrypoint and set permissions for storage and cache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh && \
    mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
