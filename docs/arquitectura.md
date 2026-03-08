# Arquitectura del Sistema — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## 1. Visión General

El SPP 2026 es una aplicación web monolítica basada en Laravel 12 que implementa el ciclo completo de planeación programática gubernamental. La arquitectura prioriza simplicidad operativa, mantenibilidad y seguridad sobre escalabilidad horizontal.

```
┌──────────────────────────────────────────────────────────┐
│                      Navegador                            │
│              (Livewire 3 + Alpine.js)                     │
├──────────────────────────────────────────────────────────┤
│                      Nginx                                │
│              (Reverse Proxy + SSL)                        │
├──────────────────────────────────────────────────────────┤
│                   Laravel 12 (PHP 8.2)                    │
│  ┌─────────┐ ┌──────────┐ ┌──────────┐ ┌─────────────┐  │
│  │ Livewire│ │ Services │ │ Models   │ │ Middleware  │  │
│  │ Comps   │ │          │ │ (Eloquent)│ │             │  │
│  └─────────┘ └──────────┘ └──────────┘ └─────────────┘  │
├──────────────────────────────────────────────────────────┤
│  PostgreSQL 16  │   Redis 7    │  Storage (local fs)     │
│  (+ pgvector)   │  (cache/queue│                         │
│                 │   /sessions) │                         │
└──────────────────────────────────────────────────────────┘
```

---

## 2. Stack Tecnológico

| Capa | Tecnología | Justificación |
|------|-----------|---------------|
| Backend | Laravel 12, PHP 8.2+ | Framework maduro, ecosistema gobierno México |
| Frontend | Livewire 3 + Alpine.js + Tailwind CSS | Interactividad sin SPA, SSR por defecto |
| Base de Datos | PostgreSQL 16 | JSONB, pgvector para embeddings, robustez |
| Cache/Queue/Session | Redis 7 | Bajo overhead, integración nativa Laravel |
| Auth | Jetstream + Fortify + Sanctum | 2FA, Teams, API tokens out-of-box |
| Permisos | Spatie Permission | RBAC flexible, guard-aware |
| Auditoría | Spatie Activity Log | Registro automático de cambios |
| PDF | barryvdh/laravel-dompdf | Generación server-side |
| Excel | Maatwebsite/Excel | Import/export de hojas de cálculo |
| Embeddings | pgvector + OpenAI API | Búsqueda semántica (optional) |
| Fórmulas | Symfony Expression Language | Evaluación segura de fórmulas de indicadores |
| CI | GitHub Actions | Pipeline automatizado |
| Dev | Laravel Sail (Docker) | Entorno reproducible |

---

## 3. Organización del Código

### 3.1 Estructura de Directorios

```
app/
├── Console/Commands/       # 7 comandos artisan programados
├── Enums/                  # 15 enums PHP 8.1 (estados, tipos)
├── Http/
│   ├── Controllers/        # Controllers por dominio
│   │   ├── Cascade/        # PED, Alineación, Programas Derivados
│   │   ├── Evaluation/     # Export, Datos Abiertos
│   │   ├── Tracking/       # Evidencias
│   │   └── OnboardingController.php
│   └── Middleware/          # 4 middleware custom
├── Livewire/               # 37 componentes Livewire
│   ├── Admin/              # MonitoreoIa, GestionUsuarios, Auditoria
│   ├── Cascade/            # PED tree, alineación, importador
│   ├── Mml/                # MIR editor, árboles, importación
│   ├── Tracking/           # Captura, flujos, desbloqueos
│   ├── Evaluation/         # Evaluación, panel transversal
│   ├── Dashboard.php       # Dashboard role-based
│   ├── NotificationBell.php
│   └── NotificationsIndex.php
├── Models/                 # 38 modelos Eloquent
│   ├── Mml/                # Arbol, Indicador, MirNivel, MetaPeriodo...
│   ├── Tracking/           # Avance, AvanceEvidencia, Desbloqueo
│   ├── Evaluation/         # EvaluacionPrograma, AnexoTransversal
│   └── [root]              # User, Team, PED models, ODS, PND
├── Notifications/          # Notificaciones del sistema
└── Services/               # Lógica de negocio
    ├── DashboardService.php
    ├── InvitacionUsuarioService.php
    ├── PedMarkdownParser.php
    ├── Embeddings/         # Generación y búsqueda semántica
    ├── Evaluation/         # Cálculo de índices
    ├── Llm/                # Integración OpenAI
    ├── Mml/                # Lógica MML (árboles, MIR)
    └── Tracking/           # Semáforo, workflows
```

### 3.2 Dominios Funcionales

