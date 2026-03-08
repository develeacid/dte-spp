# Guía de Despliegue — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## 1. Requisitos del Servidor

### Hardware Mínimo

| Recurso | Mínimo | Recomendado |
|---------|--------|-------------|
| CPU | 2 cores | 4 cores |
| RAM | 4 GB | 8 GB |
| Disco | 40 GB SSD | 100 GB SSD |

### Software

| Componente | Versión | Notas |
|------------|---------|-------|
| PHP | 8.2+ | Extensiones: pdo_pgsql, pgsql, mbstring, xml, bcmath, gd, zip, redis |
| PostgreSQL | 16+ | Con extensión pgvector |
| Redis | 7+ | Cache, sesiones y colas |
| Node.js | 18+ | Solo para build de assets |
| Nginx | 1.24+ | O Apache con mod_rewrite |
| Supervisor | 4+ | Para workers de cola |
| Certbot | Último | Para certificados SSL |

---

## 2. Preparación del Servidor

### 2.1 Instalar dependencias del sistema

```bash
# Ubuntu 22.04+
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx postgresql-16 redis-server supervisor \
    php8.2-fpm php8.2-cli php8.2-pgsql php8.2-mbstring php8.2-xml \
    php8.2-bcmath php8.2-gd php8.2-zip php8.2-redis php8.2-curl \
    git unzip curl

# Instalar Composer
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar Node.js (via NodeSource)
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs
```

### 2.2 Configurar PostgreSQL

```bash
sudo -u postgres psql <<SQL
CREATE USER spp_user WITH PASSWORD 'CONTRASEÑA_SEGURA';
CREATE DATABASE spp_2026 OWNER spp_user;
\c spp_2026
CREATE EXTENSION IF NOT EXISTS vector;
SQL
```

### 2.3 Crear usuario del sistema

```bash
sudo useradd -m -s /bin/bash spp
sudo mkdir -p /var/www/spp
sudo chown spp:www-data /var/www/spp
```

---

## 3. Despliegue de la Aplicación

### 3.1 Clonar y configurar

```bash
sudo -u spp bash
cd /var/www/spp
git clone <repo-url> .
cp .env.example .env
```

### 3.2 Configurar variables de entorno

Editar `/var/www/spp/.env`:

```ini
APP_NAME="SPP 2026"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://spp.ejemplo.gob.mx
APP_KEY=  # Se genera en paso siguiente

APP_LOCALE=es
APP_FAKER_LOCALE=es_MX

BCRYPT_ROUNDS=12

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=spp_2026
DB_USERNAME=spp_user
DB_PASSWORD=CONTRASEÑA_SEGURA

SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true

CACHE_STORE=redis
QUEUE_CONNECTION=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.ejemplo.gob.mx
MAIL_PORT=587
MAIL_USERNAME=spp@ejemplo.gob.mx
MAIL_PASSWORD=CLAVE_SMTP
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=spp@ejemplo.gob.mx
MAIL_FROM_NAME="SPP 2026"

FILESYSTEM_DISK=local

# Reportes
REPORT_INSTITUCION="Gobierno del Estado"
REPORT_DEPENDENCIA="Secretaría de Planeación"
APP_EJERCICIO_FISCAL=2026

# Ponderaciones de evaluación
EVAL_PESO_FIN=0.40
EVAL_PESO_PROPOSITO=0.30
EVAL_PESO_COMPONENTE=0.20
EVAL_PESO_ACTIVIDAD=0.10

# Embeddings / IA (opcional)
EMBEDDING_API_KEY=sk-xxx
EMBEDDING_API_URL=https://api.openai.com/v1/embeddings
EMBEDDING_MODEL=text-embedding-ada-002
EMBEDDING_OBSERVERS_ENABLED=true
```

### 3.3 Instalar dependencias y compilar

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=Database\\Seeders\\Cascade\\OdsSeeder
php artisan db:seed --class=Database\\Seeders\\Cascade\\PndSeeder
php artisan optimize
```

### 3.4 Permisos de directorios

```bash
sudo chown -R spp:www-data /var/www/spp
sudo chmod -R 755 /var/www/spp
sudo chmod -R 775 /var/www/spp/storage /var/www/spp/bootstrap/cache
```

---

## 4. Configuración de Nginx

Crear `/etc/nginx/sites-available/spp`:

```nginx
server {
    listen 80;
    server_name spp.ejemplo.gob.mx;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name spp.ejemplo.gob.mx;

    ssl_certificate /etc/letsencrypt/live/spp.ejemplo.gob.mx/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/spp.ejemplo.gob.mx/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;

    root /var/www/spp/public;
    index index.php;

    client_max_body_size 20M;

    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 60;
    }

    location ~ /\.(?!well-known) {
        deny all;
    }

    location /storage {
        deny all;
        return 403;
    }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/spp /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

