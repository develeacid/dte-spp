## Sprint 4: MIR y Validaciones

---

### S4-T1: (DEPRECADO) Migración y modelo para Programas Presupuestarios

**Tipo:** chore
**Estado:** Cerrado / Fusionado

**Descripcion:**
Se detectó una redundancia con el ticket `S3-T1`. Para evitar inconsistencias, todas las tareas de extensión del modelo `ProgramaPresupuestario` han sido consolidadas en `S3-T1`. Este ticket ya no debe ejecutarse.

---

### S4-T2: Migraciones y modelos para MIR

**Tipo:** feat
**Rama:** `feat/S4-T2-modelos-mir`
**Depende de:** S3-T1, S1-T7

**Descripcion:**
Crear la tabla `mir_niveles` que almacena la jerarquía de 4 niveles (Fin, Propósito, Componente, Actividad) y la tabla `mir_versiones` para snapshots.

**Decisiones técnicas:**

- Se utiliza una FK específica `componente_id` en lugar de un `parent_id` genérico para garantizar la integridad referencial (una Actividad solo puede pertenecer a un Componente).
- Se incluyen FKs `nullable` para la alineación con la cascada de planes y para la asignación de UR Coadyuvante.

**Campos mir_niveles:**

- `id`, `programa_presupuestario_id` (FK), `tipo_nivel` (enum), `componente_id` (FK recursiva, nullable), `resumen_narrativo`, `supuestos`, `arbol_nodo_id` (FK), `orden`
- FKs de alineación: `ped_objetivo_estrategico_id`, `programa_derivado_objetivo_id`, `ped_linea_accion_id`
- FK de UR Coadyuvante: `team_id` (nullable)

**Criterios de aceptacion:**

- [ ] Enum: fin, proposito, componente, actividad
- [ ] Migración `mir_niveles` con FK `componente_id` que apunta a `mir_niveles(id)`.
- [ ] Relación `Actividad belongsTo Componente` y `Componente hasMany Actividades` definidas en el modelo.
- [ ] Trazabilidad a `arbol_nodos` vía `arbol_nodo_id`.
- [ ] Tabla `mir_versiones` con campo `snapshot` de tipo JSONB.
- [ ] Actualizar documentacion del esquema

---

### S4-T3: Migraciones y modelos para Indicadores y Ficha Técnica

**Tipo:** feat
**Rama:** `feat/S4-T3-modelos-indicadores`

**Descripcion:**
Crear las tablas que componen la ficha técnica completa de un indicador: `indicadores`, `indicador_variables`, `catalogo_unidades_medida`, `medios_verificacion` y `cremaa_validaciones`.

**Criterios de aceptacion:**

- [ ] `indicadores` con todos los campos de ficha tecnica (nombre, formula_texto, tipo, dimension, frecuencia, sentido, linea_base, meta, rangos semaforo)
- [ ] Backed Enums para `tipo`, `dimension`, `frecuencia` y `sentido` definidos.
- [ ] `indicador_variables` con campo `simbolo` (string 5) para evaluacion matematica
- [ ] `catalogo_unidades_medida` como catalogo con clave y nombre (seeder CONAC)
- [ ] `medios_verificacion` vinculados a indicador
- [ ] `cremaa_validaciones` con 6 campos boolean + observacion por letra
- [ ] Relaciones Eloquent completas
- [ ] Actualizar documentacion del esquema

---

### S4-T11: Form Requests y UI dinámica condicional — Motor Poka-Yoke del Indicador

**Tipo:** feat
**Rama:** `feat/S4-T11-form-requests-condicional-indicador`

**Descripcion:**
Implementar el motor de reglas condicionales de la Sección 5.5 del plan. El formulario de la ficha técnica del indicador debe mostrar campos y opciones diferentes según el nivel (fila) de la MIR que se está editando. La validación se aplica tanto en el frontend (Livewire/Alpine.js) como en el backend (Laravel Form Requests).

**Reglas a implementar por nivel:**

| Campo                   | Fin                     | Proposito               | Componente                     | Actividad                      |
| ----------------------- | ----------------------- | ----------------------- | ------------------------------ | ------------------------------ |
| Tipo de indicador       | Estratégico (bloqueado) | Estratégico (bloqueado) | Editable (Estratégico/Gestión) | Gestión (bloqueado)            |
| Dimensiones disponibles | Solo Eficacia           | Eficacia, Eficiencia    | Eficacia, Eficiencia, Calidad  | Eficacia, Eficiencia, Economía |
| Frecuencias disponibles | Anual, Bianual, Sexenal | Semestral, Anual        | Trimestral, Semestral          | Mensual, Trimestral            |

