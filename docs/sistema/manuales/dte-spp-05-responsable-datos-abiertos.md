# Manual de Usuario — Responsable de Datos Abiertos (RDA)

**Sistema:** dte-spp (PbR-SED — Planeación de Programas Presupuestales)
**Rol:** `responsable_datos_abiertos`
**Módulo de trabajo:** Transparencia → Datos Abiertos
**Alcance:** Gobierno del flujo de publicación de datasets abiertos: aprobar, publicar, retirar, rechazar y editar plantillas del catálogo. Bajo **segregación de funciones** (el administrador del sistema NO puede ejecutar estas acciones).

---

## 1. Quién es este rol y qué permisos tiene

El **Responsable de Datos Abiertos (RDA)** es la figura de control final del pipeline de transparencia. Mientras que el planeador y el operador pueden preparar borradores de datasets, **solo el RDA autoriza que un dato salga al portal público** y solo el RDA puede modificar las plantillas del catálogo institucional.

### Permisos reales (del seeder)

Confirmados en `database/seeders/TransparenciaPermissionsSeeder.php`. El rol `responsable_datos_abiertos` recibe **exactamente** estos tres permisos:

| Permiso | Qué habilita |
|---|---|
| `ver_datasets_abiertos` | Entrar al índice y al detalle de cualquier dataset (plantilla o entrega). |
| `gestionar_dataset_abierto` | Crear borradores, crear entregas (clonar plantilla por periodo), editar borradores, enviar a revisión. |
| `aprobar_datos_abiertos` | **Permiso exclusivo del RDA.** Aprobar, publicar, rechazar, retirar y editar plantillas del catálogo. |

### Punto clave de segregación de funciones

`aprobar_datos_abiertos` está en la lista `$permisosSegregados` de `RolesAndPermissionsSeeder.php`. Esto significa que:

- El **admin NO** lo recibe automáticamente (a diferencia de otros dominios donde admin tiene todo).
- El **planeador** tiene `ver_datasets_abiertos` + `gestionar_dataset_abierto`, pero **NO** `aprobar_datos_abiertos`.
- El **operador** no tiene ningún permiso de datasets.
- Solo **tú (RDA)** cierras el ciclo de publicación.

> No inventes permisos: si una acción no está respaldada por uno de los tres permisos de arriba, no la verás o recibirás un 403/flash de error.

---

## 2. A qué entras al iniciar sesión y cómo navegas

Al iniciar sesión aterrizas en el **dashboard general** de dte-spp (no hay landing dedicada al RDA). Tu trabajo vive en un único grupo del menú lateral.

### Sidebar — grupo "Transparencia"

Definido en `resources/views/components/layout/sidebar-nav.blade.php` (gateado por `@can('ver_datasets_abiertos')`):

- **Grupo:** `Transparencia` (ícono de globo).
- **Entrada:** `Datos Abiertos` → ruta `transparencia.datos-abiertos.index` → URL `/transparencia/datos-abiertos`.

Esa entrada es tu pantalla base. Todo lo demás se navega desde ahí.

> Existe también una entrada "Datos Abiertos" dentro del grupo **Evaluación** (`evaluation.datos-abiertos.diccionario`): es OTRA cosa (exportaciones CSV/JSON/ZIP de evaluaciones, gateada por `exportar_reportes`), no la confundas con tu módulo. Tu módulo es el del grupo **Transparencia**.

---

## 3. Tareas principales paso a paso

El ciclo de vida de un dataset es:

```
borrador → revision → aprobado → publicado → (retirado)
                ↘ (rechazar) → borrador
```

Cada dataset es una **plantilla** (campo `periodo = null`, del catálogo DS-01..DS-G04) o una **entrega** (clon de una plantilla para un periodo concreto, p. ej. `2026-Q1`). Las plantillas se editan; las entregas son las que recorren el flujo y se publican.

---

### Flujo A — Encontrar y revisar un dataset

**Objetivo:** ubicar el dataset, ver su estado, metadatos y bitácora antes de decidir.

1. **Índice de datasets**
   - URL: `/transparencia/datos-abiertos` · Ruta: `transparencia.datos-abiertos.index`
   - Componente: `App\Livewire\Transparencia\Datasets\Index` (vista `livewire/transparencia/datasets/index.blade.php`)
   - Acciones: buscar por texto; filtrar por **status**, **sistema** y **tipo**; ver KPIs agregados (Total, Publicados, En revisión, Borradores).
   - Resultado: tabla de datasets; clic en uno te lleva al detalle.

