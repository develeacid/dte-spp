# Documento de Seguridad — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## 1. Resumen Ejecutivo

El SPP 2026 implementa un modelo de seguridad en capas que cubre autenticación multifactor, autorización basada en roles y permisos, aislamiento por equipos (multi-tenant), protección de cabeceras HTTP, auditoría de cambios, y cifrado de datos sensibles. El sistema está diseñado para operar en infraestructura gubernamental con requisitos de confidencialidad e integridad.

---

## 2. Autenticación

### 2.1 Mecanismo

- **Framework:** Laravel Fortify + Jetstream
- **Hash:** bcrypt con 12 rounds (`BCRYPT_ROUNDS=12`)
- **Sesiones:** Almacenadas en Redis, cifradas opcionalmente (`SESSION_ENCRYPT=true`)
- **Tokens API:** Laravel Sanctum para tokens de acceso personal
- **Timeout de sesión:** 120 minutos (configurable)

### 2.2 Autenticación de Dos Factores (2FA)

- **Obligatoria:** El middleware `RequireTwoFactorAuthentication` bloquea acceso a rutas protegidas si 2FA no está configurado
- **Método:** TOTP (Time-based One-Time Password) compatible con Google Authenticator, Authy, etc.
- **Códigos de recuperación:** 8 códigos de un solo uso generados al activar 2FA
- **Almacenamiento:** Secret cifrado en base de datos

### 2.3 Flujo de Activación de Cuentas

Los usuarios no se auto-registran. El flujo es:

1. Admin crea cuenta → se genera `invitation_token` (UUID)
2. Se envía email con enlace de activación (expira según `invitation_expires_at`)
3. Usuario establece contraseña en `/activar/{token}`
4. Usuario configura 2FA en `/activar/{token}/2fa`
5. Cuenta marcada como `activated_at = now()`

**Controles:**
- Token de un solo uso (se invalida al activar)
- Expiración configurable
- Rutas de activación no requieren autenticación (usan token)

### 2.4 Middleware de Activación

`EnsureUserIsActivated` verifica que:
- La cuenta esté marcada como activa (`active = true`)
- El campo `activated_at` no sea nulo
- Si falla, redirige a página de cuenta inactiva

---

## 3. Autorización

### 3.1 Modelo RBAC

Se usa **Spatie Laravel Permission** con 5 roles y 19 permisos:

| Rol | Permisos |
|-----|----------|
| **admin** | Todos los permisos (Gate::before) |
| **planeador** | gestionar_catalogos, crear_programa, editar_mir, revisar_avance, aprobar_avance, exportar_reportes, ver_sabana_captura, ver_concentrado_captura, ver_datos_financieros, exportar_cuenta_publica, ver_sustento_legal |
| **operador** | capturar_avance, exportar_reportes, ver_sabana_captura, ver_concentrado_captura |
| **analista_financiero** | gestionar_presupuesto, capturar_avance_financiero, ver_datos_financieros, exportar_cuenta_publica, ver_sustento_legal |
| **analista_juridico** | gestionar_sustento_legal, validar_sustento_legal, ver_sustento_legal, gestionar_reglas_operacion, ver_datos_financieros |

> Referencia completa: [`docs/roles-permisos-rutas.md`](roles-permisos-rutas.md)

### 3.2 Protección de Rutas

```
Middleware Stack:
auth:sanctum → jetstream.auth_session → verified → [permission/can]
```

