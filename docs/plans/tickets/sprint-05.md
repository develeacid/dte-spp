## Sprint 5: Importación de Programas Existentes

**Objetivo:** Permitir la importación de programas presupuestarios existentes (de ejercicios anteriores o de otras dependencias) al sistema, con diagnóstico de completitud, corrección asistida por IA, vinculación con la cascada de planes, y calendarización de metas para activar el seguimiento.

**Baseline técnico al iniciar Sprint 5:**
- 248 tests passing, 7 skipped
- Modelos MIR completos: `MirNivel`, `Indicador` (con rangos semáforo, 6 enums), `IndicadorVariable`, `MedioVerificacion`, `CremaaValidacion`
- `ProgramaPresupuestario` con enums `OrigenPrograma` (nuevo/importado) y `EstadoPrograma` (borrador/activo/cerrado)
- Servicios disponibles: `LlmService` (suggest/validate), `SemanticSearchService` (findSimilar), `MirSnapshotService`, `MirPrellenadoService`, `IndicadorReglasService`
- `indicadores` NO tiene campo `activo_seguimiento` (requiere migración en este sprint)
- `MirEditor` (479 líneas, 20 métodos): CRUD niveles/indicadores/medios/variables, validaciones IA, alineación, snapshots, UR Coadyuvante

**Dependencias externas:**
- `maatwebsite/laravel-excel` ^3.1 (instalar en S5-T1)
- Sprint 6 define tabla `metas_periodo` (S5-T4 depende de ella o la crea anticipadamente)

---

### S5-T1: Importador Multi-formato y Diagnóstico

**Tipo:** feat
**Rama:** `feat/S5-T1-importador-multiformato`
**Depende de:** S4-T2 (MirNivel), S4-T3 (Indicador + variables + medios)

**Descripción:**
Importador multi-formato (Markdown, CSV, Excel) que parsea programas existentes, los convierte a un DTO intermedio (`ImportedMirData`), y genera un diagnóstico de completitud persistido en base de datos.

**Decisiones técnicas:**

- DTO `ImportedMirData` como capa de abstracción entre parsers y lógica de negocio. Estructura:
  ```
  ImportedMirData {
    nombre, clave, ejercicio_fiscal,
    niveles: [{ tipo_nivel, resumen_narrativo, supuestos, orden, componente_idx?,
      indicadores: [{ nombre, formula_texto?, tipo?, dimension?, frecuencia?,
        sentido?, linea_base?, meta?, rangos_semaforo?,
        variables: [{ simbolo, nombre }],
        medios: [{ nombre, fuente? }]
      }]
    }]
  }
  ```
- `ImportacionReporte` persiste en nueva tabla `importacion_reportes` con campos: `programa_presupuestario_id` (nullable, se llena en S5-T2), `archivo_original` (nombre), `formato` (md/csv/xlsx), `datos_parseados` (JSONB — el DTO serializado), `diagnostico` (JSONB — array de huecos), `estado` (pendiente/procesado/descartado), `created_by`, timestamps.
- Diagnóstico clasifica huecos como:
  - **Crítico:** campo obligatorio faltante que impide seguimiento (fórmula, tipo, dimensión, frecuencia, al menos 1 medio de verificación)
  - **Menor:** campo recomendado faltante (sentido, línea base, meta, rangos semáforo, supuestos)
  - **Advertencia:** valor no reconocido que requiere mapeo manual (ej. tipo indicador con texto libre que no matchea enum)
- `MirParserService` con métodos `fromMarkdown(string $content)`, `fromCsv(string $path)`, `fromExcel(string $path)` que retornan `ImportedMirData`.
- Paquete `maatwebsite/laravel-excel` para CSV y XLSX.
- Template CSV/XLSX descargable para estandarizar el formato de importación.

**Criterios de aceptación:**

