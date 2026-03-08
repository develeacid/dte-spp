# Contexto: Padrón de Beneficiarios

Este documento describe qué es un padrón de beneficiarios, cómo se vincula con la MIR, cuáles son sus casos de uso operativos y las conclusiones arquitectónicas que llevaron al diseño del Padrón Único de la Secretaría.

---

## 1. Definición

Un padrón de beneficiarios es la **relación oficial de las personas, actores sociales, instituciones, comunidades u organismos que efectivamente reciben los bienes, servicios o beneficios de una intervención pública**.

Este registro se conforma por la **población atendida o beneficiaria**: el subgrupo de la población objetivo sobre el cual ya se aplicaron los criterios de focalización y que cumple con el perfil socioeconómico establecido en las Reglas de Operación del programa.

No es una lista de aspirantes ni de población potencial — es la evidencia oficial de que el beneficio fue entregado.

---

## 2. Naturaleza del Padrón: Activo Transversal del Estado

El padrón no es propiedad de un programa ni de una secretaría. Es un **activo transversal** con dos tipos de dueño:

**Dueño Normativo (central):** Suele ser la Secretaría de Finanzas, Planeación o Bienestar. Administra el Padrón Único del Estado, dicta los catálogos oficiales (INEGI, SIPPRES) y ejecuta los cruces de duplicidad entre dependencias.

**Dueño Operativo (descentralizado):** Son las Unidades Responsables (UR). Cada secretaría captura a sus beneficiarios — la de Desarrollo Económico a sus emprendedores, la de Agricultura a sus productores — siguiendo los lineamientos del dueño normativo.

Esta dualidad es la razón por la que los padrones deben eventualmente consolidarse en un sistema centralizado estatal.

---

## 3. Vínculo con la MIR

El padrón se vincula metodológicamente con la Matriz de Indicadores para Resultados en cuatro formas:

### 3.1 Como Actividad formal

La "administración o actualización del padrón de beneficiarios" se establece como una **Actividad** dentro del resumen narrativo de la MIR, con sus propios indicadores de gestión. Ejemplo: *"Tasa de variación anual de personas que integran el padrón de beneficiarios"*.

### 3.2 Como Medio de Verificación

El padrón es la fuente de evidencia oficial que sustenta los valores de los indicadores. Si un indicador mide cuántas personas recibieron un apoyo, el padrón es el documento auditable que cualquier evaluador externo consultará para corroborar ese número.

### 3.3 Como fuente de variables para fórmulas

Los indicadores son relaciones matemáticas entre variables. El padrón provee los datos del numerador: la **Población Beneficiaria** efectivamente atendida. Como el padrón debe registrar datos desagregados por sexo, género y grupos específicos, permite que los indicadores reporten con granularidad (ejemplo: *"porcentaje de mujeres indígenas beneficiadas"*).

### 3.4 Para medir cobertura y errores de focalización

El padrón permite contrastar estadísticamente si la población atendida realmente pertenece a la población objetivo. Esto permite detectar:

- **Error de inclusión:** proporción de personas inscritas que no cumplían los criterios de elegibilidad.
- **Error de exclusión:** proporción de la población objetivo que quedó fuera del padrón sin recibir el beneficio.

---

## 4. Estructura de Datos Obligatoria

Todo padrón debe registrar a la población con el mayor nivel de desagregación posible, clasificando por:

**Desagregación demográfica:**
- Sexo y género
- Grupos etarios (neonatos, infantes, adolescentes, jóvenes, adultos mayores)
- Raza y origen étnico (indígena, afrodescendiente)
- Condición de vulnerabilidad (discapacidad, estatus migratorio)

**Caracterización socioeconómica y geográfica:**
- Ubicación geográfica (localidades de alta marginación, Zonas de Atención Prioritaria)
- Condición de pobreza o nivel de ingresos
- Características del hogar (monoparental, número de carencias, menores de 14 años)

