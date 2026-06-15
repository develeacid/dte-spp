# Insumo legacy — "Informe de acción" de la Subsecretaría de Crecimiento y Desarrollo Económico (SCCyDE)

> **Tipo:** insumo conceptual transversal (referencia para implementación).
> **Origen:** archivo `Preguntas Subsecretaría de Crecimiento y Desarrollo Económico.xlsx` — formato de captura **previo al sistema** con el que la SCCyDE reportaba su operación. El `.xlsx` no se versiona; este documento conserva su análisis.
> **Fecha de análisis:** 2026-06-15.

---

## 1. Qué es el artefacto

No es un cuestionario suelto: es un **catálogo de 37 plantillas de "Informe de acción"**, una por cada **Componente y Actividad** de la MIR de **3 Programas Presupuestarios** de la Subsecretaría:

- **121** — Productividad y Transferencia de Conocimiento
- **142** — Fomento al Empleo
- **172** — Fortalecimiento del Mercado Interno

La columna `Act/Com` es la **clave MIR** del bloque (`121_C3`, `121_C3_A3.1`, `172_C2_A2.4`, …): Programa → Componente → Actividad. Cada plantilla tiene ~38–43 campos repartidos en **6 secciones fijas**. La unidad de reporte es **una acción/evento ejecutado en campo** (no el trimestre): el operador llenaba un informe por cada acción realizada.

### Las 6 secciones

| # | Sección | Contenido |
| --- | --- | --- |
| I | **Identificación Institucional y Programática** | Folio, Subsecretaría, Dirección, Departamento, responsable, **Programa Presupuestario, Componente MIR, Actividad MIR, Indicador MIR asociado**. |
| II | **Datos Operativos y Geográficos** | Fecha inicio/fin, **municipio (de los 570)**, lugar/sede, **coordenadas lat-long**. |
| III | **Descripción y Alineación del Servicio** | Nombre del servicio, objetivo, tipo de servicio, descripción, **mapeo de actores**, tipo de beneficiario. |
| IV | **Medición de Impacto y Resultados** | **Total de eventos/acciones realizados**, asistencia, **UE/MiPyMEs beneficiadas, desglose por sexo**, inversión ejercida, derrama económica, ahorro, **municipios aledaños beneficiados**, impacto económico/social/político. |
| V | **Sistematización de la Experiencia** | Factores de éxito / factores inhibidores, innovación, **lecciones aprendidas y recomendaciones**. |
| VI | **Evidencias y Comprobables** | Listas de asistencia firmadas, evidencia fotográfica, PDF. |

**Dato clave:** el formato **no captura meta ni programado** (cero ocurrencias). Solo recoge el *realizado* + su expediente. El denominador del indicador vive en la MIR. Es decir, el formato es un **alimentador granular** (numerador + evidencia + padrón + geo + aprendizajes), **no** el cálculo del indicador.

---

## 2. A qué proceso(s) del sistema pertenece — es **multi-proceso**

El instrumento **no pertenece a un solo proceso**: es un formato "todo en uno" que el sistema actual reparte en **4 módulos distintos**. Mapeo sección ↔ proceso:

| Sección del formato legacy | Proceso en el sistema | Materialización |
| --- | --- | --- |
| **I.** Identificación + alineación (Pp, Componente, Actividad, Indicador) | **MIR / Planeación (Mml)** | Estructura Fin→Propósito→Componente→Actividad→Indicador ya modelada. La acción "cuelga" del nodo MIR correcto. |
| **IV.** "Total de eventos/acciones realizados en esta acción" | **Seguimiento (Tracking) — captura de avance** | Es el **numerador** del indicador. Todos los indicadores son "Porcentaje de X realizadas/os" = realizados/programados × 100 → alimenta la **variable de la fórmula** en `CapturaAvance`. |
| **IV.** UE/MiPyMEs beneficiadas, **desglose por sexo**, tipo de beneficiario | **Padrón / Desagregación demográfica (GeoBase)** | DS-G02 (buckets demográficos, sexo). |
| **II.** Municipio (570), coordenadas lat-long, "municipios aledaños" (+ anexo `municipiosindirectos.xlsx`) | **Cobertura geográfica/municipal (GeoBase)** | DS-G01 (cobertura municipal) + DS-G03 (cobertura geográfica / polígonos). |
| **VI.** Listas de asistencia, fotos, PDF | **Seguimiento — evidencias del avance** + medios de verificación | `EvidenciaAvance` / medios de verificación. |
| **V.** Factores de éxito/inhibidores, lecciones, innovación + las preguntas de **impacto económico/social/político** de IV | **Evaluación** | Proto-evaluación de procesos/impacto: hallazgos→recomendaciones→ASM (`EvaluacionExterna`); parcialmente, el **análisis de desviación** (causa/acción/proyección) del Tracking. |
| **III.** Objetivo, descripción, mapeo de actores | Transversal | Narrativa cualitativa; en parte medio de verificación, en parte alineación. |

