# Manual de Usuario — DTE-SPP 2026

> Sistema de Planeación Programática — Dirección Técnica de Evaluación

## Introducción

El SPP 2026 es un sistema web para la gestión del ciclo completo de planeación programática: desde la definición de la cascada de alineación (PED → PND → ODS) hasta el seguimiento de indicadores con Matriz de Indicadores para Resultados (MIR) y evaluación de programas presupuestarios.

**URL de acceso:** La URL proporcionada por su administrador de sistemas.

---

## 1. Acceso al Sistema

### 1.1 Activación de Cuenta

Los usuarios no se registran directamente. Un Administrador crea la cuenta y envía un enlace de activación por correo.

1. Abrir el enlace recibido por correo electrónico
2. Establecer contraseña (mínimo 8 caracteres)
3. Configurar autenticación de dos factores (2FA) con una app como Google Authenticator
4. Al completar ambos pasos, la cuenta queda activa

> El enlace de activación expira después de un tiempo definido por el administrador.

### 1.2 Inicio de Sesión

1. Ir a la página de login (`/login`)
2. Ingresar correo electrónico y contraseña
3. Ingresar código 2FA de la aplicación autenticadora
4. Al autenticarse, se redirige al Dashboard

### 1.3 Perfil y Seguridad

Desde el menú de usuario (esquina superior derecha):
- **Perfil:** Cambiar nombre y foto de perfil
- **Contraseña:** Actualizar contraseña
- **2FA:** Regenerar códigos de recuperación
- **Sesiones:** Ver y cerrar sesiones activas en otros dispositivos

---

## 2. Roles del Sistema

| Rol | Descripción | Acceso Principal |
|-----|-------------|------------------|
| **Admin** | Administrador general con acceso total | Todos los módulos, gestión de usuarios, monitoreo IA, auditoría |
| **Planeador** | Responsable de planeación y revisión | Cascada, MML, revisión de avances, reportes |
| **Operador** | Capturista de avances | Captura de avances, evidencias, mis pendientes |

---

## 3. Dashboard

El dashboard se adapta automáticamente al rol del usuario:

### 3.1 Dashboard Admin

- **KPIs globales:** Programas evaluados, eficacia promedio, vencidos cross-team
- **KPIs del equipo:** Programas, indicadores activos, avance promedio, vencidos
- **Gráficas:** Semáforo global, avance por programa, tendencia de captura
- **Avances por revisar:** Lista de avances pendientes de revisión
- **Enlaces rápidos:** Monitoreo IA, Gestión de Usuarios, Auditoría

### 3.2 Dashboard Planeador

- **KPIs del equipo:** Programas, indicadores, avance promedio, vencidos
- **Gráficas:** Semáforo global, avance por programa, tendencia de captura
- **Avances por revisar:** Lista de avances pendientes

### 3.3 Dashboard Operador

- **Mis Pendientes:** Indicadores pendientes de captura
- **Capturados este mes:** Conteo de avances enviados
- **Semáforo personal:** Distribución de mis indicadores por semáforo
- **Enlaces rápidos:** Ir a capturar, ver pendientes

### 3.4 Notificaciones

- El ícono de campana en la barra superior muestra notificaciones no leídas
- Hacer clic abre un dropdown con las últimas 5
- "Ver todas" lleva a la página completa de notificaciones (`/notifications`)
- Se pueden marcar como leídas individualmente o todas a la vez

---

## 4. Módulo: Cascada de Planeación

**Ruta:** `/cascade/ped`
**Permiso requerido:** `gestionar_catalogos`

### 4.1 Plan Estatal de Desarrollo (PED)

La cascada sigue la jerarquía: **Plan → Eje → Tema → Objetivo Estratégico → Estrategia → Línea de Acción**

**Crear un Plan:**
1. Ir a Cascada → PED
2. Clic en "Crear Plan"
3. Llenar nombre, periodo de inicio y fin
4. Guardar

**Agregar nodos (Ejes, Temas, etc.):**
1. Seleccionar el plan activo en el árbol
2. Clic en "Agregar" junto al nivel correspondiente
3. Llenar el nombre y guardar
4. Los nodos se pueden reordenar

**Activar un Plan:**
Solo puede haber un plan activo a la vez. Al activar un plan, el anterior se desactiva automáticamente.

### 4.2 Importación de PED

1. Ir a Cascada → PED → Importar
2. Subir archivo Markdown con la estructura del PED
3. El sistema parsea y crea la jerarquía automáticamente

### 4.3 Matriz de Alineación

**Ruta:** `/cascade/alineacion`

Permite vincular:
- **PED ↔ PND:** Objetivos estratégicos del PED con objetivos del PND
- **PND ↔ ODS:** Objetivos del PND con metas de los ODS
- **Líneas de Acción ↔ Programas Derivados:** Líneas del PED con objetivos de programas derivados

