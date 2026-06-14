# Manual de usuario — Acceso general (todos los usuarios)

> Aplica a **todos los usuarios autenticados** de los dos sistemas del ecosistema PbR-SED:
> **dte-spp** (planeación / MIR / seguimiento / evaluación / transparencia) y
> **geobase** (padrón de beneficiarios y reportes territoriales).
>
> Este manual cubre lo común a cualquier rol: inicio de sesión, recuperación de
> contraseña, autenticación de dos factores (2FA), perfil/equipos y cómo se navega.
> Las capacidades de cada rol (planeador, operador, analista, etc.) se documentan en
> sus manuales específicos.

---

## 1. Quién es este rol y qué permisos tiene

"Acceso general" **no es un rol del sistema** con permisos propios: es el conjunto
de capacidades base que tiene **cualquier cuenta**, sin importar el rol asignado,
por el solo hecho de estar autenticada. Estas capacidades las da el framework de
cuentas (Jetstream/Fortify), no el seeder de permisos del dominio.

Toda cuenta autenticada puede, en ambos sistemas:

- Iniciar y cerrar sesión.
- Recuperar su contraseña por correo (auto-servicio).
- Cambiar su contraseña.
- Editar su información de perfil (nombre, correo).
- Activar/gestionar su autenticación de dos factores (2FA).
- Ver el panel principal (Dashboard) y el menú lateral filtrado a lo que su rol permite.
- Cambiar de equipo activo (Unidad Responsable / UR) si pertenece a más de uno.

**Roles que existen en cada sistema** (un usuario tiene uno o varios; los permisos
de cada uno se confirman en el seeder de roles de cada repo):

- **dte-spp** (`SystemRole`): `admin`, `planeador`, `operador`, `analista_financiero`,
  `analista_juridico`, `responsable_datos_abiertos`.
- **geobase**: `sysadmin`, `analista_global`, `admin_dependencia`, `operador`,
  `enlace_mir` (este último es máquina-a-máquina, no de uso humano).

> **Diferencia clave dte-spp vs geobase:** los permisos de "acceso general" son los
> mismos en ambos, pero en **geobase la 2FA es obligatoria en producción** (un
> middividleware te obliga a activarla antes de poder usar el sistema). En **dte-spp**
> la 2FA está disponible pero su exigencia depende de la configuración del entorno
> (`FORTIFY_TWO_FACTOR`). Ver sección 3.

### Cosas que NINGUNA cuenta puede hacer por sí sola

- **Auto-registrarse.** El registro abierto está deshabilitado en ambos sistemas
  (`Features::registration()` comentado). Las cuentas se crean **solo por invitación**
  de un administrador.
- **Eliminar su propia cuenta en dte-spp** (`accountDeletion` deshabilitado por
  política de auditoría). En **geobase sí** está habilitada la eliminación de cuenta.

---

## 2. A qué entra al iniciar sesión y cómo navega

### Landing tras el login

En ambos sistemas, al iniciar sesión correctamente llegas al **Dashboard**:

- **URL:** `/dashboard` · **Ruta:** `dashboard`
- **dte-spp** — componente `App\Livewire\Dashboard` (`livewire/dashboard.blade.php`).
  Es de **solo lectura** y se **adapta a tu rol**: muestra estadísticas de
  seguimiento, finanzas, semáforo de indicadores y notificaciones recientes solo
  de las áreas a las que tienes acceso. Si tu rol no tiene permisos sobre un área,
  esas tarjetas no aparecen.
- **geobase** — dashboard de reportes territoriales / padrón, también filtrado por rol.

### Estructura de navegación (menú lateral / sidebar)

La navegación está en el menú lateral (`components/layout/sidebar-nav.blade.php`).
**Solo ves los grupos para los que tu rol tiene permiso** (cada grupo está envuelto
en directivas `@can` / `@canany`). El primer ítem, **Dashboard**, lo ve todo el mundo.

**Grupos del sidebar en dte-spp** (aparecen según permisos):