---

## 3. Por qué lo hacían así (racional)

1. **No tenían un sistema relacional que separara planeación / seguimiento / padrón / evaluación.** Construyeron **un solo formato que captura todo lo que esos cuatro procesos necesitan aguas abajo**. Para el operador, "un evento" es **una cosa real** con todas esas facetas a la vez; normalizarla (como hace el sistema) era imposible en Excel.
2. **Reportan al grano más fino (cada acción/evento), no por trimestre**, porque el indicador es un *conteo*. Cada informe = **1 unidad del numerador + su dossier**. La meta trimestral se obtiene **sumando manualmente** estos informes — justo lo que Tracking hace hoy con variables de fórmula.
3. **Orden top-down siguiendo la MIR** (Pp→Componente→Actividad→Indicador) para poder **agregar después** cada acción al indicador correcto. Las 37 plantillas existen porque hicieron **una por nodo MIR**, adaptando las preguntas a la naturaleza de cada actividad (difusión, organización, sensibilización, acuerdos…).
4. **Las 6 secciones son un mini-ciclo PbR por acción**: identificar → operar → describir → medir → aprender → comprobar. PbR-SED hecho a mano: querían no solo el número (eficacia) sino **impacto, georreferencia, beneficiarios desagregados y lecciones**.

**En una frase:** es una **ficha de seguimiento operativo a nivel actividad** que mezcla seguimiento (numerador + evidencias), padrón/cobertura (beneficiarios + geo) y evaluación (sistematización + impacto), colgada de la MIR — un sustituto manual y monolítico de los cuatro módulos que el sistema ya separa.

---

## 4. Implicaciones para la implementación

- **El sistema ya cubre la mayor parte del formato**, pero **repartido**: el operador que antes llenaba un Excel monolítico hoy toca Tracking (avance + evidencias), GeoBase (padrón + cobertura) y Evaluación (sistematización). Vale la pena documentar este "mapa de equivalencias" en la capacitación de la SCCyDE para que no perciban pérdida de información.
- **Brecha de captura cualitativa.** Las secciones III y V (descripción, factores de éxito/inhibidores, lecciones aprendidas, impacto económico/social/político) son **preguntas abiertas** que hoy caen en campos de texto libre del informe. Es el punto donde más se puede perder valor → ver §5.
- **Grano de captura.** El formato reporta por *acción individual*; el Tracking captura por *indicador × periodo*. Para conservar la trazabilidad por acción (deseable para auditoría y cobertura), considerar que cada evidencia/acción pueda registrarse como ítem y que la suma alimente el numerador, en lugar de capturar solo el agregado trimestral.
- **Beneficios indirectos / municipios aledaños.** El concepto "municipios aledaños beneficiados" (con anexo) es cobertura *indirecta*; no tiene equivalente directo en el modelo de cobertura municipal actual — evaluar si se modela en GeoBase.

## 5. Cómo solventar las "preguntas abiertas" en el informe del sistema

**El problema real** no es que sean campos de texto, sino que **el texto libre sin estructura no se puede agregar, comparar ni auditar** — y justo las secciones III y V (descripción, factores de éxito/inhibidores, lecciones aprendidas, impacto económico/social/político) son las de mayor valor analítico. Si se vuelcan a un único `textarea` por sección, se pierde la trazabilidad que el formato legacy sí tenía (cada pregunta era específica).

Hay tres enfoques, de menor a mayor esfuerzo, **combinables**. Los tres reutilizan patrones que el sistema ya tiene implementados, así que no parten de cero.

### Enfoque A — Descomponer cada "pregunta abierta" en sub-campos guiados *(recomendado, base)*

En vez de un `textarea` por sección, el informe replica las preguntas concretas del formato como **campos separados** con *placeholder*, ayuda contextual y validación mínima. Ejemplo con la sección V (Sistematización):

| Campo legacy (era 1 "pregunta abierta") | Sub-campo estructurado | Validación |
| --- | --- | --- |
| Factores de éxito | `factores_exito` | obligatorio, mín. N caracteres |
| Factores inhibidores | `factores_inhibidores` | obligatorio, mín. N caracteres |
| Brecha planeado vs. real | `brecha_planeado_real` | obligatorio |
| Qué haríamos diferente | `recomendacion` | obligatorio |