- [ ] Migración y modelo `ImportacionReporte` creados con campos JSONB para datos y diagnóstico.
- [ ] DTO `ImportedMirData` definido en `app/DTOs/ImportedMirData.php` con método `toArray()` y validación interna.
- [ ] `MirParserService::fromMarkdown()` parsea formato estándar MIR (tabla 4×4 en Markdown).
- [ ] `MirParserService::fromCsv()` y `fromExcel()` parsean archivos tabulares con columnas: Nivel, Resumen Narrativo, Indicador, Fórmula, Tipo, Dimensión, Frecuencia, Medio Verificación, Supuestos.
- [ ] `MirDiagnosticoService::diagnosticar(ImportedMirData $data): array` genera lista de huecos clasificados.
- [ ] Componente Livewire `ImportarPrograma` con upload de archivos (MD, CSV, XLSX, max 5MB).
- [ ] Al subir, se muestra previsualización de la estructura importada (tabla resumen por nivel) y reporte de diagnóstico con código de colores (rojo=crítico, amarillo=menor, azul=advertencia).
- [ ] Conteo de huecos: "X críticos, Y menores, Z advertencias".
- [ ] Botón "Continuar" habilitado solo si no hay errores de formato irrecuperables (archivo corrupto, estructura no reconocida).
- [ ] Botón "Descargar plantilla" para CSV/XLSX vacíos.
- [ ] Tests: parsers para cada formato, diagnóstico con huecos mixtos, rechazo de archivo corrupto.

---

### S5-T2: Persistencia y Flujo de Completitud de Programas Importados

**Tipo:** feat
**Rama:** `feat/S5-T2-flujo-completitud`
**Depende de:** S5-T1, S3-T8 (LlmService), S4-T9 (extraerVariables)

**Descripción:**
Tras el diagnóstico, persiste el programa importado en las tablas del sistema y provee una interfaz para que el usuario corrija los huecos detectados, con asistencia de IA.

**Decisiones técnicas:**

- Migración: agregar campo `activo_seguimiento` (boolean, default true) a tabla `indicadores`. Los indicadores importados con huecos críticos se crean con `activo_seguimiento = false`.
- La persistencia se realiza dentro de `DB::transaction`. Se crea:
  1. `ProgramaPresupuestario` con `origen = importado`, `estado = borrador`
  2. `MirNivel` por cada nivel del DTO, con mapeo de `componente_id` para actividades (misma lógica que `MirSnapshotService::deserializarMir()`)
  3. `Indicador` con campos disponibles; valores de tipo/dimension/frecuencia mapeados a enums via `tryFrom()` o marcados como hueco
  4. `IndicadorVariable` y `MedioVerificacion` si existen en el DTO
- `ImportacionReporte.programa_presupuestario_id` se actualiza con el ID del programa creado.
- Componente `CompletarHuecos` muestra huecos agrupados por indicador, ordenados: críticos primero.
- Reutilizar `extraerVariables()` de `MirEditor` para autocompletar variables faltantes.
- Reutilizar `IndicadorReglasService` para validar que los valores corregidos cumplan Poka-Yoke.
- Barra de progreso: `(total_huecos - huecos_pendientes) / total_huecos * 100`.

**Criterios de aceptación:**

- [ ] Migración `add_activo_seguimiento_to_indicadores` con boolean default true.
- [ ] Campo `activo_seguimiento` en fillable y casts de modelo `Indicador`.
- [ ] `MirPersistenciaService::persistir(ImportedMirData $data, int $teamId, int $userId): ProgramaPresupuestario` crea el programa completo en transacción.
- [ ] Mapeo de valores texto a enums: tipo ("Estratégico" → `TipoIndicador::ESTRATEGICO`), dimensión, frecuencia. Valores no reconocidos se guardan en diagnóstico como advertencia.
- [ ] Indicadores con huecos críticos: `activo_seguimiento = false`.
- [ ] Componente `CompletarHuecos` con lista de huecos, agrupados por nivel → indicador.
- [ ] Cada hueco muestra: campo faltante, contexto (nombre indicador/nivel), formulario de corrección inline.
- [ ] Botón "Extraer Variables" disponible para indicadores sin variables pero con fórmula (reutiliza `LlmService`).
- [ ] Guardado automático por campo (`wire:change`).
- [ ] Barra de progreso visual con porcentaje.
- [ ] Al corregir todos los huecos críticos de un indicador: `activo_seguimiento` se actualiza a `true`.
- [ ] Al completar todos los huecos críticos: botón "Finalizar importación" cambia estado del reporte a `procesado`.
- [ ] Tests: persistencia completa, mapeo enums, huecos críticos desactivan seguimiento, corrección reactiva, progreso.

