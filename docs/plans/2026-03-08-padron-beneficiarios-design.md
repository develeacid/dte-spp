# Diseño: Padrón Único de Beneficiarios de la Secretaría

**Fecha:** 2026-03-08
**Estado:** Aprobado — pendiente de plan de implementación

---

## 1. Posicionamiento Arquitectónico

### ¿Qué es este sistema?

El Padrón Único de Beneficiarios de la Secretaría es una **aplicación Laravel independiente** que actúa como fuente de verdad sobre quién recibe beneficios públicos dentro de la dependencia. No es un módulo del sistema MIR/PbR — es un servicio que el sistema MIR consume, igual que lo harán otros sistemas internos.

### ¿Por qué separado?

Tres razones que se combinan y hacen la separación necesaria, no opcional:

1. **PII sensible** — Contiene nombre, domicilio, CURP, condición socioeconómica. Requiere políticas de seguridad distintas al sistema de indicadores, que maneja información pública.
2. **PostGIS** — Usa geometrías, queries espaciales y capas INEGI. Una carga técnica que no debe compartir recursos con transacciones de la MIR.
3. **Ciclo de vida independiente** — Debe poder entregarse al estado como aplicación autónoma cuando centralicen el padrón, sin necesidad de extraerlo de otro sistema.

### Modelo de integración (hub-and-spoke)

```
Sistema MIR (este)    ──┐
Sistema RRHH          ──┤
Sistema de Trámites   ──┤──→  Padrón Único  ──→ (futuro: SIPPRES estatal)
Otros sistemas UR     ──┘         ↑
                              API interna
                              autenticada
```

### Ruta de migración al estado

El sistema se diseña desde el inicio para ser entregable: base de datos propia, autenticación por tokens, sin dependencias de código externo. Cuando el estado centralice el padrón, se entrega la aplicación completa o se adapta como adaptador hacia el sistema estatal.

---

## 2. Stack Técnico y Modelo de Datos

### Stack

| Capa | Tecnología | Justificación |
|------|-----------|---------------|
| Backend | Laravel 12, PHP 8.2+ | Consistencia con el ecosistema interno |
| Base de datos | PostgreSQL + PostGIS | Geometrías, `ST_Contains`, `ST_Intersects`, queries espaciales |
| Frontend operativo | Leaflet.js + Alpine.js (TALL) | Mapas interactivos sin SPA completa |
| Caché | Redis | GeoJSON pesados, endpoints públicos con TTL |
| Analítica profunda | Metabase (Docker) | Conectado a réplica de lectura, sin código nuevo por query |
| Auth inter-servicio | Laravel Sanctum (tokens) | API interna para sistemas de la secretaría |

### Modelo de datos principal

**Separación identidad / transacción** — un beneficiario es un sujeto de derecho único; un enrollment es el apoyo recibido. Esta separación es la clave para detectar duplicidades y evitar duplicar PII.

```
beneficiaries  (una fila por persona/ente — nunca se duplica)
├── id
├── curp_rfc
├── type                      (persona_fisica | persona_moral)
├── nombre / razon_social / apellidos
├── beneficiary_catalog_key   (catálogo SIPPRES, claves 1–99)
├── sex, gender, age_group, ethnicity, disability
├── socioeconomic_level, household_type
├── location                  (PostGIS POINT — domicilio georreferenciado)
└── address                   (estado, municipio, localidad, calle)

enrollments  (una fila por apoyo recibido — muchos por beneficiario)
├── id
├── beneficiary_id            (FK → beneficiaries)
├── program_id
├── component_id
├── enrollment_date
├── status                    (aprobado | rechazado | observado)
└── monto_entregado           (para analítica de inversión por municipio)
```

**Capas geoespaciales importadas**

```
inegi_layers
├── estados                   (MULTIPOLYGON)
├── municipios                (MULTIPOLYGON)
├── localidades               (POINT / POLYGON)
└── zonas_atencion_prioritaria (MULTIPOLYGON)

coneval_layers
└── pobreza_municipio         (porcentaje pobreza, año, fuente)

marginacion_layers
└── indices_marginacion       (float, por municipio/localidad, fuente CONAPO)
```

### Dos APIs con políticas distintas

```
/api/v1/padron/*             → Sanctum token requerido
                               PII completa disponible
                               Para: MIR, RRHH, sistemas internos de la secretaría

/api/v1/datos-abiertos/*     → Anónimo, rate-limited, Redis cache
                               Solo datos agregados: conteos, porcentajes, montos totales
                               GeoJSON de zonas, nunca puntos individuales de domicilio
                               Para: transparencia, QGIS, portales ciudadanos, Contraloría
```

---

## 3. Las Cuatro Capas Operativas

### Capa 1 — Operativa (Operadores de Unidades Responsables)

Flujo de inscripción con validación geográfica bloqueante (poka-yoke geográfico):

```
Operador abre formulario de inscripción
    → Leaflet muestra polígono del municipio permitido por las ROP del programa
    → Operador coloca pin de domicilio del beneficiario
    → Alpine.js envía coordenadas al backend en tiempo real
    → PostGIS ejecuta ST_Contains(poligono_municipio_rop, punto_domicilio)
    → Falla: formulario no guarda, mensaje descriptivo al operador
    → Éxito: inscripción procede, beneficiario entra al padrón con status "aprobado"
```

**Escenario de conectividad degradada (captura en campo):**

```
    → Formulario funciona sin mapa (GPS del dispositivo o coordenadas manuales)
    → La validación ST_Contains se ejecuta al sincronizar, no bloquea la captura
    → Registros quedan en estado "pendiente_validacion_geografica"
    → El operador es notificado al sincronizar si algún registro no pasó validación
```

