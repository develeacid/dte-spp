# Inventario de Conceptos Load-Bearing del Temario MIR — V2

**Fecha:** 2026-05-19
**Fuente:** Módulos 01-10 del temario en `/home/eleacid/code/laravel/matrices de indicadores/`
**Propósito:** Insumo para verificación de brechas V2 en dte-spp y geobase

## Convenciones

- **ID:** `C-NNN` numerado secuencialmente del 001 al final
- **Criterio verificable:** UNA pregunta sí/no que un auditor pueda responder con grep + lectura de modelo en ≤5 min (falseable, no abstracto)
- **Severidad si falta:** 🔴 crítico (bloquea normativa/expone PII) · 🟠 alto (requerido Cuenta Pública/auditoría) · 🟡 medio (degrada calidad MIR) · 🟢 bajo (doc/ergonomía)
- **También-en:** opcional, lista de otros módulos donde aparece (cross-reference)

## Inventario por módulo

### Módulo 1 — Contexto y Fundamentos del PbR

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-001 | Jerarquía de planeación Constitución → PND → Programa Sectorial → PED → Programa Presupuestario → Indicadores | ¿Existe modelo/tabla que persista los 5 niveles de la cadena de planeación con FK explícitas entre ellos? | 🟠 | M9 |
| C-002 | Alineación institucional del programa al PND (eje + objetivo) | ¿Existe campo/relación en `programa_presupuestario` que vincule a un objetivo específico del PND? | 🟠 | M9 |
| C-003 | Alineación institucional del programa al PED (eje + objetivo) | ¿Existe campo/relación en `programa_presupuestario` que vincule a un objetivo específico del PED? | 🟠 | M9 |
| C-004 | Programa Sectorial / Especial / Institucional / Regional como nivel derivado del PND | ¿Existe catálogo de programas sectoriales con FK a PND y a programas presupuestarios derivados? | 🟡 | M9 |
| C-005 | Marco normativo LFPRH art. 111 (SED como obligación) | ¿Existe referencia normativa persistida o documentada en código que cite LFPRH art. 111? | 🟢 | M9 |
| C-006 | Marco normativo LGCG art. 46-III-C (presupuesto debe incluir indicadores) | ¿Existe referencia normativa o validación que invoque LGCG art. 46-III-C en formulación de indicadores? | 🟢 | M5, M9 |
| C-007 | Lineamientos SHCP-CONEVAL como referencia técnica obligatoria | ¿Existe documento/seed/config con lineamientos SHCP-CONEVAL versionados como base de validaciones? | 🟢 | — |
| C-008 | Tres actores institucionales centrales: SHCP, CONEVAL, ASF | ¿Existe enum/catálogo con los actores institucionales SHCP/CONEVAL/ASF para auditoría y trazabilidad? | 🟢 | M6, M9, M10 |
| C-009 | Sistema de Evaluación del Desempeño (SED) como mecanismo institucional | ¿Existen entidades/modelos que articulen indicadores + evaluaciones externas + ASM como SED operativo? | 🟠 | M6, M10 |
| C-010 | Ciclo presupuestario de 7 etapas (Planeación → Programación → Presupuestación → Ejercicio → Seguimiento → Evaluación → Rendición) | ¿Existe enum/máquina de estados que represente las 7 etapas del ciclo en programas o ejercicios? | 🟡 | M8, M9 |
| C-011 | Ejercicio fiscal como periodo de planeación y operación anual | ¿Existe entidad `ejercicio_fiscal` con año y fechas de inicio/cierre? | 🟠 | M8, M9 |
| C-012 | Cadena de Valor Público: Insumos → Actividades → Componentes → Propósito → Fin | ¿La estructura de MIR refleja explícitamente los 5 eslabones de la Cadena de Valor en su modelado? | 🟡 | M3, M4 |

### Módulo 2 — Metodología de Marco Lógico (MML)

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-013 | Fases MML 1-6: Definición/Análisis/Objetivo/Alternativas/EAPp/MIR | ¿Existe enum o tabla de fases MML con producto esperado por cada fase? | 🟡 | — |
| C-014 | Ficha de Información Básica del programa (Fase 1) | ¿Existe modelo/sección que persista la ficha de información básica (problema, magnitud, justificación)? | 🟠 | M9 |
| C-015 | Árbol de Problemas con problema central + causas (directas/raíz) + efectos (directos/finales) | ¿Existe estructura jerárquica `arbol_problemas` con nodos tipados (problema/causa_directa/causa_raiz/efecto_directo/efecto_final)? | 🟠 | M9 |
| C-016 | Regla: un solo problema central por árbol | ¿Existe validación que impida más de un nodo tipo `problema_central` por árbol de programa? | 🟡 | — |
| C-017 | Regla: problema expresado como estado negativo verificable, no como ausencia de solución | ¿Existe validación o lint (sintáctica/heurística) que rechace formulaciones con "falta de"/"ausencia de"/"no hay"? | 🟢 | — |
| C-018 | Árbol de Objetivos como transformación positiva del Árbol de Problemas | ¿Existe estructura `arbol_objetivos` con relación 1:1 a nodos del Árbol de Problemas? | 🟡 | — |
| C-019 | Análisis de Involucrados (beneficiarios directos/indirectos/ejecutores/aliados/neutrales/opositores) | ¿Existe tabla `involucrados` con tipo categorizado e impacto en supuestos del programa? | 🟢 | M6 |
| C-020 | Selección de Alternativas con criterios (viabilidad técnica, jurídica, presupuesto, tiempo, impacto, complementariedad) | ¿Existe documento/tabla `analisis_alternativas` con 6 criterios evaluables persistidos? | 🟢 | M9 |
| C-021 | Principio de no duplicidad con otros programas | ¿Existe validación que cruce contra catálogo de programas existentes para detectar duplicidad? | 🟠 | M7, M9 |
| C-022 | Tres poblaciones: Potencial, Objetivo, Atendida | ¿Existen 3 campos numéricos (poblacion_potencial/objetivo/atendida) en `programa_presupuestario` con fuente documentada? | 🔴 | M3, M7, M9 |
| C-023 | Brecha de cobertura (Potencial − Objetivo) | ¿Existe campo calculado o vista que reporte la brecha Potencial−Objetivo por programa y periodo? | 🟡 | M7 |
| C-024 | Brecha de desempeño (Objetivo − Atendida) | ¿Existe campo calculado o vista que reporte la brecha Objetivo−Atendida por programa y periodo? | 🟠 | M7, M8 |
| C-025 | Desagregación obligatoria de poblaciones por sexo, grupo etario, etnia, condición de vulnerabilidad, ubicación geográfica | ¿Existen 5 dimensiones de desagregación obligatoria en el modelo de poblaciones/padrón? | 🔴 | M7 |
| C-026 | EAPp (Estructura Analítica del Programa Presupuestario) como puente Árbol→MIR | ¿Existe estructura `eapp` que organice los 4 niveles MIR en árbol antes de la matriz? | 🟢 | M3 |