| Dominio | Responsabilidad | Modelos Clave |
|---------|-----------------|---------------|
| **Cascade** | Cascada PED → PND → ODS, alineación | PedPlan, PedEje, PedTema, PedObjetivoEstrategico, PedEstrategia, PedLineaAccion |
| **MML** | Metodología Marco Lógico, MIR | ProgramaPresupuestario, Arbol, ArbolNodo, MirNivel, Indicador |
| **Tracking** | Seguimiento de avances | Avance, AvanceEvidencia, MetaPeriodo, Desbloqueo |
| **Evaluation** | Evaluación y reportes | EvaluacionPrograma, AnexoTransversal |
| **Admin** | Usuarios, IA, auditoría | User, Team, LlmLog, ActivityLog |

---

## 4. Patrones Arquitectónicos

### 4.1 Livewire Full-Page Components

Las rutas apuntan directamente a componentes Livewire como full-page components:

```php
Route::get('/dashboard', Dashboard::class)->name('dashboard');
Route::get('/seguimiento/captura/{avance}', CapturaAvance::class);
```

**Ventajas:** Sin controllers intermedios para vistas simples, estado manejado por Livewire.

### 4.2 Tradicional Controllers para CRUD Complejo

Los dominios con CRUD extenso (PED, Alineación) usan controllers tradicionales:

```php
Route::post('/ped/plan', [PedController::class, 'storePlan']);
Route::put('/ped/eje/{eje}', [PedController::class, 'updateEje']);
```

### 4.3 Services para Lógica de Negocio

La lógica compleja se extrae a Services inyectados en componentes:

```
Livewire Component → Service → Model/Query
     ↓                  ↓
   Vista            Cache (Redis)
```

Servicios clave:
- `DashboardService`: Estadísticas, semáforo, tendencias (cacheado 10 min)
- `InvitacionUsuarioService`: Flujo de activación de cuentas
- `PedMarkdownParser`: Parser de archivos Markdown para importar PED
- `Tracking/SemaforoService`: Cálculo de semáforo (verde/amarillo/rojo)
- `Evaluation/IndiceEficaciaService`: Cálculo ponderado de eficacia

### 4.4 Computed Properties (Livewire)

El Dashboard usa `#[Computed]` properties con lógica condicional por permisos:

```php
#[Computed]
public function adminStats(): ?object
{
    if (!auth()->user()->can('revisar_avance')) return null;
    return app(DashboardService::class)->getAdminStats($this->teamId());
}
```

### 4.5 Multi-Tenancy por Teams

Aislamiento de datos usando Jetstream Teams:

```php
// Scope en modelo
public function scopeParaTeam(Builder $query, int $teamId): Builder
{
    return $query->where('team_id', $teamId);
}

// Uso en queries
ProgramaPresupuestario::paraTeam($user->currentTeam->id)->get();
```

---

## 5. Flujo de Datos

### 5.1 Ciclo de Planeación

```
1. Cascada PED
   PedPlan → Ejes → Temas → Objetivos → Estrategias → Líneas de Acción

2. Alineación
   PED ↔ PND ↔ ODS (tablas pivot)
   Líneas de Acción ↔ Programas Derivados

3. MML (por programa)
   Definición Problema → Árbol Problema → Árbol Objetivos → Alternativas → MIR

4. MIR
   MirNivel (4 niveles) → Indicador → Variables, Medios, CREMAA

5. Calendarización
   Indicador → MetaPeriodo (metas por período fiscal)

6. Seguimiento
   MetaPeriodo → Avance (captura) → Evidencias
   Avance: en_captura → en_revision → [observado ↔ en_captura] → aprobado
                                                                  ↓
                                                               vencido

7. Evaluación
   Avances aprobados → EvaluacionPrograma (índice de eficacia ponderado)
```

### 5.2 Flujo de un Avance

```
Operador captura          Planeador/Admin revisa       Sistema calcula
┌─────────────┐          ┌──────────────────┐         ┌───────────────┐
│ CapturaAvance│    →     │  FlujosAvance    │    →    │ Semáforo auto │
│ (resultado)  │         │ (aprobar/observar)│        │ (verde/amarillo│
│ + evidencias │         │                  │         │  /rojo)        │
└─────────────┘          └──────────────────┘         └───────────────┘
```

---

## 6. Base de Datos

### 6.1 Diseño

- **54 tablas** organizadas en 5 dominios + sistema
- **Soft deletes** en ProgramaPresupuestario
- **JSONB** para snapshots de MIR, resultados de evaluación, validación de sintaxis, datos importados
- **pgvector** para embeddings de búsqueda semántica
- **Índices HNSW** para búsqueda vectorial eficiente

### 6.2 Relaciones Clave

