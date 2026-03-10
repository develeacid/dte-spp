# Producción: Dos Proyectos en un VPS — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Dejar el VPS correctamente configurado para alojar DTE-SPP y el futuro Padrón Único como dos aplicaciones independientes, sin repetir los 7 errores del primer despliegue.

**Architecture:** Nginx corre directamente en el host del VPS como reverse proxy en el puerto 80/443. Cada aplicación Laravel corre en su propio stack Docker (contenedor PHP + PostgreSQL + Redis) en un puerto interno diferente (8080, 8081...). Nginx enruta el tráfico por dominio o subdominio.

**Tech Stack:** Ubuntu VPS, Nginx (host), Docker + Docker Compose, Laravel Sail (Dockerfile custom en `docker/8.2/`), PostgreSQL 18, Redis.

**VPS:** `srv1476074.hstgr.cloud` — IP `187.124.148.2`

---

## Contexto: Lecciones del Primer Despliegue

Estos errores ya ocurrieron. El plan los previene, no los repite.

| Error | Causa | Fix incluido en este plan |
|-------|-------|--------------------------|
| groupadd invalid GID | WWWGROUP vacío en .env | `.env.production.example` con WWWGROUP=1000 |
| vendor/ no existe | composer install no ejecutado | Paso explícito en cada deploy |
| Puerto 80 no responde | SUPERVISOR_PHP_COMMAND no definida | Incluida en `.env.production.example` |
| Seeders bloqueados en producción | Guard `app()->environment('production')` | Script de seeding con bypass documentado |
| fake() undefined | fakerphp/faker solo en require-dev | **Tarea 1: mover a require** |
| Teams no encontrados | Orden de seeders incorrecto | Orden documentado en script |
| 2FA bloqueante | Feature activa sin configuración | Variable `FORTIFY_TWO_FACTOR=false` en .env |

---

## Parte 1 — Fixes de Código (Antes de Push)

### Tarea 1: Mover fakerphp/faker a dependencia de producción

`fakerphp/faker` está en `require-dev`. Al instalar con `--no-dev` en el VPS, `UserFactory` falla. Solución: moverlo a `require`.

**Archivos:**
- Modify: `composer.json`

**Paso 1: Ejecutar el comando en local**

```bash
composer require fakerphp/faker --no-interaction
```

Esto mueve automáticamente `fakerphp/faker` de `require-dev` a `require` en `composer.json` y actualiza `composer.lock`.

**Paso 2: Verificar el cambio en composer.json**

```bash
grep -A2 '"fakerphp/faker"' composer.json
```

Esperado: aparece bajo `"require"`, no bajo `"require-dev"`.

**Paso 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "fix: move fakerphp/faker to production dependencies"
```

---

### Tarea 2: Crear .env.production.example con todas las variables requeridas

Previene que en el siguiente deploy falten variables críticas.

**Archivos:**
- Create: `.env.production.example`

**Paso 1: Crear el archivo**

```bash
cat > .env.production.example << 'EOF'
# ============================================
# VARIABLES REQUERIDAS EN PRODUCCIÓN
# Copiar a .env y llenar los valores reales
# ============================================

APP_NAME="DTE-SPP"
APP_ENV=production
APP_KEY=                          # php artisan key:generate --show
APP_DEBUG=false
APP_URL=http://TU_DOMINIO_O_IP

# ---- SAIL / DOCKER -------------------------
APP_PORT=8080                     # Puerto externo del contenedor PHP
WWWGROUP=1000                     # Obligatorio al correr como root en VPS
WWWUSER=1000

# ---- SUPERVISOR (arranque del servidor) ----
SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
SUPERVISOR_PHP_USER=sail

# ---- BASE DE DATOS -------------------------
DB_CONNECTION=pgsql
DB_HOST=pgsql                     # Nombre del contenedor en docker-compose
DB_PORT=5432
DB_DATABASE=dte_spp
DB_USERNAME=sail
DB_PASSWORD=CAMBIA_ESTO

# ---- REDIS ---------------------------------
REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# ---- SESIONES / CACHÉ ----------------------
SESSION_DRIVER=redis
SESSION_LIFETIME=120
CACHE_STORE=redis
QUEUE_CONNECTION=redis

# ---- SEGURIDAD -----------------------------
BCRYPT_ROUNDS=12
LOG_LEVEL=error