### Módulo 3 — Arquitectura de la MIR

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-027 | Estructura MIR 4×4: 4 niveles (Fin/Propósito/Componentes/Actividades) × 4 columnas (RN/Indicador/MV/Supuestos) | ¿Existe modelo `mir` con relación a niveles + cada nivel con 4 columnas obligatorias? | 🔴 | M4, M5, M6 |
| C-028 | Restricción: un solo Fin por MIR | ¿Existe validación que limite a 1 nivel tipo FIN por programa? | 🟠 | — |
| C-029 | Restricción: un solo Propósito por MIR | ¿Existe validación que limite a 1 nivel tipo PROPOSITO por programa? | 🟠 | — |
| C-030 | Componentes pueden ser varios (típicamente 2-5) | ¿Existe modelo `componente` con FK al Propósito y permite N filas (sin máximo rígido)? | 🟡 | — |
| C-031 | Actividades codificadas A[n].[m] donde n=componente, m=orden | ¿Existe campo `clave` o `codigo` en tabla actividades con patrón A{n}.{m} y validación de unicidad por programa? | 🟠 | M4 |
| C-032 | Resumen Narrativo como punto de partida de cada fila | ¿Existe campo `resumen_narrativo` (text) requerido en cada nivel de MIR? | 🔴 | M4 |
| C-033 | Indicador (nombre en MIR + ficha técnica separada) | ¿Existe relación 1:N nivel→indicadores y modelo separado `ficha_tecnica` por indicador? | 🔴 | M5 |
| C-034 | Medio de Verificación (fuente documental para calcular el indicador) | ¿Existe campo/relación `medios_verificacion` por indicador con nombre de fuente, organismo y periodicidad? | 🔴 | M6 |
| C-035 | Supuesto (condición externa al control del ejecutor) | ¿Existe modelo `supuestos` con FK al nivel MIR y campo descriptivo del riesgo externo? | 🟠 | M6 |
| C-036 | MIR compartida entre múltiples Unidades Responsables (con dependencia administradora designada) | ¿Existe modelo que permita N URs por MIR con flag `es_administradora` única? | 🟡 | — |
| C-037 | Tipos de Unidad Responsable: Sustantiva vs. Apoyo (Staff) | ¿Existe enum `tipo_ur` con valores sustantiva/apoyo en tabla unidades_responsables? | 🟢 | — |

### Módulo 4 — Construcción del Resumen Narrativo y Lógica de la Matriz

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-038 | Sintaxis FIN: verbo "contribuir a" + objetivo superior + estrategia | ¿Existe validación/lint que verifique que el RN de FIN inicia con "Contribuir a"? | 🟡 | — |
| C-039 | Sintaxis PROPÓSITO: [Población objetivo] + [verbo cambio de estado] + [condición mejorada] | ¿Existe validación/lint que detecte verbos de acción del programa (otorgar/entregar/impartir) en RN de Propósito? | 🟡 | — |
| C-040 | Sintaxis COMPONENTES: verbo en participio pasado (entregadas/impartidos/construidas) | ¿Existe validación/lint que verifique que RN de Componente termina con participio pasado o palabra clave correspondiente? | 🟡 | — |
| C-041 | Sintaxis ACTIVIDADES: código A[n].[m] + acción + insumo | ¿Existe validación de formato del campo `clave` actividad con regex `^A\d+\.\d+$`? | 🟠 | M3 |
| C-042 | Lógica vertical: prueba si/entonces ascendente Actividades→Componentes→Propósito→Fin | ¿Existe validación/test/UI que documente la prueba si/entonces ascendente del programa? | 🟢 | — |
| C-043 | Lógica vertical: prueba de completitud descendente (¿son suficientes los componentes para el Propósito?) | ¿Existe validación/test/UI que documente la prueba de completitud descendente? | 🟢 | — |
| C-044 | Lógica horizontal: coherencia RN ↔ Indicador ↔ MV ↔ Supuesto en cada fila | ¿Existe validación o checklist que verifique que el indicador mide el RN y el MV puede calcular el indicador? | 🟡 | M5, M6 |
| C-045 | Necesidad y suficiencia de Componentes (si quitas uno, Propósito falla; con todos juntos basta) | ¿Existe documentación/checklist en el flujo de creación de MIR para validar necesidad+suficiencia? | 🟢 | — |
| C-046 | Codificación trazable A[componente].[orden] vincula visualmente actividad→componente | ¿La UI/exportación de MIR muestra actividades agrupadas y codificadas por componente padre? | 🟡 | M3 |

