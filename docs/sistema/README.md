# Documentación integral del sistema PbR-SED (dte-spp + geobase)

> Índice maestro de la documentación del ecosistema de Planeación para Programas Presupuestales basado en Resultados (PbR-SED): los dos sistemas (`dte-spp` y `geobase`), su cobertura del temario MIR módulo por módulo, los manuales de usuario por rol y la fuente de verdad conceptual.

Última verificación adversarial contra el código: **2026-06-14** (Fase 3).

---

## 1. Propósito y cómo navegar

Esta carpeta documenta **qué cubre el sistema frente al temario MIR / PbR-SED**, **cómo se opera por rol** y **dónde están las brechas reales**. El ecosistema lo forman dos aplicaciones hermanas:

- **dte-spp** — planeación (MML/MIR), seguimiento (IAFF/avances), presupuestación, evaluación externa, transparencia.
- **geobase** — padrón de beneficiarios, cobertura geográfica, validaciones P-01..P-08, ROP versionado (C-098).

Se conectan vía API M2M (token Sanctum `padron:*`) + webhooks. La frontera de datos está documentada en la fuente de verdad.

### Estructura de carpetas

| Carpeta | Qué contiene |
| --- | --- |
| [`modulos/`](modulos/) | Un documento por cada módulo del temario (M01–M10) con su **matriz de cumplimiento** requerimiento ↔ ruta/vista/componente del código real. Es el cruce temario ↔ sistema. |
| [`manuales/`](manuales/) | Manuales de usuario **por rol**, agrupados por sistema (dte-spp / geobase) más uno de acceso general. |
| [`brechas/`](brechas/) | Registro consolidado de **brechas verificadas adversarialmente** contra el código (reales / parciales / falsos positivos) con evidencia y recomendación. |
| [`fuente-de-verdad/`](fuente-de-verdad/) | Insumos conceptuales transversales: glosario MIR, catálogo de diagramas, integración geobase↔dte y el inventario de conceptos del temario. |

Mapa general del sistema (visión arquitectónica): [`_mapa-sistema.md`](_mapa-sistema.md).

---

## 2. Matriz de cobertura M1–M10 × sistema

Cobertura del temario MIR por módulo. El **estado global** resume el nivel de cumplimiento que reporta cada documento de módulo; los conteos de **brechas reales** y **brechas altas** se derivan del [registro consolidado de brechas](brechas/README.md). La columna **Sistema** indica dónde recaen mayoritariamente las brechas del módulo.

| Módulo | Tema | Estado global | # brechas reales | # brechas altas | Sistema | Doc |
| --- | --- | --- | --- | --- | --- | --- |
| **M01** | Contexto y fundamentos del PbR | Alto | 1 | 0 | dte-spp | [M01](modulos/M01-fundamentos-pbr.md) |
| **M02** | Metodología de Marco Lógico (MML) | Alto (≈80%) | 11 | 0 | dte-spp | [M02](modulos/M02-marco-logico.md) |
| **M03** | Arquitectura de la MIR (4×4) | Alto | 3 | 0 | dte-spp | [M03](modulos/M03-arquitectura-mir.md) |
| **M04** | Resumen Narrativo y lógica de la matriz | Alto | 4 | 0 | dte-spp | [M04](modulos/M04-resumen-narrativo.md) |
| **M05** | Diseño de indicadores de desempeño | Alto | 5 | 1 | dte-spp | [M05](modulos/M05-indicadores.md) |
| **M06** | Medios de Verificación y Supuestos | Alto | 4 | 0 | dte-spp | [M06](modulos/M06-mv-supuestos.md) |
| **M07** | Padrón de beneficiarios | Alto | 9 | 4 | geobase / ambos | [M07](modulos/M07-padron.md) |
| **M08** | Seguimiento y cierre fiscal | Alto | 7 | 0 | dte-spp | [M08](modulos/M08-seguimiento-cierre.md) |
| **M09** | Presupuestación e instrumentos de alineación | Alto | 4 | 1 | dte-spp / ambos | [M09](modulos/M09-presupuestacion.md) |
| **M10** | Evaluación y mejora continua | Alto | 6 | 0 | dte-spp | [M10](modulos/M10-evaluacion.md) |
| | **Total** | | **54** | **6** | | |