---

### S5-T3: Vinculación de Programas Importados con Cascada de Planes

**Tipo:** feat
**Rama:** `feat/S5-T3-vinculacion-importados`
**Depende de:** S5-T2, S4-T8 (buscarAlineacion/seleccionarAlineacion), S2-T11 (SemanticSearchService)

**Descripción:**
Alinea el programa importado con la cascada de planes (PED → PND → ODS) reutilizando la lógica de S4-T8 en una interfaz dedicada paso-a-paso.

**Decisiones técnicas:**

- Reutilizar `SemanticSearchService::findSimilar()` con los mismos modelos target que `MirEditor::buscarAlineacion()`:
  - Fin/Propósito → `PedObjetivoEstrategico` (hereda PND y ODS)
  - Componente/Actividad → `PedLineaAccion` (hereda Estrategia → Obj. Estratégico)
- Umbral de similitud configurable por contexto: default 0.7 para sugerencias, highlight >= 0.85 como "Alta confianza".
- Interfaz paso-a-paso (wizard): un nivel a la vez, empezando por Fin (el más importante para la herencia).
- Al seleccionar alineación del Fin, mostrar cadena heredada completa: Obj. Estratégico → Tema → Eje → PND → ODS.
- Las FKs ya existen en `mir_niveles`: `ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`.
- Si el programa importado ya contenía texto de alineación (ej. "PED 1.2.3"), se usa como contexto adicional para la búsqueda.

**Criterios de aceptación:**

- [ ] Componente `VincularAlineacion` que detecta niveles sin alineación y ofrece wizard.
- [ ] Para cada nivel, búsqueda semántica con `findSimilar()` usando resumen narrativo como query.
- [ ] Sugerencias mostradas con: score %, texto completo del objetivo, etiqueta "Alta confianza" si score >= 0.85.
- [ ] Al seleccionar: se guardan FKs en `mir_niveles` via `seleccionarAlineacion()`.
- [ ] Cadena heredada visible tras selección: Línea de Acción → Estrategia → Obj. Estratégico → Tema → Eje + PND + ODS.
- [ ] Si ya hay alineación, validar coherencia: la alineación del Propósito debe estar en la misma rama que la del Fin.
- [ ] Botón "Omitir" por nivel (la alineación no es bloqueante, pero queda marcada como pendiente).
- [ ] Resumen final: tabla con todos los niveles y su estado de alineación (vinculado/pendiente).
- [ ] Tests: búsqueda semántica mock, persistencia de FKs, validación coherencia intra-nivel, wizard completo.

---

### S5-T4: Calendarización de Metas al Activar Programa

**Tipo:** feat
**Rama:** `feat/S5-T4-calendarizacion-metas`
**Depende de:** S5-T2 (activo_seguimiento), S6-T1 (tabla metas_periodo) **← dependencia cruzada**

**Descripción:**
Al activar un programa (cambiar estado borrador → activo), el sistema genera los registros de `metas_periodo`, distribuyendo la meta anual de cada indicador activo según su frecuencia de medición.

**Decisiones técnicas:**

- **Dependencia cruzada con S6-T1:** Este ticket necesita la tabla `metas_periodo` que se define en Sprint 6. Opciones:
  1. **(Recomendado)** Crear la migración `metas_periodo` anticipadamente en este ticket con los campos mínimos necesarios, y Sprint 6 la extiende si necesita campos adicionales.
  2. Reordenar para ejecutar S6-T1 primero.
- Tabla `metas_periodo` (campos mínimos): `id`, `indicador_id` (FK), `periodo` (int: 1..12 para mensual, 1..4 para trimestral, etc.), `meta_periodo` (decimal), `ejercicio_fiscal` (int), `activo` (boolean), timestamps. Unique: `[indicador_id, periodo, ejercicio_fiscal]`.
- Distribución según frecuencia (`FrecuenciaMedicion` enum ya existe):
  - **MENSUAL**: 12 períodos, meta / 12
  - **TRIMESTRAL**: 4 períodos, meta / 4
  - **SEMESTRAL**: 2 períodos, meta / 2
  - **ANUAL**: 1 período, meta completa
  - **BIANUAL/SEXENAL**: 1 período, meta completa (seguimiento en el año correspondiente)
