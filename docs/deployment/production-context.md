# Contexto de Despliegue en Producción — dte-spp-2026

> Generado: 2026-03-10. Cubre el primer deploy completo al VPS Hostinger.

---

## 1. Infraestructura

| Componente | Detalle |
|---|---|
| VPS | Hostinger — `srv1476074.hstgr.cloud` (IP: 187.124.148.2) |
| Directorio del proyecto | `/var/www/dte-spp` |
| OS | Ubuntu 22.04 LTS |
| Acceso SSH | `ssh dte-spp-vps` (alias configurado en `~/.ssh/config`) |
| Clave SSH | `~/.ssh/dte_spp_hostinger` (Ed25519) |
| Puerto Nginx → App | `80/443` → `127.0.0.1:8080` |
| URL pública | `http://srv1476074.hstgr.cloud` |

### `~/.ssh/config` entrada local
```
Host dte-spp-vps
    HostName 187.124.148.2
    User root
    IdentityFile ~/.ssh/dte_spp_hostinger
    IdentitiesOnly yes
```

---

## 2. Repositorio

| | |
|---|---|
| Repo GitHub | `git@github.com:develeacid/dte-spp.git` |
| Remote en VPS | SSH (no HTTPS) |
| Rama de producción | `desarrollo` |
| Último commit desplegado | `b21cbf0` |

---

## 3. Docker Compose de Producción

Archivo: `docker-compose.prod.yml` en la raíz del proyecto.

### Servicios
- **`laravel.test`** — App PHP 8.2 con Supervisor
  - Build context: `./docker/8.2` (NO `.` — la raíz no tiene `php.ini`)
  - Dockerfile: `./docker/8.2/Dockerfile`
  - Puerto: `${APP_PORT:-8080}:80`
  - Imagen resultante: `dte-spp-app`
- **`pgsql`** — `pgvector/pgvector:pg16` (**no** `postgres:17` — necesita extensión vector)
- **`redis`** — `redis:alpine`

### Red y volúmenes
- Red: `dte-spp` (bridge)
- Volúmenes: `dte-spp-pgsql`, `dte-spp-redis`

### Comandos útiles en VPS
```bash
cd /var/www/dte-spp

# Ver estado
docker compose -f docker-compose.prod.yml ps

# Ver logs de la app
docker compose -f docker-compose.prod.yml logs laravel.test -f

# Shell en el contenedor
docker compose -f docker-compose.prod.yml exec laravel.test bash

# Shell como root (para permisos)
docker compose -f docker-compose.prod.yml exec -u root laravel.test bash

# Reiniciar app
docker compose -f docker-compose.prod.yml restart laravel.test

# Detener todo
docker compose -f docker-compose.prod.yml down

# Rebuild desde cero
docker compose -f docker-compose.prod.yml build --no-cache
docker compose -f docker-compose.prod.yml up -d
```

---

## 4. Archivo `.env` en Producción

Ubicación: `/var/www/dte-spp/.env` (no está en git — copiar de `.env.production.example`).

Variables críticas:
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=http://srv1476074.hstgr.cloud
APP_PORT=8080

DB_CONNECTION=pgsql
DB_HOST=pgsql
DB_PORT=5432
DB_DATABASE=dte_spp
DB_USERNAME=sail
DB_PASSWORD=<password>

REDIS_HOST=redis

WWWGROUP=1000
WWWUSER=1000

SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
SUPERVISOR_PHP_USER=sail

