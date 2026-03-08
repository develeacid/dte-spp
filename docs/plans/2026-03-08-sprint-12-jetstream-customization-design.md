# Sprint 12: Jetstream Customization — Auth, Perfil & Teams — Documento de Diseño

## Objetivo

Personalizar Jetstream para el contexto gubernamental mexicano: rediseño visual de auth (split screen), protección de registro, perfil adaptado al layout con sidebar, terminología de Unidades Responsables, y restricciones de seguridad/auditoría.

## 1. Login y Auth — Split Screen

**Layout guest rediseñado** (`layouts/guest.blade.php`):
- Split screen 50/50 (responsive: en mobile solo formulario)
- **Lado izquierdo**: fondo emerald oscuro, logo del sistema, nombre "Sistema de Planeación y Programación", subtítulo institucional, ejercicio fiscal 2026
- **Lado derecho**: fondo blanco/gris claro, formulario centrado verticalmente

**Vistas a rediseñar** (todas en español):
- `login.blade.php` — email, contraseña, recordarme, link recuperación
- `forgot-password.blade.php` — email para reset
- `reset-password.blade.php` — nueva contraseña
- `two-factor-challenge.blade.php` — código 2FA

## 2. Registro Protegido

- Deshabilitar registro público: quitar `Features::registration()` de `config/fortify.php`
- Ocultar links de registro en login
- Ruta `/register` retorna 404 o redirect a login
- Dejar nota en config para futuro flujo de invitación por token

## 3. Foto de Perfil

- Activar `Features::profilePhotos()` en `config/jetstream.php`
- El avatar del topbar usa `x-ui.avatar` — conectar con `profile_photo_url`
- Dropdown muestra foto real si existe, iniciales si no

## 4. Dropdown de Perfil (topbar)

```
┌─────────────────────────┐
│ [foto] Nombre Completo  │
│ planeador · SE-001      │  ← rol + UR
│─────────────────────────│
│ Mi Perfil               │
│ Cerrar Sesión           │
└─────────────────────────┘
```

Sin switcher de UR — el cambio de UR se hace desde settings.

## 5. Vista de Perfil — Adaptada al Layout

Reestructurar `profile/show.blade.php` para usar `x-page.container` con sidebar:

| Sección | Contenido | Visible para |
|---------|-----------|-------------|
| Información Personal | Nombre, email, foto de perfil | Todos |
| Seguridad | Contraseña + 2FA + sesiones activas | Todos |
| Mi Unidad Responsable | Datos de UR (solo lectura para operador) | Todos |
| Servidores Públicos | Gestión de miembros de la UR | admin, planeador |
| ~~Eliminar Cuenta~~ | Ocultar completamente (auditoría) | Nadie |

## 6. Terminología — Teams → Unidades Responsables

| Original | Nuevo |
|----------|-------|
| Team | Unidad Responsable |
| Team Settings | Configuración de la Unidad |
| Team Members | Servidores Públicos |
| Team Owner | Titular |
| Create New Team | Crear Unidad Responsable |
| Team Name | Nombre de la Unidad |
| Add Team Member | Agregar Servidor Público |
| team member's role | rol del servidor público |
| Leave Team | Salir de la Unidad |
| Remove | Remover |

## 7. Restricción de Creación de UR

- Ocultar "Crear Unidad Responsable" para operadores
- Solo visible para `admin` o `planeador` (via `@can` / `hasRole`)
- La ruta `/teams/create` verifica autorización via gate de Jetstream

## 8. Traducción Completa al Español

- Labels de formularios, mensajes de confirmación, placeholders
- Mensajes de error/validación (via `lang/es/`)
- Botones, acciones y textos descriptivos
- Componentes de seguridad: 2FA, sesiones activas, confirmar contraseña

## 9. Componentes de Seguridad

- 2FA: traducir completamente, mantener funcionalidad
- Sesiones activas: traducir, mantener funcionalidad
- Confirmar contraseña: traducir modal
- Eliminar cuenta: ocultar sección, deshabilitar `Features::accountDeletion()` en config

## Decisiones Técnicas

- **Split screen**: CSS grid `lg:grid-cols-2`, lado izquierdo `hidden lg:flex`
- **Branding**: reusar tokens CSS existentes (`--color-primary`, `--color-primary-dark`)
- **Foto de perfil**: usar disco `public`, Jetstream maneja upload/resize
- **Traducciones**: archivos en `lang/es/` para mensajes de Jetstream/Fortify
- **Ocultar eliminar cuenta**: quitar feature flag + no renderizar sección
- **Restricción de UR**: gate `create` en `TeamPolicy` + `@can` en vistas
