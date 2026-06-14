# Manual de usuario — Analista Financiero (dte-spp)

> Sistema: **dte-spp** (PbR-SED). Este rol opera el ciclo presupuestal: clave presupuestal, partidas, captura de avance financiero, conciliación físico-financiera y consulta de la Cuenta Pública. Algunas piezas del expediente (IAFF, cierre fiscal) las **consulta pero no las firma/avanza** (ver sección 4).

---

## 1. Quién es este rol y qué permisos tiene

El **Analista Financiero** (`analista_financiero`) es la UR que captura y administra la información presupuestal del programa: estructura del gasto (partidas / clave presupuestal), avance financiero trimestral (comprometido/devengado/pagado), conciliación contra el padrón de beneficiarios y consolidación en la Cuenta Pública.

**Permisos reales** (confirmados en los seeders `PresupuestoPermissionsSeeder`, `PadronPermissionsSeeder`, `JuridicoPermissionsSeeder`):

| Permiso | Origen (seeder) | Para qué le sirve |
|---|---|---|
| `gestionar_presupuesto` | PresupuestoPermissionsSeeder | CRUD de partidas, adecuaciones, importación CSV |
| `capturar_avance_financiero` | PresupuestoPermissionsSeeder | Captura trimestral de metas de gasto y avances |
| `ver_datos_financieros` | PresupuestoPermissionsSeeder | Panel presupuestal, conciliación, POA |
| `exportar_cuenta_publica` | PresupuestoPermissionsSeeder | Cuenta Pública (vista + export PDF/Excel) |
| `exportar_reportes` | PresupuestoPermissionsSeeder | Exportaciones generales (PDF/Excel) del módulo de evaluación |
| `ver_padron` | PadronPermissionsSeeder | Consulta (solo lectura) del padrón/cobertura GeoBase para conciliar |
| `ver_sustento_legal` | JuridicoPermissionsSeeder | Consulta (solo lectura) de fundamentos legales / ROP del programa |

Estos son **todos** los permisos del rol. No tiene `editar_mir`, `capturar_avance` (físico), `revisar_avance`, `firmar_iaff`, `gestionar_cierre_fiscal`, `generar_snapshot_padron`, `exportar_padron_shcp`, ni los permisos de transparencia/jurídico de gestión. Ver sección 4.

**Acceso de prueba (dev/QA):** usuario `financiero.<ur-slug>@sistema.test` (p. ej. `financiero.se@sistema.test`) / contraseña `password`. El rol de equipo Jetstream asociado es `editor`. El analista ve solo programas de su `currentTeam` (UR), salvo admin.

---

## 2. A qué entra al iniciar sesión y cómo navega

- **Landing tras login:** `/dashboard` (componente `Dashboard`). Es el panel general del sistema; las tarjetas que no correspondan a sus permisos no son accionables.
- **Su pantalla de trabajo real:** el **Panel Presupuestal** en `/presupuesto/` (`presupuesto.panel`, componente `PanelPresupuestal`), gated por `ver_datos_financieros`. Muestra KPIs (total aprobado, ejercido, % ejercido) y gráficos por programa para el ejercicio fiscal seleccionado.
- **Navegación (sidebar):** grupo **Presupuesto**. Desde ahí llega a Partidas, Captura de Avance Financiero, Importar, Conciliación, POA y Cuenta Pública. Las acciones por programa (clave presupuestal, conciliación, captura) se abren con el `{programa}` en la URL.
- También puede entrar (solo lectura) a Padrón/Cobertura (`/{programa}/padron`, `/{programa}/cobertura`) y al Panel Jurídico (`/juridico/`) por sus permisos de consulta.

---

## 3. Tareas principales paso a paso

### Flujo A — Estructura del gasto (clave presupuestal y partidas)

