# Despliegue en Railway

El proyecto ya trae todo lo necesario: `Dockerfile`, `docker/entrypoint.sh`, `docker/php.ini` y `railway.json`.
Railway detecta el `Dockerfile` solo.

## 1. Subir el código
El repositorio de GitHub tiene el proyecto dentro de la carpeta `mi-nuevo-futbol`.

## 2. Crear el proyecto
1. railway.com → **New Project → Deploy from GitHub repo** → elige el repositorio y la rama.
2. En el servicio: **Settings → Root Directory** = `mi-nuevo-futbol`.
3. En el mismo proyecto: **New → Database → MySQL**.

## 3. Variables (servicio de la app → Variables)

| Variable | Valor |
|---|---|
| `APP_KEY` | obligatoria. Genérala en tu PC: `php artisan key:generate --show` |
| `APP_URL` | la dirección pública (paso 5), con `https://` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `${{MySQL.MYSQLHOST}}` |
| `DB_PORT` | `${{MySQL.MYSQLPORT}}` |
| `DB_DATABASE` | `${{MySQL.MYSQLDATABASE}}` |
| `DB_USERNAME` | `${{MySQL.MYSQLUSER}}` |
| `DB_PASSWORD` | `${{MySQL.MYSQLPASSWORD}}` |
| `SUPERADMIN_EMAIL` | tu correo de administrador |
| `SUPERADMIN_PASSWORD` | una clave tuya y segura |
| `SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` | `database` |

Si no defines `SUPERADMIN_PASSWORD`, el sistema genera una clave aleatoria y la muestra **una sola vez** en el
registro del primer despliegue (pestaña Deployments → Logs). Ya vienen puestos por defecto: `APP_ENV=production`,
`APP_DEBUG=false` y `LOG_CHANNEL=stderr`.

Correo (para los avisos y las confirmaciones): `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`,
`MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`. Sin esto los correos no salen.

## 4. Volume para fotos y logos (importante)
El disco de Railway se borra en cada despliegue y el sistema guarda los logos, las fotos de cancha y los diseños
de uñas en `public/uploads`. En el servicio: **Settings → Volumes → New Volume** con ruta de montaje
`/var/www/html/public/uploads`. Sin esto, los clientes pierden sus imágenes al actualizar.

## 5. Dirección pública
**Settings → Networking → Generate Domain**. Copia esa dirección en `APP_URL` y redespliega.

## Qué hace solo cada vez que arranca
- Crea las carpetas de subidas y prepara las cachés.
- Ejecuta las migraciones (`php artisan migrate --force`).
- Crea la cuenta de superadmin si no existe (si ya existe, no la toca).
- Deja corriendo el programador de tareas: el aviso diario de mensualidades de parqueadero sale a las 7 a. m.
  (hora de Colombia). No hace falta un servicio aparte.

Variables opcionales: `RUN_MIGRATIONS=0` (no migrar al arrancar) y `RUN_SCHEDULER=0` (sin tareas programadas).

## Comprobaciones tras el primer despliegue
1. `https://tu-dominio/up` responde 200.
2. Entras con el superadmin y creas un cliente de prueba.
3. Subes un logo, haces un nuevo despliegue y compruebas que el logo sigue ahí (confirma el Volume).
