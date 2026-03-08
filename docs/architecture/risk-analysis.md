# Análisis de Riesgos — Arquitectura y Funcionalidades

**Fecha:** 2026-03-08
**Baseline:** 430 tests, 7 skipped | Sprints 1-8 completados | Branch: desarrollo

---

## 1. CI/CD no planificado

**Riesgo:** ALTO
**Estado actual:** No existe `.github/workflows/`, ni Dockerfile de producción, ni pipeline de ningún tipo. Solo Laravel Sail para desarrollo local.

**Impacto:** Deploys manuales propensos a error. Sin gate automático que valide que los 430+ tests pasen antes de mergear a main.

**Recomendación:**
- **Inmediato:** GitHub Actions con `phpunit` + `pint` en cada push a `desarrollo` y `main`. Un workflow de ~30 líneas. Bajo esfuerzo, alto valor.
- **Pre-producción:** Pipeline de deploy (Forge deploy hook, GitHub Actions deploy, o similar según hosting elegido).

**Esfuerzo estimado:** 30 min (CI), variable (CD según hosting).

---

## 2. Backups no mencionados

**Riesgo:** ALTO
**Estado actual:** No hay `spatie/laravel-backup` ni estrategia documentada. PostgreSQL con datos críticos: programas presupuestarios, MIR, avances, evaluaciones, evidencias.

**Impacto:** Pérdida de datos irrecuperable en caso de fallo de disco, error humano o corrupción.

**Recomendación:**
- Si hosting managed (RDS, DigitalOcean Managed DB): backups automáticos del proveedor cubren el 90%.
- Si VPS propio: `spatie/laravel-backup` + cron diario + storage externo (S3/equivalente).
- Documentar RPO (Recovery Point Objective) y RTO (Recovery Time Objective) como requisito de infraestructura.

**Esfuerzo estimado:** Documentación ahora, implementación al definir hosting.

---

## 3. Monitoreo de aplicación

**Riesgo:** MEDIO
**Estado actual:** `LOG_CHANNEL=stack` → logs a archivo local. No hay Sentry, Telescope, ni health checks. El dashboard `MonitoreoIa` cubre métricas de LLM pero no errores de aplicación general.

**Impacto:** Errores en producción invisibles hasta que un usuario reporta. Queries lentas, jobs fallidos y excepciones pasan desapercibidos.

**Recomendación:**
| Nivel | Herramienta | Esfuerzo | Cuándo |
|-------|------------|----------|--------|
| Dev/Staging | Laravel Telescope | 1 paquete + config | Pre-staging |
| Producción | Sentry (free tier: 5K eventos/mes) | 1 paquete + DSN en .env | Pre-producción |

No construir solución custom — Sentry y Telescope son maduros y tienen free tiers suficientes.

**Esfuerzo estimado:** 15 min cada uno.

---

## 4. Ambientes (solo local definido)

**Riesgo:** MEDIO
**Estado actual:** Solo `.env` y `.env.example` para local. No hay configuración para staging ni producción. No hay Docker de producción.

**Impacto:** Al deployar, falta documentación de qué variables son requeridas. Riesgo de dejar `APP_DEBUG=true` o `APP_ENV=local` en producción.

**Recomendación:**
- Crear `.env.production.example` documentando todas las variables requeridas (sin valores reales).
- Laravel maneja ambientes nativamente con `config:cache` y `route:cache` — no requiere código nuevo.
- Checklist de deploy que incluya verificar `APP_ENV=production`, `APP_DEBUG=false`.

**Esfuerzo estimado:** 30 min (documentación).

---

## 5. Secrets management

**Riesgo:** MEDIO-BAJO
**Estado actual:** API keys en `.env` (LLM_API_KEY, EMBEDDING_API_KEY). `.env` en `.gitignore`. `.env.example` usa placeholders sin valores reales.

**Impacto:** Bajo mientras `.env` no se commitee. Riesgo principal: copia insegura de credenciales entre desarrolladores o ambientes.

**Recomendación:**
| Hosting | Solución |
|---------|----------|
| Laravel Forge | Variables de entorno en panel web |
| AWS | SSM Parameter Store o Secrets Manager |
| Docker/K8s | Docker secrets o HashiCorp Vault |
| VPS manual | `.env` con permisos 600, acceso solo root |

No implementar vault o secrets manager hasta definir hosting. El manejo actual con `.gitignore` es aceptable para desarrollo.

**Esfuerzo estimado:** Ninguno ahora, configuración al definir hosting.

---

## Matriz de priorización

| # | Gap | Riesgo | Acción inmediata | Acción pre-producción |
|---|-----|--------|------------------|----------------------|
| 1 | CI/CD | ALTO | GitHub Actions (tests) | Pipeline de deploy |
| 2 | Backups | ALTO | Documentar requisito | Implementar según hosting |
| 3 | Monitoreo | MEDIO | — | Sentry + Telescope |
| 4 | Ambientes | MEDIO | — | `.env.production.example` + checklist |
| 5 | Secrets | MEDIO-BAJO | — | Configurar según hosting |

**Única acción recomendada ahora:** CI con GitHub Actions (bajo esfuerzo, alto valor).

---

## Decisiones pendientes (bloqueantes para resolver estos gaps)

1. **Hosting:** VPS propio, Forge, AWS, DigitalOcean — define la estrategia de backups, secrets y deploy.
2. **Dominio y SSL:** Requerido para producción.
3. **Servicio de correo:** Notificaciones actualmente solo usan canal `database`. Definir proveedor SMTP.
4. **Queue worker en producción:** Redis está configurado pero falta Supervisor o Horizon para workers persistentes.

---
---

# Parte II: Funcionalidades Faltantes

**Fecha de análisis:** 2026-03-08

---

## 6. Sistema de notificaciones unificado

**Cobertura actual:** 60%
**Riesgo:** MEDIO

**Lo que YA existe:**
- 6 clases de notificación implementadas:
  - `PeriodoAbiertoNotification`, `AvanceVencidoNotification` (scheduling)
  - `AvanceObservadoNotification`, `AvanceEnRevisionNotification` (estado avances)
  - `ReporteListoNotification` (exportación)
  - `LlmBudgetAlertNotification` (monitoreo IA)
- Todas usan canal `database` exclusivamente
- Tabla `notifications` migrada (UUID, morphs, JSONB data, read_at)
- Despacho desde comandos artisan (`mir:abrir-periodos`, `mir:cerrar-vencidos`) y servicios
- Queue Redis configurada con `after_commit: true`

**Lo que FALTA:**
- Canal `mail` no configurado (`MAIL_MAILER=log` en todos los ambientes)
- No hay `NotificationService` centralizado — cada consumidor despacha directamente
- No hay preferencias de notificación por usuario (opt-in/opt-out)
- No hay centro de notificaciones en la UI (el usuario no ve sus notificaciones en la app)
- No hay rate limiting de notificaciones (un usuario podría recibir muchas a la vez)