**A.1 Definir la clave presupuestal canónica (SEFIP/CONAC)**
- **Objetivo:** segmentar la clave SEFIP de 32 caracteres y capturar las clasificaciones administrativa, programática y funcional.
- **Ruta:** `/mml/programas/{programa}/clave-presupuestal` (`mml.clave-presupuestal`).
- **Componente:** `ClavePresupuestalEditor` (`clave-presupuestal-editor.blade.php`). Gate: `can:editar_mir`.
- **Pasos:** pegar la clave SEFIP y presionar **segmentar** (`segmentarClave`), o capturar manualmente Grupo/UR/UE (administrativa) y Programa/Subprograma/Proyecto/Actividad (programática). Seleccionar en cascada **Finalidad → Función → Subfunción** (CONAC). Revisar la vista previa de la clave canónica (17 dígitos) y **Guardar**.
- **Resultado:** se guardan `clave_sefip` y `finalidad_id/funcion_id/subfuncion_id`; la segmentación es informativa (no bloquea).
- **OJO — gating:** esta pantalla exige `editar_mir`, que el analista financiero **no** tiene. Normalmente **solo la consulta**; el alta/edición de la clave la realiza el planeador. Si necesita capturarla, coordínela con el rol Planeador.

**A.2 Gestionar partidas presupuestales (CRUD)**
- **Objetivo:** dar de alta/editar las partidas (clave COG + montos aprobado/modificado) del programa.
- **Ruta:** `/presupuesto/partidas` (`presupuesto.partidas`). Gate: `can:gestionar_presupuesto`.
- **Componente:** `GestionPartidas` (`gestion-partidas.blade.php`).
- **Pasos:** **Nueva Partida** abre `/presupuesto/partidas/create` (`PartidaForm`): elegir programa, ejercicio, clave COG (`clave_partida`), descripción, monto aprobado/modificado → **Guardar**. Editar en `/presupuesto/partidas/{partida}/edit`. Buscar/filtrar por programa/ejercicio.
- **Eliminar:** botón con `wire:confirm` — "¿Eliminar esta partida? Los avances financieros asociados también se eliminarán."
- **Resultado:** crea/actualiza `PartidaPresupuestal`. Estas partidas alimentan POA, IAFF y Cuenta Pública.

**A.3 Registrar adecuaciones (ampliaciones / reducciones)**
- **Objetivo:** registrar modificaciones al presupuesto de una partida (D6: historial de modificaciones).
- **Ruta:** `/presupuesto/partidas/{partida}/modificaciones` (`presupuesto.partidas.modificaciones`). Gate: `can:gestionar_presupuesto`.
- **Componente:** `ModificacionesPartida` (`modificaciones-partida.blade.php`).
- **Pasos:** elegir tipo (**Ampliación** / **Reducción** — enum `TipoModificacionPresupuestal`), monto, fecha, número de oficio y justificación → registrar.
- **Resultado:** se acumula en el historial vía `ModificacionPresupuestalService` y recalcula `monto_modificado`.

**A.4 Importar presupuesto por CSV (carga masiva)**
- **Objetivo:** cargar/actualizar muchas partidas de una vez.
- **Ruta:** `/presupuesto/importar` (`presupuesto.importar`). Gate: `can:gestionar_presupuesto`.
- **Componente:** `ImportarPresupuesto`.
- **Pasos:** subir CSV (columnas `clave_programa`, `clave_partida`, `descripcion`, `monto_aprobado`, `monto_modificado`) → **Procesar** → revisar **Preview** → **Confirmar** (`wire:confirm` "¿Confirmar la importación de {count} registros?").
- **Resultado:** reporte de creados / actualizados / errores.

### Flujo B — Captura de avance financiero trimestral

**B.1 Calendarizar metas de gasto y capturar avance**
- **Objetivo:** registrar metas de gasto por trimestre y el ejercido (comprometido/devengado/pagado).
- **Ruta:** `/presupuesto/captura/{programa}` (`presupuesto.captura`). Gate: `can:capturar_avance_financiero`.
- **Componente:** `CapturaAvanceFinanciero` (`captura-avance-financiero.blade.php`).
- **Pasos:**
  1. Tab **Calendarización:** distribuir las metas de gasto T1–T4 (`MetaGastoTrimestral`) → **Guardar metas**.
  2. Tab **Avance:** por trimestre capturar **comprometido / devengado / pagado** + observaciones → **Guardar avances**.
- **Resultado:** persiste `AvanceFinanciero` / `MetaGastoTrimestral`. Estos datos son la sección 3 del IAFF y la base de la conciliación y la Cuenta Pública.
- **Validaciones duras:** ver sección 5.

