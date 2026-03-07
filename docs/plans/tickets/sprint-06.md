## Sprint 6: Seguimiento y Captura Periodica

---

### S6-T1: Migraciones para tablas de seguimiento

**Tipo:** feat
**Rama:** `feat/S6-T1-migraciones-seguimiento`

**Descripcion:**
Crear las tablas que soportan el ciclo de seguimiento: `metas_periodo`, `avances`, `avance_variables`, `avance_evidencias` y `desbloqueos`.

**Decisiones técnicas:**

- **`avances.historial_observaciones` (JSONB):** Almacenará un array de objetos con una estructura definida para auditoría: `[{id, fecha, usuario_id, usuario_nombre, rol, accion, estado_anterior, estado_nuevo, observacion, adjuntos:[]}]`.
- **`EstadoAvance` Enum:** Se amplía para incluir estados de ciclo de vida: `PENDIENTE_APERTURA`, `EN_CAPTURA`, `EN_REVISION`, `OBSERVADO`, `APROBADO`, `VENCIDO`.

**Criterios de aceptacion:**

- [ ] Todas las migraciones con `up()` y `down()`
- [ ] `avances.historial_observaciones` como JSONB
- [ ] `avance_evidencias.hash_archivo` para integridad SHA-256
- [ ] `desbloqueos` con relacion polimorfica
- [ ] Backed Enum `EstadoAvance` creado con todos los estados del ciclo de vida.
- [ ] Indices en campos de consulta frecuente
- [ ] `migrate:fresh --seed` sin errores
- [ ] Actualizar documentacion del esquema

---

### S6-T2: Calendario de captura y notificaciones

**Tipo:** feat
**Rama:** `feat/S6-T2-calendario-notificaciones`

**Descripcion:**
El sistema gestiona el ciclo de vida de los periodos de captura. Un comando schedulable detecta periodos por abrir, notifica a los operadores, y marca como vencidos los periodos que no se completaron a tiempo.

**Criterios de aceptacion:**

- [ ] Comando artisan schedulable que detecta periodos por abrir
- [ ] Notificacion a operadores de la UR cuando se abre un periodo
- [ ] Vista del operador: "Mis indicadores pendientes de captura"
- [ ] Vista del planeador: "Indicadores con captura vencida"
- [ ] Los periodos tienen una fecha de cierre explícita (ej. 15 días después de la apertura).
- [ ] Si un avance no está `APROBADO` al llegar la fecha de cierre, su estado cambia a `VENCIDO`.
- [ ] Se contempla un mecanismo (puede ser administrativo en v1) para extender periodos vencidos con justificación.

---

### S6-T3: Formulario de captura de avance

**Tipo:** feat
**Rama:** `feat/S6-T3-formulario-captura-avance`

**Descripcion:**
Componente Livewire para que el operador capture los valores de las variables de un indicador. El cálculo del resultado y del semáforo debe diferenciar entre variables acumulables y continuas.

**Decisiones técnicas:**

- El cálculo del semáforo debe respetar el `sentido` del indicador (ascendente/descendente).
- Para variables `acumulables`, el formulario mostrará el valor acumulado anterior y el cálculo se hará sobre el nuevo total.

**Flujo:**

1. Operador selecciona indicador y periodo
2. Sistema muestra campos por cada variable (con nombre y simbolo)
3. Al capturar valores, el sistema calcula el resultado aplicando la formula
4. Se muestra el semaforo sugerido
5. Si amarillo/rojo: formulario de justificacion obligatorio

**Criterios de aceptacion:**

- [ ] Campos dinamicos segun variables del indicador
- [ ] Calculo automatico usando MathExecutor con simbolos mapeados
- [ ] Semaforo calculado correctamente según rangos y `sentido` del indicador.
- [ ] El usuario puede ajustar el semaforo con justificacion
- [ ] La lógica de cálculo diferencia entre variables `acumulables` y `continuas`.
- [ ] Solo accesible con permiso `capturar_avance`
- [ ] Solo para indicadores con `activo_seguimiento = true`

---

### S6-T4: Generacion de justificaciones con IA

**Tipo:** feat
**Rama:** `feat/S6-T4-justificaciones-ia`

**Descripcion:**
Cuando un indicador cae en amarillo o rojo, la IA genera un borrador de justificacion que el usuario edita y aprueba.

**Fuentes de la IA (exclusivamente):**

