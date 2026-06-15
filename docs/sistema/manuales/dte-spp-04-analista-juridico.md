# Manual de Usuario — Analista Jurídico (dte-spp)

> Sistema PbR-SED de planeación de programas presupuestales.
> Rol: **Analista Jurídico** (`analista_juridico`). Sistema: **dte-spp** únicamente.
> Alcance: sustento legal del programa, fundamentos, checklist de validación jurídica y documentos normativos / ROP en PDF.

---

## 1. Quién es este rol y qué permisos tiene

El **Analista Jurídico** es el responsable de dar y validar el **sustento legal** de cada programa presupuestario: registrar los fundamentos jurídicos (facultad de la Unidad Responsable, mandato de gasto, reglas de operación), cargar los documentos normativos en PDF (ROP, leyes, reglamentos, periódico oficial) y emitir la **validación jurídica final** que confirma que el programa tiene piso legal para ejercer gasto.

### Permisos reales (seeder `JuridicoPermissionsSeeder`)

El rol `analista_juridico` recibe exactamente estos 5 permisos:

| Permiso (valor) | Para qué sirve |
|---|---|
| `ver_sustento_legal` | Ver el panel jurídico, la vista de sustento legal por programa y descargar documentos. |
| `gestionar_sustento_legal` | Crear, editar y eliminar fundamentos jurídicos de un programa. |
| `validar_sustento_legal` | Validar / rechazar / marcar en revisión el sustento legal de un programa. |
| `gestionar_reglas_operacion` | Subir, verificar y eliminar documentos normativos (PDF), incluidas las ROP. |
| `ver_datos_financieros` | Acceso de lectura a datos financieros (contexto presupuestal del programa). |

> Nota: el Analista Jurídico es **el único rol no-admin** con `gestionar_sustento_legal`, `validar_sustento_legal` y `gestionar_reglas_operacion`. El `admin` también tiene los 4 permisos jurídicos. El `planeador` y el `analista_financiero` solo reciben `ver_sustento_legal` (lectura). Estos permisos no son inventados: provienen de `database/seeders/JuridicoPermissionsSeeder.php`.

---

## 2. Al iniciar sesión: dashboard y navegación

- **Landing tras login**: `/dashboard` (ruta `dashboard`, componente `App\Livewire\Dashboard`). Es el dashboard general del sistema.
- **Tu zona de trabajo** está en el grupo **"Jurídico"** del sidebar izquierdo.

### Navegación (sidebar)

El grupo **Jurídico** del menú lateral (`components/layout/sidebar-nav.blade.php`) aparece solo si tienes `ver_sustento_legal` — como Analista Jurídico, siempre lo verás. Contiene un único acceso directo:

- **Panel** → `/juridico/` (ruta `juridico.dashboard`).

Desde ese Panel navegas a las demás pantallas (sustento legal por programa, documentos, validación). No hay enlaces directos a esas sub-pantallas en el sidebar; se llega entrando primero a un programa desde el Panel.

> Importante: las pantallas de sustento/documentos/validación están **scoped por team (Unidad Responsable)**: solo verás los programas de tu `currentTeam`.

---

## 3. Tareas principales paso a paso

Prefijo de todas las URLs: `/juridico`. Todas requieren sesión autenticada y verificada (`auth:sanctum` + `verified`).

### Flujo A — Revisar el estado jurídico de todos los programas

**Objetivo:** ver de un vistazo qué programas tienen sustento validado, pendiente, rechazado o sin registro, y detectar documentos por vencer.

1. Entra a **Panel Jurídico**.
   - **URL:** `/juridico/`
   - **Ruta:** `juridico.dashboard`
   - **Componente:** `App\Livewire\Juridico\PanelJuridico` (vista `panel-juridico.blade.php`)
   - **Permiso:** `ver_sustento_legal`
2. Lee los **KPIs**: Validados (verde), Pendientes (ámbar), Rechazados (rojo), Sin registro.
3. Usa los filtros:
   - **Filtro por ejercicio** (año fiscal).
   - **Filtro por estado**: `validado`, `pendiente` / `en_revision`, `rechazado`, o `sin_registro`.
4. Revisa la **alerta de documentos próximos a vencer** (documentos normativos con `fecha_vigencia` cercana del team).
5. Desde la lista, haz clic en **Ver** sobre un programa para abrir su sustento legal, o en **Validar** para ir directo a la validación.
   - **Resultado:** llegas a la pantalla del programa elegido.

---

### Flujo B — Registrar el sustento legal de un programa (fundamentos)

**Objetivo:** dejar registrados los fundamentos jurídicos que sostienen el programa.

#### B.1 Abrir la vista de sustento legal del programa

- **URL:** `/juridico/programa/{programa}`
- **Ruta:** `juridico.programa`
- **Componente:** `App\Livewire\Juridico\SustentoLegalPrograma` (vista `sustento-legal-programa.blade.php`)
- **Permiso:** `ver_sustento_legal` (lectura); las acciones de crear/editar/eliminar exigen `gestionar_sustento_legal`.
- **Qué ves:** fundamentos agrupados por tipo, documentos normativos asociados y el estado de la validación automática (checklist).