- Distribución ajustable: el usuario ve tabla editable antes de confirmar.
- Validación según comportamiento de variables del indicador:
  - **Acumulable** (variables con `comportamiento = 'acumulable'`): suma de períodos = meta anual.
  - **Continua/Tasa**: cada período es independiente, no se valida suma.
- Solo indicadores con `activo_seguimiento = true` y `meta IS NOT NULL` generan metas.
- `CalendarizacionService::generar(ProgramaPresupuestario $programa)` crea registros propuestos.
- `CalendarizacionService::confirmar(ProgramaPresupuestario $programa, array $metasAjustadas)` persiste.

**Criterios de aceptación:**

- [ ] Migración `create_metas_periodo_table` con constraint unique `[indicador_id, periodo, ejercicio_fiscal]`.
- [ ] Modelo `MetaPeriodo` con relación a `Indicador`.
- [ ] `CalendarizacionService::generar()` genera distribución propuesta por frecuencia.
- [ ] Componente `CalendarizarMetas` con tabla editable: columnas = períodos, filas = indicadores.
- [ ] Celdas editables con `wire:change` para ajustar valores.
- [ ] Validación en tiempo real: para indicadores acumulables, si suma ≠ meta anual → warning visual.
- [ ] Al cambiar estado a `activo`, se redirige a la interfaz de calendarización si no hay metas creadas.
- [ ] Solo indicadores con `activo_seguimiento = true` y `meta` definida aparecen.
- [ ] Indicadores sin `meta` se muestran como fila gris con "Meta no definida — defina la meta en la MIR".
- [ ] Botón "Confirmar calendarización" persiste registros y actualiza `EstadoPrograma` a ACTIVO.
- [ ] Tests: distribución por frecuencia, validación acumulables, solo activos, confirmar persiste.

---

### S5-T5: Ruta y dashboard de importación

**Tipo:** feat
**Rama:** `feat/S5-T5-ruta-dashboard-importacion`
**Depende de:** S5-T1, S5-T2, S5-T3

**Descripción:**
Flujo de navegación completo para la importación: ruta `/importar`, dashboard que muestra importaciones en curso y completadas, y navegación entre los pasos del wizard (importar → completar → vincular → calendarizar).

**Decisiones técnicas:**

- Ruta en `routes/web/mml.php`: `/programas/{programa}/importar`
- Dashboard en la pantalla principal de programas: sección "Importaciones recientes" con estado.
- Stepper visual: 4 pasos con estado (completado/activo/pendiente).

**Criterios de aceptación:**

- [ ] Rutas registradas con middleware de permisos (`importar_programa`).
- [ ] Componente stepper que refleja progreso: Subir archivo → Completar huecos → Vincular planes → Calendarizar.
- [ ] Dashboard de importaciones: tabla con programa, fecha, estado (pendiente/procesado/descartado), enlace a retomar.
- [ ] Navegación forward/back entre pasos con persistencia de estado.
- [ ] Permiso `importar_programa` agregado al sistema de roles.
- [ ] Tests: acceso con/sin permiso, navegación entre pasos.

---

## Orden de ejecución recomendado

```
S5-T1 → S5-T2 → S5-T3 → S5-T4 → S5-T5
```

Todos son secuenciales excepto S5-T3 y S5-T4 que podrían paralelizarse (S5-T3 no necesita `metas_periodo`).

## Notas de integración con Sprint 4

| Recurso de Sprint 4 | Reutilizado en Sprint 5 |
|---|---|
| `MirSnapshotService::deserializarMir()` | Lógica de mapeo componente_id en S5-T2 |
| `MirEditor::extraerVariables()` | Autocompletado de variables en S5-T2 |
| `MirEditor::buscarAlineacion()` + `seleccionarAlineacion()` | Wizard de vinculación en S5-T3 |
| `IndicadorReglasService` | Validación Poka-Yoke en corrección de huecos S5-T2 |
| `SemanticSearchService::findSimilar()` | Búsqueda de alineación en S5-T3 |
| `FrecuenciaMedicion` enum | Cálculo de períodos en S5-T4 |
| `MirNivel` FKs de alineación | Persistencia de vínculos en S5-T3 |
| `IndicadorVariable.comportamiento` | Validación acumulable/continua en S5-T4 |