Micro-dashboards del operador: mapa de beneficiarios capturados hoy, heatmap de densidad por zona para orientar encuestadores. Sin queries pesados — datos del día, no históricos.

---

### Capa 2 — Analítica (Secretarios, DGPOP, evaluadores)

Metabase conectado a **réplica de lectura** de PostgreSQL. Los queries de análisis de brechas (joins de polígonos INEGI contra miles de beneficiarios) son operaciones pesadas que no deben competir con las transacciones operativas.

| Tipo de análisis | Operación PostGIS | Vista en Metabase |
|-----------------|------------------|------------------|
| Cobertura regional | `COUNT` agrupado por municipio | Mapa coroplético de beneficiarios |
| Brecha de pobreza | `ST_Intersects` capa CONEVAL + enrollments de programa | Zonas con apoyo vs zonas con mayor índice de pobreza |
| Duplicidad espacial | `ST_Within` domicilio del beneficiario vs polígono elegible del programa | Beneficiarios cuyo domicilio está fuera de la zona objetivo |
| Tablero de inversión | `SUM(monto_entregado)` agrupado por municipio | Distribución del gasto, detección de concentración sectorial |

Los analistas construyen sus propios tableros y cruces (género × municipio × programa × año) sin requerir código nuevo.

---

### Capa 3 — Transparencia y Datos Abiertos (ciudadanos, Contraloría, QGIS)

```
GET /api/v1/datos-abiertos/geojson?programa=feria-mezcal&año=2025
    → GeoJSON de zonas de cobertura (polígonos de municipios, no puntos de domicilio)
    → Atributos: total_beneficiarios, monto_total, desagregacion_genero
    → Cache Redis (TTL configurable por programa)

GET /api/v1/datos-abiertos/csv?programa=feria-mezcal&año=2025
    → CSV anonimizado: tipo_beneficiario, municipio, monto
    → Sin nombre, sin CURP, sin domicilio exacto
```

Todos los endpoints públicos: Redis cache, rate limiting, sin PII. Cumple la obligación normativa de publicación de padrones en formatos accesibles (Ley de Transparencia y Acceso a la Información).

---

### Capa 4 — Pipeline INEGI/CONEVAL (proceso administrativo interno)

Las capas geoespaciales de referencia no se actualizan en tiempo real. Son procesos que ejecuta el administrador del sistema cuando los organismos publican nuevas versiones (~anualmente).

```bash
# Importar capa del Marco Geoestadístico Nacional
php artisan padron:import-inegi municipios municipios_2025.shp

# Importar índices de pobreza CONEVAL
php artisan padron:import-coneval 2025 pobreza_municipal_2025.csv
```

Cada importación registra versión, fecha y fuente para trazabilidad en auditorías.

---

## 4. Integración Inter-sistemas y Seguridad PII

### Scopes de autenticación por sistema

Cada sistema interno recibe un token Sanctum con scopes mínimos necesarios:

| Sistema | Scopes | Justificación |
|---------|--------|---------------|
| Sistema MIR | `padron:read`, `padron:validate` | Lee conteos para indicadores, verifica duplicidad |
| Sistema RRHH | `padron:validate` | Solo verificar si una CURP ya está en padrón |
| Operadores UR | `padron:enroll`, `padron:read` | Inscriben y consultan sus propios beneficiarios |
| Contraloría | `padron:read` (con auditoría) | Acceso completo con log de cada consulta |

### Flujo de validación bloqueante (caso duplicidad espacial)

```
UR intenta inscribir beneficiario
    → Padrón verifica: ¿CURP ya tiene enrollment activo en programa incompatible?
    → Padrón verifica: ¿domicilio está dentro del polígono del programa?
                       ST_Contains(poligono_programa, punto_domicilio)
    → Falla en cualquier punto: rechaza con código de error específico
    → Éxito: crea enrollment con status "aprobado"
    → Sistema MIR consume COUNT de enrollments aprobados para sus indicadores MIR
```

### Separación de acceso a PII

```
Operadores UR (sesión app)       → nombre, CURP, domicilio, monto de sus programas
Sistemas internos (token API)    → según scope otorgado
Metabase / analistas (réplica)   → datos demográficos y geográficos; sin nombre ni domicilio exacto
API pública (sin auth)           → municipio, tipo_beneficiario, conteo, monto_total
Contraloría (exportación)        → CSV completo con auditoría de acceso registrada
```

### Auditoría de acceso a PII

Todo acceso a datos personales queda registrado: sistema solicitante, token, endpoint, timestamp. Requerimiento de la Ley General de Protección de Datos Personales en Posesión de Sujetos Obligados (LGPDPPSO).

---

## Resumen de decisiones clave

| Decisión | Alternativa descartada | Razón |
|----------|----------------------|-------|
| App Laravel independiente | Módulo interno en sistema MIR | PII requiere aislamiento; entregable al estado |
| PostGIS completo | Solo claves INEGI de catálogo | Análisis de brechas y duplicidad espacial lo requieren |
| DB réplica para Metabase | Conexión directa a DB principal | Queries analíticos pesados no deben competir con operaciones |
| Dos APIs separadas | Una API con flags | Políticas de seguridad radicalmente distintas |
| Separación beneficiaries/enrollments | Program_id en tabla beneficiaries | Muchos-a-muchos; duplicidad imposible de detectar de otra forma |
| Captura offline con validación diferida | Bloquear sin conexión | Operadores en zonas marginadas tienen conectividad limitada |