**Tipo de beneficiario (Catálogo SIPPRES — 99 claves oficiales):**
- Personas físicas: individuos segmentados por perfil demográfico u ocupacional (agricultores, estudiantes, artesanas, víctimas, etc.)
- Personas morales y colectivos: empresas, instituciones, escuelas, hospitales, comunidades, comités
- Áreas territoriales: zonas rurales, zonas urbanas, regiones, colonias

Esta desagregación no es opcional — es el mecanismo que convierte el concepto de "igualdad" en evidencia matemática auditable.

---

## 5. Reglas Operativas

### Un programa puede tener más de un padrón

Cuando un programa entrega diferentes tipos de apoyos (componentes distintos), puede tener sub-padrones por componente. Esto es válido normativamente, pero exige:

1. **Cruce de duplicidades** entre sub-padrones para evitar entregar el mismo apoyo dos veces a la misma persona.
2. **Consolidación en una sola MIR** — todos los registros deben agregarse en una única matriz de indicadores.
3. **Transparencia total** — todos los padrones del programa deben publicarse, sin importar cuántos sean.

### El ciclo de vida del registro

```
Convocatoria pública
    → Solicitud del ciudadano (física o virtual)
    → Cédula de calificación (criterios de priorización con puntaje)
    → Aprobación → Inscripción al padrón (población atendida)
    → Publicación obligatoria del padrón
    → Cruce con otros programas (detección de duplicidades)
    → Uso como Medio de Verificación en la MIR
```

---

## 6. Casos de Uso que Motivaron el Diseño

Estos casos de uso concretos fueron analizados durante el diseño y definieron las decisiones arquitectónicas del Padrón Único de la Secretaría.

### CU-01: Inscripción con validación geográfica bloqueante

**Contexto:** A veces se otorgan apoyos para una zona específica (ejemplo: apoyos a PyMEs de un municipio determinado). El requisito de comprobante de domicilio se puede burlar con un comprobante de otra zona. El sistema debe detectar esto en el momento de inscripción.

**Solución:** Al capturar el domicilio del beneficiario, el sistema verifica con `ST_Contains` que el punto georreferenciado caiga dentro del polígono del municipio o zona elegible según las Reglas de Operación. Si no, el formulario no avanza.

**Impacto arquitectónico:** Requiere PostGIS con geometrías de municipios importadas. La validación es en tiempo de escritura, no en reportes.

---

### CU-02: Detección de duplicidades entre programas

**Contexto:** Juan Pérez recibe un apoyo de fertilizante en enero y luego intenta inscribirse a un apoyo de sistema de riego. Ambos son incompatibles según la normativa. Sin un padrón centralizado, la UR del segundo programa no sabe que Juan ya tiene un apoyo activo.

**Solución:** El Padrón Único verifica por CURP si existe un `enrollment` activo en un programa incompatible antes de aprobar la nueva inscripción. La consulta es simple gracias a la separación identidad/transacción:

```sql
SELECT COUNT(*) FROM enrollments
WHERE beneficiary_id = (SELECT id FROM beneficiaries WHERE curp_rfc = ?)
  AND program_id IN (programas_incompatibles)
  AND status = 'aprobado'
```

**Impacto arquitectónico:** La tabla `beneficiaries` guarda al sujeto de derecho (una fila por CURP). La tabla `enrollments` guarda cada apoyo recibido. Sin esta separación, la detección de duplicidades requeriría deduplicar filas repetidas de PII.

---

### CU-03: Análisis de brechas territoriales (superposición de capas INEGI)

**Contexto:** La DGPOP necesita saber si el programa de apoyos económicos realmente está llegando a las zonas de mayor pobreza, o si se está concentrando en zonas con mejor acceso pero menor necesidad.

**Solución:** Superposición de la capa de índices de pobreza CONEVAL (polígonos por municipio) sobre los puntos de beneficiarios del programa, usando `ST_Intersects`. El resultado muestra zonas con alta pobreza y baja cobertura (brecha de exclusión) y zonas con baja pobreza y alta cobertura (posible error de focalización).

