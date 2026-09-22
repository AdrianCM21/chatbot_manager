#!/bin/sh
set -e

# Asegurar enlace de storage
php artisan storage:link --no-interaction 2>/dev/null || true

# Si el comando es el servidor web principal, corre migraciones y optimiza
if [ "$1" = "frankenphp" ]; then
    echo ">> Ejecutando migraciones automáticas..."
    php artisan migrate --force --no-interaction || true

    echo ">> Optimizando cache de Laravel..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