```
ProgramaPresupuestario (1) ──→ (N) MirNivel (1) ──→ (N) Indicador
                                                          │
                                                    (1) ──→ (N) MetaPeriodo
                                                                  │
                                                            (1) ──→ (1) Avance
                                                                        │
                                                                  (1) ──→ (N) AvanceEvidencia
```

### 6.3 Estrategia de Cache

| Clave | TTL | Datos |
|-------|-----|-------|
| `dashboard:admin-stats:{teamId}` | 10 min | KPIs admin |
| `dashboard:semaforo:{teamId}` | 10 min | Distribución semáforo |
| `dashboard:avance-programa:{teamId}` | 10 min | Avance por programa |
| `dashboard:tendencia:{teamId}` | 10 min | Tendencia 6 meses |
| `dashboard:operador-stats:{userId}` | 10 min | KPIs operador |
| Spatie Permission cache | Indefinido | Roles y permisos |

---

## 7. Frontend

### 7.1 Arquitectura de Componentes

Patrón **Atomic Design con Slots**:

```
Layout (x-app-layout)
  └── Page (x-page.container, x-page.header)
       └── Module (Livewire full-page component)
            └── UI Components (x-ui.*, x-forms.*, x-data.*)
```

### 7.2 Componentes Blade Reutilizables

| Categoría | Componentes |
|-----------|------------|
| `layout/` | Wrapper principal |
| `page/` | container, header, form-footer |
| `ui/` | topbar, sidebar, tooltip, help-label |
| `forms/` | section, inputs |
| `data/` | Tablas, listas |
| `modals/` | confirm (con focus trap) |

### 7.3 Dashboard Role-Based

```blade
@if($this->dashboardRole === 'admin')
    @include('livewire.dashboard.partials._admin')
@elseif($this->dashboardRole === 'planeador')
    @include('livewire.dashboard.partials._planeador')
@else
    @include('livewire.dashboard.partials._operador')
@endif
```

Admin incluye Planeador, que a su vez tiene sus propios widgets.

---

## 8. Integraciones Externas

| Servicio | Uso | Configuración |
|----------|-----|---------------|
| OpenAI API | Embeddings, asistencia IA, validación sintaxis | `EMBEDDING_API_KEY`, rate limited |
| Fonts Bunny | Tipografía web | CDN, incluido en CSP |
| UI Avatars | Avatares por defecto | CDN, incluido en CSP |
| SMTP | Envío de correos (invitaciones, notificaciones) | `MAIL_*` en .env |

---

## 9. Seguridad (Resumen)

- Autenticación 2FA obligatoria
- RBAC con 3 roles y 9 permisos
- Multi-tenant por Teams con aislamiento de queries
- CSP, X-Frame-Options, X-Content-Type-Options
- Auditoría con spatie/laravel-activitylog (8 modelos)
- Archivos en storage privado con descarga controlada

Ver `docs/seguridad.md` para documento completo.

---

## 10. Observabilidad

### Logs

| Log | Ubicación | Contenido |
|-----|-----------|-----------|
| Aplicación | `storage/logs/laravel.log` | Errores, warnings, debug |
| Workers | `storage/logs/worker.log` | Jobs de cola |
| LLM | Tabla `llm_logs` | Calls a OpenAI con costos |
| Auditoría | Tabla `activity_log` | Cambios en modelos |

### Comandos de Mantenimiento

| Comando | Frecuencia | Función |
|---------|-----------|---------|
| `mir:abrir-periodos` | Diario 06:00 | Abrir períodos de captura |
| `mir:cerrar-vencidos` | Diario 23:00 | Marcar avances vencidos |
| `reports:cleanup` | Diario 03:00 | Limpiar reportes expirados |
| `app:embeddings-generate` | Diario 02:00 | Generar embeddings |
| `llm:cleanup-logs` | Mensual | Purgar logs LLM (>90 días) |

---

## 11. Decisiones de Diseño

| Decisión | Alternativa Considerada | Justificación |
|----------|------------------------|---------------|
| Monolito Laravel | Microservicios | Equipo pequeño, operación gubernamental simple, menor complejidad operativa |
| Livewire 3 | Vue.js/React SPA | SSR por defecto, menor complejidad frontend, sin API separada |
| PostgreSQL | MySQL | JSONB nativo, pgvector para embeddings, mejor para datos complejos |
| Redis | Database cache | Sesiones + cache + queue en un servicio, baja latencia |
| Spatie Permission | Bouncer, Gates manuales | Maduro, bien documentado, soporte Teams |
| Full-page Livewire | Controller + View | Menos boilerplate para vistas con estado |
| Atomic Design | BEM/Component library | Consistencia visual, slots para composición |
| Docker Sail (dev) | Vagrant, nativo | Reproducibilidad, onboarding rápido |
