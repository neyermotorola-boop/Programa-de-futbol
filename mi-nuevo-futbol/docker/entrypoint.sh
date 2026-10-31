#!/bin/sh
# Arranque del contenedor en Railway: prepara la app y luego inicia Apache.
set -e
cd /var/www/html

# Railway indica el puerto en la variable PORT
PORT="${PORT:-80}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: falta la variable APP_KEY. Generala con: php artisan key:generate --show" >&2
    exit 1
fi

# Carpeta de subidas (logos, fotos, diseños). En Railway va montada como Volume,
# que llega vacío la primera vez: hay que crear las subcarpetas y dar permisos.
mkdir -p public/uploads/logos public/uploads/canchas public/uploads/disenos
chown -R www-data:www-data public/uploads storage bootstrap/cache

# Cachés de producción (se calculan aquí porque dependen de las variables de entorno)
php artisan config:cache
php artisan view:cache

# Base de datos y cuenta de superadmin (si ya existen, no se toca nada)
if [ "${RUN_MIGRATIONS:-1}" = "1" ]; then
    php artisan migrate --force
    php artisan db:seed --class=SuperAdminSeeder --force
fi

# Tareas programadas: el aviso diario de mensualidades de parqueadero
if [ "${RUN_SCHEDULER:-1}" = "1" ]; then
    su -s /bin/sh www-data -c "php artisan schedule:work" &
fi

exec docker-php-entrypoint "$@"