- Supuestos del nivel correspondiente (Col. 4 de la MIR)
- Magnitud numerica de la desviacion respecto a la meta del periodo
- Historial del indicador (ejercicio anterior si existe)

**Criterios de aceptacion:**

- [ ] Borrador generado automaticamente al detectar amarillo/rojo
- [ ] El borrador cita explicitamente los supuestos de la MIR
- [ ] El usuario puede editar libremente antes de guardar
- [ ] El texto final se guarda en `avances.justificacion_final`
- [ ] Si no hay supuestos, la IA lo indica y permite al usuario escribir la justificación manualmente.
- [ ] El borrador de IA se guarda en `avances.justificacion_ia` (para auditoria)
- [ ] La IA nunca inventa contexto externo

---

### S6-T5: Adjuntar medios de verificacion (evidencia)

**Tipo:** feat
**Rama:** `feat/S6-T5-adjuntar-evidencia`

**Descripcion:**
Funcionalidad para que el operador adjunte archivos de evidencia por cada avance reportado.

**Criterios de aceptacion:**

- [ ] Upload de archivos (PDF, Excel, imagenes)
- [ ] Registro de: nombre, area generadora, fecha del documento
- [ ] Generacion automatica de hash SHA-256 al subir
- [ ] Validación: el medio adjuntado corresponde al registrado en la MIR (advertencia si difiere).
- [ ] Almacenamiento seguro (no accesible publicamente)

---

### S6-T6: Maquina de estados del reporte de avance

**Tipo:** feat
**Rama:** `feat/S6-T6-maquina-estados-avance`

**Descripcion:**
Implementar el flujo de estados: `En Captura` → `En Revision` → `Observado` → `Aprobado y Congelado`. Se deben definir límites de tiempo para cada estado.

**Decisiones técnicas:**

- Límites de tiempo (ej. 5 días para revisión) configurables. Al vencer, se escala notificación.
- El historial de observaciones (`historial_observaciones`) seguirá el schema JSON definido.

**Criterios de aceptacion:**

- [ ] Estado inicial: `en_captura` (editable por operador)
- [ ] Transicion a `en_revision`: operador "envia" el reporte. Se bloquea para edicion
- [ ] Transicion a `observado`: planeador rechaza con comentarios (se agregan al historial JSONB con schema definido).
- [ ] Transicion a `aprobado`: planeador aprueba. Se registra `congelado_at`
- [ ] Post-aprobacion: ni variables, ni justificacion, ni archivos son editables
- [ ] Notificacion al operador cuando su reporte es observado
- [ ] Notificacion al planeador cuando hay reportes en revision
- [ ] Solo planeador puede aprobar (`aprobar_avance`)
- [ ] Historial de observaciones visible como timeline

---

### S6-T7: Congelamiento y desbloqueo excepcional

**Tipo:** feat
**Rama:** `feat/S6-T7-congelamiento-desbloqueo`

**Descripcion:**
Implementar la inmutabilidad post-aprobacion y el flujo de desbloqueo excepcional con registro de auditoria.

**Criterios de aceptacion:**

- [ ] Avance aprobado: todos los campos inmutables
- [ ] Archivo adjunto congelado: no se puede reemplazar ni eliminar
- [ ] Flujo de desbloqueo: operador solicita → admin aprueba/rechaza
- [ ] Registro en tabla `desbloqueos` con motivo, solicitante, aprobador
- [ ] Al desbloquear, se crea entrada en el historial de observaciones
- [ ] Solo admin puede aprobar desbloqueos

---

### S6-T8: Vista de seguimiento para planeadores

**Tipo:** feat
**Rama:** `feat/S6-T8-vista-seguimiento-planeador`

**Descripcion:**
Panel consolidado donde el planeador ve todos los programas de su UR con sus indicadores, metas, avances y semáforos. La vista debe respetar el aislamiento Multi-UR.

**Criterios de aceptacion:**

- [ ] Tabla resumen: Nivel | Indicador | Meta periodo | Avance | Semaforo
- [ ] Expandible: al clic, muestra historial de capturas, variables, justificacion, evidencia
- [ ] Filtros: por programa, por estado de avance, por semaforo
- [ ] Vista comparativa: ejercicio actual vs anterior (side by side)
- [ ] La vista respeta el aislamiento Multi-UR (S1-T5), mostrando solo los programas y componentes/actividades a los que el planeador tiene acceso.
- [ ] Solo accesible con permiso `revisar_avance`

---
