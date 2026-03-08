# Sprint 12: Jetstream Customization — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Personalizar Jetstream para contexto gubernamental: auth split-screen, registro deshabilitado, perfil adaptado, terminología de URs, foto de perfil, y restricciones de auditoría.

**Architecture:** Modificar vistas Blade existentes de Jetstream, configs de Fortify/Jetstream, topbar/sidebar, y crear traducciones. No se crean nuevos modelos ni migraciones.

**Tech Stack:** Laravel 12, Jetstream Livewire 3, Tailwind CSS, Alpine.js, Spatie Permission

---

### Task 1: Config — deshabilitar registro y eliminar cuenta, activar fotos

**Files:**
- Modify: `config/fortify.php`
- Modify: `config/jetstream.php`
- Test: `tests/Feature/JetstreamCustomizationTest.php`

**Step 1: Escribir tests**

Crear `tests/Feature/JetstreamCustomizationTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JetstreamCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_register_route_is_disabled(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_register_post_is_disabled(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertStatus(404);
    }

    public function test_login_page_renders(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Iniciar Sesión');
    }

    public function test_login_page_does_not_show_register_link(): void
    {
        $response = $this->get('/login');
        $response->assertDontSee('register');
    }

    public function test_forgot_password_renders_in_spanish(): void
    {
        $response = $this->get('/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Recuperar Contraseña');
    }

    public function test_profile_does_not_show_delete_account(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/user/profile');
        $response->assertDontSee('Delete Account');
        $response->assertDontSee('Eliminar Cuenta');
    }

    public function test_profile_shows_spanish_labels(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/user/profile');
        $response->assertSee('Información Personal');
        $response->assertSee('Seguridad');
    }

    public function test_topbar_shows_role_and_ur(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertSee('Mi Perfil');
        $response->assertSee('Cerrar Sesión');
    }

    public function test_team_settings_shows_ur_terminology(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/teams/' . $user->currentTeam->id);
        $response->assertSee('Unidad Responsable');
    }

    public function test_operador_cannot_see_create_team(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertDontSee('Crear Unidad Responsable');
    }
}
```

**Step 2: Correr tests para verificar que fallan**

Run: `./vendor/bin/sail artisan test --filter=JetstreamCustomizationTest 2>&1 | tail -20`
Expected: FAIL (varias)

**Step 3: Modificar configs**

En `config/fortify.php`, comentar `Features::registration()`:

```php
'features' => [
    // Features::registration(), // Deshabilitado — registro solo por invitación (futuro sprint)
    Features::resetPasswords(),
    // Features::emailVerification(),
    Features::updateProfileInformation(),
    Features::updatePasswords(),
    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]),
],
```

En `config/jetstream.php`, activar fotos y quitar eliminación de cuenta:

```php
'features' => [
    // Features::termsAndPrivacyPolicy(),
    Features::profilePhotos(),
    // Features::api(),
    Features::teams(['invitations' => true]),
    // Features::accountDeletion(), // Deshabilitado — auditoría requiere conservar cuentas
],
```

**Step 4: Correr tests de config**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_register_route_is_disabled|JetstreamCustomizationTest::test_register_post_is_disabled"`
Expected: 2 PASS

**Step 5: Commit**

```bash
git add config/fortify.php config/jetstream.php tests/Feature/JetstreamCustomizationTest.php
git commit -m "feat(S12): disable registration, enable profile photos, remove account deletion"
```

---

### Task 2: Rediseño del guest layout — split screen

**Files:**
- Modify: `resources/views/layouts/guest.blade.php`

