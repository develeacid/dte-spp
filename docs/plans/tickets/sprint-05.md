## Sprint 5: Importacion de Programas Existentes

---

### S5-T1: Importador Multi-formato y Diagnóstico

**Tipo:** feat
**Rama:** `feat/S5-T1-importador-multiformato`
**Depende de:** S4-T2, S4-T3

**Descripcion:**
Implementar un importador multi-formato (Markdown, CSV, Excel) que parsea programas existentes, los convierte a un DTO intermedio (`ImportedMirData`), y genera un diagnóstico de completitud que se persiste en la base de datos.

**Decisiones técnicas:**

- Se usará un DTO (`ImportedMirData`) como capa de abstracción entre los parsers y la lógica de negocio.
- El diagnóstico se persistirá en una nueva tabla `importacion_reportes` para auditoría y reanudación.
- Se utilizará el paquete `maatwebsite/laravel-excel` para los parsers de CSV y Excel.

**Criterios de aceptacion:**

- [ ] Migración y modelo para `importacion_reportes` creados.
- [ ] DTO `ImportedMirData` definido.
- [ ] Servicio `MirParserService` con métodos `fromMarkdown()` y `fromCsv()` que retornan el DTO.
- [ ] Componente Livewire que permite subir archivos (MD, CSV, XLSX).
- [ ] Al subir, se genera y persiste un `ImportacionReporte` con el diagnóstico de huecos (críticos y menores).
- [ ] La UI muestra una previsualización de la estructura importada y el reporte de diagnóstico.
- [ ] El usuario no puede proceder al siguiente paso si el archivo tiene errores de formato irrecuperables.

---

### S5-T2: Persistencia y Flujo de Completitud de Programas Importados

**Tipo:** feat
**Rama:** `feat/S5-T2-flujo-completitud`
**Depende de:** S5-T1, S3-T8

**Descripcion:**
Tras el diagnóstico, este ticket se encarga de persistir el programa en la base de datos y proveer una interfaz para que el usuario corrija los huecos detectados.

**Decisiones técnicas:**

- La persistencia se realiza dentro de una transacción (`DB::transaction`).
- La interfaz de completitud tendrá guardado automático por campo (`wire:model.lazy`) y una barra de progreso.
- La IA (`LlmService`) asistirá en la corrección de huecos, como la extracción de variables de fórmulas.

**Criterios de aceptacion:**

- [ ] Al confirmar la previsualización de S5-T1, los datos del DTO se persisten en las tablas `programas_presupuestarios`, `mir_niveles`, `indicadores`, etc.
- [ ] El programa se crea con `origen = importado`.
- [ ] Indicadores con huecos críticos se marcan como `activo_seguimiento = false`.
- [ ] Componente Livewire `CompletarHuecos` que muestra la lista de huecos pendientes (críticos primero).
- [ ] Al seleccionar un hueco, se muestra un formulario para corregirlo, con asistencia de IA si aplica (ej. "Extraer Variables").
- [ ] El guardado es automático al salir del campo.
- [ ] Una barra de progreso visual muestra el avance de la completitud.
- [ ] Al corregir todos los huecos críticos de un indicador, se actualiza a `activo_seguimiento = true`.

---

### S5-T3: Vinculación de Programas Importados con Cascada de Planes

**Tipo:** feat
**Rama:** `feat/S5-T3-vinculacion-importados`
**Depende de:** S5-T2, S2-T5, S2-T11

**Descripcion:**
Una vez que el programa importado está en la base de datos, este ticket se encarga de alinearlo con la cascada de planes (PED, PND, ODS) usando la Matriz de Alineación y el servicio de búsqueda semántica.

**Decisiones técnicas:**

- Se reutiliza la lógica de `S4-T8` pero aplicada a un programa ya existente.
- El umbral de similitud para sugerencias automáticas se establece más alto (ej. 0.85) para evitar falsos positivos.
- El usuario debe confirmar explícitamente cada vínculo sugerido.

**Criterios de aceptacion:**

- [ ] Interfaz que detecta si el programa importado carece de alineación.
- [ ] Para cada nivel de la MIR (Fin, Propósito, etc.), se usa `SemanticSearchService` para sugerir alineaciones con la cascada de planes.
- [ ] Las sugerencias se presentan con su score de similitud y el texto completo del objetivo para que el usuario juzgue.
- [ ] El usuario confirma cada vínculo, que se guarda en las FKs de la tabla `mir_niveles`.
- [ ] Si el programa importado ya tenía una alineación, el sistema la valida contra la Matriz y señala inconsistencias.

---

### S5-T4: Calendarización de Metas al Activar Programa

**Tipo:** feat
**Rama:** `feat/S5-T4-calendarizacion-metas`
**Depende de:** S5-T2, S6-T1

**Descripcion:**
Al activar un programa (nuevo o importado), el sistema genera los registros de `metas_periodo`, distribuyendo la meta anual según la frecuencia de cada indicador.

**Decisiones técnicas:**

- La lógica de distribución varía según el tipo de variable (`acumulable`, `continua`, `tasa`).
- Se presenta una tabla de calendarización editable para que el usuario ajuste la distribución antes de confirmar.

**Criterios de aceptacion:**

- [ ] Al cambiar el estado de un programa a `activo`, se dispara el proceso de calendarización.
- [ ] Se presenta una tabla editable con la distribución de metas por período.
- [ ] El sistema valida la distribución según el tipo de variable:
    - **Acumulable:** La suma de los períodos debe ser igual a la meta anual.
    - **Continua/Tasa:** No se valida la suma, cada período es independiente.
- [ ] Al confirmar, se crean los registros en la tabla `metas_periodo`.

---
