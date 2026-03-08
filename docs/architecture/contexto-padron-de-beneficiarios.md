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