### Módulo 5 — Diseño de Indicadores de Desempeño

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-047 | Tipo: Indicador Estratégico vs. De Gestión | ¿Existe enum `tipo_indicador` con valores estrategico/gestion en tabla indicadores? | 🟠 | — |
| C-048 | Regla: FIN y PROPÓSITO siempre estratégicos | ¿Existe validación que rechace indicador "de gestión" en niveles FIN/PROPOSITO? | 🟠 | — |
| C-049 | Regla: ACTIVIDADES siempre de gestión | ¿Existe validación que rechace indicador "estratégico" en nivel ACTIVIDAD? | 🟡 | — |
| C-050 | Regla: COMPONENTES depende del tipo de entrega (directa→estratégico, indirecta→gestión) | ¿Existe campo/lógica que distinga tipo de entrega en componentes para sugerir/validar tipo de indicador? | 🟢 | — |
| C-051 | Dimensiones de medición: Eficacia / Eficiencia / Calidad / Economía | ¿Existe enum `dimension_indicador` con 4 valores en tabla indicadores? | 🟠 | — |
| C-052 | Regla: Eficacia obligatoria en todos los niveles | ¿Existe validación que requiera ≥1 indicador de eficacia por nivel MIR? | 🟠 | — |
| C-053 | Regla: Economía solo en Actividades | ¿Existe validación que rechace dimension=Economia fuera de nivel ACTIVIDAD? | 🟡 | — |
| C-054 | Regla: Calidad principalmente en Componentes (no recomendada en Propósito/Fin) | ¿Existe warning/sugerencia cuando dimension=Calidad se asigna a FIN o PROPOSITO? | 🟢 | — |
| C-055 | Atributos CREMAA: Claro, Relevante, Económico, Monitoreable, Adecuado, Aporte Marginal | ¿Existen 6 booleans/checklist persistidos para validar atributos CREMAA por indicador? | 🟡 | — |
| C-056 | Fórmula matemática del indicador (porcentaje/tasa/variación/promedio/razón/absoluto) | ¿Existe campo `formula` (text) requerido por indicador? | 🔴 | — |
| C-057 | Variables del indicador con nombre + descripción + fuente | ¿Existe relación 1:N indicador→variables con 3 campos load-bearing (nombre, descripcion, fuente)? | 🟠 | — |
| C-058 | Unidad de medida del indicador (%/Tasa/Promedio/Número absoluto/Índice) | ¿Existe campo `unidad_medida` requerido por indicador? | 🟠 | — |
| C-059 | Línea base (valor + año/periodo) | ¿Existen campos `linea_base_valor` y `linea_base_anio` requeridos por indicador? | 🔴 | — |
| C-060 | Meta (valor + año/periodo) | ¿Existen campos `meta_valor` y `meta_anio` requeridos por indicador? | 🔴 | M8 |
| C-061 | Nombre del indicador debe ser neutral (sin "incremento"/"reducción"/"mejora") | ¿Existe validación/lint que rechace palabras con dirección en nombre de indicador? | 🟢 | — |
| C-062 | Sentido del indicador: Ascendente vs. Descendente | ¿Existe enum `sentido` con valores ascendente/descendente requerido por indicador? | 🟠 | — |
| C-063 | Periodicidad (Mensual/Trimestral/Semestral/Anual/Bianual/Trianual/Sexenal) | ¿Existe enum `periodicidad` con los 7 valores normativos por indicador? | 🟠 | M6, M8 |
| C-064 | Regla: periodicidad indicador ≤ periodicidad del MV (compatibilidad) | ¿Existe validación que compare periodicidad del indicador vs. periodicidad del MV declarado? | 🟡 | M6 |
| C-065 | Semaforización con rangos Verde / Amarillo / Rojo (bajo) / Rojo (alto/sobrecumplimiento) | ¿Existe modelo `semaforizacion` con 4 rangos numéricos (min/max) por indicador? | 🟠 | M8 |
| C-066 | Regla semáforo 1: meta cae dentro del rango Verde | ¿Existe validación que verifique meta_valor ∈ [verde_min, verde_max] por indicador? | 🟠 | — |
| C-067 | Regla semáforo 2: sin solapamiento entre rangos | ¿Existe validación que verifique no-solapamiento (verde_max < amarillo_min, etc.)? | 🟡 | — |
| C-068 | Regla semáforo 3: usar la unidad de medida del indicador | ¿Existe validación de coherencia entre unidad del indicador y unidad de los rangos del semáforo? | 🟢 | — |
| C-069 | Regla semáforo 4: rango Rojo no inicia en cero | ¿Existe validación que rechace rojo_min=0? | 🟢 | — |
| C-070 | Ficha Técnica del Indicador con 5 bloques (Identificación/Medición/Variables/Línea base y Meta/Semaforización) | ¿Existe vista/PDF/exportación de Ficha Técnica con los 5 bloques completos por indicador? | 🟠 | M8, M9 |

