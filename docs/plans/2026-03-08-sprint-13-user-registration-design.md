# Sprint 13: Registro de Usuarios por Invitación — Documento de Diseño

## Objetivo

Implementar un sistema cerrado de alta de usuarios donde solo un admin (o quien tenga el permiso `invitar_usuarios`) puede crear cuentas desde un panel interno. El usuario recibe un enlace de activación de un solo uso, establece su contraseña y configura 2FA obligatoriamente antes de acceder al sistema.

## 1. Panel de Administración de Usuarios

**Nueva sección** en el sidebar (separada de la actual `admin.users`):

| Elemento | Detalle |
|----------|---------|
| Ruta | `/admin/usuarios` |
| Permiso | `invitar_usuarios` (nuevo permiso, asignado a admin) |
| Componente | `App\Livewire\Admin\GestionUsuarios` |

**Vista principal — tabla de usuarios:**

```
┌─────────────────────────────────────────────────────────────────┐
│ Gestión de Usuarios                    [+ Invitar Usuario]      │
│─────────────────────────────────────────────────────────────────│
│ Nombre        │ Correo              │ Rol       │ UR      │ Estado     │
│───────────────│─────────────────────│───────────│─────────│────────────│
│ Juan Pérez    │ juan@gob.mx         │ planeador │ SE-001  │ ● Activo   │
│ María López   │ maria@gob.mx        │ operador  │ SE-001  │ ○ Pendiente│
│ Pedro Gómez   │ pedro@gob.mx        │ planeador │ SS-002  │ ● Activo   │
│ Ana Ruiz      │ ana@gob.mx          │ operador  │ SS-002  │ ◌ Inactivo │
└─────────────────────────────────────────────────────────────────┘
```

**Estados del usuario:**
- `pendiente` — invitado pero no ha completado onboarding (`activated_at IS NULL`, token válido)
- `activo` — onboarding completado (`activated_at IS NOT NULL`, `active = true`)
- `expirado` — el token venció sin activar (72h)
- `inactivo` — desactivado por admin (`active = false`)

**Acciones por usuario:**
- Reenviar invitación (solo si pendiente/expirado)
- Desactivar/Reactivar (toggle)
- Editar rol y UR (cambio inmediato)

## 2. Formulario de Invitación

**Página completa** (no modal) — `/admin/usuarios/invitar`:

```
┌─────────────────────────────────────────┐
│ Invitar Usuario                          │
│──────────────────────────────────────────│
│ Correo electrónico: [________________]   │
│ Nombre completo:    [________________]   │
│ Rol:                [▼ Seleccionar   ]   │
│ Unidad Responsable: [▼ Seleccionar   ]   │
│                                          │
│              [Cancelar] [Enviar Invitación]│
└─────────────────────────────────────────┘
```

- Validación: email único, rol válido (admin/planeador/operador), UR existente y activa
- Al enviar: crea User con `password=null`, `activated_at=null`, `active=true`, genera token, envía email

## 3. Modelo de Datos

**Migración — agregar campos a `users`:**

```php
$table->timestamp('activated_at')->nullable();     // null = no ha completado onboarding
$table->boolean('active')->default(true);           // admin puede desactivar
$table->string('invitation_token', 64)->nullable()->unique(); // token de un solo uso
$table->timestamp('invitation_sent_at')->nullable(); // para calcular expiración 72h
```

**No se crea tabla nueva** — los campos van directo en `users`. El token se invalida (se pone null) al completar onboarding.

## 4. Nuevo Permiso

**`invitar_usuarios`** — nuevo permiso en `SystemPermission`:
- Asignado a `admin` por defecto
- Se puede asignar a otros roles a futuro (RH, etc.)
- Controla acceso al panel de gestión de usuarios completo

## 5. Flujo de Activación (Onboarding)

