# Matriz de Roles, Permisos y Rutas — DTE-SPP 2026

> Referencia técnica actualizada al 2026-03-19 (post Fase 3: Jurídico + GeoBase)

---

## 1. Roles del Sistema

| Rol | Clave | Descripción |
|-----|-------|-------------|
| Administrador | `admin` | Acceso total. Gestión de usuarios, auditoría, todos los módulos |
| Planeador | `planeador` | Diseña programas, construye MIR, revisa y aprueba avances, exporta reportes |
| Operador | `operador` | Captura avances de indicadores, consulta reportes |
| Analista Financiero | `analista_financiero` | Gestiona partidas presupuestales, captura avance financiero, exporta Cuenta Pública |
| Analista Jurídico | `analista_juridico` | Registra sustento legal, valida programas jurídicamente, gestiona ROP |

---

## 2. Permisos por Rol

### Leyenda: ✓ = tiene permiso | — = no tiene permiso

| Permiso | Admin | Planeador | Operador | Financiero | Jurídico |
|---------|:-----:|:---------:|:--------:|:----------:|:--------:|
| **Generales** |||||
| `gestionar_catalogos` | ✓ | ✓ | — | — | — |
| `crear_programa` | ✓ | ✓ | — | — | — |
| `editar_mir` | ✓ | ✓ | — | — | — |
| `capturar_avance` | ✓ | — | ✓ | — | — |
| `revisar_avance` | ✓ | ✓ | — | — | — |
| `aprobar_avance` | ✓ | ✓ | — | — | — |
| `exportar_reportes` | ✓ | ✓ | ✓ | — | — |
| `administrar_usuarios` | ✓ | — | — | — | — |
| `invitar_usuarios` | ✓ | — | — | — | — |
| `ver_sabana_captura` | ✓ | ✓ | ✓ | — | — |
| `ver_concentrado_captura` | ✓ | ✓ | ✓ | — | — |
| **Presupuesto** |||||
| `gestionar_presupuesto` | ✓ | — | — | ✓ | — |
| `capturar_avance_financiero` | ✓ | — | — | ✓ | — |
| `ver_datos_financieros` | ✓ | ✓ | — | ✓ | ✓ |
| `exportar_cuenta_publica` | ✓ | ✓ | — | ✓ | — |
| **Jurídico** |||||
| `gestionar_sustento_legal` | ✓ | — | — | — | ✓ |
| `validar_sustento_legal` | ✓ | — | — | — | ✓ |
| `ver_sustento_legal` | ✓ | ✓ | — | ✓ | ✓ |
| `gestionar_reglas_operacion` | ✓ | — | — | — | ✓ |

---

## 3. Rutas por Módulo y Permiso Requerido

### 3.1 Autenticación (sin rol)

| Ruta | Descripción |
|------|-------------|
| `/login` | Inicio de sesión |
| `/forgot-password` | Recuperación de contraseña |
| `/reset-password/{token}` | Restablecer contraseña |
| `/activar/{token}` | Activación de cuenta (invitación) |
| `/activar/{token}/2fa` | Configuración obligatoria de 2FA |

### 3.2 Comunes (cualquier usuario autenticado)

| Ruta | Nombre | Descripción |
|------|--------|-------------|
| `/dashboard` | `dashboard` | Panel principal con resumen por rol |
| `/notifications` | `notifications.index` | Centro de notificaciones |
| `/user/profile` | `profile.show` | Perfil y configuración de cuenta |