### Módulo 6 — Medios de Verificación y Supuestos

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-071 | MV con fuente específica + organismo + periodicidad + URL/sistema | ¿Existen 4 campos load-bearing (fuente, organismo, periodicidad, ubicacion) por MV? | 🟠 | M5 |
| C-072 | Atributos CREMA del MV: Confiable, Relevante, Económico, Monitoreable, Asequible | ¿Existen 5 booleans/checklist persistidos para validar atributos CREMA por MV? | 🟡 | — |
| C-073 | Regla: FIN y PROPÓSITO requieren fuentes externas e independientes (INEGI/CONEVAL/Estadística 911/etc.) | ¿Existe validación que el `tipo_fuente` de MV sea "externa" en niveles FIN/PROPOSITO? | 🟠 | — |
| C-074 | Regla: COMPONENTES y ACTIVIDADES admiten registros administrativos propios en sistemas oficiales | ¿Existe enum `tipo_fuente` con valores externa/administrativa_propia/evaluacion_externa por MV? | 🟡 | — |
| C-075 | Supuesto válido: externo + relevante + razonablemente probable de cumplirse | ¿Existen 3 booleans/checklist (es_externo, es_relevante, probabilidad_razonable) por supuesto? | 🟡 | — |
| C-076 | Prueba si/entonces inverso para validar Supuestos | ¿Existe documentación/UI que guíe la prueba si/entonces inverso al crear un supuesto? | 🟢 | — |
| C-077 | Supuestos categorizados por nivel: Actividades / Componentes / Propósito | ¿Existe FK obligatoria del supuesto al nivel MIR que lo invoca? | 🟠 | M3 |
| C-078 | Evaluaciones externas: Diseño / Procesos / ECR / Impacto / EED (Específica de Desempeño) | ¿Existe enum `tipo_evaluacion` con los 5 valores en tabla evaluaciones_externas? | 🟠 | M10 |
| C-079 | Auditoría de Desempeño ASF como verificador de MV y valores reportados | ¿Existe campo/registro de hallazgos/observaciones ASF por programa? | 🟢 | M9, M10 |
| C-080 | Contraloría Ciudadana habilitada por MV monitoreables y públicos | ¿El padrón y los MV son consultables sin autenticación (transparencia proactiva)? | 🟠 | M7, M9 |

### Módulo 7 — Padrón de Beneficiarios

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-081 | Padrón con bloque A (identificación+elegibilidad) y bloque B (entrega del apoyo) | ¿El modelo `padron_beneficiarios` distingue campos de los 2 bloques (identificación vs. entrega)? | 🟠 | — |
| C-082 | CURP como identificador único del beneficiario | ¿Existe campo `curp` con UNIQUE constraint y validación de 18 caracteres por beneficiario? | 🔴 | — |
| C-083 | Datos mínimos de identificación: nombre, municipio, localidad, marginación/pobreza | ¿Existen los 5 campos load-bearing de identificación en el padrón? | 🟠 | — |
| C-084 | Datos mínimos de entrega: tipo apoyo, monto/cantidad, fecha entrega, folio evidencia, estado registro | ¿Existen los 5 campos load-bearing de entrega en el padrón? | 🔴 | M8 |
| C-085 | Desagregación obligatoria: sexo (M/H) | ¿Existe enum `sexo` con M/H requerido en padrón? | 🔴 | M2 |
| C-086 | Desagregación obligatoria: grupo etario (consistente con ciclo de vida del programa) | ¿Existe campo `grupo_etario` (calculado o categorizado) requerido en padrón? | 🔴 | M2 |
| C-087 | Desagregación obligatoria: pertenencia étnica (indígena/no indígena/afromexicano) | ¿Existe enum `pertenencia_etnica` requerido en padrón? | 🔴 | M2 |
| C-088 | Desagregación obligatoria: condición de discapacidad (con/sin, opc. por tipo: motriz/visual/auditiva/intelectual) | ¿Existe enum/tabla `condicion_discapacidad` con tipo desagregado en padrón? | 🔴 | M2 |
| C-089 | Estados del registro: activo / baja temporal / baja definitiva (con causal documentada) | ¿Existe enum `estado_registro` con 3 valores + campo `causal_baja` para bajas? | 🟠 | M8 |
| C-090 | Archivo de Bajas: ningún registro se elimina, todos conservan historial con causal | ¿Existe soft-delete o tabla histórica que conserve bajas con causal normativa? | 🟠 | — |
| C-091 | Cruce contra Padrón Único de Beneficiarios (PUBP) federal para evitar duplicidad interinstitucional | ¿Existe job/integración que cruce padrón contra PUBP federal en validación de elegibilidad? | 🟠 | M9 |
| C-092 | Verificación de CURP contra RENAPO en validación de elegibilidad | ¿Existe job/integración o validador de CURP que consulte RENAPO o estructura sintáctica? | 🟡 | — |
| C-093 | Cruce con CONAPO para verificar índice de marginación por localidad | ¿Existe seed/tabla CONAPO con grado de marginación por localidad y validación en captura? | 🟠 | M9 |
| C-094 | 4 vínculos Padrón↔MIR: (1) define Población Atendida del Propósito, (2) sustenta numerador de indicadores de Componentes, (3) provee MV de Componentes/Actividades, (4) alimenta evaluación del Propósito y monitoreo de Supuestos | ¿Existen consultas/vistas que materialicen los 4 vínculos Padrón↔MIR? | 🟠 | M2, M3, M5, M6 |
| C-095 | Catálogo de 8 errores típicos del padrón (P-01..P-08) con validación preventiva | ¿Existen las 8 validaciones preventivas (campos vacíos, duplicidad CURP, inelegible, etc.) en captura? | 🟡 | — |
| C-096 | Regla: registros "entregados" requieren referencia bancaria/folio acta como evidencia | ¿Existe validación que rechace estado=entregado sin folio_evidencia o referencia_transferencia? | 🟠 | M8 |
| C-097 | Monto del apoyo = campo calculado no editable, tomado de ROP vigentes | ¿El campo `monto` es calculado/no editable y referenciado a tabla `ROP` vigente? | 🟡 | M9 |
| C-098 | Reglas de Operación (ROP) como fuente normativa de criterios de elegibilidad y montos | ¿Existe modelo `reglas_operacion` versionado por ejercicio fiscal y ligado a programa? | 🟠 | M9 |

