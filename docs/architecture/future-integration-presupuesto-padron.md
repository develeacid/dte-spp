# Integración Futura: Módulo Presupuestal y Padrón de Beneficiarios

> **Estado:** Documento arquitectónico de referencia — estos módulos **no existen aún**.
> Describe las tablas, relaciones, permisos y puntos de integración sugeridos
> para cuando se decida implementarlos.

---

## 1. Módulo Presupuestal (Financial Tracking)

### Propósito

Habilitar la generación de la **Cuenta Pública** (reporte financiero anual con
obligación legal) cruzando el **avance físico** (resultados MIR, ya existente en
el sistema) con el **avance financiero** (ejecución presupuestal, por construir).

Actualmente el sistema registra la cadena:

```
ProgramaPresupuestario
  └─ MirNivel (fin, propósito, componente, actividad)
       └─ Indicador (fórmula, semáforo, frecuencia)
            └─ MetaPeriodo (meta por trimestre/semestre/año)
                 └─ Avance (resultado capturado, semáforo, evidencias)
```

El módulo presupuestal añade una rama paralela desde `ProgramaPresupuestario`
para capturar la ejecución financiera trimestral.

### Tablas sugeridas

#### `partidas_presupuestales`

Partidas de gasto asignadas a cada programa por ejercicio fiscal.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | Auto-incremental |
| `programa_presupuestario_id` | bigint FK → `programa_presupuestarios.id` | Programa al que pertenece |
| `clave_partida` | varchar(20) | Clave del Clasificador por Objeto del Gasto (COG) |
| `descripcion` | varchar(255) | Descripción de la partida |
| `monto_aprobado` | decimal(15,2) | Presupuesto aprobado por el Congreso/Legislatura |
| `monto_modificado` | decimal(15,2) | Presupuesto después de adecuaciones |
| `ejercicio_fiscal` | smallint | Año fiscal (e.g. 2026) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indices sugeridos: `(programa_presupuestario_id, ejercicio_fiscal)`.

#### `avance_financiero`

Avance financiero trimestral por partida.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `partida_presupuestal_id` | bigint FK → `partidas_presupuestales.id` | |
| `trimestre` | smallint CHECK 1-4 | Trimestre del ejercicio |
| `monto_pagado` | decimal(15,2) | Monto efectivamente pagado |
| `monto_devengado` | decimal(15,2) | Monto devengado (obligación reconocida) |
| `monto_comprometido` | decimal(15,2) | Monto comprometido (contrato/pedido) |
| `registrado_por` | bigint FK → `users.id` | Usuario que capturó |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indices sugeridos: `(partida_presupuestal_id, trimestre)` UNIQUE.

### Relaciones con modelos existentes

```php
// ProgramaPresupuestario (existente — agregar relación)
public function partidasPresupuestales(): HasMany
{
    return $this->hasMany(PartidaPresupuestal::class);
}

// PartidaPresupuestal (nuevo)
public function programa(): BelongsTo
{
    return $this->belongsTo(ProgramaPresupuestario::class, 'programa_presupuestario_id');
}

public function avancesFinancieros(): HasMany
{
    return $this->hasMany(AvanceFinanciero::class);
}

// AvanceFinanciero (nuevo)
public function partida(): BelongsTo
{
    return $this->belongsTo(PartidaPresupuestal::class, 'partida_presupuestal_id');
}

public function registrador(): BelongsTo
{
    return $this->belongsTo(User::class, 'registrado_por');
}
```

**Cruce clave:** El servicio `IndiceEficaciaService` (o equivalente) puede
extenderse para calcular la razón avance financiero vs. avance físico por
programa y trimestre:

```
Índice de eficiencia = (% avance físico) / (% avance financiero)
```

Donde `% avance físico` se obtiene de `Avance.resultado` vs `MetaPeriodo.meta_periodo`,
y `% avance financiero` de `sum(monto_pagado)` vs `sum(monto_aprobado)` por programa.