# ---- 2FA (desactivar hasta configurar) -----
# Cuando 2FA esté listo, borrar esta línea y
# descomentar Features::twoFactorAuthentication() en config/fortify.php
FORTIFY_TWO_FACTOR=false
EOF
```

**Paso 2: Agregar al .gitignore correcto** (ya está `.env`, este archivo sí va al repo)

```bash
grep ".env.production.example" .gitignore || echo "El archivo puede commitearse"
```

**Paso 3: Commit**

```bash
git add .env.production.example
git commit -m "docs: add production env template with all required variables"
```

---

### Tarea 3: Hacer el 2FA configurable por variable de entorno

Actualmente `config/fortify.php` tiene `Features::twoFactorAuthentication()` hardcodeado. Lo hacemos condicional.

**Archivos:**
- Modify: `config/fortify.php`

**Paso 1: Leer las líneas actuales del 2FA en fortify.php**

```bash
grep -n "twoFactor" config/fortify.php
```

**Paso 2: Reemplazar el bloque hardcodeado por condicional**

Buscar el bloque:
```php
Features::twoFactorAuthentication([
    'confirm' => true,
    'confirmPassword' => true,
]),
```

Reemplazarlo por:
```php
...env('FORTIFY_TWO_FACTOR', false) ? Features::twoFactorAuthentication([
    'confirm' => true,
    'confirmPassword' => true,
]) : null,
```

Nota: el `...` (spread) descarta el `null` sin romper el array de features.

**Paso 3: Limpiar cache local y verificar**

```bash
php artisan config:clear
php artisan tinker --execute="dd(config('fortify.features'));"
```

Esperado: sin `two-factor-authentication` en el array cuando `FORTIFY_TWO_FACTOR=false`.

**Paso 4: Commit**

```bash
git add config/fortify.php
git commit -m "fix: make 2FA feature configurable via FORTIFY_TWO_FACTOR env var"
```

---

### Tarea 4: Crear docker-compose.prod.yml

No existe un `docker-compose.yml` comprometido en el repositorio (lo genera Sail en local). Crear uno explícito para producción evita depender de generación automática en el VPS.

**Archivos:**
- Create: `docker-compose.prod.yml`

**Paso 1: Crear el archivo**

```yaml
# docker-compose.prod.yml
# Uso: docker compose -f docker-compose.prod.yml up -d

services:
  laravel.test:
    build:
      context: .
      dockerfile: docker/8.2/Dockerfile
      args:
        WWWGROUP: '${WWWGROUP:-1000}'
    image: dte-spp-app
    extra_hosts:
      - 'host.docker.internal:host-gateway'
    ports:
      - '${APP_PORT:-8080}:80'
    environment:
      WWWUSER: '${WWWUSER:-1000}'
      LARAVEL_SAIL: 1
      SUPERVISOR_PHP_COMMAND: '${SUPERVISOR_PHP_COMMAND}'
      SUPERVISOR_PHP_USER: '${SUPERVISOR_PHP_USER:-sail}'
    volumes:
      - '.:/var/www/html'
    networks:
      - dte-spp
    depends_on:
      - pgsql
      - redis

  pgsql:
    image: 'postgres:18'
    ports:
      - '${DB_PORT:-5432}:5432'
    environment:
      PGPASSWORD: '${DB_PASSWORD:-password}'
      POSTGRES_DB: '${DB_DATABASE:-dte_spp}'
      POSTGRES_USER: '${DB_USERNAME:-sail}'
      POSTGRES_PASSWORD: '${DB_PASSWORD:-password}'
    volumes:
      - 'dte-spp-pgsql:/var/lib/postgresql/data'
    networks:
      - dte-spp
    healthcheck:
      test: ["CMD", "pg_isready", "-q", "-d", "${DB_DATABASE}", "-U", "${DB_USERNAME}"]
      retries: 3
      timeout: 5s

  redis:
    image: 'redis:alpine'
    volumes:
      - 'dte-spp-redis:/data'
    networks:
      - dte-spp
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      retries: 3
      timeout: 5s

networks:
  dte-spp:
    driver: bridge

volumes:
  dte-spp-pgsql:
    driver: local
  dte-spp-redis:
    driver: local