FORTIFY_TWO_FACTOR=false
```

> `FORTIFY_TWO_FACTOR=false` previene el lockout en login al no tener 2FA configurado.

---

## 5. Procedimiento de Despliegue (desde cero)

### Paso 1: Nginx en el host
```bash
cat > /etc/nginx/sites-available/dte-spp << 'EOF'
server {
    listen 80;
    server_name srv1476074.hstgr.cloud;
    client_max_body_size 50M;
    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
EOF
ln -s /etc/nginx/sites-available/dte-spp /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### Paso 2: Clonar y configurar
```bash
mkdir -p /var/www && cd /var/www
git clone git@github.com:develeacid/dte-spp.git dte-spp
cd dte-spp
cp .env.production.example .env
# Editar .env con los valores reales
nano .env
php artisan key:generate  # si no está en .env ya
```

### Paso 3: Build Docker
```bash
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d
```

### Paso 4: Composer (con ignore-platform-reqs)
```bash
docker compose -f docker-compose.prod.yml exec laravel.test \
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs
```
> `--ignore-platform-reqs` es **obligatorio**: `symfony/expression-language` y `spatie/laravel-permission` declaran PHP ≥8.4 pero corren en 8.2.

### Paso 5: Assets Vite
```bash
docker compose -f docker-compose.prod.yml exec laravel.test npm ci
docker compose -f docker-compose.prod.yml exec laravel.test npm run build
```
> Sin este paso aparece HTTP 500 "Vite manifest not found".

### Paso 6: Permisos de storage
```bash
docker compose -f docker-compose.prod.yml exec -u root laravel.test bash -c \
    'chown -R sail:sail /var/www/html/storage /var/www/html/bootstrap/cache && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache'
```
> `sail` = UID 1337. Sin esto: HTTP 500 por no poder escribir logs/caché.

### Paso 7: Reiniciar supervisor (si hay FATAL state)
```bash
docker compose -f docker-compose.prod.yml restart laravel.test
```

### Paso 8: Migraciones y seeders
```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan migrate --force

# Seeders requieren APP_ENV=local para pasar la guardia de QaTestingSeeder
docker compose -f docker-compose.prod.yml exec -e APP_ENV=local laravel.test \
    php artisan db:seed --force
```

---

## 6. Procedimiento de Actualización (deploy continuo)

```bash
ssh dte-spp-vps "cd /var/www/dte-spp && git pull origin desarrollo"

# Si hay cambios en composer.json:
ssh dte-spp-vps "cd /var/www/dte-spp && \
    docker compose -f docker-compose.prod.yml exec laravel.test \
    composer install --no-dev --optimize-autoloader --ignore-platform-reqs"

# Si hay cambios en assets JS/CSS:
ssh dte-spp-vps "cd /var/www/dte-spp && \
    docker compose -f docker-compose.prod.yml exec laravel.test npm ci && \
    docker compose -f docker-compose.prod.yml exec laravel.test npm run build"

# Migraciones pendientes:
ssh dte-spp-vps "cd /var/www/dte-spp && \
    docker compose -f docker-compose.prod.yml exec laravel.test php artisan migrate --force"

# Limpiar caché:
ssh dte-spp-vps "cd /var/www/dte-spp && \
    docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize:clear && \
    docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize"
```

---

## 7. Refrescar Base de Datos (migrate:fresh + seed)

```bash
ssh dte-spp-vps "cd /var/www/dte-spp && \
    docker compose -f docker-compose.prod.yml exec laravel.test \
        php artisan migrate:fresh --force && \
    docker compose -f docker-compose.prod.yml exec -e APP_ENV=local laravel.test \
        php artisan db:seed --force"
```

Después de `migrate:fresh` re-verificar permisos (ver Paso 6).

---

## 8. Usuarios QA disponibles

> **ACTUALIZACIÓN 2026-05-15 (post-reset 2026-05-14):** los usuarios `ele.*@gmail.com` con contraseña `LseRdlP0P` listados abajo son **históricos** del `QaTestingSeeder` y **ya no existen en el VPS** tras el reset. El VPS actual tiene los mismos usuarios del dev seeder: patrón `<rol>.<secretaria>@sistema.test` con contraseña `password` (mismo patrón que dev local). Para login en VPS usar p.ej. `planeador.se@sistema.test` / `password`. La tabla histórica se conserva por si se vuelve a aplicar `QaTestingSeeder`.

Contraseña histórica: `LseRdlP0P`

| Nombre | Email | Rol | UR(s) |
|---|---|---|---|
| QA Admin | ele.admin@gmail.com | admin | SE-001, SS-002 |
| QA Planeador | ele.planeador@gmail.com | planeador | SE-001 |
| QA Operador | ele.operador@gmail.com | operador | SE-001 |
| QA Planeador 2 | ele.planeador2@gmail.com | planeador | SE-001 |
| QA Revisor | ele.revisor@gmail.com | planeador | SS-002 |
| QA Operador 2 | ele.operador2@gmail.com | operador | SS-002 |

> `ele.leader@gmail.com` (rol PLANEADOR) **no** está en el seeder — debe crearse vía flujo de invitación.
> `AdminUserSeeder` está comentado en `DatabaseSeeder.php` — el admin real también debe crearse vía invitación.

---

## 9. Seeders — Orden y Notas

```php
// database/seeders/DatabaseSeeder.php
RolesAndPermissionsSeeder::class,
// AdminUserSeeder::class,  // <-- COMENTADO, crear vía invitación
DesarrolloSeeder::class,
OdsSeeder::class,
PndSeeder::class,
PedSeeder::class,
ProgramasDerivadosSeeder::class,
AnexosTransversalesSeeder::class,
QaTestingSeeder::class,
```

`QaTestingSeeder` tiene guardia de producción — siempre pasar `-e APP_ENV=local`.

---

## 10. Problemas Conocidos y Soluciones

| Error | Causa | Solución |
|---|---|---|
| `php.ini` not found en build | Context era `.` (raíz), Dockerfile busca en context root | `context: ./docker/8.2` |
| `composer install` falla con conflicto de versiones | `symfony/expression-language` declara PHP ≥8.4 | Agregar `--ignore-platform-reqs` |
| Migration falla — pgvector no disponible | Imagen `postgres:17` no incluye pgvector | Usar `pgvector/pgvector:pg16` |
| Supervisor FATAL al iniciar | `vendor/` no existía cuando arrancó el contenedor | `docker compose restart laravel.test` después de `composer install` |
| HTTP 500 — permisos storage | `storage/` y `bootstrap/cache/` propiedad de root | `chown -R sail:sail` + `chmod -R 775` |
| HTTP 500 — Vite manifest | `npm run build` nunca corrió | `npm ci && npm run build` en el contenedor |
| `git pull` conflicto | Edits locales en VPS sin commitear | `git checkout -- <archivo>` antes de pull |
| Seeder duplicate key | Seeder parcial previo, tablas con datos | `migrate:fresh --force` antes de `db:seed` |

---

## 11. Archivos Clave Creados/Modificados para Producción

| Archivo | Cambio |
|---|---|
| `docker-compose.prod.yml` | Creado — compose para producción |
| `.env.production.example` | Creado — plantilla de variables de entorno |
| `config/fortify.php` | `FORTIFY_TWO_FACTOR` env var para 2FA opt-in |
| `composer.json` | `fakerphp/faker` movido a `require` (no `require-dev`) |
| `database/seeders/DatabaseSeeder.php` | `AdminUserSeeder` comentado |
| `database/seeders/QaTestingSeeder.php` | `ele.leader@gmail.com` comentado |

---

## 12. BD Pública (Transparencia)

`.env` prod debe tener `DB_PUBLIC_PORTAL_PASSWORD` con un secret distinto a `DB_PASSWORD`. Estructura mínima:

```env
DB_PUBLIC_DATABASE=spp_public
DB_PUBLIC_PORTAL_USER=spp_portal
DB_PUBLIC_PORTAL_PASSWORD=<secret_distinto_a_DB_PASSWORD>
```

Post-deploy (idempotente, re-ejecutable):

```bash
docker compose -f docker-compose.prod.yml exec laravel.test \
    php artisan transparencia:provision-public-db
docker compose -f docker-compose.prod.yml exec laravel.test \
    php artisan migrate --path=database/migrations/public --database=pgsql_public --force
```

El comando crea la BD `spp_public` y el rol `spp_portal` (LOGIN, NOSUPERUSER, NOINHERIT, NOCREATEDB, NOCREATEROLE) con `GRANT CONNECT` + `GRANT USAGE ON SCHEMA public`. Las migrations en `database/migrations/public/` agregan `GRANT SELECT` por tabla.

**Garantía técnica:** la conexión `pgsql_public_read` usa el usuario `spp_portal` con SELECT only — el test `PortalReadOnlyTest` (4 tests) bloquea cualquier merge futuro que afloje los grants.

## 13. Pipeline N2-03 — Sync al portal público

`DatasetAbierto::publicar()` y `retirar()` disparan el job `SyncPublicDatasetJob` que sincroniza la tabla `pub_*` correspondiente. No requiere comandos adicionales tras deploy — el flujo se gatilla automáticamente desde N2-01.

### Recovery manual

Si necesitas re-sincronizar (e.g., tras restore de BD privada o tras corregir un bug en un Publisher):

```bash
docker compose -f docker-compose.prod.yml exec laravel.test \
    php artisan transparencia:sync-public DS-01
```

Códigos soportados: `DS-01`, `DS-02`, `DS-03`, `DS-04`, `DS-05`, `DS-G04`. Las claves `DS-00` (políticas) y `DS-G01..G03` (cobertura GeoBase) NO se sincronizan en este sprint y devuelven exit code 1 con mensaje claro.

### Auditoría histórica

```sql
SELECT dataset_clave, action, success, payload_hash, registros_count, publicado_at
FROM transparencia_publicaciones
ORDER BY publicado_at DESC LIMIT 20;
```

`success=false` indica que un job falló — la `pub_*` NO cambió (rollback transaccional). Re-correr con `transparencia:sync-public {clave}` cuando se arregle la causa.

### Garantía de coherencia

Cada `publicar()` o `retirar()` exitoso re-sincroniza `pub_datasets_catalogo` automáticamente. Si el Publisher principal falla, el catálogo NO se re-sincroniza (semántica todo-o-nada).

El hash sha256 excluye `id`/`created_at`/`updated_at` para que re-ejecutar el sync con los mismos datos source produzca exactamente el mismo hash (idempotencia verificable).