2. **Detalle del dataset**
   - URL: `/transparencia/datos-abiertos/{dataset}` · Ruta: `transparencia.datos-abiertos.show`
   - Componente: `App\Livewire\Transparencia\Datasets\Show` (vista `livewire/transparencia/datasets/show.blade.php`)
   - Qué ves: metadatos, **metadata DCAT**, estado actual y la **bitácora de actividad** (últimos 20 eventos con autor y fecha, vía `Activity` log).
   - Desde aquí ejecutas todas las acciones del flujo (los botones aparecen según el estado y tu permiso).

---

### Flujo B — Aprobar y publicar (el núcleo del rol)

**Objetivo:** llevar una entrega desde revisión hasta el portal público.

Todas las acciones se ejecutan desde el **detalle del dataset** (`Show`). Cada una pide confirmación con `wire:confirm`.

1. **Aprobar** (`revision → aprobado`)
   - Botón **Aprobar** (visible solo si el dataset está en `revision`).
   - Método: `aprobar()` → `authorize('aprobar')` → `DatasetAbierto::aprobar(auth()->user())`.
   - Registra quién aprobó (`aprobado_por`). Resultado: flash "Dataset aprobado.".

2. **Publicar** (`aprobado → publicado`)
   - Botón **Publicar** (visible solo si el dataset está en `aprobado`).
   - Método: `publicar()` → `DatasetAbierto::publicar()`.
   - **Efecto cross-sistema:** publicar **dispara el pipeline N2-03**. Se encola `SyncPublicDatasetJob`, que ejecuta el Publisher correspondiente a `dataset_clave` y escribe la tabla `pub_*` de la BD pública `spp_public` (DELETE+INSERT transaccional con hash sha256). A partir de ahí el dato es visible en el **portal público** `/transparencia` (sin autenticación).
   - Resultado: flash "Dataset publicado." + fila en `transparencia_publicaciones` (auditoría).

3. **Retirar** (`publicado → retirado`)
   - Botón **Retirar** (visible solo si el dataset está en `publicado`).
   - Requiere capturar **motivo** (textarea, `required|min:5|max:500`).
   - Método: `retirar()` → `DatasetAbierto::retirar($motivo)`.
   - **Efecto cross-sistema:** dispara el pipeline de sync para **remover** el dato del portal público.
   - Resultado: flash "Dataset retirado de publicación.".

4. **Rechazar** (`revision → borrador`)
   - Botón **Rechazar** (visible solo si el dataset está en `revision`).
   - Requiere capturar **motivo** (textarea, `required|min:5|max:500`).
   - Método: `rechazar()` → `DatasetAbierto::rechazar($motivo)`.
   - Devuelve la entrega a `borrador` para que el editor la corrija. Resultado: flash "Dataset rechazado y devuelto a borrador.".

> Botón **Enviar a revisión** (`enviarARevision`, `borrador → revision`): también lo puedes ejecutar (tu permiso `aprobar_datos_abiertos` te lo permite sobre cualquier borrador). Normalmente lo dispara el editor que preparó el borrador; tú lo usas si tú mismo preparaste la entrega.

---

### Flujo C — Editar plantillas del catálogo (exclusivo RDA)

**Objetivo:** ajustar nombre / descripción / metadata DCAT de una plantilla del catálogo (DS-01..DS-G04). Solo el RDA puede hacerlo.

1. Desde el **detalle** de una **plantilla** (un dataset con `periodo = null`), botón **Editar plantilla**.
   - URL: `/transparencia/datos-abiertos/{dataset}/editar-plantilla` · Ruta: `transparencia.datos-abiertos.editar-plantilla`
   - Componente: `App\Livewire\Transparencia\Datasets\EditarPlantilla`
   - Permiso/gate: `editarPlantilla` de la Policy → requiere `periodo === null` **y** `aprobar_datos_abiertos`.
   - Campos: **Nombre** (máx 255), **Descripción** (máx 5000), **DCAT Metadata** (JSON).
   - Acciones: **Guardar plantilla** / **Cancelar**.
   - Resultado: la plantilla actualizada se usará como base para las próximas entregas.

> Si intentas "Editar plantilla" sobre una **entrega** (con periodo), no aplica: las entregas se editan solo en estado `borrador` con la acción "Editar borrador".

---

### Flujo D — Crear entregas y editar borradores (compartido con planeador)

**Objetivo:** generar la entrega de un periodo y prepararla para revisión. Estas acciones las puedes hacer tú o el planeador (`gestionar_dataset_abierto`).

1. **Crear entrega** (clonar plantilla por periodo)
   - Desde el detalle de una **plantilla** (`periodo = null`), botón **Crear entrega**.
   - URL: `/transparencia/datos-abiertos/{dataset}/crear-entrega` · Ruta: `transparencia.datos-abiertos.crear-entrega`
   - Componente: `App\Livewire\Transparencia\Datasets\CrearEntrega`
   - Campo: **Periodo** — formato `YYYY` o `YYYY-Q1..Q4` (regex validado).
   - Resultado: nuevo dataset con `status=borrador`, metadatos heredados de la plantilla.