```

**Nota sobre nombres únicos:** Cada proyecto en el VPS debe tener nombres de red y volúmenes únicos. DTE-SPP usa `dte-spp`, el Padrón usará `padron`.

**Paso 2: Verificar que construye localmente (opcional)**

```bash
docker compose -f docker-compose.prod.yml build --no-cache
```

**Paso 3: Commit**

```bash
git add docker-compose.prod.yml
git commit -m "feat: add production docker-compose with named networks and volumes"
```

---

## Parte 2 — Preparación del VPS (Una sola vez)

Estos pasos se ejecutan directamente en el VPS via SSH. Se hacen una vez y sirven para todos los proyectos que se agreguen.

### Tarea 5: Instalar Nginx en el VPS como reverse proxy

**Paso 1: Conectar al VPS**

```bash
ssh root@187.124.148.2
```

**Paso 2: Instalar Nginx**

```bash
apt-get update && apt-get install -y nginx
systemctl enable nginx
systemctl start nginx
```

**Paso 3: Verificar que Nginx corre en puerto 80**

```bash
curl -I http://localhost
```

Esperado: `HTTP/1.1 200 OK` o `404` — cualquier respuesta HTTP confirma que Nginx responde.

---

### Tarea 6: Crear estructura de directorios en el VPS

```bash
mkdir -p /var/www/dte-spp
mkdir -p /var/www/padron        # Para el futuro Padrón Único
```

---

### Tarea 7: Configurar Nginx como reverse proxy para DTE-SPP

**Paso 1: Crear el archivo de configuración**

```bash
cat > /etc/nginx/sites-available/dte-spp << 'EOF'
server {
    listen 80;
    server_name srv1476074.hstgr.cloud 187.124.148.2;

    client_max_body_size 100M;

    location / {
        proxy_pass         http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header   Upgrade $http_upgrade;
        proxy_set_header   Connection 'upgrade';
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
    }
}
EOF
```

**Paso 2: Activar el sitio**

```bash
ln -s /etc/nginx/sites-available/dte-spp /etc/nginx/sites-enabled/dte-spp
rm -f /etc/nginx/sites-enabled/default   # Quitar la página por defecto de Nginx
```

**Paso 3: Validar configuración y recargar**

```bash
nginx -t && systemctl reload nginx
```

Esperado: `nginx: configuration file /etc/nginx/nginx.conf test is successful`

---

## Parte 3 — Despliegue de DTE-SPP

### Tarea 8: Clonar y configurar el proyecto en el VPS

**Paso 1: Clonar el repositorio**

```bash
cd /var/www/dte-spp
git clone https://github.com/TU_ORG/dte-spp-2026.git .
```

**Paso 2: Crear .env desde la plantilla**

```bash
cp .env.production.example .env
nano .env     # Llenar: APP_KEY, DB_PASSWORD, APP_URL
```

Variables mínimas a editar:
- `APP_KEY` → ejecutar en el siguiente paso
- `DB_PASSWORD` → una contraseña segura
- `APP_URL` → `http://srv1476074.hstgr.cloud`

**Paso 3: Verificar que APP_PORT=8080 y WWWGROUP=1000 están presentes**

```bash
grep -E "APP_PORT|WWWGROUP|SUPERVISOR_PHP_COMMAND" .env
```

Esperado: las tres líneas deben aparecer con sus valores.

---

### Tarea 9: Build y arranque de contenedores

**Paso 1: Construir la imagen**

```bash
docker compose -f docker-compose.prod.yml build --no-cache
```

Esperado: `Successfully built ...` al final. Si falla con `groupadd invalid group ID`, verificar que `WWWGROUP=1000` está en `.env`.

**Paso 2: Levantar los contenedores en background**

```bash
docker compose -f docker-compose.prod.yml up -d
```

**Paso 3: Verificar que el contenedor PHP responde internamente**

```bash
sleep 5 && curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8080
```

Esperado: `200` o `302`. Si devuelve `000`, el proceso PHP no arrancó — revisar `SUPERVISOR_PHP_COMMAND` en `.env`.

**Paso 4: Verificar a través de Nginx (puerto 80)**

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost
```

Esperado: `200` o `302`.

---

### Tarea 10: Configuración inicial de Laravel

**Paso 1: Instalar dependencias PHP (sin dev)**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test \
  composer install --no-dev --optimize-autoloader
```

**Paso 2: Generar APP_KEY**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan key:generate
```

**Paso 3: Ejecutar migraciones**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan migrate --force
```