**Criterios de aceptacion:**

- [ ] Livewire: el campo Tipo se renderiza como label de solo lectura en Fin/Propósito/Actividad, y como select en Componente.
- [ ] Alpine.js: el dropdown de Dimensión filtra dinámicamente sus opciones según la propiedad `nivel` del indicador.
- [ ] Alpine.js: el dropdown de Frecuencia muestra solo las opciones válidas según el nivel.
- [ ] Laravel Form Request `StoreIndicadorRequest`: regla condicional que valida `tipo` según `nivel` antes de persistir.
- [ ] Form Request valida que `dimension` y `frecuencia` estén en el set permitido para el `nivel` recibido.
- [ ] Tests de Form Request: intentar guardar "Economía" en nivel Fin retorna error de validación 422.
- [ ] UI no muestra mensajes de error cuando las opciones inválidas simplemente no aparecen en el dropdown.

---

### S4-T4: Interfaz de captura MIR 4x4

**Tipo:** feat
**Rama:** `feat/S4-T4-interfaz-mir-4x4`
**Depende de:** S4-T1, S4-T2, S4-T3, **S4-T11**

**Descripcion:**
Componente Livewire que renderiza la matriz 4x4. Columna 1 (Resumen Narrativo) viene prellenada desde la EAP. El usuario captura las columnas 2, 3 y 4. La UI aplica las reglas condicionales del motor Poka-Yoke (`S4-T11`).

**Decisiones técnicas:**

- Usar `wire:model.defer` para inputs de texto para mejorar el rendimiento.
- Implementar guardado por bloques (ej. "Guardar Indicadores") en lugar de un único formulario monolítico.

**Criterios de aceptacion:**

- [ ] Matriz visual 4 filas x 4 columnas.
- [ ] Col 1 prellenada y editable.
- [ ] Col 2: formulario de indicador integrado con las restricciones dinámicas de S4-T11.
- [ ] Col 3: formulario de medios de verificación.
- [ ] Col 4: textarea para supuestos.
- [ ] Múltiples componentes e indicadores por nivel (agregar/quitar).
- [ ] Campo de asignación de UR Coadyuvante visible en filas de Componente y Actividad (ver S4-T12).
- [ ] Guardado por bloques con `wire:model.defer`.
- [ ] Solo accesible con permiso `editar_mir`.

---

### S4-T5: Validacion sintactica SHCP del Resumen Narrativo

**Tipo:** feat
**Rama:** `feat/S4-T5-validacion-sintaxis-shcp`

**Descripcion:**
La IA audita que el Resumen Narrativo de cada nivel cumpla con la sintaxis obligatoria de la SHCP.

**Reglas:**

- Fin: "Contribuir a [Impacto] mediante [Solucion]"
- Proposito: "[Poblacion] + [Verbo presente/participio] + [Condicion]"
- Componente: "[Bien/Servicio] + [Participio -ado/-ido]"
- Actividad: "[Sustantivo deverbal] + [Complemento]"

**Criterios de aceptacion:**

- [ ] Al guardar/validar un nivel, la IA analiza la sintaxis
- [ ] Resultado: cumple / no cumple con explicacion especifica
- [ ] Sugerencia de reescritura que respeta la fórmula
- [ ] El usuario decide si acepta la sugerencia
- [ ] No bloquea el guardado (es advertencia, no error)
- [ ] El resultado de la validación se persiste con un timestamp.

---

### S4-T6: Validacion CREMAA desglosada

**Tipo:** feat
**Rama:** `feat/S4-T6-validacion-cremaa`

**Descripcion:**
Al crear o editar un indicador, la IA evalua los 6 criterios CREMAA individualmente y guarda el resultado en `cremaa_validaciones`.

**Criterios de aceptacion:**

- [ ] Boton "Validar CREMAA" en la ficha del indicador
- [ ] Resultado visual: 6 letras, cada una en verde (cumple) o rojo (no cumple)
- [ ] Cada letra tiene explicacion especifica del fallo
- [ ] Sugerencias de mejora por criterio.
- [ ] Resultado se persiste en la tabla cremaa_validaciones
- [ ] No bloquea guardado

---

### S4-T7: Validacion de logica vertical y horizontal

**Tipo:** feat
**Rama:** `feat/S4-T7-validacion-logica`

**Descripcion:**
Validacion automatica de la coherencia interna de la MIR.

**Logica vertical:**

- Actividades producen Componentes
- Componentes logran Proposito
- Proposito contribuye al Fin
- La IA analiza si la relacion causal es coherente

**Logica horizontal:**