### Módulo 8 — Seguimiento y Cierre Fiscal

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-099 | IAFF (Informe de Avance Físico-Financiero) trimestral | ¿Existe modelo `iaff` con FK a programa, trimestre, ejercicio fiscal? | 🔴 | M9 |
| C-100 | IAFF sección 2: avance de indicadores con línea base/meta anual/meta trimestre/valor observado/variación/semáforo/análisis desviación | ¿Existen los 7 campos load-bearing de avance de indicadores por IAFF? | 🔴 | M5 |
| C-101 | IAFF sección 3: avance presupuestal (aprobado/modificado/comprometido/devengado/pagado) por capítulo de gasto | ¿Existen los 5 campos load-bearing de avance presupuestal por capítulo en IAFF? | 🔴 | M9 |
| C-102 | IAFF sección 4: análisis integrado y acciones de mejora con responsable + fecha | ¿Existe campo/tabla de acciones correctivas con responsable y fecha límite en IAFF? | 🟠 | — |
| C-103 | Calendario IAFF: 1T (abril+30d), 2T (julio+30d), 3T (octubre+30d), 4T (con cierre fiscal, +45d siguiente año) | ¿Existe lógica de plazos de entrega configurable por trimestre del IAFF? | 🟡 | — |
| C-104 | Análisis de desviación con 4 elementos: dato + causa (interna/externa) + acción + proyección | ¿Existen 4 campos requeridos en análisis de desviación cuando semáforo ≠ verde? | 🟠 | — |
| C-105 | Avance físico (cumplimiento de metas) separado de avance financiero (ejercicio del presupuesto) | ¿La consulta de IAFF reporta independientemente avance físico y financiero, sin fusionarlos? | 🟠 | — |
| C-106 | Semaforización aplicada al valor observado del período (no al acumulado) | ¿La lógica de cálculo del semáforo opera sobre el valor observado del período del IAFF? | 🟡 | M5 |
| C-107 | Indicadores anuales se semaforizan solo al cierre del ejercicio (Estadística 911, etc.) | ¿Existe lógica que omita semaforización en trimestres intermedios para indicadores con periodicidad anual? | 🟡 | M5, M6 |
| C-108 | Tablero de control (dashboard) con semaforización de todos los indicadores | ¿Existe vista/página tablero que agregue semáforos de todos los indicadores del programa? | 🟠 | — |
| C-109 | Reuniones de seguimiento: operativo (mensual), gestión (trimestral), directivo (semestral o rojo) | ¿Existe modelo `minuta_seguimiento` con tipo categorizado y acuerdos persistidos? | 🟢 | — |
| C-110 | Cierre fiscal con 4 fases: Conciliación / Cálculo definitivo / Informe de cierre / Lecciones aprendidas + ASM | ¿Existe modelo/proceso `cierre_fiscal` con 4 fases trackeadas por programa? | 🟠 | M10 |
| C-111 | Conciliación físico-financiera (correspondencia entre padrón y registros de Tesorería) | ¿Existe reporte/job que cruce padrón vs. registros bancarios/tesorería al cierre? | 🟠 | — |
| C-112 | ASM (Aspectos Susceptibles de Mejora) con 5 elementos: descripción + acción + tipo + responsable + plazo | ¿Existe modelo `asm` con los 5 campos load-bearing? | 🟠 | M6, M10 |
| C-113 | POA (Programa Operativo Anual): calendarización + responsables + presupuesto por actividad + metas trimestrales | ¿Existe modelo `poa` con 4 campos load-bearing por actividad? | 🟠 | M9 |
| C-114 | Devengado como gasto comprometido + bien/servicio recibido (aunque pago aún no se realice) | ¿Existe campo `devengado` separado de `pagado` en avance presupuestal? | 🟡 | M9 |