### Reportes que se desbloquean

| Reporte | Nivel | Descripción |
|---|---|---|
| **Cuenta Pública** | Nivel 1 — Legal | Cruce de avance físico (modelo `Avance`) con avance financiero. Obligatorio ante la ASF/ASE. |
| **FMyE mejorada** | Nivel 2 — Táctico | Agrega sección "Presupuesto ejercido" a la ficha de monitoreo existente. |
| **Alerta de subejercicio** | Operativo | `monto_aprobado - monto_pagado` acumulado al cierre del trimestre; alerta si el subejercicio supera umbral configurable. |

### Puntos de integración con sistemas externos

- **SIIF, SAP, o ERP estatal:** Importación vía CSV o API REST.
- **Sugerencia:** Crear un job `ImportacionFinanciera` que:
  1. Reciba un archivo CSV (clave_partida, trimestre, pagado, devengado, comprometido).
  2. Valide contra partidas registradas.
  3. Cree/actualice registros de `AvanceFinanciero`.
  4. Registre el resultado en `importacion_reportes` (tabla existente).

### Permisos necesarios

| Permiso | Descripción |
|---|---|
| `gestionar_presupuesto` | CRUD de partidas presupuestales |
| `capturar_avance_financiero` | Registrar avance financiero trimestral |
| `exportar_cuenta_publica` | Generar y descargar reporte de Cuenta Pública |

---

## 2. Módulo Padrón de Beneficiarios

### Propósito

Registrar a los beneficiarios de programas de gobierno, permitir la generación
del **Padrón Público anonimizado** (obligación de transparencia) y habilitar
análisis geográfico de **Focalización y Brechas**.

### Tablas sugeridas

#### `beneficiarios`

Registro individual de personas beneficiarias.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `curp` | text (encrypted) | CURP — cifrado en reposo (LGPDPPSO) |
| `nombre_completo` | text (encrypted) | Nombre completo — cifrado en reposo |
| `fecha_nacimiento` | date | |
| `genero` | varchar(1) | M, F, X |
| `municipio_id` | bigint FK nullable | Catálogo de municipios (por crear o vincular a INEGI) |
| `localidad` | varchar(255) | Nombre de localidad |
| `latitud` | decimal(10,7) | Coordenada GPS |
| `longitud` | decimal(10,7) | Coordenada GPS |
| `activo` | boolean default true | Beneficiario vigente |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indice sugerido: `curp` (unique, sobre el valor cifrado para evitar duplicados).

#### `apoyos_entregados`

Registro de cada apoyo entregado a un beneficiario.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `beneficiario_id` | bigint FK → `beneficiarios.id` | |
| `programa_presupuestario_id` | bigint FK → `programa_presupuestarios.id` | Programa que otorga el apoyo |
| `tipo_apoyo` | varchar(100) | Descripción del tipo (monetario, especie, servicio) |
| `monto` | decimal(12,2) | Valor monetario del apoyo |
| `fecha_entrega` | date | |
| `evidencia_path` | varchar(500) nullable | Ruta al archivo de evidencia |
| `entregado_por` | bigint FK → `users.id` | Usuario que registró la entrega |
| `ejercicio_fiscal` | smallint | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

Indices: `(beneficiario_id, programa_presupuestario_id, ejercicio_fiscal)`.

#### `documentos_soporte`

Documentos de identificación y comprobantes del beneficiario.

| Campo | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `beneficiario_id` | bigint FK → `beneficiarios.id` | |
| `tipo_documento` | enum: `INE`, `comprobante_domicilio`, `CURP`, `otro` | |
| `archivo_path` | varchar(500) | Ruta al archivo digitalizado |
| `verificado` | boolean default false | Documento verificado por un revisor |
| `verificado_por` | bigint FK nullable → `users.id` | |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