**Opinión:**
El 60% existente cubre los flujos críticos. Lo que más impacto tiene es:
1. **Centro de notificaciones en UI** — un dropdown en el navbar mostrando notificaciones no leídas. Livewire + `auth()->user()->unreadNotifications`. ~2h de trabajo.
2. **Canal mail** — solo requiere configurar SMTP en `.env`. El código ya soporta agregar `'mail'` al array `via()`. Depende de decisión de proveedor SMTP.
3. **NotificationService centralizado** — útil pero no crítico. Las 6 clases actuales son simples y consistentes. Un service agrega complejidad sin ganancia clara a esta escala.

**Prioridad:** MEDIA. El centro de notificaciones en UI es lo único urgente.

---

## 7. API REST / documentación

**Cobertura actual:** 10%
**Riesgo:** BAJO (por ahora)

**Lo que YA existe:**
- `laravel/sanctum ^4.0` instalado en composer.json
- `routes/api.php` con un único endpoint: `GET /api/user` (autenticado)
- 7 controllers web con lógica de negocio que podría exponerse via API

**Lo que FALTA:**
- No hay controllers en `app/Http/Controllers/Api/`
- No hay rutas API para ningún dominio (MIR, indicadores, avances, evaluaciones)
- No hay documentación OpenAPI/Swagger
- No hay versionado de API
- No hay API Resources (transformers)
- No hay tests de API

**Opinión:**
**No es un gap real ahora.** El sistema es una aplicación interna con Livewire — no necesita API REST salvo que exista un requisito de integración con otros sistemas. Sanctum está instalado como parte del boilerplate de Jetstream.

Si en el futuro se necesita:
- `DatosAbiertosController` (S7-T8) ya exporta CSV/JSON — es una forma de API de datos abiertos
- Crear API sería straightforward: API Resources wrapping los modelos existentes + rutas en `routes/api.php`

**Prioridad:** BAJA. Solo actuar si hay requisito concreto de integración.

---

## 8. Búsqueda global

**Cobertura actual:** 70%
**Riesgo:** BAJO

**Lo que YA existe:**
- `SemanticSearchService` con búsqueda vectorial sobre 11 modelos (PED, ODS, PND, ProgramaDerivado)
- Índices HNSW en PostgreSQL para embeddings (pgvector)
- `EmbeddingService` + comando `app:embeddings-generate` para batch processing
- Búsqueda por texto (`$search` con `ilike`) en 5 componentes Livewire:
  - `AlineacionLineaPrograma`, `MirEditor`, `VincularAlineacion`, `AlineacionPedPnd`, `AlineacionPndOds`
- Config completa en `config/embedding.php`

**Lo que FALTA:**
- No hay Scout ni Meilisearch (búsqueda full-text)
- No hay componente de búsqueda global en la UI (barra de búsqueda universal)
- `SemanticSearchService` no está expuesto como endpoint — es solo un servicio interno
- No hay búsqueda en: programas, indicadores, avances, usuarios

**Opinión:**
La búsqueda semántica cubre el caso más complejo (alineación PED/ODS/PND). La búsqueda por texto con `ilike` es suficiente para los volúmenes esperados (cientos, no millones de registros).

**No recomiendo Meilisearch/Elasticsearch** — es over-engineering para el volumen de datos. PostgreSQL `ilike` + pg_trgm (si se necesita fuzzy) cubre todo.

Una barra de búsqueda global sería nice-to-have pero no es crítica en un sistema donde los usuarios navegan por programa → MIR → indicador en una jerarquía clara.

**Prioridad:** BAJA. El stack actual es suficiente.

---

## 9. Dashboard de inicio

**Cobertura actual:** 10%
**Riesgo:** ALTO (UX)

**Lo que YA existe:**
- Ruta `GET /dashboard` renderiza `dashboard.blade.php`
- Vista: solo muestra el componente `<x-welcome />` de Jetstream (texto genérico)
- `MonitoreoIa` existe como dashboard admin separado (`/admin/monitoreo-ia`)
- `MisIndicadoresPendientes` y `IndicadoresVencidos` existen como páginas separadas en tracking

**Lo que FALTA:**
- El dashboard no muestra ningún dato del sistema
- No hay KPIs (total programas, indicadores evaluados, índice promedio)
- No hay feed de actividad reciente
- No hay notificaciones pendientes visibles
- No hay accesos rápidos según rol
- No hay diferenciación por rol (operador vs planeador vs admin)

**Opinión:**
**Este es el gap más visible para los usuarios.** Después de login, ven una página vacía. Debería mostrar:

| Rol | Widgets sugeridos |
|-----|-------------------|
| **Operador** | Mis indicadores pendientes, avances vencidos, notificaciones |
| **Planeador** | Programas bajo su UR, semáforos consolidados, avances por revisar |
| **Admin** | KPIs globales, índice de eficacia promedio, alertas de presupuesto IA |

Los componentes `MisIndicadoresPendientes` e `IndicadoresVencidos` ya existen — solo falta incrustarlos en el dashboard en lugar de tenerlos como páginas separadas.

**Prioridad:** ALTA. Es lo primero que ve el usuario. Impacto UX directo.

---

## 10. Historial de cambios / auditoría

**Cobertura actual:** 40%
**Riesgo:** MEDIO

**Lo que YA existe:**
- `historial_observaciones` (JSONB append-only) en tabla `avances` — rastrea transiciones de estado con usuario, timestamp y observación
- `llm_logs` — auditoría completa de operaciones IA (user_id, method, tokens, cost, duration)
- `llm_budgets` — tracking de gasto IA por scope/mes
- Timestamps estándar (created_at, updated_at) en todos los modelos

**Lo que FALTA:**
- No hay `spatie/laravel-activitylog` ni equivalente
- No hay auditoría de cambios en modelos críticos: PedPlan, MirNivel, Indicador, MetaPeriodo
- No hay registro de quién creó/editó/eliminó nodos PED, niveles MIR, indicadores
- No hay soft deletes (datos eliminados se pierden permanentemente)
- No hay UI de auditoría/timeline de cambios

**Opinión:**
El patrón `historial_observaciones` en avances es bueno pero es ad-hoc para un solo modelo. Para auditoría general hay dos opciones:

| Opción | Pros | Contras |
|--------|------|---------|
| `spatie/laravel-activitylog` | Probado, trait simple, UI-ready | Tabla crece rápido, queries extra en cada write |
| Auditoría selectiva (solo modelos críticos) | Menor overhead, más control | Más código manual |

**Recomendación:** `spatie/laravel-activitylog` con trait `LogsActivity` solo en modelos críticos:
- `PedPlan`, `PedEje`, `PedTema` (estructura PED)
- `MirNivel`, `Indicador` (MIR)
- `EvaluacionPrograma` (evaluación)
- `User` (cambios de permisos/roles)

No aplicar a modelos de alto volumen (avances, llm_logs) — ya tienen su propia auditoría.

**Prioridad:** MEDIA. Importante para compliance y transparencia gubernamental, pero el sistema funciona sin él.

---

## Matriz consolidada — Funcionalidades