| Grupo | Aparece si tienes | Lleva a |
|---|---|---|
| Dashboard | (siempre) | `/dashboard` |
| Planeación | `crear_programa` o `editar_mir` | Programas, Importaciones |
| Seguimiento | `revisar_avance`, `capturar_avance`, `ver_sabana_captura` o `ver_concentrado_captura` | Panel, Mis Indicadores, Vencidos, Sábana, Concentrado |
| Presupuesto | `ver_datos_financieros`, `gestionar_presupuesto`, `capturar_avance_financiero` o `exportar_cuenta_publica` | Panel, Partidas, POA, Cuenta Pública |
| Jurídico | `ver_sustento_legal` | Panel jurídico |
| Catálogos | `gestionar_catalogos` | Plan Estatal, Prog. Derivados, Alineación |
| Reportes | `exportar_reportes`, `ver_asm` o `ver_evaluacion_externa` | Transversal, Desviaciones, Acumulado Anual, ASMs, Evaluaciones externas, Datos Abiertos |
| Transparencia | `ver_datasets_abiertos` | Datos Abiertos |
| Administración | `administrar_usuarios` o `invitar_usuarios` | Usuarios, Monitor IA, Auditoría |

**Grupos del sidebar en geobase** (según permisos):

| Grupo | Lleva a |
|---|---|
| Dashboard | `/dashboard` |
| Padrón | Beneficiarios, Inscripciones |
| Territorio | Análisis espacial |
| Reportes | Reportes territoriales |
| Integración | Sincronización, API y webhooks |
| Administración | Equipo y usuarios |

### Menú de cuenta (arriba a la derecha)

Independiente del rol, el menú de usuario (esquina superior derecha) te da acceso a:

- **Perfil** (`profile.show`)
- **Cambiar de equipo** (si perteneces a más de una UR)
- **Cerrar sesión** (`logout`)

---

## 3. Tareas principales paso a paso

### Flujo: Entrar al sistema

#### 3.1 Iniciar sesión

- **Objetivo:** acceder al sistema.
- **URL:** `/login`
- **Pasos:**
  1. Abre la URL del sistema en el navegador.
  2. Captura tu **correo** y **contraseña**.
  3. (Opcional) Marca **"Recordarme"** para mantener la sesión.
  4. Botón **"Iniciar sesión"**.
- **Resultado:** si las credenciales son válidas, llegas a `/dashboard`. Si tienes
  2FA activa, primero te pedirá el código (paso 3.6). En geobase de producción, si
  **no** tienes 2FA activa, te redirige a tu perfil con un aviso para que la actives
  antes de continuar (ver 3.5).
- **Nota:** **no hay opción de "Crear cuenta"**. Si no tienes credenciales, debes
  pedir a un administrador que te **invite** (recibirás un correo de invitación).

#### 3.2 Aceptar una invitación (primera vez)

- **Objetivo:** activar tu cuenta recién creada por un administrador.
- **Pasos:**
  1. Abre el **correo de invitación**.
  2. Haz clic en el enlace para **aceptar la invitación** / establecer tu contraseña.
  3. Define tu contraseña y entra.
- **Resultado:** quedas dentro de un **equipo (UR)** con el rol que te asignó el
  administrador.

---

### Flujo: Recuperar acceso

#### 3.3 Recuperar contraseña olvidada

- **Objetivo:** restablecer tu contraseña sin ayuda del administrador.
- **URL:** desde `/login` → enlace **"¿Olvidaste tu contraseña?"** (`/forgot-password`).
- **Pasos:**
  1. Captura tu **correo**.
  2. Botón **"Enviar enlace de recuperación"**.
  3. Abre el correo y haz clic en el **enlace de restablecimiento**.
  4. Captura **nueva contraseña** y **confirmación**, y guarda.
- **Resultado:** la contraseña queda cambiada; vuelves a `/login` para entrar.
- **Disponible en ambos sistemas** (`Features::resetPasswords()` activo). El enlace
  tiene caducidad; si expira, repite el proceso.

#### 3.4 Cambiar contraseña estando dentro

- **Objetivo:** cambiar tu contraseña ya con sesión iniciada.
- **URL:** `/user/profile` (Ruta `profile.show`) → sección **"Actualizar contraseña"**.
- **Pasos:** captura **contraseña actual**, **nueva** y **confirmación** → **Guardar**.
- **Resultado:** contraseña actualizada de inmediato.

---

### Flujo: Seguridad de la cuenta (2FA)

#### 3.5 Activar la autenticación de dos factores (2FA)

- **Objetivo:** proteger tu cuenta con un segundo factor (app autenticadora).
- **URL:** `/user/profile` → sección **"Autenticación de dos factores"**.
- **Pasos:**
  1. Botón **"Activar"**.
  2. (Te puede pedir **confirmar tu contraseña**.)
  3. Escanea el **código QR** con tu app autenticadora (Google Authenticator,
     Authy, etc.).
  4. Captura el **código de 6 dígitos** que genera la app para **confirmar**.
  5. **Guarda los códigos de recuperación** que se muestran (te sirven si pierdes
     el teléfono).