```
Email recibido
    │
    ├─ Click en enlace: /activar/{token}
    │
    ├─ Validar token (existe + no expirado)
    │   ├─ Inválido → página de error "Enlace inválido o expirado"
    │   └─ Válido → continuar
    │
    ├─ Paso 1: Establecer contraseña
    │   ├─ Formulario: nueva contraseña + confirmar
    │   ├─ Submit → guarda password, marca sesión temporal
    │   └─ Redirect → Paso 2
    │
    ├─ Paso 2: Configurar 2FA (obligatorio)
    │   ├─ Muestra QR + clave de configuración
    │   ├─ Campo para ingresar código OTP para confirmar
    │   ├─ Submit → habilita 2FA, guarda recovery codes
    │   └─ Muestra recovery codes (una sola vez)
    │
    ├─ Paso 3: Confirmación
    │   ├─ "Tu cuenta está lista"
    │   ├─ activated_at = now()
    │   ├─ invitation_token = null (invalidar)
    │   └─ [Ir al Dashboard] → redirect con sesión completa
    │
    └─ Si cierra el navegador a medio proceso:
        ├─ Si password ya fue establecida pero 2FA no → al volver al enlace,
        │   detecta que tiene password y lo manda directo a Paso 2
        └─ Si el token ya fue consumido (activated_at != null) → "Ya activaste tu cuenta"
```

**Rutas del onboarding** (sin auth middleware, usan el token):

| Ruta | Método | Acción |
|------|--------|--------|
| `/activar/{token}` | GET | Valida token, muestra formulario de contraseña |
| `/activar/{token}/password` | POST | Guarda contraseña, redirect a 2FA |
| `/activar/{token}/2fa` | GET | Muestra QR para 2FA |
| `/activar/{token}/2fa` | POST | Confirma código OTP, activa cuenta |

## 6. Middleware de Onboarding

**`EnsureUserIsActivated`** — middleware que se aplica a TODAS las rutas autenticadas:

```php
if (auth()->check() && auth()->user()->activated_at === null) {
    return redirect()->route('onboarding.pending');
}

if (auth()->check() && auth()->user()->active === false) {
    auth()->logout();
    return redirect()->route('login')->with('error', 'Tu cuenta ha sido desactivada.');
}
```

- Se registra en `bootstrap/app.php` después de `auth`
- Excluye las rutas de onboarding y logout
- Un usuario que no ha completado onboarding no puede ver nada del sistema

## 7. Email de Invitación

**Mailable:** `App\Mail\InvitacionUsuario`

```
Asunto: Te han invitado al Sistema de Planeación y Programación

Hola {nombre},

Has sido invitado a participar en el Sistema de Planeación y Programación
como {rol} en la unidad {nombre_ur}.

Para activar tu cuenta, haz clic en el siguiente enlace:

[Activar mi cuenta]

Este enlace expira en 72 horas.

Si no solicitaste esta invitación, ignora este mensaje.
```

- Template en español, branding emerald consistente con login
- Usa Mailpit en desarrollo para previsualizar

## 8. Desactivación de Usuarios

Desde el panel de gestión:
- **Desactivar:** `active = false` → el middleware cierra su sesión al siguiente request
- **Reactivar:** `active = true` → puede volver a hacer login
- No se elimina el usuario (auditoría)
- El admin no puede desactivarse a sí mismo

## 9. Impacto en Código Existente

| Archivo | Cambio |
|---------|--------|
| `app/Enums/SystemPermission.php` | Agregar `INVITAR_USUARIOS` |
| `database/seeders/RolesAndPermissionsSeeder.php` | Asignar permiso a admin |
| `app/Models/User.php` | Agregar campos, scopes, helpers |
| `routes/web/admin.php` | Nuevas rutas de gestión + onboarding |
| `resources/views/components/layout/sidebar-nav.blade.php` | Enlace a gestión de usuarios |
| `bootstrap/app.php` | Registrar middleware `EnsureUserIsActivated` |
| `resources/views/admin/users-placeholder.blade.php` | Reemplazar por vista real |

## Decisiones Técnicas

- **Token:** `Str::random(64)` — suficientemente largo, single-use
- **Expiración:** 72 horas desde `invitation_sent_at`
- **2FA obligatorio:** Se usa `TwoFactorAuthenticatable` de Fortify — el onboarding llama a los mismos métodos que usa el perfil
- **Sin restricción de dominio de correo** — el admin es responsable de ingresar correos válidos
- **Middleware `EnsureUserIsActivated`** — separado de `verified` de Jetstream, es custom
- **Guest layout para onboarding** — reutiliza el split-screen del login
- **Recovery codes:** Se muestran una sola vez al final del onboarding, con aviso claro de guardarlos
- **Reenvío de invitación:** Genera nuevo token, resetea `invitation_sent_at`, invalida el anterior
