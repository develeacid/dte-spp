# Guía de Monitoreo y Alertas — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## 1. Componentes a Monitorear

| Componente | Criticidad | Verificación |
|-----------|-----------|-------------|
| PHP-FPM | Crítico | Proceso activo, respuesta HTTP |
| PostgreSQL | Crítico | Conexiones, espacio, queries lentos |
| Redis | Crítico | Ping, memoria, conexiones |
| Nginx | Crítico | Proceso activo, SSL válido |
| Queue Workers (Supervisor) | Alto | Procesos activos, jobs fallidos |
| Cron / Scheduler | Alto | Tareas ejecutadas a tiempo |
| Storage | Medio | Espacio en disco |
| Aplicación | Crítico | Endpoint de salud, logs de error |

---

## 2. Health Check de la Aplicación

### 2.1 Endpoint de Verificación Manual

```bash
# Verificar que la aplicación responde
curl -s -o /dev/null -w "%{http_code}" https://spp.ejemplo.gob.mx/login
# Esperado: 200
```

### 2.2 Script de Health Check

```bash
#!/bin/bash
# /opt/scripts/healthcheck.sh

ERRORES=0

# PHP-FPM
if ! systemctl is-active --quiet php8.2-fpm; then
    echo "ALERTA: PHP-FPM no está activo"
    ERRORES=$((ERRORES+1))
fi

# Nginx
if ! systemctl is-active --quiet nginx; then
    echo "ALERTA: Nginx no está activo"
    ERRORES=$((ERRORES+1))
fi

# PostgreSQL
if ! pg_isready -h 127.0.0.1 -p 5432 -q; then
    echo "ALERTA: PostgreSQL no responde"
    ERRORES=$((ERRORES+1))
fi

# Redis
if ! redis-cli ping | grep -q PONG; then
    echo "ALERTA: Redis no responde"
    ERRORES=$((ERRORES+1))
fi

# Workers de cola
WORKERS=$(supervisorctl status spp-worker:* | grep -c RUNNING)
if [ "$WORKERS" -lt 2 ]; then
    echo "ALERTA: Solo $WORKERS workers activos (esperados: 2)"
    ERRORES=$((ERRORES+1))
fi

# Espacio en disco
DISCO=$(df / | tail -1 | awk '{print $5}' | tr -d '%')
if [ "$DISCO" -gt 85 ]; then
    echo "ALERTA: Disco al ${DISCO}%"
    ERRORES=$((ERRORES+1))
fi

# Respuesta HTTP
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" --max-time 10 https://spp.ejemplo.gob.mx/login)
if [ "$HTTP_CODE" != "200" ]; then
    echo "ALERTA: HTTP respondió $HTTP_CODE"
    ERRORES=$((ERRORES+1))
fi

# Resumen
if [ $ERRORES -gt 0 ]; then
    echo "$ERRORES alertas detectadas" | mail -s "ALERTA SPP: $ERRORES problemas" admin@ejemplo.gob.mx
    exit 1
fi

echo "OK: Todos los servicios operando normalmente"
```

```cron
# Health check cada 5 minutos
*/5 * * * * /opt/scripts/healthcheck.sh >> /var/log/spp-health.log 2>&1
```

---

## 3. Monitoreo de Base de Datos

### 3.1 Conexiones Activas

```bash
# Ver conexiones actuales
sudo -u postgres psql -c "SELECT count(*) as conexiones, state FROM pg_stat_activity WHERE datname='spp_2026' GROUP BY state;"
```

**Alerta si:** conexiones activas > 50

### 3.2 Tamaño de la Base de Datos

```bash
# Tamaño total
sudo -u postgres psql -c "SELECT pg_size_pretty(pg_database_size('spp_2026'));"

# Tablas más grandes
sudo -u postgres psql -d spp_2026 -c "
SELECT schemaname || '.' || relname as tabla,
       pg_size_pretty(pg_total_relation_size(relid)) as tamano
FROM pg_catalog.pg_statio_user_tables
ORDER BY pg_total_relation_size(relid) DESC
LIMIT 10;"
```

### 3.3 Queries Lentos

Habilitar en `postgresql.conf`:

```ini
log_min_duration_statement = 1000  # Log queries > 1 segundo
log_statement = 'none'             # No log de queries normales
```

```bash
# Revisar queries lentos
grep "duration:" /var/log/postgresql/postgresql-16-main.log | tail -20
```

### 3.4 Activity Log Crecimiento

```bash
# Monitorear tamaño de activity_log (auditoría)
sudo -u postgres psql -d spp_2026 -c "
SELECT count(*) as registros,
       pg_size_pretty(pg_total_relation_size('activity_log')) as tamano
FROM activity_log;"
```

---

## 4. Monitoreo de Redis

```bash
# Estadísticas generales
redis-cli info stats | grep -E "connected_clients|used_memory_human|total_commands"

# Memoria
redis-cli info memory | grep used_memory_human

# Keys por base de datos
redis-cli info keyspace
```

**Alertas:**
- Memoria > 500MB: investigar
- Conexiones > 100: posible fuga
- Hit rate < 80%: revisar estrategia de cache

---

## 5. Monitoreo de Cola (Queue)

### 5.1 Estado de Workers

```bash
# Ver estado de workers
sudo supervisorctl status spp-worker:*

# Ver jobs fallidos
cd /var/www/spp && php artisan queue:failed
```