#### B.2 Agregar un fundamento

1. Botón **Agregar fundamento**.
   - **URL:** `/juridico/programa/{programa}/fundamento/create`
   - **Ruta:** `juridico.fundamento.create`
   - **Componente:** `App\Livewire\Juridico\FundamentoForm` (vista `fundamento-form.blade.php`)
   - **Permiso:** `gestionar_sustento_legal`
2. Completa el formulario:
   - **Tipo** (`tipo`, obligatorio): `facultad_ur` (Facultad de la UR), `mandato_gasto` (Mandato de gasto), `regla_operacion` (Regla de operación) u `otro`.
   - **Nivel de jerarquía** (`nivel_jerarquia`, obligatorio): `constitucional`, `federal`, `estatal`, `reglamentario` u `operativo`.
   - **Ordenamiento** (`ordenamiento`, obligatorio, máx. 255): nombre de la ley/reglamento. Hay un **modal de selección de catálogo** que, al elegir un ordenamiento, autocompleta el nombre y el nivel.
   - **Artículo** (`articulo`, opcional, máx. 100).
   - **Descripción** (`descripcion`, opcional, máx. 2000).
   - **Vigente** (checkbox `vigente`): márcalo si el fundamento está vigente. **Esto es clave para el checklist** (ver §5).
3. Botón **Registrar** (o **Guardar cambios** en edición).
   - **Resultado:** el fundamento se guarda, se **recalcula el checklist** del programa y vuelves a la vista del programa.

#### B.3 Editar / eliminar un fundamento

- **Editar:** `/juridico/programa/{programa}/fundamento/{fundamento}/edit` (ruta `juridico.fundamento.edit`, mismo `FundamentoForm`).
- **Eliminar:** botón en la vista del programa con confirmación `wire:confirm` ("¿Eliminar este fundamento?").
- **Permiso:** `gestionar_sustento_legal`.
- **Resultado:** al eliminar/editar fundamentos vigentes, el checklist se recalcula automáticamente.

---

### Flujo C — Cargar documentos normativos y ROP (PDF)

**Objetivo:** subir y verificar los PDF normativos del programa (ROP, leyes, reglamentos, periódico oficial).

1. Entra a **Documentos normativos**.
   - **URL:** `/juridico/programa/{programa}/documentos`
   - **Ruta:** `juridico.documentos`
   - **Componente:** `App\Livewire\Juridico\DocumentosNormativos` (vista `documentos-normativos.blade.php`)
   - **Permiso:** `gestionar_reglas_operacion`
2. **Subir documento** (formulario embebido, muestra progreso con `wire:loading`):
   - **Archivo** (`archivo`, obligatorio): solo **PDF** (`mimes:pdf`), tamaño máx. **10 MB** (configurable vía `juridico.max_upload_size_mb`).
   - **Tipo de documento** (`tipo_documento`, obligatorio): `reglas_operacion` (ROP), `periodico_oficial`, `reglamento_interior`, `ley_organica` u `otro`.
   - **Nombre** (`nombre`, obligatorio, máx. 255).
   - **Fecha de publicación** y **Fecha de vigencia** (opcionales; la vigencia alimenta la alerta de "próximos a vencer" del Panel).
3. **Verificar documento**: botón que marca el documento como `verificado`. **Para que la ROP cuente en el checklist, el documento tipo `reglas_operacion` debe estar verificado** (ver §5).
4. **Descargar documento**: botón → `/juridico/documento/{documento}/download` (ruta `juridico.documento.download`, controlador `DocumentoNormativoController@download`, permiso `ver_sustento_legal`). Descarga controlada del PDF.
5. **Eliminar documento**: botón con confirmación ("¿Eliminar este documento?").
   - **Resultado:** documentos disponibles para descarga y, si son ROP verificadas, habilitan el item ROP del checklist.

---

### Flujo D — Validación jurídica final del programa

**Objetivo:** emitir el dictamen jurídico (validar, rechazar o marcar en revisión) una vez que el checklist está completo.

1. Entra a **Validación jurídica**.
   - **URL:** `/juridico/programa/{programa}/validacion`
   - **Ruta:** `juridico.validacion`
   - **Componente:** `App\Livewire\Juridico\ValidacionJuridica` (vista `validacion-juridica.blade.php`)
   - **Permiso:** `validar_sustento_legal`
   - **Servicio:** `ValidacionJuridicaService`
2. La pantalla **recalcula el checklist** al cargar y muestra los 3 items automáticos:
   - **Facultad de la UR** → existe un fundamento `facultad_ur` **vigente**.
   - **Mandato de gasto** → existe un fundamento `mandato_gasto` **vigente**.
   - **Reglas de Operación** → solo aplica si el programa tiene `requiere_rop = true`; entonces exige un documento `reglas_operacion` **verificado**. Si el programa no requiere ROP, este item queda como **No aplica** (no bloquea).