**Crear una alineación:**
1. Ir a la pestaña correspondiente (PED-PND, PND-ODS, o Línea-Programa)
2. Buscar y seleccionar los elementos a vincular
3. Confirmar la alineación

**Ver cadena de alineación:**
Desde cualquier línea de acción, hacer clic en "Ver cadena" para visualizar la cascada completa desde ODS hasta línea de acción.

### 4.4 Programas Derivados

**Ruta:** `/cascade/programas-derivados`

Gestión de programas sectoriales, especiales, institucionales o regionales con sus objetivos.

---

## 5. Módulo: Marco Lógico (MML)

### 5.1 Programas Presupuestarios

**Ruta:** `/mml/programas`

**Crear programa:**
1. Ir a MML → Programas
2. Clic en "Nuevo Programa"
3. Llenar nombre, clave y datos básicos
4. El programa se crea en estado "Borrador"

**Importar programa:**
1. Ir a MML → Importar → Nuevo
2. Subir archivo Excel/CSV con la MIR
3. Completar datos faltantes (paso "Completar Huecos")
4. Vincular alineación con PED
5. Calendarizar metas por período

### 5.2 Etapas del MML

Cada programa sigue 7 etapas secuenciales. Las primeras 6 conforman la planeación (con un stepper visual de progreso) y la séptima es la construcción de la MIR:

| Etapa | Nombre | Descripción |
|-------|--------|-------------|
| 1 | Definición del Problema | Identificar el problema central |
| 2 | Árbol de Problemas | Construir causas y efectos |
| 3 | Árbol de Objetivos | Transformar problemas en objetivos |
| 4 | Selección de Alternativas | Elegir la mejor alternativa |
| 5 | Embudo de Poblaciones | Definir poblaciones de referencia, potencial y objetivo |
| 6 | Alineación Estratégica | Vincular con PED, ODS y anexos transversales |
| 7 | Matriz MIR | Construir la Matriz de Indicadores para Resultados |

### 5.3 Árbol de Problemas (Etapa 2)

1. Se parte del problema central definido en Etapa 1
2. Agregar causas directas e indirectas (abajo)
3. Agregar efectos directos e indirectos (arriba)
4. Cada nodo se puede editar, reordenar o eliminar

### 5.4 Árbol de Objetivos (Etapa 3)

1. Se genera automáticamente a partir del árbol de problema
2. Los problemas se convierten en medios y fines
3. Se puede editar el texto transformado

### 5.5 Embudo de Poblaciones (Etapa 5)

1. Definir la unidad de medida (ej. "Niños", "Familias", "MIPYMES")
2. Registrar la Población de Referencia (total, con fuente opcional)
3. Registrar la Población Potencial (afectada por el problema, con fuente opcional)
4. Registrar la Población Objetivo (que el programa atenderá, con justificación opcional)
5. El sistema valida que Objetivo <= Potencial <= Referencia

### 5.6 Alineación Estratégica (Etapa 6)

1. Seleccionar Eje, Tema y Objetivo Estratégico del PED (selects dependientes)
2. Opcionalmente vincular con ODS (Agenda 2030)
3. Marcar Anexos Transversales aplicables (Igualdad de Género, Discapacidad, Infancia, etc.)
4. Se puede usar la búsqueda semántica con IA para que el sistema sugiera el Objetivo Estratégico más afín al problema central del programa

Al completar las 6 etapas de planeación, el sistema permite finalizar la planeación y generar automáticamente la estructura de la MIR.

### 5.7 Matriz MIR (Etapa 7)

La MIR es una tabla con 4 niveles: **Fin → Propósito → Componente → Actividad**

Para cada nivel:
1. Definir el resumen narrativo
2. Agregar indicadores con:
   - Nombre, tipo (estratégico/gestión), dimensión
   - Frecuencia de medición, sentido (ascendente/descendente/regular)
   - Meta anual y fórmula de cálculo
   - Variables del indicador
   - Medios de verificación
3. Validar criterios CREMAA (Claro, Relevante, Económico, Monitoreable, Adecuado, Aportación Marginal)

**Tooltips:** Los encabezados y conceptos de la MIR tienen íconos de ayuda (?) que muestran definiciones del glosario MML al hacer clic.

---

## 6. Módulo: Seguimiento

### 6.1 Panel de Seguimiento

**Ruta:** `/seguimiento`
Vista general del estado de todos los indicadores con semáforos.

### 6.2 Captura de Avances (Operador)

**Ruta:** `/seguimiento/pendientes`

1. Ver lista de indicadores pendientes de captura
2. Seleccionar un indicador
3. Ingresar el resultado del período
4. Si el indicador tiene variables, capturar el valor de cada una
5. Agregar observaciones (opcional)
6. Enviar a revisión

**Semáforo automático:** Al capturar, el sistema calcula automáticamente el semáforo:
- **Verde:** Cumplimiento ≥ 85%
- **Amarillo:** Cumplimiento entre 70% y 84%
- **Rojo:** Cumplimiento < 70%

### 6.3 Evidencias