**Step 1: Reescribir el layout guest con split screen**

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen lg:grid lg:grid-cols-2">
            {{-- Left panel — branding (hidden on mobile) --}}
            <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-emerald-900 via-emerald-800 to-emerald-950 text-white p-12">
                <div>
                    <div class="flex items-center space-x-3">
                        <x-application-mark class="block h-10 w-auto" />
                        <span class="text-2xl font-bold tracking-tight">SPP</span>
                    </div>
                </div>

                <div class="space-y-4">
                    <h1 class="text-4xl font-bold leading-tight">
                        Sistema de Planeación<br>y Programación
                    </h1>
                    <p class="text-emerald-200 text-lg">
                        Ejercicio Fiscal 2026
                    </p>
                    <div class="h-1 w-16 bg-emerald-400 rounded"></div>
                    <p class="text-emerald-300 text-sm max-w-md">
                        Plataforma integral para la gestión de programas presupuestarios, indicadores y seguimiento del desempeño gubernamental.
                    </p>
                </div>

                <p class="text-emerald-400 text-xs">
                    &copy; {{ date('Y') }} — Dirección de Tecnología y Evaluación
                </p>
            </div>

            {{-- Right panel — form --}}
            <div class="flex flex-col justify-center px-6 py-12 lg:px-16 bg-gray-50">
                {{-- Mobile logo --}}
                <div class="lg:hidden flex justify-center mb-8">
                    <div class="flex items-center space-x-2">
                        <x-application-mark class="block h-8 w-auto" />
                        <span class="text-lg font-bold text-gray-900">SPP 2026</span>
                    </div>
                </div>

                <div class="w-full max-w-md mx-auto">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
```

**Step 2: Verificar visualmente**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_login_page_renders"` (aún fallará por textos en español — se arregla en Task 3)

**Step 3: Commit**

```bash
git add resources/views/layouts/guest.blade.php
git commit -m "feat(S12): redesign guest layout with split-screen branding"
```

---

### Task 3: Traducir vistas de auth al español

**Files:**
- Modify: `resources/views/auth/login.blade.php`
- Modify: `resources/views/auth/forgot-password.blade.php`
- Modify: `resources/views/auth/reset-password.blade.php`
- Modify: `resources/views/auth/two-factor-challenge.blade.php`
- Modify: `resources/views/auth/confirm-password.blade.php`

**Step 1: Reescribir login.blade.php**

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Iniciar Sesión</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tus credenciales para acceder al sistema.</p>
    </div>

    <x-validation-errors class="mt-4" />

    @session('status')
        <div class="mt-4 font-medium text-sm text-green-600">
            {{ $value }}
        </div>
    @endsession

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <div>
            <x-label for="password" value="Contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center">
                <x-checkbox id="remember_me" name="remember" />
                <span class="ms-2 text-sm text-gray-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm text-brand hover:text-brand-dark font-medium" href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-button class="w-full justify-center">
            Iniciar Sesión
        </x-button>
    </form>
</x-guest-layout>
```

**Step 2: Reescribir forgot-password.blade.php**

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Recuperar Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tu correo electrónico y te enviaremos un enlace para restablecer tu contraseña.</p>
    </div>

    @session('status')
        <div class="mt-4 font-medium text-sm text-green-600">
            {{ $value }}
        </div>
    @endsession

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
        </div>

        <x-button class="w-full justify-center">
            Enviar Enlace de Recuperación
        </x-button>

        <div class="text-center">
            <a href="{{ route('login') }}" class="text-sm text-brand hover:text-brand-dark font-medium">
                Volver al inicio de sesión
            </a>
        </div>
    </form>
</x-guest-layout>
```

**Step 3: Reescribir reset-password.blade.php**

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Restablecer Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Ingresa tu nueva contraseña para continuar.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-label for="email" value="Correo electrónico" />
            <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
        </div>

        <div>
            <x-label for="password" value="Nueva contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="new-password" />
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmar contraseña" />
            <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <x-button class="w-full justify-center">
            Restablecer Contraseña
        </x-button>
    </form>