| # | Gap | Cobertura | Riesgo | Acción recomendada |
|---|-----|:---------:|--------|-------------------|
| 6 | Notificaciones | 60% | MEDIO | Centro de notificaciones en UI |
| 7 | API REST | 10% | BAJO | Solo si hay requisito de integración |
| 8 | Búsqueda global | 70% | BAJO | Suficiente con stack actual |
| 9 | **Dashboard** | **10%** | **ALTO** | **Widgets por rol — prioridad inmediata** |
| 10 | Auditoría | 40% | MEDIO | spatie/laravel-activitylog en modelos clave |

---

## Roadmap sugerido

| Fase | Items | Esfuerzo |
|------|-------|----------|
| **Sprint 9 (inmediato)** | Dashboard por rol (#9), Centro notificaciones UI (#6) | 8-13 pts |
| **Pre-staging** | CI/CD (#1), Auditoría (#10), Limpieza archivos (#12) | 5-8 pts |
| **Pre-producción** | Monitoreo (#3), Ambientes (#4), Mail (#6 canal), Storage S3 (#11) | Config + migración |
| **Si se requiere** | API REST (#7), Búsqueda global UI (#8), Antivirus (#13), Versionado archivos (#14) | Variable |

---
---

# Parte III: Gestión de Archivos

**Fecha de análisis:** 2026-03-08

---

## 11. Almacenamiento de evidencias — estrategia de storage

**Cobertura actual:** 50%
**Riesgo:** MEDIO

**Lo que YA existe:**
- Disco `local` (privado) en `storage/app/private/` como default
- Disco `s3` pre-configurado en `config/filesystems.php` (solo necesita credenciales en `.env`)
- Evidencias almacenadas en `storage/app/private/evidencias/{avance_id}/`
- Reportes generados en `storage/app/private/reportes/`
- Datos abiertos en `storage/app/private/datos-abiertos/` (auto-eliminados tras descarga)
- Validación: mimes PDF/Excel/Images/Word, max 10MB, SHA-256 hash de integridad
- Control de acceso en descarga (solo capturador o con `revisar_avance`)

**Lo que FALTA:**
- Sin política de retención formal (evidencias persisten indefinidamente)
- Sin migración a S3/Wasabi — todo en disco local del servidor
- Reportes PDF/Excel tienen TTL de 24h configurado pero **no se ejecuta limpieza**
- Sin redundancia — si el disco falla, se pierden todas las evidencias

**Opinión:**
Para desarrollo y staging, local es correcto. Para producción:

| Volumen esperado | Recomendación |
|-----------------|---------------|
| < 50 GB | Local + backups del servidor es suficiente |
| 50-500 GB | S3/Wasabi con driver `s3` (ya configurado, solo agregar credenciales) |
| > 500 GB | S3 + CDN para descargas + lifecycle policies |

La migración a S3 es trivial: cambiar `FILESYSTEM_DISK=s3` en `.env` y configurar credenciales. El código ya usa `Storage::disk()` — no hay paths hardcodeados.

**Acción inmediata:** Ninguna.
**Acción pre-producción:** Decidir si local o S3, configurar credenciales.

---

## 12. Limpieza de archivos huérfanos

**Cobertura actual:** 20%
**Riesgo:** MEDIO-ALTO

**Lo que YA existe:**
- `DatosAbiertosController::zip()` usa `deleteFileAfterSend()` — auto-limpieza correcta
- `LlmLogsCleanup` limpia logs de BD (no archivos) — scheduled monthly
- `EvidenciaAvance::eliminar()` borra archivo del disco al eliminar desde UI

**Problemas detectados:**

1. **Evidencias huérfanas por cascade delete:** Cuando un `Avance` se elimina, la FK con `cascadeOnDelete` borra los registros de `avance_evidencias` en BD, pero los archivos en `storage/app/private/evidencias/{avance_id}/` **quedan en disco**.

2. **Reportes sin cleanup:** `config('evaluation.exports.ttl_hours') = 24` está configurado pero **no existe comando ni job que lo ejecute**. Los archivos en `reportes/` se acumulan indefinidamente.

3. **Evidencias reemplazadas:** Si un usuario sube un archivo nuevo para el mismo documento, el archivo anterior queda huérfano (la UI crea nuevo registro, no reemplaza).

**Recomendación:**
Tres correcciones concretas:

```
1. Event listener en AvanceEvidencia::deleting → Storage::delete($ruta_archivo)
2. Comando reports:cleanup → borrar archivos en reportes/ con modified_time > TTL
3. Event listener en Avance::deleting → borrar directorio evidencias/{avance_id}/
```

El comando `reports:cleanup` debería schedularse diario a las 03:00.

**Prioridad:** MEDIA-ALTA. No es urgente en desarrollo, pero en producción el disco se llena.

---

## 13. Escaneo de malware

**Cobertura actual:** 0%
**Riesgo:** MEDIO (contexto gobierno)

**Lo que YA existe:**
- Validación de MIME type (PDF, Excel, Images, Word)
- Límite de 10MB
- SHA-256 para integridad post-upload

**Lo que FALTA:**
- Sin integración ClamAV ni VirusTotal
- Sin escaneo de contenido (un PDF malicioso pasaría la validación MIME)
- Sin cuarentena de archivos sospechosos

**Opinión:**
La validación MIME + tamaño es la primera barrera y cubre el 90% de ataques casuales. Para un sistema gubernamental, el riesgo depende de:

| Factor | Evaluación |
|--------|-----------|
| ¿Usuarios internos solamente? | Si solo operadores/planeadores conocidos → riesgo bajo |
| ¿Hay upload público? | No hay — todos los uploads requieren autenticación + permisos |
| ¿Política de seguridad institucional exige antivirus? | Si sí → ClamAV obligatorio |

**Si se requiere ClamAV:**
- Opción A: `sunspikes/clamav-validator` (paquete Laravel) — valida en upload, ~5 min setup
- Opción B: ClamAV daemon en el servidor + scan async post-upload via job

**Prioridad:** BAJA (si solo usuarios internos). MEDIA (si política institucional lo exige).

---

## 14. Versionado de archivos

**Cobertura actual:** 0%
**Riesgo:** BAJO

**Lo que YA existe:**
- MIR tiene `MirSnapshotService` con versionado de datos (JSONB snapshots), pero no de archivos
- Evidencias no tienen versionado — cada upload es un registro nuevo independiente
- Reportes se generan con timestamp único en el nombre (no reemplazan versiones anteriores)

**Lo que FALTA:**
- Si un usuario elimina una evidencia y sube otra, la original se pierde permanentemente
- No hay soft deletes en `AvanceEvidencia`
- No hay historial de versiones de un mismo documento

**Opinión:**
**No es un gap real para este sistema.** Las evidencias son documentos de soporte (PDFs de dependencias, fotos, oficios). No son documentos colaborativos que necesiten versionado.

El flujo actual es:
1. Operador sube evidencia durante captura
2. Planeador revisa
3. Se aprueba → avance se congela → evidencias inmutables

Una vez congelado, nadie puede modificar ni eliminar. El versionado solo sería útil durante la ventana de captura, y ahí el operador puede simplemente subir un archivo nuevo.

**Si en el futuro se requiere:** Agregar `SoftDeletes` a `AvanceEvidencia` + columna `version` + scope `latestVersion()`. Esfuerzo: ~2h.

**Prioridad:** BAJA. El modelo de congelamiento ya protege las evidencias aprobadas.

---

## Matriz consolidada — Gestión de Archivos

| # | Gap | Cobertura | Riesgo | Acción recomendada |
|---|-----|:---------:|--------|-------------------|
| 11 | Storage strategy | 50% | MEDIO | Decidir local vs S3 al definir hosting |
| 12 | **Archivos huérfanos** | **20%** | **MEDIO-ALTO** | **3 correcciones: event listeners + cleanup command** |
| 13 | Escaneo malware | 0% | BAJO-MEDIO | Solo si política institucional lo exige |
| 14 | Versionado archivos | 0% | BAJO | No necesario — congelamiento ya protege |

---

## Hallazgos técnicos específicos

| Ubicación | Problema | Fix |
|-----------|----------|-----|
| `AvanceEvidencia` model | Sin event listener `deleting` — archivo queda en disco | Agregar `deleting` → `Storage::delete()` |
| `Avance` model | `cascadeOnDelete` en FK no limpia archivos | Agregar `deleting` → borrar directorio `evidencias/{id}/` |
| `config/evaluation.php` | `ttl_hours: 24` sin enforcement | Crear comando `reports:cleanup` + schedule diario |
| `reportes/` directory | Acumulación indefinida | Scheduled cleanup a las 03:00 |

---
---

# Parte IV: Experiencia de Usuario

**Fecha de análisis:** 2026-03-08

---

## 15. Onboarding de nuevos usuarios

**Cobertura actual:** 0%
**Riesgo:** ALTO (UX)

**Lo que YA existe:**
- Dashboard post-login muestra `<x-welcome />` genérico de Jetstream (links a documentación de Laravel/Tailwind)
- Navegación con 5-7 items claros (Dashboard, Programas, Importaciones, Seguimiento, Plan Estatal, etc.)
- Breadcrumbs en páginas complejas via `x-page.container`
- Sin campo `onboarding_completed` ni detección de primer login en User

**Lo que FALTA:**
- Sin wizard, tour guiado ni flujo de bienvenida
- Sin explicación del sistema al primer ingreso
- Sin guía de "próximos pasos" según rol
- Sin detección de primer login

**Opinión:**
Un onboarding completo (tour interactivo tipo Shepherd.js) es deseable pero de alto esfuerzo. Lo que sí es **crítico** e inmediato es reemplazar el `<x-welcome />` con contenido útil — esto se solapa con el gap #9 (Dashboard).

Dos niveles de solución:

| Nivel | Descripción | Esfuerzo |
|-------|-------------|----------|
| **Mínimo** | Dashboard con KPIs y accesos rápidos por rol (reemplaza `<x-welcome />`) | 5 pts — ya planeado en gap #9 |
| **Completo** | Tour guiado con Shepherd.js/Intro.js + checklist de setup inicial | 8 pts — sprint dedicado |

El nivel mínimo elimina el 80% del problema. El tour guiado es nice-to-have para después de producción, cuando haya usuarios reales y feedback sobre qué confunde.

**Prioridad:** ALTA — pero se resuelve mayormente con el Dashboard (#9).

---

## 16. Ayuda contextual / tooltips metodológicos

**Cobertura actual:** 30%
**Riesgo:** MEDIO

**Lo que YA existe:**
- Descripciones a nivel de sección via `x-forms.section` con atributo `description`:
  - Etapa 1: "Describe la situación no deseada..."
  - Etapa 4: "Estos son los medios identificados..."
- Placeholders orientativos: "Ej: Alto índice de deserción escolar...", "Ej: (A / B) x 100"
- 13 prompts Blade con definiciones metodológicas completas (SHCP, CREMAA, lógica vertical/horizontal) — pero solo visibles para la IA, no para el usuario

**Lo que FALTA:**
- Sin tooltips inline para términos MML (Fin, Propósito, Componente, Actividad, Resumen Narrativo, Supuestos, Medios de Verificación)
- Sin glosario de términos accesible desde la UI
- Sin ayuda contextual en formularios MIR (el usuario debe conocer la metodología SHCP previamente)
- Las definiciones que ya existen en los prompts de IA no se reutilizan para el usuario

**Opinión:**
Las definiciones ya están escritas en los 13 prompts Blade — es contenido validado metodológicamente. La solución más eficiente es:

1. **Componente `x-ui.tooltip`** — wrapper con Alpine.js que muestra texto al hover/click (~30 min)
2. **Archivo de definiciones** — `config/glosario.php` o JSON con los términos MML extraídos de los prompts
3. **Aplicar tooltips** en MirEditor, formularios de indicador, y el árbol de problemas/objetivos

No recomiendo un tour complejo. **Tooltips puntuales en los campos más confusos** (resumen narrativo, CREMAA, supuestos) cubren el 90% de la necesidad.

**Prioridad:** MEDIA. Alto impacto para usuarios no-expertos en MML, bajo esfuerzo.

---

## 17. Accesibilidad (WCAG 2.1 AA)

**Cobertura actual:** 40%
**Riesgo:** MEDIO (contexto gobierno → puede ser requisito legal)

**Lo que YA existe:**
- ~143 form labels con `<x-label>` + input pairing (Jetstream pattern)
- 76 instancias de `focus:` en Tailwind (focus rings en inputs y botones)
- 6 imágenes con `alt` text descriptivo
- 2 `aria-label` (banner dismiss + stepper de importación)
- Diseño responsive con breakpoints sm:/md:/lg: y menú hamburguesa
- Contraste decente (indigo-600 sobre blanco, gray-800 para texto)

**Lo que FALTA:**
- Solo 2 `aria-label` en toda la app — insuficiente
- Sin `sr-only` para texto solo de screen reader
- Sin `role=` attributes en componentes custom
- Sin "skip to main content" link
- Sin focus trap en modales (`x-modals.confirm` usa Alpine pero no gestiona focus)
- Sin pruebas de accesibilidad automatizadas (no hay axe, pa11y en package.json)
- Sin keyboard navigation documentada

**Opinión:**
Jetstream provee una base razonable (labels, focus rings), pero para cumplir WCAG 2.1 AA necesitaría:

| Criterio WCAG | Estado | Esfuerzo |
|--------------|--------|----------|
| 1.1.1 Non-text content (alt) | Parcial | Bajo — agregar alt faltantes |
| 1.3.1 Info and relationships (ARIA) | Débil | Medio — agregar roles y aria-* |
| 2.1.1 Keyboard accessible | Parcial (focus rings) | Medio — focus trap en modales |
| 2.4.1 Skip navigation | No existe | Bajo — 1 link |
| 2.4.6 Headings and labels | Bueno (x-label) | OK |
| 4.1.2 Name, Role, Value | Débil | Medio — ARIA en componentes custom |

**Recomendación:** No auditoría completa ahora. Agregar:
1. Skip-to-main link (5 min)
2. `aria-label` en botones de acción sin texto (icons)
3. Focus trap en `x-modals.confirm` (Alpine `x-trap`)

Si hay requisito legal de accesibilidad gubernamental → auditoría completa con axe-core.

**Prioridad:** MEDIA. Baja urgencia si usuarios son internos con equipo estándar. Alta si hay mandato legal.

---

## 18. Modo oscuro

**Cobertura actual:** 15%
**Riesgo:** BAJO

**Lo que YA existe:**
- 45 instancias de clases `dark:` en templates Blade (fragmentos de Jetstream)
- `tailwind.config.js` **no tiene `darkMode` configurado** — default es `media` (sigue preferencia del OS)
- Sin toggle de modo oscuro en la UI
- Las clases `dark:` existentes son heredadas del boilerplate Jetstream, no fueron agregadas por el equipo

**Lo que FALTA:**
- `darkMode: 'class'` no está configurado en Tailwind (necesario para toggle manual)
- Sin botón/switch de tema en la navegación
- Solo ~45 de cientos de elementos tienen variantes `dark:` — inconsistente
- Componentes custom (`x-page.*`, `x-forms.section`, `x-ui.badge`) no tienen variantes dark

**Opinión:**
**No vale la pena ahora.** Implementar modo oscuro correctamente requiere:
1. Configurar `darkMode: 'class'` en Tailwind
2. Agregar `dark:` variants a TODOS los componentes custom (page, forms, badges, tables)
3. Toggle con persistencia (localStorage + clase en `<html>`)
4. Testing visual de todas las vistas

Eso es 2-3 días de trabajo puramente estético. Para un sistema interno gubernamental, el retorno es mínimo.

**Si se quiere:** Fase cosmética post-producción. No bloquea nada funcional.

**Prioridad:** BAJA. Cosmético, alto esfuerzo relativo al valor.

---

## 19. PWA / Modo offline

**Cobertura actual:** 0%
**Riesgo:** BAJO

**Lo que YA existe:**
- Nada. No hay `manifest.json`, service worker, ni paquete PWA.

**Lo que FALTA:**
- Todo: manifest, service worker, cache strategy, offline fallback

**Opinión:**
**No recomiendo implementar PWA.** Razones:

1. **El sistema requiere datos actualizados** — avances, evaluaciones, MIR son datos en tiempo real. Un modo offline mostraría datos stale y generaría conflictos de sincronización.
2. **Los usuarios son operadores en oficinas** — tienen conectividad estable. No es una app de campo.
3. **La complejidad de sync offline ↔ online es enorme** — especialmente con máquina de estados (avances), fórmulas calculadas, y validaciones de IA.
4. **El costo-beneficio es negativo** — semanas de desarrollo para un escenario que probablemente no ocurra.

Lo único útil sería un **service worker de cache-first para assets estáticos** (CSS, JS, imágenes) para acelerar carga. Eso toma 30 min con Workbox, pero no es "modo offline".

**Prioridad:** MUY BAJA. No implementar salvo requisito explícito.

---

## Matriz consolidada — Experiencia de Usuario

| # | Gap | Cobertura | Riesgo | Acción recomendada |
|---|-----|:---------:|--------|-------------------|
| 15 | **Onboarding** | **0%** | **ALTO** | **Se resuelve con Dashboard por rol (#9)** |
| 16 | Ayuda contextual | 30% | MEDIO | Componente tooltip + glosario MML |
| 17 | Accesibilidad | 40% | MEDIO | Skip link + aria-labels + focus trap |
| 18 | Modo oscuro | 15% | BAJO | No implementar ahora — cosmético |
| 19 | PWA/Offline | 0% | BAJO | No implementar — datos requieren conectividad |

---

## Quick wins (< 2h cada uno)

| Fix | Esfuerzo | Impacto |
|-----|----------|---------|
| Reemplazar `<x-welcome />` con contenido por rol | 1h | Alto — elimina "página vacía" |
| Componente `x-ui.tooltip` con Alpine.js | 30 min | Medio — base para ayuda contextual |
| Skip-to-main link en layout | 5 min | Bajo — cumplimiento accesibilidad básica |
| `aria-label` en botones de icono | 30 min | Bajo — mejora screen readers |
| Limpiar clases `dark:` huérfanas o configurar darkMode | 15 min | Bajo — consistencia Tailwind |

---
---

# Parte V: Testing y Calidad

**Fecha de análisis:** 2026-03-08

---

## 20. Tests E2E no planificados

**Cobertura actual:** 0% (E2E) / 85% (Feature+Unit)
**Riesgo:** MEDIO

**Lo que YA existe:**
- **437 tests** en 84 archivos (76 Feature, 7 Unit, 1 TestCase base)
- ~11,400 líneas de código de test
- Tests de componentes Livewire completos (`Livewire::test()`) — cubren rendering, interacciones, validación, permisos
- 160+ assertions de BD (`assertDatabaseHas`, `assertStatus`)
- Mocking HTTP para LLM (`Http::fake()`)
- Factories con setup de teams, roles, permisos
- `phpunit.xml` con suites Unit/Feature, aislamiento de BD, queues sync

**Lo que FALTA:**
- Sin Laravel Dusk (no hay `tests/Browser/`)
- Sin Playwright ni Cypress en `package.json`
- Sin tests de flujo completo cross-component (ej: crear programa → definir MIR → capturar avance → evaluar)
- Sin tests de JavaScript/Alpine.js (interacciones client-side)

**Opinión:**
Los tests Livewire con `Livewire::test()` son **equivalentes funcionales a E2E** para el 90% de los flujos. Prueban rendering, wire:click, validación, redirección, emisión de eventos — todo server-side. Lo único que no cubren es:

1. **Interacciones Alpine.js** (dropdowns, modales confirm, toggles)
2. **Flujos multi-página** (navegar de programa → MIR → indicador como haría un usuario real)
3. **JavaScript client-side** (filtros, ordenamiento, lazy loading)

| Opción | Cobertura | Esfuerzo | Recomendación |
|--------|-----------|----------|---------------|
| Laravel Dusk | Flujos Livewire completos | Medio — requiere ChromeDriver | Solo si hay bugs de Alpine.js |
| Playwright | Cross-browser, más robusto | Alto — setup complejo | Overkill para app interna |
| Mantener Livewire::test() | 90% de flujos | Ya implementado | **Suficiente por ahora** |

**Prioridad:** BAJA. Los tests Livewire ya cubren la gran mayoría de flujos. Solo considerar Dusk si aparecen bugs de integración Alpine/Livewire en producción.

---

## 21. Tests de performance

**Cobertura actual:** 0%
**Riesgo:** MEDIO

**Lo que YA existe:**
- Nada. Sin k6, Locust, Artillery, JMeter ni Laravel Benchmark.
- Sin N+1 query detection (`laravel-query-detector` no instalado)
- Sin métricas de response time (excepto `duration_ms` en llm_logs para llamadas IA)

**Lo que FALTA:**
- Sin baseline de tiempos de respuesta
- Sin detección de queries N+1
- Sin tests de carga concurrente
- Sin profiling de queries lentas

**Opinión:**
Para un sistema gubernamental con ~50-200 usuarios concurrentes, el performance risk es **moderado**. Los puntos críticos serían:

| Ruta | Riesgo | Por qué |
|------|--------|---------|
| `MirEditor` | ALTO | Carga MIR completa con 4 niveles + indicadores + relaciones |
| `PanelSeguimiento` | ALTO | Query todos los avances del equipo con eager loading |
| `PanelTransversal` | MEDIO | Joins cruzados evaluaciones → programas → MIR → PED |
| `evaluacion:calcular-indice --all` | MEDIO | Cálculo batch de todos los programas |

**Recomendación por fases:**

| Fase | Herramienta | Esfuerzo |
|------|------------|----------|
| **Inmediato** | `barryvdh/laravel-debugbar` (dev only) | 5 min — visibilidad de queries |
| **Pre-staging** | `beyondcode/laravel-query-detector` | 5 min — detecta N+1 automáticamente |
| **Pre-producción** | k6 script para 3-5 rutas críticas | 2h — baseline de performance |
| **Post-producción** | Laravel Telescope queries tab | Ya recomendado en gap #3 |

No recomiendo tests de carga complejos ahora. Un script k6 simple para las 4 rutas críticas es suficiente como baseline.

**Prioridad:** MEDIA. `laravel-query-detector` es el quick win más valioso (5 min, detecta problemas antes de que lleguen a producción).

---

## 22. Tests de seguridad

**Cobertura actual:** 30%
**Riesgo:** MEDIO (contexto gobierno)

**Lo que YA existe:**
- **CSRF:** Protección activa por default de Laravel (middleware `VerifyCsrfToken`)
- **XSS:** Blade escapa variables por default (`{{ }}` vs `{!! !!}`)
- **SQL Injection:** Eloquent ORM con prepared statements
- **Auth:** Jetstream + Sanctum + Fortify (2FA disponible)
- **Permisos:** Spatie Laravel-Permission con 5 permisos granulares
- **File upload validation:** MIME types + tamaño máximo
- **SHA-256 hash** en evidencias para integridad
- **Rate limiting** en LlmService (configurable por usuario)
- **Multi-tenancy:** Aislamiento por team_id en queries

**Lo que FALTA:**
- Sin OWASP ZAP ni scanner de vulnerabilidades
- Sin security headers middleware (`Content-Security-Policy`, `X-Frame-Options`, etc.)
- Sin tests específicos de seguridad (inyección, IDOR, etc.)
- Sin dependency vulnerability scanning (`composer audit` no está en CI)
- Sin política de CORS explícita (usa default Laravel)

**Opinión:**
Laravel provee una base sólida out-of-the-box. Los riesgos reales para este sistema son:

| Ataque | Protección actual | Gap |
|--------|-------------------|-----|
| SQL Injection | Eloquent ORM | ✓ Cubierto |
| XSS | Blade escaping | ✓ Cubierto (verificar `{!! !!}` usage) |
| CSRF | Middleware | ✓ Cubierto |
| IDOR (acceso a datos de otro team) | team_id scoping | ⚠️ Depende de que TODAS las queries filtren por team |
| Dependency vulnerabilities | Nada | ❌ `composer audit` no está en CI |
| Security headers | Nada | ❌ Falta CSP, X-Frame-Options |

**Recomendación:**

| Acción | Esfuerzo | Impacto |
|--------|----------|---------|
| `composer audit` en CI | 1 línea | Alto — detecta CVEs en dependencias |
| Security headers middleware | 30 min | Medio — hardening básico |
| Grep `{!! !!}` y verificar | 15 min | Alto — detectar XSS potenciales |
| OWASP ZAP scan manual | 2h | Medio — auditoría one-time |

**Prioridad:** MEDIA. `composer audit` en CI + security headers son quick wins. OWASP ZAP como auditoría pre-producción.

---

## Matriz consolidada — Testing y Calidad

| # | Gap | Cobertura | Riesgo | Acción recomendada |
|---|-----|:---------:|--------|-------------------|
| 20 | Tests E2E | 0% (pero Livewire cubre 90%) | BAJO | Mantener Livewire::test(), Dusk solo si hay bugs Alpine |
| 21 | Tests performance | 0% | MEDIO | `laravel-query-detector` (5 min) + k6 baseline pre-prod |
| 22 | Tests seguridad | 30% | MEDIO | `composer audit` en CI + security headers middleware |

---
---

# Parte VI: Documentación

**Fecha de análisis:** 2026-03-08

---

## 23. Manual de usuario

**Cobertura actual:** 0%
**Riesgo:** MEDIO

**Lo que YA existe:**
- 85+ archivos de documentación técnica en `docs/` (sprints, tickets, esquemas, retrospectivas)
- Descripciones de sección en formularios (`x-forms.section` con `description`)
- Placeholders orientativos en inputs
- 13 prompts Blade con definiciones metodológicas (uso interno IA)

**Lo que FALTA:**
- Sin manual de usuario para ningún rol
- Sin guía de flujos paso a paso
- Sin capturas de pantalla
- Sin FAQ

**Opinión:**
La documentación técnica es **excelente** (85+ archivos). Pero no existe documentación orientada al usuario final. Para un sistema gubernamental, esto es relevante porque los operadores rotan y necesitan capacitación.

**Recomendación:** No crear un manual extenso ahora. En su lugar:

1. **Post-producción:** Grabar screencasts cortos (5 min cada uno) de los 3 flujos principales por rol
2. **In-app:** Los tooltips (#16) y el dashboard (#9) reducen la necesidad de manual externo
3. **Si es requisito formal:** Generar manual con screenshots después de que la UI esté estabilizada

Crear un manual ahora es prematuro — la UI seguirá cambiando.

**Prioridad:** BAJA ahora. MEDIA post-estabilización de UI.

---

## 24. Manual metodológico MML

**Cobertura actual:** 40% (implícita)
**Riesgo:** BAJO

**Lo que YA existe:**
- 13 prompts Blade con definiciones SHCP rigurosas (sintaxis FIN/Propósito/Componente/Actividad, CREMAA, lógica vertical/horizontal)
- Validaciones Poka-Yoke con reglas de negocio MML
- Descripciones en formularios de etapas MML
- Sistema de IA que guía al usuario en tiempo real

**Lo que FALTA:**
- Sin documento formal de metodología MML descargable
- Sin glosario de términos accesible al usuario
- Sin referencia a normas SHCP originales

**Opinión:**
El sistema **es** el manual metodológico — las validaciones y la IA guían al usuario en tiempo real. Un documento PDF adicional:

- Sería redundante con lo que el sistema ya valida
- Se desactualizaría rápido si cambian las reglas

Lo que sí tiene valor es el **glosario de términos** (#16) — extraer las definiciones de los prompts a un recurso accesible in-app.

**Prioridad:** BAJA. El sistema guía al usuario. Solo crear manual si lo exige el marco normativo.

---

## 25. Documentación de API

**Cobertura actual:** 0%
**Riesgo:** BAJO

**Lo que YA existe:**
- Sanctum instalado, 1 endpoint `/api/user`
- `DatosAbiertosController` exporta CSV/JSON (funciona como API de datos)

**Lo que FALTA:**
- Sin OpenAPI/Swagger
- Sin documentación de endpoints
- Sin API resources

**Opinión:**
Ya analizado en gap #7. **No hay API que documentar.** Es una app Livewire interna. Crear documentación de API antes de crear la API es nonsense.

**Prioridad:** N/A. Solo actuar cuando exista API real.

---

## 26. Runbook de operaciones

**Cobertura actual:** 10%
**Riesgo:** ALTO (pre-producción)

**Lo que YA existe:**
- `docs/devlog/` con troubleshooting de Docker (1 archivo)
- `docs/plans/2026-02-28-docker-sail-implementation.md` con setup de Sail
- `README.md` prácticamente vacío ("# DTE-SPP", 10 bytes)
- Comandos artisan documentados en tickets de sprint

**Lo que FALTA:**
- Sin guía de instalación (`SETUP.md` o `INSTALL.md`)
- Sin guía de deploy
- Sin runbook de operaciones (qué hacer si X falla)
- Sin documentación de comandos artisan custom (8 comandos)
- Sin guía de troubleshooting
- Sin documentación de variables de entorno requeridas

**Opinión:**
Este es el **gap de documentación más crítico** para cuando otro desarrollador o equipo de ops necesite operar el sistema. Necesita como mínimo:

| Documento | Contenido | Esfuerzo |
|-----------|-----------|----------|
| **README.md** | Stack, requisitos, setup local, estructura de carpetas | 1h |
| **SETUP.md** | Paso a paso: clonar → sail up → migrate → seed → test | 30 min |
| **COMMANDS.md** | Lista de los 8 comandos artisan custom con descripción y uso | 30 min |
| **DEPLOY.md** | Checklist de deploy (según hosting elegido) | 1h post-decisión hosting |

Los 3 primeros se pueden crear ahora. `DEPLOY.md` depende de decidir hosting.

**Prioridad:** ALTA para README + SETUP + COMMANDS. MEDIA para DEPLOY (depende de hosting).

---

## Matriz consolidada — Documentación

| # | Gap | Cobertura | Riesgo | Acción recomendada |
|---|-----|:---------:|--------|-------------------|
| 23 | Manual usuario | 0% | MEDIO | Post-estabilización de UI — screencasts |
| 24 | Manual MML | 40% | BAJO | El sistema guía al usuario — solo glosario (#16) |
| 25 | Docs API | 0% | N/A | No hay API que documentar |
| 26 | **Runbook** | **10%** | **ALTO** | **README + SETUP + COMMANDS ahora** |

---
---

# Resumen General — Todos los Gaps

**Total gaps analizados:** 31

## Por prioridad

### ALTA (acción inmediata o pre-staging)
| # | Gap | Categoría | Acción |
|---|-----|-----------|--------|
| 1 | CI/CD | Infra | GitHub Actions con phpunit |
| **9** | **Dashboard por rol** | **UX/Funcionalidad** | **Widgets por rol — resuelve #9, #15, #27, #29** |
| 12 | Archivos huérfanos | Archivos | Event listeners + cleanup command |
| 26 | Runbook | Docs | README + SETUP + COMMANDS |
| 27 | Onboarding | Adopción | Se resuelve con #9 |
| 29 | Valor visible | Adopción | KPIs en dashboard con datos existentes |

### MEDIA (pre-producción)
| # | Gap | Categoría | Acción |
|---|-----|-----------|--------|
| 2 | Backups | Infra | Según hosting elegido |
| 3 | Monitoreo | Infra | Sentry + Telescope |
| 6 | Notificaciones UI | Funcionalidad | Centro de notificaciones en navbar |
| 10 | Auditoría | Funcionalidad | spatie/laravel-activitylog en modelos clave |
| 16 | Tooltips MML | UX | Componente tooltip + glosario |
| 17 | Accesibilidad | UX | Skip link + aria-labels |
| 21 | Performance | Testing | laravel-query-detector + k6 baseline |
| 22 | Seguridad | Testing | composer audit + security headers |

### BAJA (post-producción o si se requiere)
| # | Gap | Categoría | Acción |
|---|-----|-----------|--------|
| 4 | Ambientes | Infra | .env.production.example |
| 5 | Secrets | Infra | Según hosting |
| 7 | API REST | Funcionalidad | Solo si integración externa |
| 8 | Búsqueda global | Funcionalidad | Stack actual suficiente |
| 11 | Storage S3 | Archivos | Según volumen de datos |
| 13 | Antivirus | Archivos | Si política institucional lo exige |
| 14 | Versionado archivos | Archivos | Congelamiento ya protege |
| 18 | Modo oscuro | UX | Cosmético, alto esfuerzo |
| 19 | PWA/Offline | UX | No implementar |
| 20 | Tests E2E | Testing | Livewire::test() ya cubre 90% |
| 23 | Manual usuario | Docs | Post-estabilización UI |
| 24 | Manual MML | Docs | El sistema ya guía |
| 25 | Docs API | Docs | No hay API |
| 28 | Gamificación | Adopción | Nice-to-have post-producción |
| 29 | Valor visible | Adopción | Se resuelve con dashboard #9 |
| 30 | Capacitación | Adopción | Screencasts post-estabilización |
| 31 | Soporte in-app | Adopción | Chat/helpdesk externo, no custom |

---
---

# Parte VII: Riesgo de Adopción del Sistema

**Fecha de análisis:** 2026-03-08

> Un sistema metodológicamente perfecto que nadie usa es un fracaso.

---

## Diagnóstico de obstáculos

| Obstáculo | Severidad | Mitigación en el sistema actual |
|-----------|-----------|--------------------------------|
| **Curva de aprendizaje MML** | ALTA | Parcial — IA valida y sugiere en tiempo real, descripciones en formularios |
| **Resistencia al cambio ("lo hicimos en Excel")** | ALTA | Débil — no hay comparativa visible de beneficios |
| **Percepción de burocracia** | MEDIA | Parcial — formularios guiados, cálculos automáticos |
| **Falta de incentivos** | MEDIA | Inexistente — no hay métricas de uso ni reconocimiento |

---

## 27. Onboarding guiado (wizard de primer uso)

**Cobertura actual:** 0%
**Riesgo:** ALTO (adopción)

**Lo que YA existe:**
- Dashboard vacío (`<x-welcome />` genérico) — ya identificado en gap #9/#15
- Formularios con descripciones de sección y placeholders
- IA que guía al usuario durante captura MIR

**Lo que FALTA:**
- Sin wizard de primer uso
- Sin checklist de "primeros pasos"
- Sin detección de usuario nuevo (`onboarding_completed` no existe en User)
- Sin tour interactivo

**Opinión:**
Hay dos niveles, y el primero es **suficiente para lanzamiento**:

| Nivel | Descripción | Esfuerzo | Cuándo |
|-------|-------------|----------|--------|
| **Dashboard inteligente** | Reemplazar `<x-welcome />` con accesos rápidos por rol + checklist contextual | 5 pts | Sprint 9 — ya planeado (#9) |
| **Tour interactivo** | Shepherd.js/Intro.js con 5-8 pasos por flujo principal | 8 pts | Post-producción, con feedback real |

El dashboard inteligente elimina el 80% del problema de "¿qué hago ahora?". El tour interactivo es valioso pero necesita que la UI esté estable — si cambia, hay que re-grabar cada paso.

**Implementación mínima del dashboard por rol:**

```
Operador:
  - "Mis indicadores pendientes" (componente ya existe)
  - "Avances vencidos" (componente ya existe)
  - "Notificaciones recientes" (tabla notifications ya existe)
  - Quick link: "Capturar avance" → tracking

Planeador:
  - KPIs: programas activos, indicadores totales, semáforos consolidados
  - "Avances por revisar" (query avances EN_REVISION del team)
  - Quick link: "Panel de seguimiento" → tracking

Admin:
  - KPIs globales: índice eficacia promedio, programas evaluados
  - Alertas: presupuesto IA, avances vencidos cross-team
  - Quick link: "Monitoreo IA" → admin
```

**Prioridad:** ALTA — pero se resuelve con #9 (Dashboard por rol). No necesita implementación separada.

---

## 28. Gamificación leve

**Cobertura actual:** 0%
**Riesgo:** BAJO

**Concepto:** Badges/logros por completar MIR a tiempo, capturas puntuales, evaluaciones completas. Busca motivar al operador que siente que "es más trabajo".

**Opinión:**
Es una idea interesante pero tiene riesgos en contexto gubernamental:

| Pro | Contra |
|-----|--------|
| Motiva cumplimiento de plazos | Puede trivializar procesos serios |
| Visibiliza el esfuerzo del operador | "Jueguito" en sistema oficial puede generar resistencia |
| Fomenta captura completa | Usuarios pueden "gaming" el sistema (capturar basura por badge) |

**Recomendación:** No implementar gamificación explícita (badges). En su lugar, **métricas de cumplimiento visibles** que logren el mismo efecto sin trivializar:

- **Indicador de completitud** en dashboard: "Tu MIR está al 85% — falta definir supuestos en 2 niveles"
- **Puntualidad visible**: "5 de 6 capturas a tiempo este trimestre" (dato, no badge)
- **Ranking de UR por índice de eficacia** — ya existe en PanelTransversal (#S7-T6)

Esto da visibilidad sin gamificar. El ranking por UR ya genera incentivo competitivo natural.

**Prioridad:** BAJA. Las métricas de cumplimiento en el dashboard cubren la necesidad sin riesgo reputacional.

---

## 29. Valor visible ("¿qué gano usando el sistema?")

**Cobertura actual:** 20%
**Riesgo:** ALTO (adopción)

**Lo que YA existe:**
- Cálculo automático de fórmulas (FormulaEvaluatorService) — ahorra tiempo vs Excel
- Semáforos automáticos (SemaforoService) — antes se calculaban manualmente
- Validaciones IA en tiempo real — antes no existían
- Índice de eficacia automático — antes era manual
- Exportación PDF/Excel/datos abiertos — antes no existía formato estandarizado

**Lo que FALTA:**
- El usuario **no ve** cuánto tiempo ahorra
- No hay comparativa "antes vs después"
- No hay métricas de calidad de datos (ej: "la IA corrigió 15 errores en tu MIR")
- El valor del sistema es invisible para el usuario final

**Opinión:**
Este es el gap de adopción más subestimado. El sistema hace mucho, pero el usuario no lo percibe. Soluciones concretas que se implementan con datos que **ya existen**:

| Métrica de valor | Dato disponible | Dónde mostrarlo |
|-----------------|-----------------|-----------------|
| "Errores corregidos por IA" | `llm_logs` con method=validate + status=success | Dashboard operador |
| "Capturas a tiempo vs vencidas" | `avances` por estado (APROBADO vs VENCIDO) | Dashboard planeador |
| "Programas evaluados" | `evaluaciones_programa` count | Dashboard admin |
| "Índice de eficacia de tu UR" | `evaluaciones_programa.indice_eficacia` | Dashboard planeador |
| "Reportes generados" | `llm_logs` + export controller hits | Dashboard admin |

Todo esto se puede alimentar del dashboard (#9) sin tablas nuevas.

**Prioridad:** ALTA — pero se resuelve con #9 (Dashboard por rol con KPIs).

---

## 30. Capacitación (videos/tutoriales)

**Cobertura actual:** 0%
**Riesgo:** MEDIO

**Opinión:**
Los videos y material de capacitación son **responsabilidad del equipo de implementación**, no del equipo de desarrollo. El sistema puede facilitar la capacitación con:

1. **Tooltips MML** (#16) — ayuda contextual in-app
2. **Dashboard con onboarding** (#9) — guía al usuario nuevo
3. **Página de ayuda** — link a videos externos (YouTube/Loom)

No recomiendo construir un LMS dentro del sistema. Un canal de YouTube privado con 5 screencasts de 5 minutos es más efectivo y más barato.

**Screencasts sugeridos (post-estabilización UI):**
1. "Cómo crear un programa y definir la MIR" (operador, 5 min)
2. "Cómo capturar avances trimestrales" (operador, 3 min)
3. "Cómo revisar y aprobar avances" (planeador, 3 min)
4. "Cómo evaluar programas al cierre" (planeador, 5 min)
5. "Panel de administración y monitoreo IA" (admin, 3 min)

**Prioridad:** MEDIA. Post-estabilización de UI, no antes.

---

## 31. Soporte cercano (chat de ayuda in-app)

**Cobertura actual:** 0%
**Riesgo:** BAJO

**Opinión:**
**No construir chat custom.** Las opciones son:

| Opción | Costo | Esfuerzo dev |
|--------|-------|-------------|
| Crisp/Tawk.to (widget gratuito) | $0 | 5 min — pegar script en layout |
| WhatsApp Business link | $0 | 5 min — link en navbar |
| Email de soporte | $0 | 5 min — link en footer |
| Chat custom con Livewire | $0 | 2-3 días — no vale la pena |

Un widget Crisp/Tawk.to gratuito en `layouts/app.blade.php` resuelve esto en 5 minutos. No justifica desarrollo custom.

**Prioridad:** BAJA. Widget externo gratuito cuando se necesite.

---

## Matriz consolidada — Adopción

| # | Gap | Riesgo | Acción recomendada |
|---|-----|--------|-------------------|
| 27 | **Onboarding** | **ALTO** | **Dashboard por rol (#9) resuelve el 80%** |
| 28 | Gamificación | BAJO | Métricas de cumplimiento en dashboard, no badges |
| 29 | **Valor visible** | **ALTO** | **KPIs en dashboard con datos que ya existen** |
| 30 | Capacitación | MEDIO | 5 screencasts post-estabilización UI |
| 31 | Soporte in-app | BAJO | Widget Crisp/Tawk.to (5 min, gratuito) |

---

## Conclusión sobre adopción

Los 3 gaps de adopción más críticos (#27, #29, y parcialmente #30) **convergen en una sola implementación: el Dashboard por rol (#9).** Un dashboard bien diseñado que muestre:

- Qué tiene que hacer el usuario ahora (pendientes)
- Qué valor ha generado el sistema (métricas)
- Cómo va su UR vs otras (ranking)

...resuelve simultáneamente onboarding, valor visible y reduce la necesidad de capacitación formal.

**El Dashboard (#9) es, con diferencia, el item de mayor impacto en todo el análisis de gaps.**