- El indicador realmente mide el objetivo (Resumen Narrativo)
- El medio de verificacion puede proporcionar los datos del indicador
- La frecuencia del medio coincide con la frecuencia del indicador

**Criterios de aceptacion:**

- [ ] Boton "Validar MIR completa"
- [ ] Reporte de validacion con hallazgos por nivel
- [ ] Cada hallazgo clasificado: error critico / advertencia / sugerencia
- [ ] No bloquea el guardado pero se muestra prominentemente.

---

### S4-T8: Alineacion automatica MIR ↔ Matriz de Alineacion

**Tipo:** feat
**Rama:** `feat/S4-T8-alineacion-mir`

**Descripción:**
Al crear una MIR, el sistema sugiere la alineacion con la cascada de planes usando la Matriz de Alineacion y busqueda semantica.

**Flujo:**

1. Al capturar el Fin, el sistema busca por similitud los Objetivos Estrategicos del PED mas cercanos
2. Al seleccionar uno, hereda automaticamente: PND y ODS
3. Al capturar Componentes/Actividades, sugiere Lineas de Accion
4. Se llenan las FKs de alineacion en mir_niveles

**Criterios de aceptacion:**

- [ ] Muestra una lista de sugerencias de alineación con score de similitud.
- [ ] El usuario selecciona manualmente la alineación final. No hay auto-asignación.
- [ ] La herencia de la cadena de alineación se muestra claramente tras la selección.
- [ ] FKs de alineación guardadas en `mir_niveles`.
- [ ] Vista de cadena completa: Linea de Accion → ... → ODS

---

### S4-T9: Extraccion de variables de formulas

**Tipo:** feat
**Rama:** `feat/S4-T9-extraccion-variables`

**Descripción:**
Al capturar la formula de un indicador, la IA extrae las variables y crea registros en `indicador_variables` con nombre y simbolo.

**Ejemplo:**

- Formula: `(Alumnos inscritos / Egresados secundaria) x 100`
- Variables extraidas: A = "Alumnos inscritos", B = "Egresados secundaria"

**Criterios de aceptacion:**

- [ ] Al escribir la formula, boton "Extraer variables"
- [ ] IA identifica variables y asigna simbolos (A, B, C...)
- [ ] Se crean registros en `indicador_variables`.
- [ ] **Fallback manual:** Si la IA falla, el usuario puede agregar/editar variables manualmente.
- [ ] Usuario puede editar nombres, símbolos y clasificar comportamiento (acumulable/continua).
- [ ] Se selecciona unidad de medida del catálogo CONAC.

---

### S4-T10: Snapshots y versionado de MIR

**Tipo:** feat
**Rama:** `feat/S4-T10-snapshots-mir`

**Descripción:**
Implementar el sistema de snapshots que permite crear borradores alternos de la MIR sin destruir el trabajo actual.

**Criterios de aceptacion:**

- [ ] Boton "Crear snapshot" guarda estado completo en mir_versiones (JSONB)
- [ ] Listado de versiones con etiqueta y fecha
- [ ] Restaurar una version reemplaza la MIR actual (con confirmacion)
- [ ] Al regresar a etapas anteriores (arbol), advertencia de impacto en MIR
- [ ] Opcion: "Crear borrador alterno" vs "Sobreescribir actual"

---

### S4-T12: Asignacion de UR Coadyuvante por Componente/Actividad

**Tipo:** feat
**Rama:** `feat/S4-T12-asignacion-ur-coadyuvante`

**Descripcion:**
En la interfaz de la MIR (Columna 1 del nivel Componente y Actividad), el Planeador puede asignar una UR Coadyuvante responsable de ese nivel especifico. El sistema registra la relacion en `programa_team` y en `mir_niveles.team_id`, activando los permisos del middleware Multi-UR.

**Criterios de aceptacion:**

- [ ] Selector de UR (teams disponibles en el sistema) visible en filas de Componente y Actividad
- [ ] El campo es opcional; si no se asigna, la responsabilidad recae en la UR Coordinadora.
- [ ] Al asignar una UR: se actualiza `mir_niveles.team_id` y se crea/actualiza registro en `programa_team` con `rol = coadyuvante`.
- [ ] Al quitar la asignación: se elimina `mir_niveles.team_id` (null) y se verifica si la UR tiene otros componentes; si no, se elimina de `programa_team`.
- [ ] Solo el Planeador puede asignar/quitar UR Coadyuvante (permiso `editar_mir`).
- [ ] La UR Coadyuvante asignada aparece como etiqueta visible junto al Componente.
- [ ] Notificación opcional al operador de la UR Coadyuvante cuando se le asigna un componente.

---