</x-guest-layout>
```

**Step 4: Reescribir two-factor-challenge.blade.php**

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Autenticación de Dos Factores</h2>
    </div>

    <div x-data="{ recovery: false }">
        <p class="mt-2 text-sm text-gray-600" x-show="! recovery">
            Confirma el acceso a tu cuenta ingresando el código de autenticación de tu aplicación.
        </p>

        <p class="mt-2 text-sm text-gray-600" x-cloak x-show="recovery">
            Confirma el acceso a tu cuenta ingresando uno de tus códigos de recuperación de emergencia.
        </p>

        <x-validation-errors class="mt-4" />

        <form method="POST" action="{{ route('two-factor.login') }}" class="mt-6 space-y-4">
            @csrf

            <div x-show="! recovery">
                <x-label for="code" value="Código" />
                <x-input id="code" class="block mt-1 w-full" type="text" inputmode="numeric" name="code" autofocus x-ref="code" autocomplete="one-time-code" />
            </div>

            <div x-cloak x-show="recovery">
                <x-label for="recovery_code" value="Código de Recuperación" />
                <x-input id="recovery_code" class="block mt-1 w-full" type="text" name="recovery_code" x-ref="recovery_code" autocomplete="one-time-code" />
            </div>

            <div class="flex items-center justify-between">
                <button type="button" class="text-sm text-brand hover:text-brand-dark font-medium"
                        x-show="! recovery"
                        x-on:click="recovery = true; $nextTick(() => { $refs.recovery_code.focus() })">
                    Usar código de recuperación
                </button>

                <button type="button" class="text-sm text-brand hover:text-brand-dark font-medium"
                        x-cloak x-show="recovery"
                        x-on:click="recovery = false; $nextTick(() => { $refs.code.focus() })">
                    Usar código de autenticación
                </button>

                <x-button>
                    Iniciar Sesión
                </x-button>
            </div>
        </form>
    </div>
</x-guest-layout>
```

**Step 5: Reescribir confirm-password.blade.php**

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Confirmar Contraseña</h2>
        <p class="mt-2 text-sm text-gray-600">Esta es un área segura. Por favor confirma tu contraseña antes de continuar.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="password" value="Contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" autofocus />
        </div>

        <x-button class="w-full justify-center">
            Confirmar
        </x-button>
    </form>
</x-guest-layout>
```

**Step 6: Correr tests de auth**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_login_page_renders|JetstreamCustomizationTest::test_login_page_does_not_show_register_link|JetstreamCustomizationTest::test_forgot_password_renders_in_spanish"`
Expected: 3 PASS

**Step 7: Commit**

```bash
git add resources/views/auth/login.blade.php resources/views/auth/forgot-password.blade.php resources/views/auth/reset-password.blade.php resources/views/auth/two-factor-challenge.blade.php resources/views/auth/confirm-password.blade.php
git commit -m "feat(S12): translate auth views to Spanish with new layout"
```

---

### Task 4: Rediseño del dropdown de perfil en topbar

**Files:**
- Modify: `resources/views/components/ui/topbar.blade.php`

**Step 1: Reescribir el dropdown**

Reemplazar el contenido completo del archivo:

```blade
@props(['user' => null])

<header class="sticky top-0 z-30 flex items-center justify-between h-16 px-4 sm:px-6 bg-white border-b border-gray-200">
    {{-- Left: mobile hamburger + breadcrumb --}}
    <div class="flex items-center space-x-4">
        {{-- Mobile hamburger --}}
        <button @click="mobileOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Breadcrumb slot --}}
        @if(isset($breadcrumb))
            <nav class="hidden sm:flex text-sm text-gray-500 space-x-1">
                {{ $breadcrumb }}
            </nav>
        @endif
    </div>

    {{-- Right: user dropdown --}}
    <div class="flex items-center">
        @if($user)
            <x-dropdown align="right" width="60">
                <x-slot name="trigger">
                    <button class="flex items-center space-x-3 text-sm text-gray-600 hover:text-gray-900 focus:outline-none transition">
                        <x-ui.avatar :name="$user->name" :src="$user->profile_photo_url ?? null" size="sm" />
                        <div class="hidden md:block text-left">
                            <div class="font-medium text-gray-700">{{ $user->name }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $user->roles->first()?->name ?? 'usuario' }} · {{ $user->currentTeam?->clave_ur ?? $user->currentTeam?->name ?? '' }}
                            </div>
                        </div>
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    {{-- User info header --}}
                    <div class="px-4 py-3 border-b border-gray-100">
                        <div class="flex items-center space-x-3">
                            <x-ui.avatar :name="$user->name" :src="$user->profile_photo_url ?? null" size="md" />
                            <div>
                                <div class="font-medium text-gray-900">{{ $user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $user->email }}</div>
                            </div>
                        </div>
                    </div>

                    <x-dropdown-link href="{{ route('profile.show') }}">
                        Mi Perfil
                    </x-dropdown-link>

                    <div class="border-t border-gray-100"></div>

                    <form method="POST" action="{{ route('logout') }}" x-data>
                        @csrf
                        <x-dropdown-link href="{{ route('logout') }}" @click.prevent="$root.submit();">
                            Cerrar Sesión
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        @endif
    </div>
</header>
```

