# Plan de Respaldos y Recuperación — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## 1. Alcance

Este documento define la estrategia de respaldo, retención y recuperación para todos los componentes del SPP 2026:

- Base de datos PostgreSQL
- Archivos de evidencia (storage)
- Configuración de la aplicación
- Redis (cache/sesiones)

---

## 2. Componentes a Respaldar

| Componente | Tipo | Criticidad | Tamaño Estimado |
|-----------|------|-----------|-----------------|
| PostgreSQL (spp_2026) | Datos | **Crítico** | Variable (crece con avances) |
| `storage/app/` | Archivos | **Crítico** | Variable (evidencias PDF, imágenes) |
| `.env` | Configuración | **Alto** | < 1 KB |
| `storage/logs/` | Logs | Medio | Rotación automática |
| Redis | Cache | Bajo | Regenerable |

---

## 3. Estrategia de Respaldo

### 3.1 Base de Datos — PostgreSQL

**Respaldo diario completo:**

```bash
#!/bin/bash
# /opt/scripts/backup-db.sh

FECHA=$(date +%Y-%m-%d_%H%M)
DESTINO="/var/backups/spp/db"
ARCHIVO="${DESTINO}/spp_2026_${FECHA}.sql.gz"

mkdir -p "${DESTINO}"

pg_dump -U spp_user -h 127.0.0.1 spp_2026 \
    --format=custom \
    --compress=6 \
    --file="${DESTINO}/spp_2026_${FECHA}.dump"

# Verificar que el respaldo no esté vacío
if [ ! -s "${DESTINO}/spp_2026_${FECHA}.dump" ]; then
    echo "ERROR: Respaldo vacío" | mail -s "ALERTA: Backup SPP falló" admin@ejemplo.gob.mx
    exit 1
fi

echo "Respaldo completado: ${ARCHIVO}"
```

**Programación (crontab root):**

```cron
# Respaldo diario a las 01:00
0 1 * * * /opt/scripts/backup-db.sh >> /var/log/spp-backup.log 2>&1
```

### 3.2 Archivos de Evidencia

```bash
#!/bin/bash
# /opt/scripts/backup-storage.sh

FECHA=$(date +%Y-%m-%d_%H%M)
DESTINO="/var/backups/spp/storage"

mkdir -p "${DESTINO}"

# Respaldo incremental con rsync
rsync -av --delete \
    /var/www/spp/storage/app/ \
    "${DESTINO}/app/"

# Comprimir snapshot semanal (domingos)
if [ $(date +%u) -eq 7 ]; then
    tar czf "${DESTINO}/storage_${FECHA}.tar.gz" \
        -C /var/www/spp/storage app/
fi
```

```cron
# Sincronización diaria a las 01:30
30 1 * * * /opt/scripts/backup-storage.sh >> /var/log/spp-backup.log 2>&1
```

### 3.3 Configuración

```bash
#!/bin/bash
# /opt/scripts/backup-config.sh

DESTINO="/var/backups/spp/config"
mkdir -p "${DESTINO}"

cp /var/www/spp/.env "${DESTINO}/.env.$(date +%Y-%m-%d)"
```

```cron
# Respaldo de config tras cada despliegue (o diario)
0 2 * * * /opt/scripts/backup-config.sh
```

---

## 4. Política de Retención

| Componente | Retención Diaria | Retención Semanal | Retención Mensual |
|-----------|-----------------|-------------------|-------------------|
| Base de datos | 7 días | 4 semanas | 12 meses |
| Storage (incremental) | Continuo (rsync) | — | — |
| Storage (snapshot) | — | 4 semanas | 6 meses |
| Configuración | 30 días | — | 12 meses |

### Script de Limpieza

```bash
#!/bin/bash
# /opt/scripts/cleanup-backups.sh

# Eliminar respaldos diarios > 7 días
find /var/backups/spp/db -name "*.dump" -mtime +7 -delete

# Eliminar snapshots de storage > 30 días
find /var/backups/spp/storage -name "storage_*.tar.gz" -mtime +30 -delete

# Eliminar configs > 30 días
find /var/backups/spp/config -name ".env.*" -mtime +30 -delete
```

```cron
# Limpieza semanal (lunes 04:00)
0 4 * * 1 /opt/scripts/cleanup-backups.sh >> /var/log/spp-backup.log 2>&1
```

---

## 5. Almacenamiento Externo

Los respaldos deben copiarse a una ubicación externa al servidor de producción:

### Opción A: Servidor de Respaldos (rsync + SSH)

```bash
# En crontab del servidor de producción
0 3 * * * rsync -avz -e "ssh -i /root/.ssh/backup_key" \
    /var/backups/spp/ \
    backup@respaldo.ejemplo.gob.mx:/backups/spp/
```

### Opción B: Almacenamiento en Nube (S3-compatible)