### 3.3 Planeación (MML)

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/mml/programas` | `mml.programas` | `crear_programa` | Lista de programas presupuestarios |
| `/mml/{programa}/etapa/1` | `mml.etapa1` | auth | Etapa 1: Definición del problema |
| `/mml/{programa}/etapa/2` | `mml.etapa2` | auth | Etapa 2: Árbol de problemas |
| `/mml/{programa}/etapa/3` | `mml.etapa3` | auth | Etapa 3: Árbol de objetivos |
| `/mml/{programa}/etapa/4` | `mml.etapa4` | auth | Etapa 4: Selección de alternativa |
| `/mml/{programa}/etapa/5` | `mml.etapa5` | auth | Etapa 5: Población objetivo |
| `/mml/{programa}/etapa/6` | `mml.etapa6` | auth | Etapa 6: Alineación estratégica |
| `/mml/{programa}/etapa/7/mir` | `mml.mir` | auth | Etapa 7: Matriz de Indicadores (MIR) |
| `/mml/importar` | `mml.importaciones` | `crear_programa` | Importación masiva de programas |
| `/mml/importar/nuevo` | `mml.importar.nuevo` | `crear_programa` | Nueva importación |

### 3.4 Seguimiento (Tracking)

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/seguimiento` | `tracking.panel` | `revisar_avance` | Panel de seguimiento |
| `/seguimiento/pendientes` | `tracking.pendientes` | `capturar_avance` | Indicadores pendientes de captura |
| `/seguimiento/captura/{avance}` | `tracking.captura` | `capturar_avance` | Captura de avance de indicador |
| `/seguimiento/vencidos` | `tracking.vencidos` | `revisar_avance` | Indicadores con periodo vencido |
| `/seguimiento/sabana-captura` | `tracking.sabana-captura` | `ver_sabana_captura` | Sábana de Captura (reporte tabular) |
| `/seguimiento/concentrado-captura` | `tracking.concentrado-captura` | `ver_concentrado_captura` | Concentrado de captura |
| `/seguimiento/flujo/{avance}` | `tracking.flujo` | `revisar_avance` | Flujo de aprobación de avance |
| `/seguimiento/desbloqueos` | `tracking.desbloqueos` | `revisar_avance` | Solicitudes de desbloqueo |

### 3.5 Presupuesto

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/presupuesto` | `presupuesto.panel` | `ver_datos_financieros` | Panel presupuestal con KPIs |
| `/presupuesto/partidas` | `presupuesto.partidas` | `gestionar_presupuesto` | Lista de partidas presupuestales |
| `/presupuesto/partidas/create` | `presupuesto.partidas.create` | `gestionar_presupuesto` | Crear partida |
| `/presupuesto/partidas/{partida}/edit` | `presupuesto.partidas.edit` | `gestionar_presupuesto` | Editar partida |
| `/presupuesto/captura/{programa}` | `presupuesto.captura` | `capturar_avance_financiero` | Captura de avance financiero |
| `/presupuesto/importar` | `presupuesto.importar` | `gestionar_presupuesto` | Importar partidas desde CSV |
| `/presupuesto/cuenta-publica` | `presupuesto.cuenta-publica` | `exportar_cuenta_publica` | Vista previa de Cuenta Pública |
| `/presupuesto/exportar/pdf/{ejercicio}` | `presupuesto.exportar.pdf` | `exportar_cuenta_publica` | Exportar PDF |
| `/presupuesto/exportar/excel/{ejercicio}` | `presupuesto.exportar.excel` | `exportar_cuenta_publica` | Exportar Excel |

### 3.6 Jurídico

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/juridico` | `juridico.dashboard` | `ver_sustento_legal` | Panel jurídico con KPIs y programas |
| `/juridico/programa/{programa}` | `juridico.programa` | `ver_sustento_legal` | Vista de sustento legal de un programa |
| `/juridico/programa/{programa}/fundamento/create` | `juridico.fundamento.create` | `gestionar_sustento_legal` | Registrar fundamento jurídico |
| `/juridico/programa/{programa}/fundamento/{id}/edit` | `juridico.fundamento.edit` | `gestionar_sustento_legal` | Editar fundamento |
| `/juridico/programa/{programa}/documentos` | `juridico.documentos` | `gestionar_reglas_operacion` | Subir y gestionar documentos normativos |
| `/juridico/programa/{programa}/validacion` | `juridico.validacion` | `validar_sustento_legal` | Validar/rechazar sustento legal |
| `/juridico/documento/{documento}/download` | `juridico.documento.download` | `ver_sustento_legal` | Descarga controlada de documento |