> Notas. (a) Todos los módulos tienen cumplimiento **alto**; M02 lo reporta como ≈80%. (b) Las 4 brechas altas de M07 (P-03 ROP, P-04 PUBP, P-06 RENAPO, indicador de calidad del padrón) y varias parciales de M09 son **decisiones arquitectónicas cross-sistema documentadas** (C-098 ROP en geobase, sprints F2-01), no fallas de implementación — ver [§5 del registro de brechas](brechas/README.md#5-decisiones-arquitectonicas-no-brechas). (c) El registro de brechas resume 51 reales en su tabla de cabecera; el conteo fila-a-fila de las secciones de severidad suma 54, que es el valor usado aquí.

---

## 3. Manuales por rol

Guías operativas paso a paso por rol. Las credenciales de acceso, navegación común y reglas de sesión están en el manual de acceso general.

### dte-spp

| Rol | Manual |
| --- | --- |
| Operador | [dte-spp-01-operador](manuales/dte-spp-01-operador.md) |
| Planeador | [dte-spp-02-planeador](manuales/dte-spp-02-planeador.md) |
| Analista financiero | [dte-spp-03-analista-financiero](manuales/dte-spp-03-analista-financiero.md) |
| Analista jurídico | [dte-spp-04-analista-juridico](manuales/dte-spp-04-analista-juridico.md) |
| Responsable de Datos Abiertos | [dte-spp-05-responsable-datos-abiertos](manuales/dte-spp-05-responsable-datos-abiertos.md) |
| Administrador | [dte-spp-06-administrador](manuales/dte-spp-06-administrador.md) |

### geobase

| Rol | Manual |
| --- | --- |
| Sysadmin | [geobase-01-sysadmin](manuales/geobase-01-sysadmin.md) |
| Admin de dependencia | [geobase-02-admin-dependencia](manuales/geobase-02-admin-dependencia.md) |
| Analista global | [geobase-03-analista-global](manuales/geobase-03-analista-global.md) |
| Enlace MIR | [geobase-04-enlace-mir](manuales/geobase-04-enlace-mir.md) |
| Operador | [geobase-05-operador](manuales/geobase-05-operador.md) |

### General

| Tema | Manual |
| --- | --- |
| Acceso, navegación y credenciales | [00-acceso-general](manuales/00-acceso-general.md) |

---

## 4. Registro de brechas

El [**registro consolidado de brechas**](brechas/README.md) es la fuente de verdad del estado de cobertura del temario frente al código. Cada brecha fue confirmada o refutada leyendo archivos, migraciones, servicios, prompts y rutas reales.

| Veredicto | Cantidad |
| --- | --- |
| Reales (confirmadas) | **54** |
| Parciales (matizadas) | **11** |
| Falsos positivos | **0** |
| de las cuales, severidad alta | **6** |

---

## 5. Fuente de verdad

Insumos conceptuales transversales que sustentan los módulos y manuales:

- [**Glosario MIR**](fuente-de-verdad/glosario_MIR.md) — definiciones canónicas de términos PbR/GpR/SED, niveles MIR, CREMAA, semáforo, etc.
- [**Catálogo de diagramas**](fuente-de-verdad/catalogo_diagramas.md) — índice de diagramas del sistema (arquitectura, flujos, integraciones).
- [**Integración geobase ↔ dte-spp**](fuente-de-verdad/integracion_geobase_dte.md) — frontera de datos, API M2M, webhooks, padrón y ROP cross-sistema.
- [**Inventario de conceptos del temario (v2)**](fuente-de-verdad/inventario_conceptos_temario_v2.md) — los conceptos del temario MIR cruzados con su materialización en código.
- Fichas de módulo de la fuente de verdad: [modulo_01](fuente-de-verdad/modulo_01.md) … [modulo_10](fuente-de-verdad/modulo_10.md).