### Módulo 9 — Presupuestación, Documentación e Instrumentos de Alineación

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-115 | Estructura Programática: Finalidad → Función → Subfunción → Programa → Proyecto → Actividad Institucional | ¿Existen 6 niveles jerárquicos modelados en estructura programática? | 🟠 | M1 |
| C-116 | Clave presupuestal única del programa | ¿Existe campo `clave_presupuestal` UNIQUE en `programa_presupuestario`? | 🔴 | — |
| C-117 | Modalidades de programa: S (con ROP) / U (otros subsidios) / E (servicios públicos) / B (actividad institucional) | ¿Existe enum `modalidad` con 4 valores (al menos S/U/E/B) en programas? | 🟠 | M7 |
| C-118 | Clasificador por objeto del gasto: Cap. 1000 (servicios personales), 2000 (materiales), 3000 (servicios generales), 4000 (transferencias) | ¿Existe enum/catálogo `capitulo_gasto` con los 4 capítulos principales? | 🟠 | M8 |
| C-119 | Subcapítulo 4400 Ayudas Sociales a Personas (becas/subsidios directos) | ¿Existe nivel `subcapitulo_gasto` y enum/seed con 4400 entre otros? | 🟢 | — |
| C-120 | Expediente técnico con 8 documentos obligatorios (Diagnóstico/ROP/MIR/Fichas/Padrón/IAFF/Eval+ASM/POA) | ¿Existe checklist/modelo `expediente_tecnico` con FK a los 8 documentos requeridos? | 🟠 | M3, M7, M8, M10 |
| C-121 | Cadena de alineación ODS→PND→Programa Sectorial→PED→Programa Presupuestal (MIR) | ¿Existe relación FK explícita que conecte los 5 niveles de alineación en el programa? | 🟠 | M1 |
| C-122 | ODS (17 objetivos Agenda 2030) como nivel de alineación internacional | ¿Existe catálogo `ods` con 17 objetivos y FK desde programa a ODS aplicables? | 🟠 | — |
| C-123 | Metas específicas de ODS (ej. 4.1, 4.5, 10.2) como granularidad fina de alineación | ¿Existe tabla `ods_meta` con metas específicas vinculables al programa? | 🟡 | — |
| C-124 | Programas Derivados del PND (Sectorial/Especial/Institucional/Regional) | ¿Existe enum `tipo_programa_derivado` con 4 valores en catálogo de programas derivados? | 🟡 | M1 |
| C-125 | PEF / Decreto de Presupuesto Estatal: presupuesto aprobado por capítulo de gasto | ¿Existe modelo `presupuesto_aprobado` por programa, ejercicio fiscal, capítulo? | 🔴 | M8 |
| C-126 | Modificaciones presupuestales: ampliaciones, reducciones, adecuaciones entre capítulos | ¿Existe modelo `modificacion_presupuestal` con tipo categorizado y trazabilidad? | 🟠 | M8 |
| C-127 | Restricción especial cap. 4000 (transferencias): cambios requieren modificar ROP | ¿Existe validación que rechace modificaciones a cap. 4000 sin ROP actualizadas? | 🟡 | — |
| C-128 | Revisión de metas durante el ejercicio (justificada por cambio externo verificable) | ¿Existe modelo `revision_meta` con justificación documentada (no solo "no se alcanzó")? | 🟡 | — |
| C-129 | Cuenta Pública anual con resultados, presupuesto aprobado vs. ejercido, indicadores con valores observados, ASM | ¿Existe exportación/reporte tipo Cuenta Pública por programa? | 🟠 | — |
| C-130 | Transparencia proactiva: publicación de ROP, padrón, recursos por municipio/localidad, evaluaciones, IAFF | ¿Existen 5 endpoints públicos/datasets para los 5 ítems obligatorios de transparencia? | 🟠 | M6, M7 |
| C-131 | Datos personales sensibles del padrón se excluyen de la publicación (salud, patrimonio individual) | ¿Existe lista/lógica de campos del padrón que se enmascaran o excluyen en datasets públicos? | 🔴 | M7 |

### Módulo 10 — Evaluación y Mejora Continua

| ID | Concepto | Criterio verificable | Severidad | También-en |
|---|---|---|---|---|
| C-132 | Distinción Seguimiento (interno+durante) vs. Evaluación (externo+post) | ¿Existen modelos separados `seguimiento` y `evaluacion_externa` sin fusión conceptual? | 🟡 | M8 |
| C-133 | Principio de independencia del evaluador (sin conflicto de interés) | ¿Existe declaración/contrato de independencia persistido por evaluación externa? | 🟢 | — |
| C-134 | Tipo Evaluación de Diseño (primer/segundo año o rediseño) | ¿Está incluido `diseño` en enum tipo_evaluacion? | 🟠 | M6 |
| C-135 | Tipo Evaluación de Procesos (después de 2-3 años) | ¿Está incluido `procesos` en enum tipo_evaluacion? | 🟠 | M6 |
| C-136 | Tipo ECR Consistencia y Resultados (después de 3+ años, más frecuente en PAE) | ¿Está incluido `ecr` en enum tipo_evaluacion? | 🟠 | M6 |
| C-137 | Tipo Evaluación de Impacto (5+ años, métodos contrafactuales) | ¿Está incluido `impacto` en enum tipo_evaluacion? | 🟠 | M6 |
| C-138 | Tipo EED Específica de Desempeño (anual, sintética) | ¿Está incluido `eed` en enum tipo_evaluacion? | 🟡 | M6 |
| C-139 | PAE (Programa Anual de Evaluación) publicado antes del 30 de abril | ¿Existe modelo `pae` por ejercicio fiscal con FK a programas a evaluar y tipo? | 🟡 | — |
| C-140 | Términos de Referencia (TdR) como base contractual del evaluador | ¿Existe modelo `tdr` o campo con TdR vinculado a la evaluación externa? | 🟢 | — |
| C-141 | Informe de evaluación con: resumen ejecutivo, metodología, hallazgos, conclusiones, recomendaciones, fichas | ¿Existe estructura/PDF `informe_evaluacion` con las 6 secciones load-bearing? | 🟠 | — |
| C-142 | Hallazgo de evaluación sustentado en evidencia (padrón, indicadores, entrevistas, documental) | ¿Existe modelo `hallazgo` con FK a evaluacion + campo de evidencia? | 🟡 | — |
| C-143 | Diferencia recomendación (evaluador) vs. ASM (compromiso de UR) | ¿Existen modelos separados `recomendacion` y `asm` con FK entre ellos? | 🟠 | M8 |
| C-144 | Mecanismo de Seguimiento a los ASM con reporte semestral (cumplido/en proceso/no iniciado) | ¿Existe enum `estado_asm` con 3+ estados y reporte semestral persistido? | 🟠 | — |
| C-145 | Tipos de acción ASM: corto plazo / mediano plazo / largo plazo + cambio normativo / mejora operativa / mejora gestión información | ¿Existen 2 enums (plazo, tipo_accion) por ASM con los valores normativos? | 🟡 | — |
| C-146 | Reglas de "no cambiar la MIR": no bajar meta sin justificación, no cambiar indicador por facilidad, no reducir ambición del Propósito | ¿Existe validación/warning cuando se modifican meta/indicador/RN del propósito sin justificación documentada? | 🟡 | M5 |
| C-147 | Contrafactual y grupos de comparación como metodología de evaluación de impacto | ¿Existe documentación/UI para evaluación de impacto que mencione contrafactual + grupos de comparación? | 🟢 | — |