### Flujo C — Conciliación físico-financiera (cierre Fase 1 / D6)

**C.1 Cruzar pagado (tesorería local) vs entregado (padrón GeoBase)**
- **Objetivo:** verificar que lo pagado por tesorería coincide con lo entregado al padrón de beneficiarios.
- **Ruta:** `/presupuesto/conciliacion/{programa}` (`presupuesto.conciliacion`). Gate: `can:ver_datos_financieros`.
- **Componente:** `ConciliacionPadron` (`conciliacion-padron.blade.php`).
- **Pasos:** abrir la pantalla; el sistema suma `avances_financieros.monto_pagado` (tesorería local) y consulta `GeoBaseClient::getMontosEntregados` (padrón en vivo). Muestra la **diferencia total** y el **detalle por componente**.
- **Resultado:** vista de conciliación en vivo. Si GeoBase está caído, **degrada con aviso** y muestra solo la tesorería local (la conciliación queda parcial).
- **Apoyo:** puede consultar `/{programa}/padron` (`PadronPrograma`, `ver_padron`) y `/{programa}/cobertura` (`CoberturaPrograma`) en modo lectura para entender los montos entregados.

### Flujo D — POA y Cuenta Pública (consolidación y reporte)

**D.1 Consultar el POA (físico + financiero por trimestre)**
- **Objetivo:** ver el Programa Operativo Anual con metas físicas y financieras calendarizadas T1–T4.
- **Ruta:** `/presupuesto/poa` (`presupuesto.poa`). Gate: `can:ver_datos_financieros`.
- **Componente:** `ReportePoa` (`reporte-poa.blade.php`, vista derivada `vw_poa`).
- **Pasos:** seleccionar ejercicio fiscal; tabla agrupada por programa, tipos Físico/Financiero, T1–T4 y total anual; enlace directo a la conciliación.

**D.2 Cuenta Pública (aprobado vs ejercido, semáforos, exportación)**
- **Objetivo:** consolidar el ejercicio para rendición de cuentas y exportarlo.
- **Ruta:** `/presupuesto/cuenta-publica` (`presupuesto.cuenta-publica`). Gate: `can:exportar_cuenta_publica`.
- **Componente:** `CuentaPublicaView` (`cuenta-publica-view.blade.php`).
- **Pasos:** filtrar ejercicio; toggle **"Agrupar por Eje PED"**; revisar tabla con aprobado, ejercido, % financiero, eficiencia (costo/unidad) y semáforos físico/financiero/combinado.
- **Exportar:** `/presupuesto/exportar/pdf/{ejercicio}` (`PresupuestalController@exportarPdf`) y `/presupuesto/exportar/excel/{ejercicio}` (`@exportarExcel`). Ambos gated por `exportar_cuenta_publica`.
- **Resultado:** PDF/Excel de cierre fiscal / reporte público.

**D.3 Exportar presupuesto por capítulo (Cuenta Pública)**
- **Ruta:** `/evaluacion/exportar/presupuesto-capitulo/{programa}` (`PresupuestoCapituloXlsxController::download`). Gate: `can:exportar_cuenta_publica`.
- **Resultado:** XLSX del capítulo presupuestario.

### Flujo E — Consulta del IAFF y del cierre fiscal (solo lectura)

- **IAFF:** `/{programa}/iaff` (`mml.iaff`, `HistorialIaff`). El analista financiero **captura el avance** que nutre la sección financiera del IAFF (Flujo B) y **puede consultar** el historial, pero **NO** puede pulsar "Firmar IAFF" (requiere `firmar_iaff`).
- **Cierre fiscal:** `/{programa}/cierre-fiscal` (`mml.cierre-fiscal`, `CierreFiscalPanel`). Puede entender el estado del cierre, pero **NO** puede pulsar "Avanzar fase" (requiere `gestionar_cierre_fiscal`).
- **Su responsabilidad real en el cierre** es la **Fase 1 (conciliación, Flujo C)** y dejar lista la información financiera; la firma del IAFF Q4 y el avance de fases corresponden al Planeador.

---

## 4. Qué NO puede hacer (acciones gated)

