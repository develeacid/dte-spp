# Guía del Administrador — DTE-SPP 2026

> El Administrador tiene acceso completo al sistema. Gestiona usuarios, configura equipos, y puede operar en cualquier módulo.

---

## Tu rol en el sistema

Eres responsable de la configuración inicial del sistema, la gestión de usuarios y equipos (URs), y la supervisión general. Tienes todos los permisos de todos los roles, por lo que puedes intervenir en cualquier módulo cuando sea necesario.

---

## Módulo: Administración

### Gestión de Usuarios (`/admin/usuarios`)
Crear y administrar cuentas de usuario:
- **Invitar usuario**: genera enlace de activación enviado por correo
- **Asignar rol**: admin, planeador, operador, analista_financiero, analista_juridico
- **Asignar a equipo (UR)**: cada usuario pertenece a uno o más equipos
- **Activar/Desactivar**: controla acceso sin eliminar la cuenta

**Flujo de invitación:**
1. Click en "Invitar usuario"
2. Ingresa correo y selecciona rol
3. El sistema envía correo con enlace de activación
4. El usuario establece contraseña y configura 2FA
5. La cuenta queda activa

### Monitor de IA (`/admin/monitoreo-ia`)
Panel de uso del servicio de embeddings/LLM:
- Consumo de tokens por periodo
- Presupuesto restante
- Logs de llamadas a la API

### Auditoría (`/admin/auditoria`)
Registro completo de cambios en el sistema:
- Filtros: usuario, modelo, tipo de evento, rango de fechas
- Vista detallada de valores anteriores y nuevos
- Quién hizo qué, cuándo

---

## Todos los módulos

Como administrador, tienes acceso completo a todos los módulos documentados en las guías de los demás roles:

| Módulo | Guía de referencia |
|--------|-------------------|
| Planeación (MML) | [Guía del Planeador](04-planeador.md) |
| Seguimiento | [Guía del Planeador](04-planeador.md) + [Guía del Operador](01-operador.md) |
| Presupuesto | [Guía del Analista Financiero](02-analista-financiero.md) |
| Jurídico | [Guía del Analista Jurídico](03-analista-juridico.md) |
| Catálogos | [Guía del Planeador](04-planeador.md) |
| Reportes | [Guía del Planeador](04-planeador.md) |

---

## Configuración inicial del sistema

### Orden recomendado de setup:

1. **Crear equipos (URs)** — desde Jetstream Teams
2. **Invitar usuarios** — al menos un planeador y un operador por UR
3. **Cargar catálogos** — PED, PND, ODS (importar o crear manualmente)
4. **Crear alineación** — vincular PED ↔ PND ↔ ODS en la Matriz de Alineación
5. **Crear programas** — vía wizard MML o importación masiva
6. **Cargar presupuesto** — importar partidas CSV
7. **Registrar sustento legal** — fundamentos por programa
8. **Verificar validación tripartita** — los 3 pilares deben estar en verde

### Seeders disponibles (entorno local/QA):

| Seeder | Qué hace |
|--------|----------|
| `RolesAndPermissionsSeeder` | Crea roles y permisos base |
| `PresupuestoPermissionsSeeder` | Permisos del módulo financiero |
| `JuridicoPermissionsSeeder` | Permisos del módulo jurídico |
| `CatalogoOrdenamientosSeeder` | 15 ordenamientos legales de Oaxaca |
| `QaTestingSeeder` | Datos completos de prueba (4 programas, indicadores, avances) |
| `JuridicoTestSeeder` | Datos de prueba jurídicos |

---

## Responsabilidades exclusivas

Solo tú puedes:
- Crear y desactivar cuentas de usuario
- Asignar roles
- Consultar la auditoría completa
- Monitorear el uso de IA
- Intervenir en cualquier módulo sin restricción

---

## Supervisión: qué revisar periódicamente

| Frecuencia | Acción |
|------------|--------|
| Diario | Revisar notificaciones y dashboard |
| Semanal | Verificar avances vencidos, usuarios sin activar |
| Trimestral | Auditoría de cambios, estado tripartita de programas |
| Anual | Renovar catálogos PED/PND si hay cambios, verificar vigencia de documentos jurídicos |