### Cumplimiento LGPDPPSO (Protección de datos personales)

La Ley General de Protección de Datos Personales en Posesión de Sujetos
Obligados aplica directamente a este módulo. Consideraciones obligatorias:

1. **Cifrado en reposo:** `curp` y `nombre_completo` deben usar el cast
   `encrypted` de Laravel o `Crypt::encryptString()`. Ejemplo:

   ```php
   // app/Models/Padron/Beneficiario.php
   protected function casts(): array
   {
       return [
           'curp' => 'encrypted',
           'nombre_completo' => 'encrypted',
           'fecha_nacimiento' => 'date',
           'activo' => 'boolean',
       ];
   }
   ```

2. **Padrón Público anonimizado:** La exportación pública solo debe incluir
   `municipio`, `genero`, `tipo_apoyo`, `monto` — **nunca** CURP ni nombre.

3. **Bitácora de acceso (audit trail):** Todo acceso a datos personales debe
   registrarse. El proyecto ya usa `spatie/laravel-activitylog` (ver migración
   `2026_03_15_010000_create_activity_log_table.php`), así que el modelo
   `Beneficiario` debe usar el trait `LogsActivity`.

4. **Política de retención:** Definir en `config/padron.php` el número de años
   tras los cuales los datos se purgan automáticamente. Implementar un command
   `padron:purge-expired` para ejecutar vía scheduler.

### Georreferenciación para Focalización

El proyecto ya usa la imagen Docker `pgvector/pgvector:pg16` para PostgreSQL.
Esta imagen soporta extensiones adicionales. Para análisis geográfico:

```sql
CREATE EXTENSION IF NOT EXISTS postgis;
```

Esto habilita:
- `ST_Within(geometry, geometry)` — verificar si un punto está dentro de una zona.
- `ST_Distance(geography, geography)` — distancia entre puntos.
- `ST_MakePoint(longitud, latitud)` — crear geometrías desde coordenadas.

Caso de uso principal: generar mapas de calor que muestren la distribución
de apoyos vs. la distribución de necesidades (datos CONEVAL/INEGI), permitiendo
identificar zonas sub-atendidas.

### Reportes que se desbloquean

| Reporte | Nivel | Descripción |
|---|---|---|
| **Padrón de Beneficiarios Público** | Nivel 3 — Transparencia | Lista anonimizada de apoyos entregados por programa, municipio y tipo. |
| **Análisis de Focalización y Brechas** | Nivel 2 — Táctico | Mapas de calor geográficos: apoyo entregado vs. necesidad detectada. |
| **Concentrado de Apoyos** | Operativo | Resumen de apoyos por municipio, programa y ejercicio fiscal. |

### Permisos necesarios

| Permiso | Descripción |
|---|---|
| `gestionar_padron` | CRUD de beneficiarios y documentos soporte |
| `ver_datos_personales` | Acceso a CURP y nombre (campos cifrados) |
| `exportar_padron_publico` | Generar exportación anonimizada |

---

## 3. Diagrama de Integración

Relación de los nuevos módulos con la estructura existente del sistema:

```
ProgramaPresupuestario (existente)
│
├── MirNiveles (existente)
│   └── Indicadores (existente)
│       └── MetaPeriodo (existente)
│           └── Avance (existente) ──── avance FÍSICO
│
├── PartidaPresupuestal (NUEVO)
│   └── AvanceFinanciero (NUEVO) ──── avance FINANCIERO
│
├── ApoyosEntregados (NUEVO, desde Padrón)
│   └── Beneficiario (NUEVO)
│       ├── DocumentosSoporte (NUEVO)
│       └── Georreferenciación (latitud/longitud + PostGIS)
│
└── REPORTES CRUZADOS
    ├── Cuenta Pública = Avance Físico ⊕ Avance Financiero
    ├── FMyE mejorada = FMyE actual + sección presupuestal
    └── Focalización = Apoyos georreferenciados vs. datos CONEVAL
```