3. Acciones disponibles:
   - **Validar jurídicamente** → si el checklist está completo, marca estado `validado`, registra `validado_por` y `validado_at`, y guarda observaciones.
   - **Rechazar** → exige **observaciones obligatorias (mínimo 10 caracteres)**; marca estado `rechazado`.
   - **Marcar en revisión** → cambia el estado a `en_revision`.
   - **Añadir observaciones** → textarea de observaciones.
4. **Resultado:** el estado de validación del programa cambia y se refleja en el Panel Jurídico (KPIs y filtros). Los estados posibles son: `pendiente`, `en_revision`, `validado`, `rechazado`, `vencido`.

---

## 4. Qué NO puede hacer este rol

Acciones que verás bloqueadas (403 / `abort_if`) o que no aparecen en tu sidebar:

- **Planeación / MIR (módulos MML):** no tienes `editar_mir`. No puedes crear/editar programas, árboles, indicadores ni la MIR (`/mml/...`, etapas 1–7, importación). El grupo no es tu área.
- **Seguimiento de avances:** no tienes `revisar_avance` ni `capturar_avance`. No verás el Panel de Seguimiento, captura, ni flujos de aprobación (`/seguimiento/...`).
- **Evaluación / reportes:** no tienes `exportar_reportes`, `ver_asm`, `gestionar_asm`, ni `ver/gestionar_evaluacion_externa`. No accedes a paneles ni exportaciones de `/evaluacion/...` (MIR PDF, anexos, padrón SHCP, datos abiertos, ASM, evaluación externa).
- **Padrón / cobertura:** no tienes `ver_padron`. No accedes a `/{programa}/padron` ni `/{programa}/cobertura`.
- **Presupuesto (gestión):** tienes `ver_datos_financieros` (lectura del Panel Presupuestal y conciliación), pero **no** `gestionar_presupuesto`, `capturar_avance_financiero` ni `exportar_cuenta_publica`: no creas/editas partidas, adecuaciones, ni capturas avance financiero, ni exportas Cuenta Pública.
- **Transparencia / datos abiertos:** no tienes `ver/gestionar_dataset_abierto` ni `aprobar_datos_abiertos`. No accedes a `/transparencia/datos-abiertos`.
- **Catálogos PED:** no tienes `gestionar_catalogos`. No ves el grupo "Catálogos" (Plan Estatal, Programas Derivados, Matriz de Alineación).
- **Administración:** no tienes `administrar_usuarios`. No gestionas usuarios ni desbloqueos.
- **GeoBase:** este rol **no opera en el sistema geobase**; tu alcance es exclusivamente dte-spp.

---

## 5. Errores y validaciones comunes (reglas duras)

1. **No se puede validar con checklist incompleto.**
   `ValidacionJuridicaService::validar()` lanza `DomainException` si el checklist no está completo. Antes de pulsar **Validar jurídicamente**, asegúrate de:
   - Tener un fundamento `facultad_ur` con **Vigente = sí**.
   - Tener un fundamento `mandato_gasto` con **Vigente = sí**.
   - Si el programa **requiere ROP** (`requiere_rop = true`): tener un documento `reglas_operacion` subido **y verificado**. Si no requiere ROP, ese item no bloquea (queda "No aplica").

2. **Un fundamento no vigente no cuenta.** El checklist solo considera fundamentos con `vigente = true`. Un fundamento de facultad o mandato registrado pero sin marcar "Vigente" deja el item del checklist en rojo.

3. **ROP no verificada = item ROP pendiente.** Subir el PDF de las ROP no basta: debes pulsar **Verificar documento** para que cuente. Solo `reglas_operacion` con `verificado = true` satisface el item.

4. **Rechazar exige observaciones.** Al rechazar, las observaciones son obligatorias con **mínimo 10 caracteres** (`'observaciones' => ['required','string','min:10']`); de lo contrario el formulario no envía.

5. **Subida de documentos restringida.** El archivo debe ser **PDF** (`mimes:pdf`) y no superar **10 MB** (`config('juridico.max_upload_size_mb', 10)`). Otros formatos o archivos más grandes son rechazados por validación.

6. **Límites de longitud en fundamentos.** Ordenamiento máx. 255, artículo máx. 100, descripción máx. 2000; tipo y nivel de jerarquía deben ser valores válidos de sus catálogos (enums `TipoSustentoLegal` / `NivelJerarquiaLegal`).

7. **Scope por team.** Solo operas sobre programas de tu Unidad Responsable (`currentTeam`). Programas de otros teams no aparecen ni son editables.

8. **El checklist se recalcula solo.** Cada vez que guardas/eliminas un fundamento o entras a la pantalla de validación, el sistema recalcula los 3 items. Si validaste y luego cambias un fundamento a no-vigente o borras la ROP, el estado puede dejar de reflejar piso legal completo (revisa el Panel).