**Step 2: Correr tests del topbar**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_topbar_shows_role_and_ur"`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/views/components/ui/topbar.blade.php
git commit -m "feat(S12): redesign user dropdown with role, UR, and Spanish labels"
```

---

### Task 5: Adaptar vista de perfil al layout con sidebar

**Files:**
- Modify: `resources/views/profile/show.blade.php`

**Step 1: Reescribir profile/show.blade.php**

```blade
<x-app-layout>
    <x-page.container title="Mi Perfil" subtitle="Configuración de tu cuenta">

        {{-- Información Personal --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Información Personal</h3>
            <p class="text-sm text-gray-500 mb-6">Actualiza tu nombre, correo electrónico y foto de perfil.</p>
            @livewire('profile.update-profile-information-form')
        </div>

        {{-- Seguridad --}}
        <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">Seguridad</h3>
            <p class="text-sm text-gray-500 mb-6">Gestiona tu contraseña, autenticación de dos factores y sesiones activas.</p>

            @if (Laravel\Fortify\Features::enabled(Laravel\Fortify\Features::updatePasswords()))
                <div class="mb-8">
                    @livewire('profile.update-password-form')
                </div>
            @endif

            @if (Laravel\Fortify\Features::canManageTwoFactorAuthentication())
                <div class="mb-8 pt-6 border-t border-gray-200">
                    @livewire('profile.two-factor-authentication-form')
                </div>
            @endif

            <div class="pt-6 border-t border-gray-200">
                @livewire('profile.logout-other-browser-sessions-form')
            </div>
        </div>

        {{-- Mi Unidad Responsable --}}
        @if(Auth::user()->currentTeam)
            <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-1">Mi Unidad Responsable</h3>
                <p class="text-sm text-gray-500 mb-6">Información de la unidad responsable a la que perteneces.</p>
                @livewire('teams.update-team-name-form', ['team' => Auth::user()->currentTeam])
            </div>
        @endif

        {{-- Servidores Públicos (solo admin/planeador) --}}
        @if(Auth::user()->currentTeam)
            @can('gestionar_catalogos')
                <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">Servidores Públicos</h3>
                    <p class="text-sm text-gray-500 mb-6">Gestiona los miembros de tu unidad responsable.</p>
                    @livewire('teams.team-member-manager', ['team' => Auth::user()->currentTeam])
                </div>
            @endcan
        @endif

        {{-- NO renderizar delete-user-form — oculto por auditoría --}}

    </x-page.container>
</x-app-layout>
```

**Step 2: Correr tests**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_profile_does_not_show_delete_account|JetstreamCustomizationTest::test_profile_shows_spanish_labels"`
Expected: 2 PASS

**Step 3: Commit**

```bash
git add resources/views/profile/show.blade.php
git commit -m "feat(S12): adapt profile view to sidebar layout with Spanish sections"
```

---

### Task 6: Traducir vistas de perfil al español

**Files:**
- Modify: `resources/views/profile/update-profile-information-form.blade.php`
- Modify: `resources/views/profile/update-password-form.blade.php`
- Modify: `resources/views/profile/two-factor-authentication-form.blade.php`
- Modify: `resources/views/profile/logout-other-browser-sessions-form.blade.php`

**Step 1: Leer cada archivo y traducir todos los textos**

Para cada archivo, reemplazar todas las cadenas `__('...')` en inglés por texto en español directo:

**update-profile-information-form.blade.php** — traducciones clave:
- "Profile Information" → ya no necesario (movido al show.blade.php)
- "Photo" → "Foto"
- "Select A New Photo" → "Seleccionar Nueva Foto"
- "Remove Photo" → "Quitar Foto"
- "Name" → "Nombre"
- "Email" → "Correo Electrónico"
- "Saved." → "Guardado."
- "Save" → "Guardar"

**update-password-form.blade.php** — traducciones clave:
- "Update Password" → "Cambiar Contraseña"
- "Ensure your account is using..." → "Asegúrate de que tu cuenta use una contraseña larga y aleatoria para mayor seguridad."
- "Current Password" → "Contraseña Actual"
- "New Password" → "Nueva Contraseña"
- "Confirm Password" → "Confirmar Contraseña"
- "Saved." → "Guardado."
- "Save" → "Guardar"

**two-factor-authentication-form.blade.php** — traducciones clave:
- "Two Factor Authentication" → "Autenticación de Dos Factores"
- "Add additional security..." → "Agrega seguridad adicional a tu cuenta usando autenticación de dos factores."
- "You have not enabled..." → "No has habilitado la autenticación de dos factores."
- "When two factor authentication is enabled..." → "Cuando la autenticación de dos factores está habilitada, se te pedirá un token seguro y aleatorio durante la autenticación. Puedes obtener este token desde la aplicación Google Authenticator de tu teléfono."
- "You have enabled..." / "Finish enabling..." → "Has habilitado..." / "Finaliza la habilitación..."
- "Store these recovery codes..." → "Guarda estos códigos de recuperación en un gestor de contraseñas seguro. Se pueden usar para recuperar el acceso a tu cuenta si pierdes tu dispositivo de autenticación de dos factores."
- "Enable" → "Habilitar"
- "Disable" → "Deshabilitar"
- "Regenerate Recovery Codes" → "Regenerar Códigos de Recuperación"
- "Show Recovery Codes" → "Mostrar Códigos de Recuperación"
- "Confirm" → "Confirmar"
- "Cancel" → "Cancelar"
- "Code" → "Código"

**logout-other-browser-sessions-form.blade.php** — traducciones clave:
- "Browser Sessions" → "Sesiones del Navegador"
- "Manage and log out your active sessions..." → "Gestiona y cierra las sesiones activas en otros navegadores y dispositivos."
- "If necessary, you may log out..." → "Si es necesario, puedes cerrar todas las demás sesiones del navegador en todos tus dispositivos. Algunas de tus sesiones recientes se muestran a continuación; sin embargo, esta lista puede no ser exhaustiva. Si crees que tu cuenta ha sido comprometida, también deberías actualizar tu contraseña."
- "This device" → "Este dispositivo"
- "Last active" → "Última actividad"
- "Log Out Other Browser Sessions" → "Cerrar Otras Sesiones"
- "Done." → "Listo."
- "Please enter your password..." → "Ingresa tu contraseña para confirmar que deseas cerrar las demás sesiones del navegador."
- "Password" → "Contraseña"
- "Cancel" → "Cancelar"

**NOTA:** Cada uno de estos archivos es un Livewire component view. Debes leer el archivo completo, identificar todas las cadenas `__('...')` o texto en inglés, y reemplazar por español. Mantener la estructura de Jetstream intacta — solo cambiar textos.

**Step 2: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=JetstreamCustomizationTest`
Expected: Todos PASS

**Step 3: Commit**

```bash
git add resources/views/profile/
git commit -m "feat(S12): translate profile views to Spanish"
```

---

### Task 7: Traducir vistas de teams y aplicar terminología UR

**Files:**
- Modify: `resources/views/teams/show.blade.php`
- Modify: `resources/views/teams/create.blade.php`
- Modify: `resources/views/teams/create-team-form.blade.php`
- Modify: `resources/views/teams/update-team-name-form.blade.php`
- Modify: `resources/views/teams/team-member-manager.blade.php`
- Modify: `resources/views/teams/delete-team-form.blade.php`

**Step 1: Actualizar teams/show.blade.php**

```blade
<x-app-layout>
    <x-page.container title="Configuración de la Unidad" subtitle="{{ $team->name }}">

        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @livewire('teams.update-team-name-form', ['team' => $team])
        </div>

        <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
            @livewire('teams.team-member-manager', ['team' => $team])
        </div>

        @if (Gate::check('delete', $team) && ! $team->personal_team)
            <div class="bg-white rounded-lg border border-gray-200 p-6 mt-6">
                @livewire('teams.delete-team-form', ['team' => $team])
            </div>
        @endif
    </x-page.container>
</x-app-layout>
```

**Step 2: Actualizar teams/create.blade.php**

```blade
<x-app-layout>
    <x-page.container title="Crear Unidad Responsable" subtitle="Registrar una nueva UR en el sistema">
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            @livewire('teams.create-team-form')
        </div>
    </x-page.container>
</x-app-layout>
```

**Step 3: Traducir los demás archivos de teams/**

Para cada archivo, reemplazar textos clave:

**create-team-form.blade.php:**
- "Team Details" → "Datos de la Unidad Responsable"
- "Create a new team..." → "Crea una nueva unidad responsable para gestionar programas presupuestarios y colaborar con otros servidores públicos."
- "Team Owner" → "Titular"
- "Team Name" → "Nombre de la Unidad"
- "Create" → "Crear"

**update-team-name-form.blade.php:**
- "Team Name" → "Nombre de la Unidad Responsable"
- "The team's name..." → "El nombre de la unidad responsable y la información del titular."
- "Team Owner" → "Titular"
- "Save" → "Guardar"
- "Saved." → "Guardado."

**team-member-manager.blade.php:**
- "Add Team Member" → "Agregar Servidor Público"
- "Add a new team member..." → "Agrega un nuevo servidor público a tu unidad responsable, permitiéndole colaborar contigo."
- "email address" → "correo electrónico"
- "Please provide the email address..." → "Ingresa el correo electrónico de la persona que deseas agregar a esta unidad responsable."
- "Role" → "Rol"
- "Add" → "Agregar"
- "Pending Team Invitations" → "Invitaciones Pendientes"
- "These people have been invited..." → "Estas personas han sido invitadas a tu unidad responsable y se les ha enviado un correo de invitación."
- "Cancel" → "Cancelar"
- "Team Members" → "Servidores Públicos"
- "Leave" → "Salir"
- "Remove" → "Remover"
- "Manage Role" → "Gestionar Rol"
- "Leave Team" → "Salir de la Unidad"
- "Are you sure you would like to leave this team?" → "¿Estás seguro de que deseas salir de esta unidad responsable?"
- "Remove Team Member" → "Remover Servidor Público"
- "Are you sure you would like to remove this person..." → "¿Estás seguro de que deseas remover a esta persona de la unidad responsable?"
- "Save" → "Guardar"

**delete-team-form.blade.php:**
- "Delete Team" → "Eliminar Unidad Responsable"
- "Permanently delete this team." → "Eliminar permanentemente esta unidad responsable."
- "Once a team is deleted..." → "Una vez que una unidad responsable es eliminada, todos sus recursos y datos serán eliminados permanentemente. Antes de eliminarla, descarga cualquier dato o información que desees conservar."
- "Delete Team" (button) → "Eliminar Unidad"
- "Are you sure you want to delete this team?..." → "¿Estás seguro de que deseas eliminar esta unidad responsable? Una vez eliminada, todos sus recursos y datos serán eliminados permanentemente."
- "Cancel" → "Cancelar"

**Step 4: Correr tests**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_team_settings_shows_ur_terminology"`
Expected: PASS

**Step 5: Commit**

```bash
git add resources/views/teams/
git commit -m "feat(S12): translate team views to Spanish with UR terminology"
```

---

### Task 8: Restringir creación de UR para operadores + actualizar topbar/sidebar

**Files:**
- Modify: `resources/views/components/ui/topbar.blade.php` (quitar team switcher del dropdown — ya hecho en Task 4)
- Modify: `resources/views/components/layout/sidebar-nav.blade.php` (no necesita cambios — ya no tiene link de crear team)
- Modify: `resources/views/navigation-menu.blade.php` (si tiene link de crear team, ocultarlo para operador)
- Test: existente en `JetstreamCustomizationTest`

**Step 1: Verificar que navigation-menu.blade.php oculte crear team para operador**

Leer `resources/views/navigation-menu.blade.php` y verificar si tiene link de "Create New Team". Si lo tiene, envolverlo con:

```blade
@if(Auth::user()->hasRole('admin') || Auth::user()->hasRole('planeador'))
    {{-- Create New Team link --}}
@endif
```

Nota: como el layout principal usa sidebar + topbar (no navigation-menu), este archivo probablemente ya no se usa. Verificar si hay algún lugar que renderice "Crear Unidad Responsable" o "Create New Team".

**Step 2: Correr test**

Run: `./vendor/bin/sail artisan test --filter="JetstreamCustomizationTest::test_operador_cannot_see_create_team"`
Expected: PASS

**Step 3: Commit**

```bash
git add resources/views/
git commit -m "feat(S12): restrict team creation to admin and planeador roles"
```

---

### Task 9: Actualizar tests existentes (LayoutTest) y correr suite completa

**Files:**
- Modify: `tests/Feature/LayoutTest.php`

**Step 1: Actualizar tests que verifican textos en inglés**

El `LayoutTest` tiene:
- `test_topbar_renders_with_user_dropdown` → busca "Profile" y "Log Out" — cambiar a "Mi Perfil" y "Cerrar Sesión"

```php
public function test_topbar_renders_with_user_dropdown(): void
{
    $user = $this->createUserWithRole('admin');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertSee('Mi Perfil');
    $response->assertSee('Cerrar Sesión');
}
```

**Step 2: Correr suite completa**

Run: `./vendor/bin/sail artisan test 2>&1 | tail -10`
Expected: Todos pasan (473+ baseline + nuevos tests de S12), 0 fallos

**Step 3: Commit**

```bash
git add tests/Feature/LayoutTest.php
git commit -m "fix(S12): update LayoutTest for Spanish topbar labels"
```

---

### Task 10: Verificación final y cleanup

**Files:**
- Ningún cambio — solo verificación

**Step 1: Correr suite completa de tests**

Run: `./vendor/bin/sail artisan test 2>&1 | tail -10`
Expected: Todos pasan

**Step 2: Verificar build de Vite**

Run: `./vendor/bin/sail npm run build 2>&1 | tail -5`
Expected: Build exitoso

**Step 3: Verificar que el storage link existe (para fotos de perfil)**

Run: `./vendor/bin/sail artisan storage:link 2>&1`
Expected: Link creado o ya existe

**Step 4: Usar superpowers:finishing-a-development-branch para completar**