```bash
# Usando s3cmd o aws-cli
0 3 * * * aws s3 sync /var/backups/spp/ s3://bucket-respaldos-spp/ \
    --storage-class STANDARD_IA
```

---

## 6. Procedimientos de Recuperación

### 6.1 Recuperación Completa (Disaster Recovery)

**Escenario:** Pérdida total del servidor.

```bash
# 1. Provisionar nuevo servidor (ver docs/guia-despliegue.md)

# 2. Restaurar base de datos
pg_restore -U spp_user -h 127.0.0.1 \
    --dbname=spp_2026 \
    --clean --if-exists \
    /var/backups/spp/db/spp_2026_YYYY-MM-DD_HHMM.dump

# 3. Restaurar archivos de evidencia
rsync -av /var/backups/spp/storage/app/ /var/www/spp/storage/app/
chown -R spp:www-data /var/www/spp/storage/app/

# 4. Restaurar configuración
cp /var/backups/spp/config/.env.YYYY-MM-DD /var/www/spp/.env

# 5. Regenerar caches
cd /var/www/spp
php artisan optimize
php artisan permission:cache-reset

# 6. Reiniciar servicios
sudo systemctl restart php8.2-fpm nginx
sudo supervisorctl restart spp-worker:*
```

**Tiempo estimado de recuperación (RTO):** 1-2 horas

### 6.2 Recuperación de Base de Datos

```bash
# Detener aplicación
php artisan down

# Restaurar
pg_restore -U spp_user -h 127.0.0.1 \
    --dbname=spp_2026 \
    --clean --if-exists \
    /var/backups/spp/db/spp_2026_YYYY-MM-DD_HHMM.dump

# Limpiar cache de permisos
php artisan permission:cache-reset
php artisan cache:clear

# Restaurar aplicación
php artisan up
```

### 6.3 Recuperación de Archivos Específicos

```bash
# Restaurar un archivo de evidencia específico
cp /var/backups/spp/storage/app/evidencias/archivo.pdf \
   /var/www/spp/storage/app/evidencias/archivo.pdf

chown spp:www-data /var/www/spp/storage/app/evidencias/archivo.pdf
```

### 6.4 Point-in-Time Recovery (PITR)

Para recuperación a un punto exacto en el tiempo, configurar WAL archiving en PostgreSQL:

```ini
# postgresql.conf
wal_level = replica
archive_mode = on
archive_command = 'cp %p /var/backups/spp/wal/%f'
```

```bash
# Recuperar a un punto específico
pg_restore --target-time="2026-03-08 14:30:00" ...
```

---

## 7. Verificación de Respaldos

### Test Mensual de Restauración

```bash
#!/bin/bash
# /opt/scripts/test-restore.sh

# Crear base de datos de prueba
sudo -u postgres createdb spp_2026_test

# Restaurar último respaldo
ULTIMO=$(ls -t /var/backups/spp/db/*.dump | head -1)
pg_restore -U spp_user --dbname=spp_2026_test "${ULTIMO}"

# Verificar integridad
TABLAS=$(sudo -u postgres psql -t -c "SELECT count(*) FROM information_schema.tables WHERE table_schema='public'" spp_2026_test)
echo "Tablas restauradas: ${TABLAS}"

# Verificar conteos
sudo -u postgres psql -c "SELECT 'users' as tabla, count(*) FROM users UNION ALL SELECT 'programas', count(*) FROM programa_presupuestarios UNION ALL SELECT 'avances', count(*) FROM avances" spp_2026_test

# Limpiar
sudo -u postgres dropdb spp_2026_test

echo "Test de restauración completado: $(date)"
```

```cron
# Test mensual (primer domingo del mes a las 05:00)
0 5 1-7 * 0 /opt/scripts/test-restore.sh >> /var/log/spp-backup.log 2>&1
```

---

## 8. Monitoreo de Respaldos

### Alertas

Configurar alertas para:
- [ ] Respaldo no ejecutado (archivo ausente o vacío)
- [ ] Espacio en disco < 20% en destino de respaldos
- [ ] Falla en transferencia a almacenamiento externo
- [ ] Test de restauración fallido

### Checklist Mensual

- [ ] Verificar que los respaldos diarios se ejecutan correctamente
- [ ] Confirmar transferencia a almacenamiento externo
- [ ] Ejecutar test de restauración
- [ ] Revisar política de retención y limpiar respaldos expirados
- [ ] Verificar espacio disponible en disco

---

## 9. RPO y RTO

| Métrica | Objetivo | Notas |
|---------|----------|-------|
| **RPO** (Recovery Point Objective) | 24 horas | Respaldo diario; con WAL: minutos |
| **RTO** (Recovery Time Objective) | 2 horas | Incluye provisión de servidor |
| **Frecuencia de test** | Mensual | Test automatizado de restauración |