**Cómo se materializa en el sistema:** es exactamente el patrón del **análisis de desviación** del Tracking — `avances.analisis_desviacion` JSONB con keys fijas `dato/causa/accion/proyeccion`, cada una obligatoria con mínimo de caracteres, exigida solo cuando aplica (semáforo amarillo/rojo/rojo_alto). Se replica: una columna **JSONB con keys fijas** por sección cualitativa + reglas de validación duras (estilo B3–B7 de `IndicadorReglasService`). Sin tablas nuevas pesadas; estructura predecible y reportable.

**Pros:** mayor retorno con menor esfuerzo; conserva toda la riqueza del formato legacy; elimina respuestas de una palabra y los "N/A". **Contra:** hay que acordar el set de sub-campos por sección (de ahí la conversación con analistas).

### Enfoque B — Validaciones, condicionales y rúbricas suaves *(complemento de A)*

- **Mínimos de longitud** y obligatoriedad **condicional** (como el análisis de desviación, que solo se exige bajo cierto semáforo): no recargar al operador cuando la pregunta no aplica.
- **Preguntas cerradas del formato** (los "¿…? ( ) SÍ ( ) NO. Si SÍ: indique cuántos y cuáles") → `radio`/`select` que **abre un sub-campo condicional** solo cuando se responde SÍ. Esto elimina ambigüedad y normaliza el dato (p. ej. municipios aledaños).
- **Rúbrica mínima** opcional: una checklist de "qué debe contener una buena respuesta" junto al campo, como guía editorial.

**Cómo se materializa:** validaciones de FormRequest / reglas de componente Livewire + campos condicionales en Blade (el sistema ya usa este patrón en captura de avance y en el editor MIR).

### Enfoque C — Asistencia IA para estructurar el borrador *(mejora posterior)*

Un botón **"Estructurar informe con IA"** que, a partir de las **evidencias adjuntas + los datos cuantitativos ya capturados** (totales, beneficiarios, geo), **pre-rellena un borrador editable** de las respuestas cualitativas con la estructura correcta. El operador **edita**, no parte de cero.

**Cómo se materializa:** el sistema ya tiene infraestructura de prompts IA versionados (`resources/views/prompts/...`, p. ej. `prompts/mir/validar-crema-mv`) y botones IA en producción ("Generar justificación IA" en captura, "Validar CREMA"). Se añade un prompt nuevo (`prompts/informe/estructurar-*`) con el mismo mecanismo. Ataca la causa raíz de las respuestas pobres (falta de tiempo/redacción) sin quitarle control al operador.

**Pros:** sube la calidad de fondo. **Contra:** depende de A (necesita los sub-campos destino) y de monitoreo de costo/calidad IA.

### Recomendación

Empezar por **A + B** (mayor retorno, reutiliza un patrón ya probado: `analisis_desviacion` JSONB + validaciones duras) y dejar **C** como mejora posterior una vez estabilizado el set de sub-campos.

### Preguntas para validar con los analistas (antes de fijar A)

Estas definen el set de sub-campos y evitan rediseños:

1. **¿Cuál es la unidad real de reporte?** ¿Una acción/evento (como el formato legacy) o el agregado trimestral del indicador? De esto depende si el informe vive ligado al avance (indicador × periodo) o como ítem por acción que suma al numerador.
2. **¿Qué secciones del formato siguen siendo obligatorias** en el sistema y cuáles eran "por completar el Excel"? (Evitar arrastrar campos muertos como los "N/A".)
3. **¿Qué respuestas se usan después y para qué** (reporte, evaluación, transparencia)? Solo eso justifica estructurarlas; el resto puede ser narrativa libre.
4. **¿Las preguntas de impacto (económico/social/político) son del operador o del evaluador?** Si son de evaluación, su lugar natural es Evaluación externa (hallazgo→recomendación→ASM), no el avance.
5. **¿Qué partes son cuantitativas disfrazadas de texto** (inversión, derrama, ahorro, asistencia)? Esas deberían ser numéricas/numerador, no texto.
6. **"Municipios aledaños beneficiados" (cobertura indirecta):** ¿se modela en GeoBase o queda como nota? No tiene equivalente directo hoy.

> **Estado:** propuesta para discusión. Registrar aquí la decisión (set de sub-campos por sección, unidad de reporte, qué va a Evaluación vs. Seguimiento) cuando se acuerde con los analistas.

---

*Referencias cruzadas:* [Glosario MIR](glosario_MIR.md) · [Inventario de conceptos del temario](inventario_conceptos_temario_v2.md) · [Integración geobase ↔ dte-spp](integracion_geobase_dte.md) · módulos [M07 Padrón](../modulos/M07-padron.md), [M08 Seguimiento](../modulos/M08-seguimiento-cierre.md), [M10 Evaluación](../modulos/M10-evaluacion.md).