**Ruta:** `/seguimiento/avance/{id}/evidencias`

1. Desde la captura de avance, ir a "Evidencias"
2. Subir archivos de soporte (PDF, imágenes, etc.)
3. Llenar: nombre del documento, área generadora, fecha del documento
4. Los archivos se almacenan de forma segura y se pueden descargar

### 6.4 Flujo de Revisión (Planeador/Admin)

1. Ver avances en estado "En Revisión" desde el dashboard o panel de seguimiento
2. Revisar resultado, evidencias y observaciones
3. Opciones:
   - **Aprobar:** El avance queda como definitivo
   - **Observar:** Se devuelve al operador con comentarios
   - **Rechazar:** Se requiere nueva captura

### 6.5 Indicadores Vencidos

**Ruta:** `/seguimiento/vencidos`

Lista de indicadores cuya fecha de cierre pasó sin captura. Los administradores pueden:
- Ver el detalle del vencimiento
- Gestionar desbloqueos para permitir captura extemporánea

### 6.6 Desbloqueos

- **Operador:** Solicitar desbloqueo en `/seguimiento/desbloqueo/{avance}` indicando motivo
- **Admin/Planeador:** Gestionar solicitudes en `/seguimiento/desbloqueos` (aprobar/rechazar)

### 6.7 Sábana de Captura

**Ruta:** `/seguimiento/sabana`
**Permiso:** `ver_sabana_captura`

Vista tabular completa de todas las metas por periodo con sus avances. Permite filtrar por programa, trimestre y estado de captura. Los administradores ven todos los programas; los planeadores ven solo los de su equipo.

### 6.8 Concentrado de Captura

**Ruta:** `/seguimiento/concentrado`
**Permiso:** `ver_concentrado_captura`

Resumen consolidado de avances capturados en un rango de fechas. Permite filtrar por fecha de inicio y fin. Útil para generar reportes de actividad de captura por periodo.

---

## 7. Módulo: Evaluación

### 7.1 Evaluación por Programa

**Ruta:** `/evaluacion/programa/{id}`

Muestra el índice de eficacia calculado del programa con ponderaciones por nivel MIR:
- Fin: 40% (configurable)
- Propósito: 30%
- Componente: 20%
- Actividad: 10%

### 7.2 Panel Transversal

**Ruta:** `/evaluacion/transversal`
**Permiso:** `exportar_reportes`

Vista comparativa de todos los programas con sus índices de eficacia y anexos transversales.

### 7.3 Exportación de Reportes

**Formatos disponibles:**
- **PDF:** Reporte individual o consolidado
- **Excel:** Datos tabulares para análisis
- **Exportación asíncrona:** Para reportes grandes, se genera en segundo plano y se notifica cuando está listo

**Datos Abiertos:**
- CSV y JSON del ejercicio fiscal completo
- Diccionario de datos en formato legible
- Paquete ZIP con todos los formatos

---

## 8. Administración

### 8.1 Gestión de Usuarios

**Ruta:** `/admin/usuarios`
**Permiso:** `invitar_usuarios`

1. Crear usuario: nombre, correo, rol
2. Se envía invitación automática por correo
3. Ver estado de activación de cada usuario
4. Activar/desactivar cuentas

### 8.2 Monitoreo IA

**Ruta:** `/admin/monitoreo-ia`
**Permiso:** `administrar_usuarios`

Monitorear consumo de la API de IA (OpenAI) para generación de embeddings y asistencia:
- Consumo por mes y equipo
- Presupuestos y alertas
- Logs detallados de llamadas

### 8.3 Auditoría

**Ruta:** `/admin/auditoria`
**Permiso:** `administrar_usuarios`

Registro cronológico de todas las acciones en el sistema:
- Filtrar por usuario, modelo afectado, tipo de evento
- Ver detalle de cambios (valores anteriores y nuevos)
- Modelos auditados: User, PED (Plan, Eje, Tema, Objetivo), MIR (Nivel, Indicador), Evaluación

---

## 9. Accesibilidad

- **Navegación por teclado:** El sistema incluye un enlace "Saltar al contenido" al inicio
- **ARIA:** Modales, menús y controles tienen etiquetas accesibles
- **Focus trapping:** Los modales atrapan el foco para evitar navegación accidental fuera del diálogo
- **Tooltips:** Activables por clic y por teclado

---

## 10. Preguntas Frecuentes

**¿Cómo recupero mi acceso si pierdo el dispositivo 2FA?**
Usa uno de los códigos de recuperación proporcionados al configurar 2FA. Si no los tienes, contacta al administrador.

**¿Puedo capturar un avance después de la fecha de cierre?**
Solo si un administrador aprueba una solicitud de desbloqueo.

**¿Cómo cambio de equipo (Unidad Responsable)?**
Desde el selector de equipo en la barra superior (si perteneces a varios equipos).

**¿Los reportes exportados se eliminan automáticamente?**
Sí, los archivos generados se eliminan después de 24 horas por seguridad.
