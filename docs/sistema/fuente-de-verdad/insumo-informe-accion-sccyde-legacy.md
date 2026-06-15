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

## 5. Pregunta abierta — campos de texto del informe en el sistema

> Cómo evitar que las preguntas abiertas del formato legacy se conviertan en texto libre sin estructura dentro del informe del sistema. Pendiente de diseñar (candidatos: plantillas guiadas con sub-campos por pregunta, rúbricas/validaciones mínimas, asistencia IA para estructurar la respuesta a partir de la evidencia). Registrar aquí la decisión cuando se tome.

---

*Referencias cruzadas:* [Glosario MIR](glosario_MIR.md) · [Inventario de conceptos del temario](inventario_conceptos_temario_v2.md) · [Integración geobase ↔ dte-spp](integracion_geobase_dte.md) · módulos [M07 Padrón](../modulos/M07-padron.md), [M08 Seguimiento](../modulos/M08-seguimiento-cierre.md), [M10 Evaluación](../modulos/M10-evaluacion.md).