| Dominio | Middleware de Permiso | Rutas |
|---------|----------------------|-------|
| Admin (IA, Auditoría) | `can:administrar_usuarios` | `/admin/monitoreo-ia`, `/admin/auditoria` |
| Admin (Usuarios) | `can:invitar_usuarios` | `/admin/usuarios` |
| Cascada | `permission:gestionar_catalogos` | `/cascade/*` |
| Evaluación (Exportar) | `can:exportar_reportes` | `/evaluacion/exportar/*`, `/evaluacion/transversal` |
| Presupuesto (Panel) | `can:ver_datos_financieros` | `/presupuesto` |
| Presupuesto (CRUD) | `can:gestionar_presupuesto` | `/presupuesto/partidas/*`, `/presupuesto/importar` |
| Presupuesto (Captura) | `can:capturar_avance_financiero` | `/presupuesto/captura/*` |
| Presupuesto (Cuenta Pública) | `can:exportar_cuenta_publica` | `/presupuesto/cuenta-publica`, `/presupuesto/exportar/*` |
| Jurídico (Panel/Consulta) | `can:ver_sustento_legal` | `/juridico`, `/juridico/programa/*` |
| Jurídico (CRUD) | `can:gestionar_sustento_legal` | `/juridico/*/fundamento/*` |
| Jurídico (Documentos) | `can:gestionar_reglas_operacion` | `/juridico/*/documentos` |
| Jurídico (Validación) | `can:validar_sustento_legal` | `/juridico/*/validacion` |
| MML y Tracking | Auth sin permiso específico | Verificación interna en componentes |

### 3.3 Aislamiento Multi-Tenant (Teams)

- Cada usuario pertenece a uno o más equipos (Jetstream Teams)
- `currentTeam` determina el contexto de datos visible
- Middleware `AislamientoMultiUR` y scope `paraTeam()` en modelos filtran datos por equipo
- Los queries de dashboard, programas, indicadores y avances siempre incluyen filtro de `team_id`

---

## 4. Cabeceras de Seguridad HTTP

El middleware `SecurityHeaders` agrega a todas las respuestas:

| Cabecera | Valor | Propósito |
|----------|-------|-----------|
| X-Frame-Options | DENY | Previene clickjacking |
| X-Content-Type-Options | nosniff | Previene MIME sniffing |
| Referrer-Policy | strict-origin-when-cross-origin | Controla información de referrer |
| Permissions-Policy | camera=(), microphone=(), geolocation=() | Desactiva APIs de hardware |
| Content-Security-Policy | Ver detalle abajo | Previene XSS e inyección |

### CSP Detallado

```
default-src 'self';
script-src 'self' 'unsafe-inline' 'unsafe-eval';
style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net;
font-src 'self' https://fonts.gstatic.com;
img-src 'self' data: https://ui-avatars.com;
connect-src 'self';
frame-ancestors 'none'
```

> En entorno local se agregan orígenes de Vite dev server (`localhost:5173`).

---

## 5. Protección contra Ataques Comunes

### 5.1 CSRF

- Laravel genera automáticamente tokens CSRF para formularios
- Livewire incluye protección CSRF en todas las interacciones

### 5.2 SQL Injection

- Uso exclusivo de Eloquent ORM y Query Builder con parámetros bindeados
- No hay queries SQL crudos sin binding

### 5.3 XSS

- Blade escapa output por defecto con `{{ }}` (htmlspecialchars)
- CSP limita ejecución de scripts a `'self'`
- `'unsafe-inline'` necesario para Alpine.js y Livewire

### 5.4 Upload de Archivos

- Validación de tipo MIME y extensión en `EvidenciaAvance` y `DocumentoNormativo`
- Archivos almacenados en `storage/app/` (no accesible públicamente)
  - Evidencias: `storage/app/private/evidencias/`
  - Documentos jurídicos: `storage/app/private/juridico/{programaId}/`
- Nginx bloquea acceso directo a `/storage`
- Descarga controlada a través de controllers con verificación de permisos:
  - `EvidenciaController::download()` — evidencias de avance
  - `DocumentoNormativoController::download()` — documentos jurídicos (verifica `team_id`)
- Integridad de documentos jurídicos: hash SHA-256 calculado al subir
- Límite de archivo documentos jurídicos: 10MB (configurable en `config/juridico.php`)
- Limpieza automática: `reports:cleanup` elimina reportes expirados (>24h)
- Eventos `deleting` en `Avance` y `AvanceEvidencia` eliminan archivos huérfanos

### 5.5 Rate Limiting

- Laravel Sanctum aplica rate limiting a autenticación
- API de embeddings tiene rate limiting configurable (`EMBEDDING_RATE_LIMIT=60`)

---

## 6. Auditoría

### 6.1 Sistema de Activity Log

Implementado con **spatie/laravel-activitylog**. Registra automáticamente:

| Evento | Datos Guardados |
|--------|-----------------|
| Creación | Todos los atributos nuevos |
| Actualización | Atributos cambiados (old/new) |
| Eliminación | Atributos del registro eliminado |