### 5.2 Cola Acumulada

```bash
# Contar jobs pendientes en Redis
redis-cli llen queues:default
redis-cli llen queues:embeddings
```

**Alerta si:** cola > 100 jobs pendientes

### 5.3 Reintentar Jobs Fallidos

```bash
# Reintentar todos
php artisan queue:retry all

# Reintentar uno específico
php artisan queue:retry <job-id>

# Limpiar fallidos
php artisan queue:flush
```

---

## 6. Monitoreo de Logs de Aplicación

### 6.1 Errores Recientes

```bash
# Últimos errores
grep -i "error\|exception\|critical" /var/www/spp/storage/logs/laravel.log | tail -20

# Contar errores del día
grep "$(date +%Y-%m-%d)" /var/www/spp/storage/logs/laravel.log | grep -ci "error"
```

### 6.2 Rotación de Logs

Configurar logrotate para evitar crecimiento descontrolado:

```
# /etc/logrotate.d/spp
/var/www/spp/storage/logs/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    create 644 spp www-data
}
```

---

## 7. Monitoreo de Tareas Programadas

### 7.1 Verificar Ejecución

```bash
# Ver última ejecución de cada tarea
grep -E "abrir-periodos|cerrar-vencidos|cleanup|embeddings" \
    /var/www/spp/storage/logs/laravel.log | tail -10
```

### 7.2 Tabla de Tareas y Horarios

| Hora | Comando | Verificación |
|------|---------|-------------|
| 02:00 | `app:embeddings-generate` | Log de embeddings generados |
| 03:00 | `reports:cleanup` | Archivos eliminados del storage |
| 06:00 | `mir:abrir-periodos` | MetaPeriodos marcados como activos |
| 23:00 | `mir:cerrar-vencidos` | Avances marcados como VENCIDO |
| Mensual | `llm:cleanup-logs` | Reducción de registros en llm_logs |

### 7.3 Alerta por Tarea No Ejecutada

```bash
#!/bin/bash
# /opt/scripts/check-scheduler.sh
# Ejecutar diario a las 23:30

# Verificar que cerrar-vencidos corrió hoy
HOY=$(date +%Y-%m-%d)
if ! grep -q "cerrar-vencidos.*$HOY" /var/www/spp/storage/logs/laravel.log; then
    echo "ALERTA: mir:cerrar-vencidos no se ejecutó hoy" | \
        mail -s "ALERTA SPP: Tarea programada no ejecutada" admin@ejemplo.gob.mx
fi
```

---

## 8. Monitoreo de SSL

```bash
# Verificar expiración del certificado
echo | openssl s_client -servername spp.ejemplo.gob.mx \
    -connect spp.ejemplo.gob.mx:443 2>/dev/null | \
    openssl x509 -noout -dates
```

**Alerta si:** certificado expira en < 14 días

```bash
#!/bin/bash
# /opt/scripts/check-ssl.sh
EXPIRY=$(echo | openssl s_client -servername spp.ejemplo.gob.mx \
    -connect spp.ejemplo.gob.mx:443 2>/dev/null | \
    openssl x509 -noout -enddate | cut -d= -f2)
EXPIRY_EPOCH=$(date -d "$EXPIRY" +%s)
NOW_EPOCH=$(date +%s)
DAYS_LEFT=$(( (EXPIRY_EPOCH - NOW_EPOCH) / 86400 ))

if [ "$DAYS_LEFT" -lt 14 ]; then
    echo "ALERTA: SSL expira en $DAYS_LEFT días" | \
        mail -s "ALERTA SPP: Certificado SSL próximo a expirar" admin@ejemplo.gob.mx
fi
```

```cron
# Verificar SSL diario
0 8 * * * /opt/scripts/check-ssl.sh
```

---

## 9. Monitoreo de Consumo IA (LLM)

El sistema tiene monitoreo interno de consumo de API en `/admin/monitoreo-ia`:

```bash
# Consultar consumo del mes actual
sudo -u postgres psql -d spp_2026 -c "
SELECT
    count(*) as llamadas,
    sum(costo_total) as costo_total,
    sum(tokens_entrada + tokens_salida) as tokens_totales
FROM llm_logs
WHERE created_at >= date_trunc('month', current_date);"

# Verificar presupuesto vs consumo
sudo -u postgres psql -d spp_2026 -c "
SELECT team_id, presupuesto, consumido,
       round((consumido/presupuesto * 100)::numeric, 1) as porcentaje
FROM llm_budgets
WHERE anio = 2026 AND mes = extract(month from current_date);"
```

**Alerta si:** consumo > 80% del presupuesto mensual

---

## 10. Dashboard de Monitoreo (Resumen)

### Checklist Diario

- [ ] Health check pasando (todos los servicios verdes)
- [ ] Sin errores críticos en logs
- [ ] Respaldo de BD completado exitosamente
- [ ] Cola de jobs vacía o en niveles normales
- [ ] Disco < 85% de uso

### Checklist Semanal

- [ ] Revisar queries lentos
- [ ] Verificar tamaño de activity_log
- [ ] Revisar consumo de Redis
- [ ] Verificar workers no reiniciados excesivamente

### Checklist Mensual

- [ ] Test de restauración de respaldo
- [ ] Revisar certificado SSL
- [ ] Revisar consumo IA vs presupuesto
- [ ] Revisar y rotar logs antiguos
- [ ] Verificar actualizaciones de seguridad del SO