**Impacto arquitectónico:** Requiere que las capas INEGI y CONEVAL estén importadas en la misma base de datos PostGIS que los beneficiarios. Se importan mediante comandos Artisan cuando los organismos publican actualizaciones (~anualmente).

---

### CU-04: Tablero de inversión por municipio

**Contexto:** Un secretario necesita verificar que la distribución del presupuesto no está favoreciendo desproporcionadamente a ciertos municipios o sectores, ya sea por razones políticas o simplemente por sesgo de acceso.

**Solución:** Agregación de `SUM(enrollments.monto_entregado)` agrupada por municipio, visualizada en Metabase como mapa coroplético. El secretario elige un municipio y ve el desglose por programa y tipo de beneficiario.

**Impacto arquitectónico:** Metabase conectado a réplica de lectura de PostgreSQL. El campo `monto_entregado` en `enrollments` es clave para esta funcionalidad.

---

### CU-05: Reporte de cobertura para indicadores MIR

**Contexto:** Al cierre del trimestre, el sistema MIR necesita saber cuántos beneficiarios aprobados tiene el programa X, desagregados por género, para calcular el avance del indicador de cobertura.

**Solución:** El sistema MIR consume la API interna del Padrón con su token Sanctum. La consulta devuelve conteos agregados — no nombres ni domicilios. El Padrón es la fuente; el MIR es el consumidor.

```
GET /api/v1/padron/programas/{id}/cobertura?trimestre=3&año=2025
→ { total: 1240, mujeres: 680, hombres: 560, indigenas: 310, ... }
```

**Impacto arquitectónico:** Define el scope `padron:read` del token del sistema MIR. El MIR nunca escribe al padrón — solo lee conteos.

---

### CU-06: Publicación de datos abiertos

**Contexto:** La normativa obliga a publicar el padrón en formatos accesibles. Periodistas, organismos civiles y portales de transparencia necesitan consumir esta información sin acceso a la base de datos interna.

**Solución:** Endpoint público que devuelve GeoJSON de zonas de cobertura (polígonos de municipios) con atributos agregados, nunca puntos de domicilio individual. Cacheado en Redis para soportar alta demanda sin afectar la operación.

**Impacto arquitectónico:** Define la separación entre API interna (PII completa, token requerido) y API pública (agregados anonimizados, sin autenticación, con rate limiting y cache).

---

## 7. Conclusiones Arquitectónicas

El análisis de los casos de uso anteriores llevó a las siguientes decisiones de diseño, todas documentadas con sus alternativas descartadas en el documento de diseño:

| Decisión | Razón |
|----------|-------|
| Aplicación Laravel independiente | PII requiere aislamiento; debe ser entregable al estado como sistema autónomo |
| PostgreSQL + PostGIS completo | CU-01, CU-02 y CU-03 requieren validación geométrica y superposición de capas |
| Separación `beneficiaries` / `enrollments` | CU-02 es imposible de implementar correctamente sin esta normalización |
| Metabase en réplica de lectura | CU-03 y CU-04 generan queries pesados que no deben competir con CU-01 |
| Dos APIs con políticas distintas | CU-05 requiere PII; CU-06 prohíbe PII — no puede ser la misma API |
| Captura offline con validación diferida | Los operadores de CU-01 frecuentemente trabajan en zonas marginadas sin conectividad |
| Pipeline de importación INEGI manual | Los datos de referencia se actualizan ~anualmente, no en tiempo real |

El documento de diseño completo se encuentra en [docs/plans/2026-03-08-padron-beneficiarios-design.md](../plans/2026-03-08-padron-beneficiarios-design.md).

---

## 8. Integración MIR ↔ Padrón: Los Tres Momentos

La comunicación entre el sistema MIR y el Padrón Único no es continua ni genérica — ocurre en tres momentos precisos del ciclo presupuestario, cada uno con una naturaleza técnica distinta.

### Momento 1 — Diseño (Programación): El Enlace Lógico