### 3.7 Catálogos (Cascade)

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/cascade/ped` | `cascade.ped.index` | `gestionar_catalogos` | Plan Estatal de Desarrollo |
| `/cascade/ped/import` | `cascade.ped.import` | `gestionar_catalogos` | Importar PED desde archivo |
| `/cascade/programas-derivados` | `cascade.programas-derivados.index` | `gestionar_catalogos` | Programas Derivados |
| `/cascade/alineacion` | `cascade.alineacion.index` | `gestionar_catalogos` | Matriz de Alineación (PED ↔ PND ↔ ODS) |

### 3.8 Reportes y Evaluación

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/evaluacion/transversal` | `evaluation.transversal` | `exportar_reportes` | Reporte transversal de desempeño |
| `/evaluacion/datos-abiertos/diccionario` | `evaluation.datos-abiertos.diccionario` | `exportar_reportes` | Diccionario de datos abiertos |
| `/evaluacion/exportar/pdf/{tipo}/{id?}` | `evaluation.exportar.pdf` | `exportar_reportes` | Exportar MIR/reportes en PDF |
| `/evaluacion/exportar/excel/{tipo}/{id?}` | `evaluation.exportar.excel` | `exportar_reportes` | Exportar en Excel |

### 3.9 Administración

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `/admin/usuarios` | `admin.users` | `invitar_usuarios` | Gestión de usuarios e invitaciones |
| `/admin/monitoreo-ia` | `admin.monitoreo-ia` | `administrar_usuarios` | Monitor de uso de IA (LLM) |
| `/admin/auditoria` | `admin.auditoria` | `administrar_usuarios` | Auditoría de cambios (activity log) |

### 3.10 GeoBase (Webhook)

| Ruta | Nombre | Permiso | Descripción |
|------|--------|---------|-------------|
| `POST /geobase/webhook` | `geobase.webhook` | HMAC signature | Webhook de cambios en padrón geoespacial |

---

## 4. Sidebar: Visibilidad por Rol

| Sección | Admin | Planeador | Operador | Financiero | Jurídico |
|---------|:-----:|:---------:|:--------:|:----------:|:--------:|
| Inicio | ✓ | ✓ | ✓ | ✓ | ✓ |
| Planeación | ✓ | ✓ | — | — | — |
| Seguimiento | ✓ | ✓ | ✓ | — | — |
| Presupuesto | ✓ | ✓ (lectura) | — | ✓ | — |
| Jurídico | ✓ | ✓ (lectura) | — | ✓ (lectura) | ✓ |
| Catálogos | ✓ | ✓ | — | — | — |
| Reportes | ✓ | ✓ | ✓ | — | — |
| Administración | ✓ | — | — | — | — |

---

## 5. Segregación de Funciones (Principio de Separación)

| Principio | Implementación |
|-----------|---------------|
| Quien planea no ejecuta | Planeador no puede `capturar_avance` |
| Quien ejecuta no aprueba | Operador no puede `aprobar_avance` |
| Quien presupuesta no planea | Financiero no puede `editar_mir` ni `crear_programa` |
| Quien valida legalmente no ejecuta | Jurídico no puede `capturar_avance` ni `gestionar_presupuesto` |
| Solo admin gestiona usuarios | `administrar_usuarios` e `invitar_usuarios` exclusivos de admin |

---

## 6. Validación Tripartita

El sistema implementa una validación cruzada de tres áreas para cada programa:

| Pilar | Responsable | Estado "completo" |
|-------|-------------|------------------|
| Planeación | Planeador | MIR completa o validada |
| Jurídico | Analista Jurídico | Validación jurídica = `validado` |
| Financiero | Analista Financiero | Partidas costeadas o calendarizadas |

El componente `x-programa.estado-tripartita` muestra el estado consolidado. Un programa requiere 3/3 para ser considerado "completo" y libre de riesgo de observación ASFE.