### 6.2 Modelos Auditados

| Modelo | Campos Incluidos |
|--------|-----------------|
| User | name, email, active, activated_at |
| PedPlan | nombre, periodo_inicio, periodo_fin, activo |
| PedEje | nombre, orden |
| PedTema | nombre, orden |
| PedObjetivoEstrategico | nombre, orden |
| MirNivel | tipo_nivel, resumen_narrativo, orden |
| Indicador | nombre, tipo, dimension, frecuencia, meta |
| EvaluacionPrograma | ejercicio_fiscal, indice_eficacia (excluye campos JSONB grandes) |
| PartidaPresupuestal | clave_partida, monto_aprobado, monto_modificado |
| AvanceFinanciero | monto_comprometido, monto_devengado, monto_pagado |
| SustentoLegalPrograma | tipo, ordenamiento, articulo, vigente, validado_por |
| DocumentoNormativo | nombre, tipo_documento, verificado, verificado_por |
| ValidacionJuridicaPrograma | estado, tiene_facultad_ur, tiene_mandato_gasto, tiene_rop, validado_por |

### 6.3 Consulta de Auditoría

- Interfaz web en `/admin/auditoria` (solo rol admin)
- Filtros: usuario, modelo, tipo de evento, rango de fechas
- Vista detallada de valores anteriores y nuevos

### 6.4 Retención

- Activity log sin expiración automática (retención indefinida)
- Logs de LLM: retención de 90 días (detallados), resúmenes mensuales permanentes
- Logs de reportes generados: 24 horas

---

## 7. Cifrado y Protección de Datos

| Dato | Protección |
|------|-----------|
| Contraseñas | bcrypt (12 rounds) |
| 2FA Secret | Cifrado AES-256-CBC por Laravel |
| Sesiones | Redis con cifrado opcional |
| Cookies | HttpOnly, Secure, SameSite=Lax |
| APP_KEY | AES-256-CBC para cifrado de aplicación |
| API Keys (embeddings) | Variable de entorno (no en código) |
| Tokens de invitación | UUID v4, expiración temporal |

---

## 8. Seguridad en CI/CD

El pipeline de GitHub Actions incluye:

1. **`composer audit`**: Verifica vulnerabilidades conocidas en dependencias PHP
2. **Laravel Pint**: Detecta patrones de código potencialmente inseguros
3. **Tests automatizados**: Incluyen tests de seguridad específicos:
   - `SecurityHeadersTest`: Verifica presencia de todas las cabeceras
   - `AccessibilityTest`: Verifica atributos ARIA y estructura correcta

---

## 9. Recomendaciones de Hardening

### Servidor

- [ ] Firewall: solo puertos 80, 443, 22 (SSH restringido por IP)
- [ ] SSH: desactivar login con contraseña, usar llaves
- [ ] Fail2ban para protección contra fuerza bruta
- [ ] Actualizaciones automáticas de seguridad del SO

### Aplicación

- [ ] `APP_DEBUG=false` en producción (CRÍTICO)
- [ ] `SESSION_ENCRYPT=true` en producción
- [ ] Redis con `requirepass` si accesible por red
- [ ] PostgreSQL: restringir IPs en `pg_hba.conf`
- [ ] Revisar logs periódicamente (`storage/logs/laravel.log`)

### Red

- [ ] HTTPS obligatorio (redirect HTTP → HTTPS)
- [ ] TLS 1.2+ únicamente
- [ ] HSTS habilitado (`Strict-Transport-Security`)
- [ ] Certificado SSL renovado automáticamente (Certbot)

---

## 10. Plan de Respuesta a Incidentes

1. **Detección:** Monitoreo de logs, alertas de acceso inusual
2. **Contención:** `php artisan down` para modo mantenimiento inmediato
3. **Investigación:** Revisar activity_log, logs de aplicación, logs de acceso Nginx
4. **Remediación:** Aplicar parche, forzar cierre de sesiones (`php artisan auth:clear-resets`)
5. **Notificación:** Informar a los usuarios afectados según protocolos institucionales
6. **Post-mortem:** Documentar causa, impacto y acciones preventivas