## Resumen ejecutivo

### Totales por módulo

| Módulo | Conceptos | 🔴 | 🟠 | 🟡 | 🟢 |
|---|---|---|---|---|---|
| 1 — Contexto PbR | 12 | 0 | 5 | 3 | 4 |
| 2 — MML | 14 | 2 | 4 | 4 | 4 |
| 3 — Arquitectura MIR | 11 | 3 | 4 | 3 | 1 |
| 4 — Resumen Narrativo y Lógica | 9 | 0 | 1 | 5 | 3 |
| 5 — Diseño de Indicadores | 24 | 3 | 11 | 7 | 3 |
| 6 — Medios Verificación y Supuestos | 10 | 0 | 5 | 3 | 2 |
| 7 — Padrón | 18 | 5 | 9 | 4 | 0 |
| 8 — Seguimiento y Cierre | 16 | 3 | 9 | 4 | 0 |
| 9 — Presupuestación y Alineación | 17 | 3 | 9 | 4 | 1 |
| 10 — Evaluación y Mejora Continua | 16 | 0 | 7 | 6 | 3 |
| **Total** | **147** | **19** | **64** | **43** | **21** |

### Distribución por severidad (global)

🔴 19 · 🟠 64 · 🟡 43 · 🟢 21

### Cross-references importantes

Conceptos que aparecen en ≥2 módulos (load-bearing cross-funcional):

- **Tres poblaciones (C-022)**: M2, M3, M7, M9 — anclaje conceptual del programa
- **Desagregación obligatoria de poblaciones (C-025)**: M2, M7 — replicada como C-085..C-088 a nivel padrón individual
- **Cadena de Valor Público (C-012)**: M1, M3, M4 — estructura inherente de la MIR
- **Estructura MIR 4×4 (C-027)**: M3, M4, M5, M6 — núcleo del sistema
- **Lógica horizontal (C-044)**: M4, M5, M6 — validación inter-columnas
- **Ficha Técnica (C-070)**: M5, M8, M9 — alimenta seguimiento y expediente
- **Periodicidad (C-063)**: M5, M6, M8 — gating de reporte
- **Semaforización (C-065)**: M5, M8 — diseño vs. aplicación operativa
- **Meta del indicador (C-060)**: M5, M8 — comprometida en diseño, evaluada en seguimiento
- **CONAPO marginación (C-093)**: M7, M9 — validación elegibilidad y focalización
- **ROP (C-098)**: M7, M9 — fuente de elegibilidad y montos
- **IAFF (C-099)**: M8, M9 — instrumento operacional y de rendición
- **POA (C-113)**: M8, M9 — calendarización y presupuesto por actividad
- **Avance presupuestal por capítulo (C-101)**: M8 + capítulos de M9 — modelo compartido
- **PEF/Decreto presupuesto (C-125)**: M8, M9 — aprobado vs. ejercido
- **Modificaciones presupuestales (C-126)**: M8, M9 — durante el ejercicio
- **Cuenta Pública (C-129)**: M8, M9 — cierre + rendición
- **Transparencia proactiva (C-130)**: M6, M7, M9 — obligación normativa LGTAIP
- **Datos sensibles excluidos (C-131)**: M7, M9 — LGPDPPSO + transparencia
- **ASM (C-112, C-143)**: M6, M8, M10 — ciclo completo evaluación→compromiso→seguimiento
- **Tipos de evaluación externa (C-134..C-138)**: M6 + M10 — enum cross-modular
- **Reglas de Operación / ROP modalidad S (C-117)**: M7, M9 — taxonomía SHCP
- **Alineación PND/PED (C-001..C-003)**: M1, M9 — el Fin se alinea allí
- **Supuestos por nivel (C-077)**: M3, M6 — definición + categorización
- **Componentes con verbo participio pasado (C-040)**: M3, M4 — sintaxis crítica
- **Codificación A[n].[m] (C-031, C-041, C-046)**: M3, M4 — trazabilidad operativa
- **Brecha de desempeño (C-024)**: M2, M7, M8 — cálculo cobertura efectiva

### Conceptos de complementariedad sistema (dte-spp ↔ geobase)

Conceptos donde uno de los dos sistemas captura y el otro reporta/visualiza territorialmente:

| ID | Concepto | dte-spp rol | geobase rol |
|---|---|---|---|
| C-022 | Tres poblaciones cuantificadas | Captura Potencial/Objetivo/Atendida en programa | Mapas de cobertura territorial por municipio |
| C-025 | Desagregación obligatoria (sexo/etario/etnia/discapacidad/ubicación) | Validación en captura padrón | Visualización geográfica + buckets PP mexicana NNA/juventud/adulto/AM |
| C-082 | CURP único | Storage cifrado (LGPDPPSO) | Padrón SHCP export sólo bajo ability `padron:export-shcp` |
| C-084 | Datos de entrega del apoyo (folio, fecha, monto, estado) | Modelo central padrón_beneficiarios | Snapshot trimestral hashed en `avance_evidencias` |
| C-085..C-088 | Desagregación por sexo/etario/etnia/discapacidad | Enums y validaciones en padrón | Cobertura territorial desagregada (DS-G02) |
| C-099 | IAFF trimestral | Generación + sección 2 (indicadores) y 3 (presupuesto) | Componente Cobertura del IAFF (sección territorial) |
| C-108 | Tablero de control con semaforización | Dashboard programa + componente MIR | Mapa cobertura con badge "datos en vivo desde GeoBase" |
| C-111 | Conciliación físico-financiera padrón ↔ Tesorería | Reporte cruce padrón vs. registros bancarios | Snapshot territorial al cierre trimestral |
| C-129 | Cuenta Pública | Exportación PDF/XLSX por programa | Datasets `pub_cobertura_municipal`, `pub_cobertura_geografica` |
| C-130 | Transparencia proactiva (publicación padrón, recursos, IAFF) | Pipeline N2-03 → `pub_*` en BD pública | Endpoints bulk `/api/v1/geobase/reportes/{...}-bulk` |
| C-131 | Datos sensibles del padrón excluidos | Lista de campos enmascarados en `pub_*` | Solo agrega/desagrega sin PII directo |
| C-093 | Cruce CONAPO marginación por localidad | Seed/tabla CONAPO + validación en captura | Polígono unión PostGIS por localidad/municipio |