- **Resultado:** la 2FA queda activa; el próximo login te pedirá el código.
- **Diferencia dte-spp vs geobase:**
  - **geobase (producción):** la 2FA es **obligatoria**. Mientras no la actives, el
    sistema te redirige a tu perfil con el aviso *"Por seguridad, debes activar la
    autenticación de dos factores para acceder al sistema"* y solo te deja ver el
    perfil, activar 2FA o cerrar sesión. En entornos `local`/`testing` no se exige.
  - **dte-spp:** la 2FA está disponible y su disponibilidad/obligatoriedad depende
    de la variable de entorno `FORTIFY_TWO_FACTOR`. Si el botón no aparece en tu
    perfil, es que está deshabilitada en ese entorno.

#### 3.6 Iniciar sesión con 2FA activa

- **Pasos:** tras capturar correo y contraseña, el sistema pide el **código de 6
  dígitos** de tu app (o un **código de recuperación** si no tienes el teléfono).
- **Resultado:** acceso al dashboard.

#### 3.7 Desactivar 2FA

- **URL:** `/user/profile` → **"Autenticación de dos factores"** → **"Desactivar"**
  (puede pedir confirmar contraseña).
- **Nota:** en **geobase producción** desactivarla te volverá a bloquear el acceso
  hasta reactivarla.

---

### Flujo: Perfil y equipos

#### 3.8 Editar tu perfil

- **Objetivo:** actualizar tu nombre/correo (y foto en dte-spp).
- **URL:** `/user/profile` (Ruta `profile.show`) → **"Información del perfil"**.
- **Pasos:** edita **nombre** y/o **correo** → **Guardar**.
- **Resultado:** datos actualizados.
- **Diferencia:** en **dte-spp** puedes subir **foto de perfil**
  (`profilePhotos` habilitado); en **geobase no** (deshabilitado).

#### 3.9 Cambiar de equipo activo (Unidad Responsable)

- **Objetivo:** trabajar en el contexto de otra UR a la que perteneces.
- **Dónde:** menú de usuario (arriba a la derecha) → selector de equipo.
- **Pasos:** elige el equipo en la lista → se recarga con ese contexto activo.
- **Resultado:** los datos que ves (programas, beneficiarios, reportes) quedan
  **acotados a la UR activa**. Si solo perteneces a un equipo, no verás el selector.
- **Importante:** el **scoping por equipo** es central. En dte-spp, los usuarios
  no-admin solo ven los programas de su `currentTeam`. En geobase, los roles
  confinados (`operador`, `admin_dependencia`) solo ven beneficiarios/inscripciones
  de su equipo; los globales (`sysadmin`, `analista_global`) ven todo.

#### 3.10 Ver/gestionar el equipo (solo si tu rol lo permite)

- **geobase:** sidebar → **Administración → Equipo y usuarios** (`teams.show`).
- **dte-spp:** la invitación/gestión de usuarios vive en **Administración → Usuarios**
  (`admin.users`) y requiere el permiso `invitar_usuarios`. La gestión de equipos de
  Jetstream existe pero las altas de usuario operativas pasan por ese módulo admin.

#### 3.11 Cerrar sesión

- **Dónde:** menú de usuario → **"Cerrar sesión"** (Ruta `logout`).
- **Resultado:** vuelves a `/login`.

---

## 4. Qué NO puede hacer (acciones bloqueadas o no visibles)

Como "acceso general", la mayoría de las funciones de negocio están **gated por
permiso**: si tu rol no las tiene, **ni siquiera aparecen** en el sidebar. Si llegas
a una URL directa sin permiso, recibes **403**. Concretamente, sin el permiso
correspondiente NO podrás:

- **Crear/editar programas o la MIR** → requiere `crear_programa` / `editar_mir`
  (grupo *Planeación* oculto).
- **Capturar o revisar avances** → `capturar_avance` / `revisar_avance`
  (grupo *Seguimiento* oculto).
- **Ver/gestionar presupuesto** → `ver_datos_financieros` / `gestionar_presupuesto`
  (grupo *Presupuesto* oculto).