**Cuándo ocurre:** Durante la etapa de programación, cuando el Planeador arma la matriz 4×4 en el sistema MIR.

**Qué sucede:**
1. El Planeador define un Componente (ej. "Créditos a MIPYMES entregados").
2. El sistema detecta que es un bien/servicio directo y pregunta: "¿Este componente requiere Padrón de Beneficiarios?"
3. Al indicar que sí, el Planeador vincula ese Componente con su ID correspondiente en el Padrón.
4. Al configurar las variables de las fórmulas de los indicadores, en lugar de campos de texto libre, el Planeador selecciona del **Diccionario de Variables** una variable estandarizada que apunta a un endpoint del Padrón:
   ```
   Variable A: "Número de mujeres con crédito"
   → endpoint: /api/v1/padron/stats
   → params estáticos: { sex: "F", catalog_keys: [30, 52] }
   → params dinámicos: component_id, trimestre, año  (los aporta el contexto de la MIR)
   ```

**En este momento:** Ambos sistemas saben que están vinculados. No se intercambian datos de ciudadanos. La API ya tiene la ruta lista aunque el padrón tenga 0 registros.

**Por qué el Diccionario de Variables es clave:** Sin él, cada Planeador nombra la variable a su criterio ("mujeres apoyadas", "beneficiarias femeninas", "nro_mujeres"). En el año 3, los reportes de distintas URs ya no son comparables. Con variables estandarizadas como entidades del sistema, una variable creada hoy sirve en los reportes de los próximos seis años y puede reutilizarse en otros programas.

---

### Momento 2 — Reporte (Seguimiento): El Enlace Operativo

**Cuándo ocurre:** Durante la etapa de seguimiento, cuando el Operador reporta el avance trimestral.

**Qué sucede:**
1. Se abre el periodo de captura del trimestre en la MIR.
2. El Operador entra a reportar el indicador "Porcentaje de mujeres apoyadas".
3. El campo del valor **está bloqueado (read-only)** — el Operador no puede escribir en él.
4. La MIR usa su token (`padron:read`) y consulta en tiempo real al Padrón.
5. El Padrón cuenta los `enrollments` aprobados con los filtros de la variable y devuelve el número exacto.
6. La MIR recibe el dato, calcula la fórmula, pinta el semáforo (Verde/Amarillo/Rojo).
7. Al presionar "Cerrar Trimestre", ocurre el **Corte de Caja** (ver sección 9).

**Por qué el campo read-only es la decisión más importante:** En un sistema tradicional el Operador teclea el número manualmente. Eso permite inflar cifras. Con el campo bloqueado, la MIR solo acepta lo que existe en el Padrón como registros georreferenciados y aprobados. Es un control anticorrupción arquitectónico, no de supervisión.

---

### Momento 3 — Auditoría (Evaluación): El Medio de Verificación

**Cuándo ocurre:** Al cierre del ejercicio fiscal, cuando entra la Contraloría o el Instituto de Transparencia.

**Qué sucede:**
1. El auditor revisa la MIR y ve que el indicador dice "1,200 apoyos entregados".
2. En la columna de Medios de Verificación encuentra: *"Padrón Único de Beneficiarios — Corte al 31 de Marzo. [Descargar Evidencia]"*.
3. Ese enlace apunta al archivo generado en el Corte de Caja: un CSV con los 1,200 registros exactos.
4. El auditor descarga el CSV, calcula su SHA-256 y lo compara contra el hash guardado en la MIR. Si coinciden, la evidencia es íntegra. Si no, hay una alerta de alteración.

---

## 9. El Corte de Caja: Snapshot Criptográfico

### Por qué es la única solución legalmente válida

El Padrón es un ente vivo (beneficiarios fallecen, se detectan fraudes, se corrigen errores). La MIR alimenta la **Cuenta Pública**, que es un instrumento legal firmado electrónicamente por el titular de la UR. Una vez cerrado un trimestre, ese reporte es inmutable por ley.