### Conceptos sin equivalente sistémico (solo proceso institucional)

Conceptos del temario que NO son responsabilidad de dte-spp ni geobase como sistemas; viven como proceso humano/institucional o documentación externa. Para evitar que subagentes downstream los reporten como falsa brecha:

- **C-005, C-006, C-007** — Marcos normativos LFPRH/LGCG/lineamientos SHCP-CONEVAL: son referencia legal, no entidades de software. Pueden vivir en seed/config si conviene auditoría, pero no son brecha si no están persistidos.
- **C-008** — Tres actores institucionales SHCP/CONEVAL/ASF: catálogo opcional. Si no está persistido, no es brecha funcional.
- **C-020** — Selección de Alternativas con 6 criterios: típicamente PDF interno; persistirlo en BD es ergonómico, no obligatorio.
- **C-026** — EAPp como árbol intermedio Árbol→MIR: documento de trabajo interno (no público según M2.8), opcional persistir.
- **C-042, C-043** — Pruebas si/entonces ascendente/descendente: documentación de proceso, no validación automatizable en código.
- **C-045** — Necesidad y suficiencia de Componentes: checklist humano, no validación automatizada.
- **C-061** — Nombre del indicador neutral: idealmente un lint, pero altamente heurístico (acceptable como warning, no como rechazo).
- **C-076** — Prueba si/entonces inverso del supuesto: guía/wizard UX, no validación dura.
- **C-080** — Contraloría Ciudadana: efecto emergente de C-130 (transparencia proactiva), no entidad propia.
- **C-109** — Reuniones de seguimiento (operativo/gestión/directivo): minutas opcionales en software; viven en agendas humanas.
- **C-133** — Independencia del evaluador externo: declaración contractual, opcional persistir.
- **C-139, C-140** — PAE y TdR: documentos externos del CONEVAL/SHCP; persistirlos es ergonomía, no normativo en dte-spp.
- **C-147** — Contrafactual y grupos de comparación: metodología de evaluación impactada, no entidad de software.
- **C-019** — Análisis de Involucrados: documento Fase MML 2; alimenta supuestos (C-035) pero la lista en sí puede vivir en doc externo.
- **C-013** — Las 6 fases MML: documentación del proceso, no necesariamente persistida como máquina de estados.

### Notas finales y desviaciones del plan preliminar

- **Conteo real: 147 conceptos** (vs. ~104 estimados por el Explore preliminar). El desvío se debe principalmente a:
  - **Módulo 5 expandido a 24 conceptos** (vs. 14 estimados): el atributo CREMAA, las 4 dimensiones, las 4 reglas de semaforización, los 7 valores de periodicidad y los 5 bloques de Ficha Técnica son entidades discretas que pesan individualmente.
  - **Módulo 7 expandido a 18 conceptos** (vs. 11 estimados): las 4 desagregaciones obligatorias se desglosaron en 4 conceptos individuales (C-085..C-088) por carga normativa propia, los 4 vínculos Padrón↔MIR se unificaron en C-094 con cross-ref a sus 4 originadores, y los 8 errores P-01..P-08 se consolidaron en C-095.
  - **Módulo 9 expandido a 17 conceptos** (vs. 9 estimados): el clasificador por capítulo de gasto, las 4 modalidades de programa, los 5 niveles de alineación ODS→PND→…→MIR y las 5 obligaciones de transparencia proactiva son load-bearing independientes.
  - **Módulo 10 expandido a 16 conceptos** (vs. 3 estimados): el preliminar subestimaba severamente este módulo. Los 5 tipos de evaluación + los 5 elementos del ASM + el mecanismo de seguimiento + las 3 reglas de "no cambiar MIR" + estructura del informe de evaluación son cada uno load-bearing.
- **Distribución 🔴 (19/147 = 13%)**: concentrada en padrón (C-082..C-088 desagregación + CURP + datos entrega), en estructura MIR base (C-027, C-032, C-033, C-034), en IAFF (C-099..C-101), en ficha técnica de indicadores (C-056, C-059, C-060), en clave presupuestal (C-116), en presupuesto aprobado (C-125), y en PII sensible (C-131). Estos son los conceptos que si faltan, el sistema no cumple normativa o expone datos personales.
- **Distribución 🟠 (64/147 = 44%)**: dominan auditoría/Cuenta Pública. Son los conceptos que la ASF y el CONEVAL revisan formalmente.
- **Cobertura por módulo balanceada**: los 10 módulos están representados con conteos razonables, sin grandes lagunas conceptuales detectadas.
- **Conceptos que viven en ambos sistemas (dte-spp ↔ geobase)**: la sección de complementariedad lista 12 conceptos compartidos. Esto debe servir como referencia para que un subagente downstream sepa que reportar el mismo concepto como brecha en ambos lados es esperable, no duplicación errónea.
