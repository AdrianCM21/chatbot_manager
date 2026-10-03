#!/bin/sh
set -e

# Asegurar enlace de storage
php artisan storage:link --no-interaction 2>/dev/null || true

# Migraciones siempre, sin importar el contenedor: son idempotentes, y el
# worker necesita la tabla `cache` (CACHE_STORE=database) para el chequeo de
# restart de `queue:work` aunque arranque antes que el `app`. Si fallan (ej.
# la base todavía no está lista), el script corta por el `set -e` de arriba
# y el `restart: unless-stopped` reintenta solo.
echo ">> Ejecutando migraciones automáticas..."
php artisan migrate --force --no-interaction

# El cache de config/rutas/vistas solo tiene sentido en el servidor web.
if [ "$1" = "frankenphp" ]; then
    echo ">> Optimizando cache de Laravel..."
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