2. **Editar borrador**
   - URL: `/transparencia/datos-abiertos/{dataset}/editar` · Ruta: `transparencia.datos-abiertos.editar`
   - Componente: `App\Livewire\Transparencia\Datasets\Edit`
   - Gate (`update` de la Policy): el dataset debe estar en `borrador`; el RDA puede editar cualquier borrador, el planeador solo los que él creó.
   - Campos: Nombre (255), Descripción (5000), DCAT Metadata (JSON).

---

### Recuperación / operación (opcional, fuera de la UI)

Si una publicación quedó desincronizada del portal (p. ej. geobase caído al publicar un dataset territorial DS-G0x), un operador con acceso a consola puede re-disparar el sync:

```bash
sail artisan transparencia:sync-public DS-01
```

El RDA no necesita esto en el flujo normal: cada `publicar()`/`retirar()` ya dispara el job automáticamente.

---

## 4. Qué NO puede hacer el RDA

| Acción | Por qué |
|---|---|
| Publicar saltándose la aprobación | El flujo es estricto: `publicar()` exige estado `aprobado`. Desde `borrador`/`revision` el botón no aparece y la Policy lo niega. |
| Aprobar un dataset que no está en `revision` | `aprobar`/`rechazar` exigen estado `revision`. |
| Retirar algo que no está `publicado` | `retirar` exige estado `publicado`. |
| Editar una **entrega** que ya no es borrador | `update` de la Policy devuelve `false` si el status no es `borrador`. Para corregir, primero **Rechazar** a borrador. |
| Editar plantillas siendo planeador/admin | `editarPlantilla` exige `aprobar_datos_abiertos`, que solo tiene el RDA. |
| Capturar avances, editar MIR, gestionar presupuesto, jurídico, padrón, evaluación externa | El RDA no tiene esos permisos. No verás esos grupos del sidebar (o recibirás 403 al forzar la URL). |
| Tocar datos del portal público desde la UI | El portal `/transparencia` es de solo lectura, alimentado por el pipeline; no se edita a mano. |

**Nota sobre el admin:** por segregación de funciones, el administrador del sistema tampoco puede aprobar/publicar/retirar/editar plantillas. `Gate::before` devuelve `null` para `DatasetAbierto`, así que la Policy es autoritativa y el admin no ve botones engañosos sobre estados de aprobación. Tú eres el único punto de control de publicación.

---

## 5. Errores y validaciones comunes

Las acciones del componente `Show` capturan `DomainException` del modelo y lo muestran como **flash de error** (no rompen la página). Lo que verás con más frecuencia:

| Situación | Mensaje / comportamiento |
|---|---|
| Ejecutar una transición desde el estado equivocado | `"No se puede aprobar/publicar/retirar/rechazar/enviar a revisión desde estado {estado}."` (flash error). Revisa el estado actual antes de actuar. |
| Rechazar o Retirar sin motivo (o < 5 caracteres) | Validación Livewire: `motivo` es `required|string|min:5|max:500`. No avanza hasta capturar un motivo válido. |
| Crear entrega con periodo mal formado | `"Formato de periodo inválido: '...'. Use YYYY o YYYY-Q[1-4]."` |
| Crear una entrega que ya existe para ese periodo | `"Ya existe entrega {clave} para periodo {periodo}."` (no se duplica). |
| "Crear entrega" o "Editar plantilla" sobre el objeto equivocado | Solo plantillas (`periodo IS NULL`) se clonan/editan como plantilla. Sobre una entrega: `"Solo se pueden clonar plantillas (periodo IS NULL)."` |
| Botón que esperabas no aparece | Es state-gating correcto: la acción no es válida en el estado actual o tu permiso no la cubre. No es un bug. |

### Buenas prácticas operativas

- **Antes de publicar**, revisa la metadata DCAT y la bitácora de actividad en el detalle: la publicación impacta el portal ciudadano de inmediato.
- **Documenta siempre el motivo** al rechazar o retirar: queda en el `Activity` log con tu usuario y sirve de trazabilidad ante transparencia/auditoría.
- **Publica entregas, no plantillas**: las plantillas (`periodo=null`) son el molde del catálogo; lo que llega al portal son las entregas por periodo.
- Si tras publicar un dataset territorial (DS-G01/G02/G03) el portal no refleja datos, avisa a operación: depende de que **geobase** esté arriba al momento del sync (el job reintenta transitorios, pero los 4xx no se reintentan).