---

## 5. Certificado SSL

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d spp.ejemplo.gob.mx
# Renovación automática ya incluida via systemd timer
```

---

## 6. Workers de Cola (Supervisor)

Crear `/etc/supervisor/conf.d/spp-worker.conf`:

```ini
[program:spp-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/spp/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=spp
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/spp/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start spp-worker:*
```

---

## 7. Tareas Programadas (Cron)

Agregar al crontab del usuario `spp`:

```bash
sudo -u spp crontab -e
```

```cron
* * * * * cd /var/www/spp && php artisan schedule:run >> /dev/null 2>&1
```

**Tareas programadas del sistema:**

| Hora | Comando | Descripción |
|------|---------|-------------|
| 02:00 | `app:embeddings-generate` | Genera embeddings para registros nuevos |
| 03:00 | `reports:cleanup` | Elimina reportes expirados (>24h) |
| 06:00 | `mir:abrir-periodos` | Abre períodos de captura |
| 23:00 | `mir:cerrar-vencidos` | Marca avances vencidos |
| Mensual | `llm:cleanup-logs` | Purga logs de LLM (>90 días) |

---

## 8. PHP-FPM Tuning

Editar `/etc/php/8.2/fpm/pool.d/www.conf`:

```ini
[www]
user = spp
group = www-data
listen = /run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data

pm = dynamic
pm.max_children = 20
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
pm.max_requests = 500

php_admin_value[upload_max_filesize] = 20M
php_admin_value[post_max_size] = 25M
php_admin_value[memory_limit] = 256M
php_admin_value[max_execution_time] = 60
```

```bash
sudo systemctl restart php8.2-fpm
```

---

## 9. Proceso de Actualización

### Despliegue estándar

```bash
sudo -u spp bash
cd /var/www/spp

# 1. Modo mantenimiento
php artisan down --retry=60

# 2. Obtener cambios
git pull origin main

# 3. Dependencias
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# 4. Migraciones
php artisan migrate --force

# 5. Cache
php artisan optimize

# 6. Reiniciar workers
sudo supervisorctl restart spp-worker:*

# 7. Salir de mantenimiento
php artisan up
```

### Rollback de emergencia

```bash
# Identificar commit anterior
git log --oneline -5

# Revertir
php artisan down
git checkout <commit-anterior>
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
sudo supervisorctl restart spp-worker:*
php artisan up
```

---

## 10. Monitoreo Básico

### Logs de la aplicación

```bash
# Log principal
tail -f /var/www/spp/storage/logs/laravel.log

# Workers
tail -f /var/www/spp/storage/logs/worker.log
```

### Verificación de salud

```bash
# PHP-FPM activo
sudo systemctl status php8.2-fpm

# Redis activo
redis-cli ping  # Debe responder PONG

# PostgreSQL activo
sudo -u postgres pg_isready

# Workers activos
sudo supervisorctl status spp-worker:*

# Cron ejecutándose
sudo -u spp crontab -l
```

### Espacio en disco

```bash
# Storage de la aplicación
du -sh /var/www/spp/storage/app/
du -sh /var/www/spp/storage/logs/

# Base de datos
sudo -u postgres psql -c "SELECT pg_size_pretty(pg_database_size('spp_2026'));"
```

---

## 11. Consideraciones de Seguridad en Producción

1. **Firewall:** Permitir solo puertos 80, 443 y 22 (SSH restringido por IP)
2. **SSH:** Desactivar login con contraseña, usar solo llaves SSH
3. **APP_DEBUG:** Siempre `false` en producción
4. **Storage:** El directorio `/storage` NO debe ser accesible desde la web
5. **Redis:** Configurar `requirepass` si Redis está expuesto a red
6. **PostgreSQL:** Limitar conexiones por IP en `pg_hba.conf`
7. **Backups:** Configurar respaldos automáticos (ver `docs/plan-respaldos.md`)
8. **Updates:** Mantener actualizados PHP, PostgreSQL y dependencias del sistema

---

## 12. CI/CD con GitHub Actions

El proyecto incluye pipeline CI en `.github/workflows/ci.yml` que ejecuta automáticamente en push a `desarrollo` y `main`:

1. Setup PHP 8.2 con extensiones
2. Instalación de dependencias (Composer con cache)
3. Laravel Pint (verificación de estilo)
4. Composer audit (vulnerabilidades)
5. Migraciones contra PostgreSQL 16 de prueba
6. PHPUnit en paralelo

Para despliegue automático, agregar un step adicional con SSH deploy o usar un servicio como Laravel Forge/Envoyer.