- **Ver/gestionar/validar sustento jurídico** → `ver_sustento_legal` /
  `gestionar_sustento_legal` / `validar_sustento_legal` (grupo *Jurídico* oculto).
- **Gestionar catálogos PED/alineación** → `gestionar_catalogos` (grupo *Catálogos*).
- **Acceder a reportes de evaluación / ASM / evaluación externa** →
  `exportar_reportes`, `ver_asm`, `ver_evaluacion_externa`.
- **Ver/aprobar datasets de transparencia** → `ver_datasets_abiertos`,
  `gestionar_dataset_abierto`, `aprobar_datos_abiertos` (este último **solo** rol RDA;
  está **segregado** y no se otorga a admin automáticamente).
- **Invitar usuarios / ver Monitor IA / Auditoría** → `invitar_usuarios` /
  `administrar_usuarios` (grupo *Administración* oculto).
- **Ver padrón / exportar padrón SHCP** → `ver_padron` / `exportar_padron_shcp`.
- En **geobase**, sin `beneficiary.view` / `enrollment.view` no ves Padrón ni
  Inscripciones; los roles confinados nunca ven datos de **otros** equipos.

Además, por diseño de "acceso general":

- **No puedes registrarte solo** (registro deshabilitado; alta solo por invitación).
- **No puedes eliminar tu cuenta en dte-spp** (deshabilitado por auditoría).
- **No puedes ver datos de equipos/UR a los que no perteneces** (scoping por team).
- **No puedes saltarte la 2FA en geobase producción.**

---

## 5. Errores y validaciones comunes

- **"Estas credenciales no coinciden con nuestros registros."** — correo o contraseña
  incorrectos en `/login`. Verifica el correo; si la olvidaste, usa 3.3.
- **No existe botón "Crear cuenta".** — es correcto: el registro está deshabilitado;
  pide invitación a un administrador.
- **Redirección forzada a tu perfil con aviso de 2FA (geobase prod).** — debes
  **activar la 2FA** (3.5) antes de poder navegar. Solo te dejará ver perfil, activar
  2FA y cerrar sesión hasta entonces.
- **"Código inválido" al confirmar/usar 2FA.** — el código de la app cambia cada ~30s;
  asegúrate de que la **hora del teléfono** esté sincronizada y captura el código vigente.
  Si perdiste el teléfono, usa un **código de recuperación**.
- **Se te pide confirmar contraseña** al activar/desactivar 2FA o en acciones
  sensibles del perfil — es una **confirmación de seguridad** normal (Fortify
  `confirmPassword`).
- **Enlace de recuperación expirado/ inválido.** — los enlaces de
  restablecimiento caducan; vuelve a solicitar uno (3.3).
- **La contraseña nueva no se acepta.** — debe cumplir la política mínima y la
  **confirmación** debe coincidir exactamente.
- **403 / "No autorizado" al abrir una URL.** — tu rol no tiene el permiso de esa
  área (ver sección 4). Si crees que deberías tenerlo, contacta al administrador.
- **Pantalla "vacía" o con menos opciones que un compañero.** — no es un error: el
  sidebar y el dashboard se **filtran por permisos**; ves solo lo de tu rol.
- **Ves datos de un solo equipo y esperabas más.** — revisa el **equipo activo**
  (selector arriba a la derecha) y cámbialo (3.9). El alcance siempre es la UR activa,
  salvo roles globales.

---

### Referencias técnicas (para soporte)

- Autenticación: Jetstream + Fortify + Livewire 3 + Teams (ambos repos).
- Funciones activas dte-spp: `resetPasswords`, `updateProfileInformation`,
  `updatePasswords`, `twoFactorAuthentication` (gated por `FORTIFY_TWO_FACTOR`),
  `profilePhotos`, `teams`. Deshabilitadas: `registration`, `accountDeletion`,
  `emailVerification`.
- Funciones activas geobase: igual base + `api`, `accountDeletion`; **2FA obligatoria
  en prod** vía middleware `RequireTwoFactorAuthentication` (escape en `local`/`testing`).
- Middleware común protegido: `auth:sanctum`, `verified`; geobase añade
  `team.context`, `activated`, `require.2fa`.
- Seeders de verdad de permisos: dte-spp `database/seeders/RolesAndPermissionsSeeder.php`
  (+ seeders de dominio Transparencia/Asm/Juridico/Presupuesto/Padron);
  geobase `database/seeders/RolesAndPermissionsSeeder.php`.