Confirmado contra los seeders. El analista financiero **no** tiene estos permisos, por lo que verá las acciones bloqueadas o no las verá:

| Acción / pantalla | Permiso requerido | Quién sí |
|---|---|---|
| **Firmar IAFF** (`/{programa}/iaff` → "Firmar IAFF") | `firmar_iaff` | Planeador, Admin |
| **Avanzar fase de cierre fiscal** (`/{programa}/cierre-fiscal`) | `gestionar_cierre_fiscal` | Planeador, Admin |
| **Editar la clave presupuestal / MIR** (`clave-presupuestal`, etapas MML, `MirEditor`) | `editar_mir` | Planeador, Admin |
| **Capturar avance físico** de indicadores (`/seguimiento/captura/...`) | `capturar_avance` | Operador |
| **Revisar/aprobar avance físico** (`/seguimiento`, flujos) | `revisar_avance` / `aprobar_avance` | Planeador, Revisor, Admin |
| **Generar snapshot / activar-desactivar padrón** (`PadronPrograma`) | `generar_snapshot_padron` | Planeador, Admin |
| **Exportar Padrón SHCP** (CURP descifrado) | `exportar_padron_shcp` | Planeador |
| **Gestionar/validar sustento legal o ROP** (jurídico) | `gestionar_sustento_legal` / `validar_sustento_legal` / `gestionar_reglas_operacion` | Analista Jurídico |
| **Gestionar datasets de transparencia / aprobar datos abiertos** | `gestionar_dataset_abierto` / `aprobar_datos_abiertos` | Editor dataset / RDA |
| **ASM y evaluación externa (CRUD)** | `gestionar_asm` / `gestionar_evaluacion_externa` | Planeador, Admin |

Notas:
- En **Padrón** y **Sustento legal** solo tiene **lectura** (`ver_padron`, `ver_sustento_legal`); los botones de gestión no le aparecen o devuelven 403.
- En **clave presupuestal** la URL existe pero el gate `editar_mir` lo bloquea para guardar; trátela como consulta y coordine la captura con el Planeador.

---

## 5. Errores y validaciones comunes (reglas duras)

**Captura de avance financiero (`CapturaAvanceFinanciero`):**
- **Orden del ejercido:** se valida **comprometido ≥ devengado ≥ pagado** por trimestre. Si captura, p. ej., pagado mayor que devengado, no guarda.
- **Tope de metas:** las metas de gasto **no pueden exceder el monto efectivo** de la partida (aprobado ± modificaciones). Ajuste antes las adecuaciones (Flujo A.3) si necesita más techo.

**Partidas / adecuaciones:**
- Eliminar una partida **arrastra sus avances financieros** (confirmación explícita). No la elimine si ya hay ejercido capturado que deba conservarse.
- La adecuación exige tipo (Ampliación/Reducción), monto, fecha, **oficio** y **justificación**; sin estos campos no registra. El historial es la evidencia D6.

**Importación CSV:**
- Cabeceras requeridas: `clave_programa`, `clave_partida`, `descripcion`, `monto_aprobado`, `monto_modificado`. Filas con clave de programa inexistente o montos inválidos caen en la sección de **errores** del reporte (no se cargan); revise el preview antes de confirmar.

**Conciliación (`ConciliacionPadron`):**
- Depende de GeoBase en vivo. Si GeoBase no responde, la pantalla **avisa y muestra solo tesorería local**: la diferencia mostrada es parcial, no la trate como conciliación final.
- Acoplamiento con el padrón: si el programa no tiene padrón activo en GeoBase, los montos entregados pueden venir vacíos (0). Verifíquelo en `/{programa}/padron` (solo lectura).

**Cierre fiscal (gate normativo, lo verá pero no lo opera):**
- La transición a **FIRMA** del cierre exige el **IAFF del trimestre 4 firmado** del ejercicio (`CierreFiscalService::validarGate`). Como analista financiero su parte es dejar la conciliación y el avance financiero completos; la firma del IAFF Q4 la hace el Planeador.

**Alcance por equipo:**
- Solo ve y opera programas de su **UR (`currentTeam`)**. Si no encuentra un programa, verifique que esté en el equipo correcto.
