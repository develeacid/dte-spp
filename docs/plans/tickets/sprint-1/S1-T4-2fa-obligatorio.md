# Plan: S1-T4 — Implementar 2FA Obligatorio (Con DX Optimizada)

**Ticket:** S1-T4
**Tipo:** feat
**Rama:** `feat/S1-T4-2fa-obligatorio`
**Sprint:** 1 — Identidad y Aislamiento
**Depende de:** S1-T1 (Jetstream instalado)

---

## Contexto

Jetstream incluye soporte nativo de 2FA (TOTP) mediante el feature `TwoFactorAuthentication`. Por defecto, 2FA es **opcional**. Este ticket lo hace **obligatorio**: cualquier usuario que no tenga 2FA configurado es redirigido a la pantalla de configuración antes de acceder a cualquier ruta protegida.

Sin embargo, para no afectar la experiencia de desarrollo (DX), esta restricción se **desactivará automáticamente** en los entornos `local` y `testing`. Esto evita que los desarrolladores tengan que configurar 2FA cada vez que hacen un `migrate:fresh` o corren las pruebas automatizadas (tests).

Jetstream genera un esqueleto para el middleware `EnsureUserHasTwoFactorEnabled` pero no lo activa por defecto — es necesario crearlo y registrarlo con los "Escape Hatches" necesarios.

---

## Pre-requisitos

- S1-T1 completado (Jetstream instalado con `--teams`)

---

## Pasos

### 1. Verificar que el feature TwoFactor está habilitado en Jetstream

Abrir `config/jetstream.php` y confirmar que `TwoFactorAuthentication` está en el array `features`:

```php
'features' => [
    Features::profilePhotos(),
    Features::api(),
    Features::teams(['invitations' => false]),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

Si `TwoFactorAuthentication` no está implementado de esta manera, agregarlo o asegurarse de configurarlo así.

### 2. Crear el middleware de 2FA obligatorio con "Escape Hatches"

```bash
sail artisan make:middleware RequireTwoFactorAuthentication
```

Editar `app/Http/Middleware/RequireTwoFactorAuthentication.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Fortify\Features;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactorAuthentication
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Si no hay usuario, seguir.
        if (! $user) {
            return $next($request);
        }

        // 2. ESCAPE HATCH para desarrollo y pruebas
        // Permite trabajar ágilmente en local/testing sin configurar 2FA cada vez.
        // En producción (APP_ENV=production), esta condición será false y se ejecutará la validación.
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        // 3. Validación de 2FA
        if (Features::enabled(Features::twoFactorAuthentication())
            && ! $user->hasEnabledTwoFactorAuthentication()
        ) {
            // Permitir rutas necesarias para configurar el 2FA y para salir (Logout)
            // Fortify/Jetstream usan rutas como 'profile.show', 'user-two-factor.*', etc.
            // IMPORTANTE: 'logout' debe estar permitido para no atrapar al usuario en un bucle.
            $allowedRoutes = [
                'profile.show',
                'user-profile-information.update',
                'user-two-factor.enable',
                'user-two-factor.confirm',
                'logout'
            ];
            
            if ($request->routeIs($allowedRoutes)) {
                return $next($request);
            }

            return redirect()->route('profile.show')
                ->with('flash.banner', 'Por seguridad, debes activar la autenticación de dos factores para acceder al sistema.')
                ->with('flash.bannerStyle', 'warning');
        }

        return $next($request);
    }
}
```

### 3. Registrar el middleware en el kernel de la aplicación

En Laravel 12, los middlewares se registran en `bootstrap/app.php`. Abrir ese archivo y agregar el middleware al grupo `web`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\RequireTwoFactorAuthentication::class,
    ]);
})
```

### 4. Preparar Factories para Tests (Futuro)

Aunque el middleware se salta automáticamente en `testing`, es una buena práctica y requerimiento futuro crear un estado en el `UserFactory` para simular un 2FA activo en tests específicos de seguridad en próximos sprints.

En `database/factories/UserFactory.php`:

```php
public function withTwoFactor()
{
    return $this->state(function (array $attributes) {
        return [
            'two_factor_secret' => encrypt('test-secret'),
            'two_factor_confirmed_at' => now(),
        ];
    });
}
```

### 5. Verificar el flujo completo de forma Manual

1. Cambiar `.env` a `APP_ENV=local`. Verificar que puedes navegar por rutas protegidas (como `/dashboard`) sin problemas y sin tener 2FA activado.
2. Cambiar `.env` a `APP_ENV=production` (o entorno simulado restando `local` del check). Intentar acceder al dashboard sin 2FA — debe redirigir a `/user/profile` con el mensaje de advertencia.
3. Verificar que la ruta `logout` funciona correctamente incluso sin 2FA.
4. En el perfil, activar 2FA:
   - Click en "Enable Two Factor Authentication"
   - Escanear el código QR con Google Authenticator o Authy
   - Confirmar con un código válido
5. Después de activar 2FA, el acceso al dashboard debe funcionar sin restricciones.

---

## Criterios de aceptación

- [ ] Middleware `RequireTwoFactorAuthentication` creado y registrado.
- [ ] **Middleware omitido automáticamente en entornos `local` y `testing` (DX Optimization).**
- [ ] **Ruta `logout` accesible para usuarios sin 2FA (evita trampa de usuario en un bucle).**
- [ ] En entorno simulado de producción, un usuario sin 2FA es redirigido a la configuración de su perfil.
- [ ] Flujo de activación de QR funcional (QR + confirmación + códigos de respaldo generados).

---

## Notas para el equipo

> "Implementamos la restricción ahora para cumplir con la arquitectura de seguridad, pero añadimos un bypass local (`app()->environment(['local', 'testing'])`) para no afectar la velocidad de desarrollo. **Ojo:** Asegurarse de que en el ambiente de Staging/Producción el bypass no se active por dejar variables de desarrollo habilitadas (verificar `APP_ENV`)."