**Paso 4: Optimizar para producción**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize
```

---

### Tarea 11: Ejecutar seeders en el orden correcto

Los seeders tienen guards `app()->environment('production')`. El bypass temporal es seguro si se restaura inmediatamente.

**Paso 1: Ejecutar DesarrolloSeeder primero (crea Teams requeridos)**

```bash
sed -i 's/APP_ENV=production/APP_ENV=local/' .env \
  && docker compose -f docker-compose.prod.yml exec laravel.test \
     php artisan db:seed --class=DesarrolloSeeder --force \
  && sed -i 's/APP_ENV=local/APP_ENV=production/' .env
```

**Paso 2: Verificar que Teams existen**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test \
  php artisan tinker --execute="echo App\Models\Team::count();"
```

Esperado: número mayor a 0.

**Paso 3: Ejecutar QaTestingSeeder**

```bash
sed -i 's/APP_ENV=production/APP_ENV=local/' .env \
  && docker compose -f docker-compose.prod.yml exec laravel.test \
     php artisan db:seed --class=QaTestingSeeder --force \
  && sed -i 's/APP_ENV=local/APP_ENV=production/' .env
```

**Paso 4: Limpiar cache después de los cambios de .env**

```bash
docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize
```

**Paso 5: Verificar acceso desde el navegador**

Abrir `http://187.124.148.2` — debe cargar el login del sistema.

---

## Parte 4 — Agregar el Padrón Único (Cuando Esté Listo)

Cuando el Padrón Único sea un repositorio independiente, el proceso para agregarlo al mismo VPS es:

### Tarea 12: Configurar Nginx para el Padrón (cuando exista)

**Paso 1: Crear virtual host para el Padrón**

```bash
cat > /etc/nginx/sites-available/padron << 'EOF'
server {
    listen 80;
    server_name padron.TU_DOMINIO.gob.mx;   # Subdominio dedicado al Padrón

    client_max_body_size 100M;

    location / {
        proxy_pass         http://127.0.0.1:8081;
        proxy_http_version 1.1;
        proxy_set_header   Host $host;
        proxy_set_header   X-Real-IP $remote_addr;
        proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header   X-Forwarded-Proto $scheme;
        proxy_read_timeout 300;
        proxy_connect_timeout 300;
    }
}
EOF

ln -s /etc/nginx/sites-available/padron /etc/nginx/sites-enabled/padron
nginx -t && systemctl reload nginx
```

**Paso 2: El docker-compose.prod.yml del Padrón usa APP_PORT=8081**

Cada proyecto tiene:
- Nombres de red únicos (`dte-spp`, `padron`)
- Nombres de volumen únicos (`dte-spp-pgsql`, `padron-pgsql`)
- Puerto externo único (`8080`, `8081`)
- Directorio propio (`/var/www/dte-spp`, `/var/www/padron`)

---

## Checklist Pre-Despliegue

Antes de cada push a producción, verificar:

- [ ] `fakerphp/faker` está en `require` (no `require-dev`)
- [ ] No hay `dd()`, `dump()` o `var_dump()` en el código
- [ ] `APP_DEBUG=false` en `.env` de producción
- [ ] `APP_ENV=production` en `.env` de producción (salvo durante seeders)
- [ ] `WWWGROUP=1000` y `WWWUSER=1000` presentes en `.env`
- [ ] `SUPERVISOR_PHP_COMMAND` definida en `.env`
- [ ] `FORTIFY_TWO_FACTOR=false` hasta configurar TOTP
- [ ] Migraciones nuevas probadas con `migrate --pretend` antes de `migrate --force`
- [ ] Seeders ejecutados en orden: DesarrolloSeeder → QaTestingSeeder

---

## Comandos de Mantenimiento Frecuentes

```bash
# Ver logs en tiempo real
docker compose -f docker-compose.prod.yml logs -f laravel.test

# Reiniciar solo el contenedor PHP (sin perder la DB)
docker compose -f docker-compose.prod.yml restart laravel.test

# Actualizar código sin downtime
git pull origin main \
  && docker compose -f docker-compose.prod.yml exec laravel.test \
     composer install --no-dev --optimize-autoloader \
  && docker compose -f docker-compose.prod.yml exec laravel.test \
     php artisan migrate --force \
  && docker compose -f docker-compose.prod.yml exec laravel.test \
     php artisan optimize

# Backup de la base de datos
docker compose -f docker-compose.prod.yml exec pgsql \
  pg_dump -U sail dte_spp > backup_$(date +%Y%m%d_%H%M%S).sql
```