Si la MIR consultara el Padrón en tiempo real después del cierre, un cambio posterior en el Padrón alteraría retroactivamente un documento oficial — equivalente a alterar un instrumento legal. La Opción A (snapshot al cierre) es la única arquitectónicamente correcta.

### Implementación técnica

**Paso 1 — Queries point-in-time en el Padrón**

El Padrón nunca hace `DELETE` ni `UPDATE` destructivos. Usa soft deletes y una tabla de historial de estados:

```
enrollment_status_history
├── enrollment_id  (FK)
├── status         (aprobado | rechazado | observado)
├── reason
├── changed_by
└── changed_at
```

Un enrollment no hace `UPDATE status = 'rechazado'`. Inserta una fila en `enrollment_status_history`. Esto permite reconstruir el estado exacto del padrón en cualquier fecha pasada:

```sql
-- ¿Cuántas mujeres estaban aprobadas al 31 de marzo?
SELECT COUNT(*) FROM enrollments e
WHERE e.created_at <= '2026-03-31 23:59:59'
  AND (e.deleted_at IS NULL OR e.deleted_at > '2026-03-31 23:59:59')
  AND (
    SELECT status FROM enrollment_status_history
    WHERE enrollment_id = e.id
      AND changed_at <= '2026-03-31 23:59:59'
    ORDER BY changed_at DESC LIMIT 1
  ) = 'aprobado'
```

El resultado siempre será 500, sin importar qué correcciones se hicieron en abril. El soft delete no es suficiente porque no captura cambios de estado — ambos mecanismos son necesarios.

**Paso 2 — El handshake del cierre**

Cuando el Operador presiona "Cerrar Trimestre" en la MIR:

```
MIR → POST /api/v1/padron/snapshot
      { component_id: 12, period: "2026-Q1", cutoff_date: "2026-03-31 23:59:59" }

Padrón → ejecuta query point-in-time
       → genera CSV con los registros exactos
       → calcula SHA-256 del archivo
       → guarda en MinIO (almacenamiento local, no nube extranjera)
       → responde: { valor_oficial: 500, snapshot_hash: "a1b2c3...", evidencia_url: "..." }

MIR → guarda en su propia DB: entero 500 + hash + url
    → congela el campo — ningún actor puede modificarlo
```

El endpoint es **idempotente**: si se llama dos veces con el mismo `component_id + period`, devuelve el snapshot existente sin regenerarlo. Esto protege contra doble clic o reintentos por error de red.

**Paso 3 — Las correcciones se absorben en el siguiente trimestre**

Los 30 enrollments corregidos después del cierre de Q1 no "deshacen" el Q1. Cuando la MIR haga el corte de Q2, el Padrón calculará el total vigente. Si entraron 100 nuevas pero salieron 30 por corrección, el Q2 reportará el neto real. La historia del Q1 queda intacta.

**¿Por qué MinIO y no AWS S3?** Los archivos de evidencia del Padrón son documentos oficiales que respaldan la Cuenta Pública. Deben residir en infraestructura bajo control de la dependencia, no en servidores extranjeros. MinIO es compatible con la API de S3 y corre en el mismo stack Docker del sistema.

### Flujo completo consolidado

```
PROGRAMACIÓN
    Planeador vincula Componente MIR → ID en Padrón
    Configura variables desde Diccionario de Variables estandarizado

SEGUIMIENTO (periodo abierto)
    MIR consulta Padrón en tiempo real (campo read-only para el operador)

SEGUIMIENTO (cierre de trimestre)
    Operador cierra → MIR dispara POST /api/v1/padron/snapshot
    Padrón: query point-in-time → CSV → SHA-256 → MinIO
    MIR recibe y congela: valor + hash + url

EVALUACIÓN (auditoría)
    Contraloría descarga CSV desde MinIO vía enlace en MIR
    Recalcula SHA-256 → compara contra hash en MIR
    Coinciden → evidencia íntegra / No coinciden → alerta de alteración
```