### Flujo de datos para Cuenta Pública

```
┌─────────────────────────────┐     ┌──────────────────────────────┐
│  AVANCE FÍSICO (existente)  │     │  AVANCE FINANCIERO (nuevo)   │
│                             │     │                              │
│  MetaPeriodo.meta_periodo   │     │  PartidaPresupuestal         │
│  Avance.resultado           │     │    .monto_aprobado           │
│  → % cumplimiento físico    │     │  AvanceFinanciero            │
│                             │     │    .monto_pagado             │
│                             │     │  → % ejercicio financiero    │
└─────────────┬───────────────┘     └──────────────┬───────────────┘
              │                                    │
              └──────────┬─────────────────────────┘
                         │
                         ▼
              ┌──────────────────────┐
              │    CUENTA PÚBLICA    │
              │                      │
              │  Por programa:       │
              │  - % avance físico   │
              │  - % avance financ.  │
              │  - Índice eficiencia │
              │  - Subejercicio      │
              └──────────────────────┘
```

---

## 4. Orden de Implementación Sugerido

La implementación se recomienda en tres fases, priorizando por riesgo legal y
complejidad técnica:

### Fase 1: Módulo Presupuestal

**Prioridad:** Alta — la Cuenta Pública es obligación legal con fecha de entrega
fija ante la ASF/ASE.

- Crear migraciones para `partidas_presupuestales` y `avance_financiero`.
- Crear modelos `PartidaPresupuestal` y `AvanceFinanciero` en `app/Models/Presupuesto/`.
- Agregar relación `partidasPresupuestales()` a `ProgramaPresupuestario`.
- Implementar CRUD de partidas y captura de avance financiero.
- Implementar job `ImportacionFinanciera` para carga CSV.
- Agregar permisos y asignarlos a roles existentes.
- Generar reporte de Cuenta Pública (PDF vía DomPDF, ya instalado).

### Fase 2: Padrón de Beneficiarios (Registro y Transparencia)

**Prioridad:** Media — requiere infraestructura de cifrado y cumplimiento
normativo antes de almacenar datos personales.

- Definir `config/padron.php` con política de retención.
- Crear migraciones para `beneficiarios`, `apoyos_entregados`, `documentos_soporte`.
- Crear modelos en `app/Models/Padron/` con casts `encrypted`.
- Implementar audit trail con `LogsActivity`.
- CRUD de beneficiarios con control de acceso a campos PII.
- Exportación de Padrón Público anonimizado (Excel vía Maatwebsite, ya instalado).
- Agregar permisos `gestionar_padron`, `ver_datos_personales`, `exportar_padron_publico`.

### Fase 3: Focalización y Análisis Geográfico

**Prioridad:** Baja — requiere PostGIS, datos geográficos de INEGI/CONEVAL y
capacidad de visualización de mapas.

- Habilitar extensión PostGIS en PostgreSQL.
- Agregar columna geometry o usar `latitud`/`longitud` ya definidos.
- Integrar catálogo de municipios/localidades de INEGI.
- Implementar consultas espaciales para análisis de brechas.
- Visualización con Leaflet.js o similar en el frontend.

---

## Referencias

- **Modelos existentes consultados:**
  - `app/Models/ProgramaPresupuestario.php` — modelo raíz de programas
  - `app/Models/Mml/MirNivel.php` — niveles de la MIR
  - `app/Models/Mml/Indicador.php` — indicadores con fórmula y semáforo
  - `app/Models/Mml/MetaPeriodo.php` — metas por periodo
  - `app/Models/Tracking/Avance.php` — avance físico capturado
- **Paquetes ya instalados:** `barryvdh/laravel-dompdf`, `maatwebsite/excel`, `spatie/laravel-activitylog`
- **Base de datos:** PostgreSQL 16 (imagen `pgvector/pgvector:pg16`)
