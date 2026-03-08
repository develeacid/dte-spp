# Sprint 13: Registro de Usuarios por Invitación — Plan de Implementación

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Sistema cerrado de alta de usuarios: panel admin para invitar, enlace de activación con onboarding obligatorio (contraseña + 2FA), y gestión de estados (activo/inactivo).

**Architecture:** Migración para campos de invitación en `users`, nuevo permiso `invitar_usuarios`, Livewire components para panel admin, controlador para flujo de onboarding guest, middleware `EnsureUserIsActivated`, y Mailable para invitación.

**Tech Stack:** Laravel 12, Livewire 3, Fortify 2FA, Spatie Permission, Mailpit (dev)

---

### Task 1: Migración — campos de invitación en users

**Files:**
- Create: `database/migrations/2026_03_13_010000_add_invitation_fields_to_users_table.php`
- Modify: `app/Models/User.php`
- Modify: `database/factories/UserFactory.php`
- Test: `tests/Feature/UserInvitation/InvitationFieldsTest.php`

**Step 1: Crear la migración**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('email_verified_at');
            $table->boolean('active')->default(true)->after('activated_at');
            $table->string('invitation_token', 64)->nullable()->unique()->after('active');
            $table->timestamp('invitation_sent_at')->nullable()->after('invitation_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['activated_at', 'active', 'invitation_token', 'invitation_sent_at']);
        });
    }
};
```

**Step 2: Actualizar User model**

Agregar los nuevos campos a `$fillable`, `casts()`, y helpers:

En `app/Models/User.php`:

Cambiar `$fillable`:
```php
protected $fillable = [
    'name',
    'email',
    'password',
    'activated_at',
    'active',
    'invitation_token',
    'invitation_sent_at',
];
```

Agregar a `casts()`:
```php
protected function casts(): array
{
    return [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'activated_at' => 'datetime',
        'active' => 'boolean',
        'invitation_sent_at' => 'datetime',
    ];
}
```

Agregar helpers al final de la clase, antes del `}`:
```php
public function isActivated(): bool
{
    return $this->activated_at !== null;
}

public function isPendingActivation(): bool
{
    return $this->activated_at === null && $this->invitation_token !== null;
}

public function isInvitationExpired(): bool
{
    if (! $this->invitation_sent_at) {
        return true;
    }

    return $this->invitation_sent_at->addHours(72)->isPast();
}

public function scopeActive($query)
{
    return $query->where('active', true);
}

public function scopePendingActivation($query)
{
    return $query->whereNull('activated_at')->whereNotNull('invitation_token');
}
```

**Step 3: Actualizar UserFactory**

En `database/factories/UserFactory.php`, agregar al array `definition()`:
```php
'activated_at' => now(),
'active' => true,
'invitation_token' => null,
'invitation_sent_at' => null,
```

Agregar nuevo state al final de la clase:
```php
public function invited(): static
{
    return $this->state(fn (array $attributes) => [
        'password' => null,
        'activated_at' => null,
        'invitation_token' => \Illuminate\Support\Str::random(64),
        'invitation_sent_at' => now(),
    ]);
}
```

**Step 4: Escribir tests**

Crear `tests/Feature/UserInvitation/InvitationFieldsTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationFieldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_has_invitation_fields(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $this->assertTrue($user->isActivated());
        $this->assertFalse($user->isPendingActivation());
        $this->assertTrue($user->active);
    }

    public function test_invited_user_is_pending(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $this->assertFalse($user->isActivated());
        $this->assertTrue($user->isPendingActivation());
        $this->assertNotNull($user->invitation_token);
    }

    public function test_invitation_expires_after_72_hours(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(73),
        ]);

        $this->assertTrue($user->isInvitationExpired());
    }

    public function test_invitation_valid_within_72_hours(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(71),
        ]);

        $this->assertFalse($user->isInvitationExpired());
    }

    public function test_scope_pending_activation(): void
    {
        User::factory()->withPersonalTeam()->create(); // activo
        User::factory()->withPersonalTeam()->invited()->create(); // pendiente

        $this->assertCount(1, User::pendingActivation()->get());
    }

    public function test_scope_active(): void
    {
        User::factory()->withPersonalTeam()->create(['active' => true]);
        User::factory()->withPersonalTeam()->create(['active' => false]);

        $this->assertCount(1, User::active()->get());
    }
}
```

**Step 5: Correr migración y tests**

Run: `./vendor/bin/sail artisan migrate`
Run: `./vendor/bin/sail artisan test --filter=InvitationFieldsTest`
Expected: 6 PASS

**Step 6: Commit**

```bash
git add database/migrations/2026_03_13_010000_add_invitation_fields_to_users_table.php app/Models/User.php database/factories/UserFactory.php tests/Feature/UserInvitation/InvitationFieldsTest.php
git commit -m "feat(S13): add invitation fields to users table with model helpers"
```

---

### Task 2: Nuevo permiso `invitar_usuarios`

**Files:**
- Modify: `app/Enums/SystemPermission.php`
- Modify: `database/seeders/RolesAndPermissionsSeeder.php`
- Test: `tests/Feature/UserInvitation/InvitationPermissionTest.php`

**Step 1: Agregar el enum**

En `app/Enums/SystemPermission.php`, agregar antes del `}`:
```php
case INVITAR_USUARIOS = 'invitar_usuarios';
```

**Step 2: Asignar al admin en el seeder**

En `database/seeders/RolesAndPermissionsSeeder.php`, el admin ya recibe `Permission::all()`, así que automáticamente obtiene el nuevo permiso. No se necesita cambio explícito para admin.

Sin embargo, hay que re-ejecutar el seeder para que el permiso exista en la base de datos.

**Step 3: Escribir test**

Crear `tests/Feature/UserInvitation/InvitationPermissionTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitationPermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_admin_has_invitar_usuarios_permission(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $this->assertTrue($user->can('invitar_usuarios'));
    }

    public function test_planeador_does_not_have_invitar_usuarios_by_default(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('planeador');

        $this->assertFalse($user->can('invitar_usuarios'));
    }

    public function test_operador_does_not_have_invitar_usuarios(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $this->assertFalse($user->can('invitar_usuarios'));
    }
}
```

**Step 4: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=InvitationPermissionTest`
Expected: 3 PASS

**Step 5: Commit**

```bash
git add app/Enums/SystemPermission.php database/seeders/RolesAndPermissionsSeeder.php tests/Feature/UserInvitation/InvitationPermissionTest.php
git commit -m "feat(S13): add invitar_usuarios permission for admin role"
```

---

### Task 3: Servicio de invitación + Mailable

**Files:**
- Create: `app/Services/InvitacionUsuarioService.php`
- Create: `app/Mail/InvitacionUsuario.php`
- Create: `resources/views/emails/invitacion-usuario.blade.php`
- Test: `tests/Feature/UserInvitation/InvitacionUsuarioServiceTest.php`

**Step 1: Crear el Mailable**

Crear `app/Mail/InvitacionUsuario.php`:

```php
<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvitacionUsuario extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $activationUrl,
        public string $roleName,
        public string $urName,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invitación al Sistema de Planeación y Programación',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invitacion-usuario',
        );
    }
}
```

**Step 2: Crear el template del email**

Crear `resources/views/emails/invitacion-usuario.blade.php`:

```blade
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="font-family: sans-serif; background-color: #f3f4f6; padding: 40px 0;">
    <div style="max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden;">
        {{-- Header --}}
        <div style="background: linear-gradient(135deg, #064e3b, #065f46); padding: 32px; text-align: center;">
            <h1 style="color: white; margin: 0; font-size: 24px;">Sistema de Planeación y Programación</h1>
            <p style="color: #6ee7b7; margin: 8px 0 0; font-size: 14px;">Ejercicio Fiscal 2026</p>
        </div>

        {{-- Body --}}
        <div style="padding: 32px;">
            <p style="color: #374151; font-size: 16px;">Hola <strong>{{ $user->name }}</strong>,</p>

            <p style="color: #4b5563; font-size: 15px;">
                Has sido invitado a participar en el Sistema de Planeación y Programación
                como <strong>{{ $roleName }}</strong> en la unidad <strong>{{ $urName }}</strong>.
            </p>

            <p style="color: #4b5563; font-size: 15px;">
                Para activar tu cuenta, haz clic en el siguiente enlace:
            </p>

            <div style="text-align: center; margin: 32px 0;">
                <a href="{{ $activationUrl }}"
                   style="background-color: #059669; color: white; padding: 14px 32px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; display: inline-block;">
                    Activar mi cuenta
                </a>
            </div>

            <p style="color: #6b7280; font-size: 13px;">
                Este enlace expira en 72 horas. Si no solicitaste esta invitación, ignora este mensaje.
            </p>

            <p style="color: #6b7280; font-size: 13px; margin-top: 24px;">
                Si el botón no funciona, copia y pega esta URL en tu navegador:<br>
                <span style="color: #059669; word-break: break-all;">{{ $activationUrl }}</span>
            </p>
        </div>

        {{-- Footer --}}
        <div style="background: #f9fafb; padding: 16px 32px; text-align: center; border-top: 1px solid #e5e7eb;">
            <p style="color: #9ca3af; font-size: 12px; margin: 0;">
                &copy; {{ date('Y') }} — Dirección de Tecnología y Evaluación
            </p>
        </div>
    </div>
</body>
</html>
```

**Step 3: Crear el servicio**

Crear `app/Services/InvitacionUsuarioService.php`:

```php
<?php

namespace App\Services;

use App\Mail\InvitacionUsuario;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InvitacionUsuarioService
{
    public function invitar(string $email, string $name, string $role, int $teamId): User
    {
        $team = Team::findOrFail($teamId);
        $token = Str::random(64);

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => null,
            'activated_at' => null,
            'active' => true,
            'invitation_token' => $token,
            'invitation_sent_at' => now(),
            'email_verified_at' => now(),
        ]);

        $user->assignRole($role);

        // Asignar al team con el rol de Jetstream (editor por defecto)
        $team->users()->attach($user, ['role' => 'editor']);
        $user->switchTeam($team);

        $this->enviarEmail($user, $role, $team->name);

        return $user;
    }

    public function reenviarInvitacion(User $user): void
    {
        $user->update([
            'invitation_token' => Str::random(64),
            'invitation_sent_at' => now(),
        ]);

        $roleName = $user->roles->first()?->name ?? 'usuario';
        $teamName = $user->currentTeam?->name ?? '';

        $this->enviarEmail($user, $roleName, $teamName);
    }

    private function enviarEmail(User $user, string $roleName, string $urName): void
    {
        $activationUrl = route('activar.show', ['token' => $user->invitation_token]);

        Mail::to($user->email)->send(
            new InvitacionUsuario($user, $activationUrl, $roleName, $urName)
        );
    }
}
```

**Step 4: Escribir tests**

Crear `tests/Feature/UserInvitation/InvitacionUsuarioServiceTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Mail\InvitacionUsuario;
use App\Models\Team;
use App\Models\User;
use App\Services\InvitacionUsuarioService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InvitacionUsuarioServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_invitar_creates_user_with_token(): void
    {
        Mail::fake();

        $admin = User::factory()->withPersonalTeam()->create();
        $team = $admin->currentTeam;

        $service = new InvitacionUsuarioService();
        $user = $service->invitar('nuevo@gob.mx', 'Nuevo Usuario', 'operador', $team->id);

        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@gob.mx',
            'name' => 'Nuevo Usuario',
        ]);
        $this->assertNull($user->password);
        $this->assertNull($user->activated_at);
        $this->assertNotNull($user->invitation_token);
        $this->assertTrue($user->hasRole('operador'));
        $this->assertEquals($team->id, $user->current_team_id);
    }

    public function test_invitar_sends_email(): void
    {
        Mail::fake();

        $admin = User::factory()->withPersonalTeam()->create();

        $service = new InvitacionUsuarioService();
        $service->invitar('nuevo@gob.mx', 'Nuevo Usuario', 'operador', $admin->currentTeam->id);

        Mail::assertSent(InvitacionUsuario::class, function ($mail) {
            return $mail->hasTo('nuevo@gob.mx');
        });
    }

    public function test_reenviar_generates_new_token(): void
    {
        Mail::fake();

        $user = User::factory()->withPersonalTeam()->invited()->create();
        $oldToken = $user->invitation_token;

        $service = new InvitacionUsuarioService();
        $service->reenviarInvitacion($user);

        $user->refresh();
        $this->assertNotEquals($oldToken, $user->invitation_token);
    }

    public function test_reenviar_sends_email(): void
    {
        Mail::fake();

        $user = User::factory()->withPersonalTeam()->invited()->create();
        $user->assignRole('operador');

        $service = new InvitacionUsuarioService();
        $service->reenviarInvitacion($user);

        Mail::assertSent(InvitacionUsuario::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
```

**Step 5: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=InvitacionUsuarioServiceTest`
Expected: 4 PASS

**Step 6: Commit**

```bash
git add app/Services/InvitacionUsuarioService.php app/Mail/InvitacionUsuario.php resources/views/emails/invitacion-usuario.blade.php tests/Feature/UserInvitation/InvitacionUsuarioServiceTest.php
git commit -m "feat(S13): add InvitacionUsuarioService and InvitacionUsuario mailable"
```

---

### Task 4: Panel de Gestión de Usuarios — Livewire component

**Files:**
- Create: `app/Livewire/Admin/GestionUsuarios.php`
- Create: `resources/views/livewire/admin/gestion-usuarios.blade.php`
- Modify: `routes/web/admin.php`
- Modify: `resources/views/components/layout/sidebar-nav.blade.php`
- Test: `tests/Feature/UserInvitation/GestionUsuariosTest.php`

**Step 1: Crear el componente Livewire**

Crear `app/Livewire/Admin/GestionUsuarios.php`:

```php
<?php

namespace App\Livewire\Admin;

use App\Models\Team;
use App\Models\User;
use App\Services\InvitacionUsuarioService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class GestionUsuarios extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filterEstado = '';

    // Formulario de invitación
    public bool $showInviteForm = false;

    #[Rule('required|email|unique:users,email')]
    public string $inviteEmail = '';

    #[Rule('required|string|min:3')]
    public string $inviteName = '';

    #[Rule('required|in:admin,planeador,operador')]
    public string $inviteRole = 'operador';

    #[Rule('required|exists:teams,id')]
    public int|string $inviteTeamId = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('invitar_usuarios'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEstado(): void
    {
        $this->resetPage();
    }

    public function openInviteForm(): void
    {
        $this->reset(['inviteEmail', 'inviteName', 'inviteRole', 'inviteTeamId']);
        $this->inviteRole = 'operador';
        $this->showInviteForm = true;
    }

    public function cancelInvite(): void
    {
        $this->showInviteForm = false;
        $this->resetValidation();
    }

    public function sendInvitation(): void
    {
        $this->validate();

        $service = new InvitacionUsuarioService();
        $service->invitar($this->inviteEmail, $this->inviteName, $this->inviteRole, (int) $this->inviteTeamId);

        $this->showInviteForm = false;
        $this->reset(['inviteEmail', 'inviteName', 'inviteRole', 'inviteTeamId']);

        session()->flash('message', 'Invitación enviada correctamente.');
    }

    public function resendInvitation(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless($user->isPendingActivation() || $user->isInvitationExpired(), 403);

        $service = new InvitacionUsuarioService();
        $service->reenviarInvitacion($user);

        session()->flash('message', 'Invitación reenviada a ' . $user->email);
    }

    public function toggleActive(int $userId): void
    {
        $user = User::findOrFail($userId);

        // No permitir desactivarse a sí mismo
        abort_if($user->id === auth()->id(), 403);

        $user->update(['active' => ! $user->active]);

        $status = $user->active ? 'activado' : 'desactivado';
        session()->flash('message', "Usuario {$user->name} {$status}.");
    }

    public function getTeamsProperty()
    {
        return Team::where('personal_team', false)->orderBy('name')->get();
    }

    public function render()
    {
        $query = User::with(['roles', 'currentTeam'])
            ->when($this->search, function ($q) {
                $q->where(function ($q2) {
                    $q2->where('name', 'ilike', "%{$this->search}%")
                       ->orWhere('email', 'ilike', "%{$this->search}%");
                });
            })
            ->when($this->filterEstado, function ($q) {
                match ($this->filterEstado) {
                    'activo' => $q->whereNotNull('activated_at')->where('active', true),
                    'pendiente' => $q->whereNull('activated_at')->whereNotNull('invitation_token'),
                    'inactivo' => $q->where('active', false),
                    default => $q,
                };
            })
            ->orderBy('name');

        return view('livewire.admin.gestion-usuarios', [
            'usuarios' => $query->paginate(15),
        ]);
    }
}
```

**Step 2: Crear la vista**

Crear `resources/views/livewire/admin/gestion-usuarios.blade.php`:

```blade
<x-page.container title="Gestión de Usuarios" subtitle="Administra las cuentas del sistema">
    <x-slot:actions>
        <button wire:click="openInviteForm" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
            + Invitar Usuario
        </button>
    </x-slot:actions>

    {{-- Formulario de invitación --}}
    @if($showInviteForm)
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invitar Usuario</h3>

            <form wire:submit="sendInvitation" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="inviteName" class="block text-sm font-medium text-gray-700">Nombre completo</label>
                        <input type="text" wire:model="inviteName" id="inviteName" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('inviteName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteEmail" class="block text-sm font-medium text-gray-700">Correo electrónico</label>
                        <input type="email" wire:model="inviteEmail" id="inviteEmail" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                        @error('inviteEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteRole" class="block text-sm font-medium text-gray-700">Rol</label>
                        <select wire:model="inviteRole" id="inviteRole" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            <option value="operador">Operador</option>
                            <option value="planeador">Planeador</option>
                            <option value="admin">Administrador</option>
                        </select>
                        @error('inviteRole') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="inviteTeamId" class="block text-sm font-medium text-gray-700">Unidad Responsable</label>
                        <select wire:model="inviteTeamId" id="inviteTeamId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                            <option value="">Seleccionar...</option>
                            @foreach($this->teams as $team)
                                <option value="{{ $team->id }}">{{ $team->clave_ur ? $team->clave_ur . ' — ' : '' }}{{ $team->name }}</option>
                            @endforeach
                        </select>
                        @error('inviteTeamId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" wire:click="cancelInvite" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2">
                        Enviar Invitación
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- Filtros --}}
    <div class="bg-white rounded-lg border border-gray-200 p-4">
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
                       class="w-full border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
            </div>
            <div>
                <select wire:model.live="filterEstado" class="border-gray-300 rounded-md shadow-sm focus:ring-emerald-500 focus:border-emerald-500 sm:text-sm">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="pendiente">Pendiente</option>
                    <option value="inactivo">Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Usuario</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Rol</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unidad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($usuarios as $usuario)
                    <tr>
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <x-ui.avatar :name="$usuario->name" :src="$usuario->profile_photo_url ?? null" size="sm" />
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">{{ $usuario->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $usuario->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $usuario->roles->first()?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $usuario->currentTeam?->clave_ur ?? $usuario->currentTeam?->name ?? '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if(! $usuario->active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                            @elseif($usuario->isPendingActivation())
                                @if($usuario->isInvitationExpired())
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Expirado</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Pendiente</span>
                                @endif
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-2">
                            @if($usuario->isPendingActivation() || ($usuario->isActivated() === false && $usuario->invitation_token))
                                <button wire:click="resendInvitation({{ $usuario->id }})" wire:confirm="¿Reenviar invitación a {{ $usuario->email }}?" class="text-emerald-600 hover:text-emerald-900">Reenviar</button>
                            @endif
                            @if($usuario->id !== auth()->id())
                                <button wire:click="toggleActive({{ $usuario->id }})" wire:confirm="{{ $usuario->active ? '¿Desactivar' : '¿Reactivar' }} a {{ $usuario->name }}?" class="{{ $usuario->active ? 'text-red-600 hover:text-red-900' : 'text-emerald-600 hover:text-emerald-900' }}">
                                    {{ $usuario->active ? 'Desactivar' : 'Reactivar' }}
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-gray-500">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($usuarios->hasPages())
            <div class="px-6 py-3 border-t border-gray-200">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</x-page.container>
```

**Step 3: Actualizar rutas**

Modificar `routes/web/admin.php` — reemplazar contenido completo:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified', 'can:administrar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    // AI monitoring
    Route::get('/monitoreo-ia', \App\Livewire\Admin\MonitoreoIa::class)->name('monitoreo-ia');
});

// User management — requires invitar_usuarios permission
Route::middleware(['auth:sanctum', 'verified', 'can:invitar_usuarios'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/usuarios', \App\Livewire\Admin\GestionUsuarios::class)->name('users');
});
```

**Step 4: Actualizar sidebar**

En `resources/views/components/layout/sidebar-nav.blade.php`, reemplazar la sección de Administración (líneas 106-124):

```blade
{{-- Administración --}}
@canany(['administrar_usuarios', 'invitar_usuarios'])
<x-ui.sidebar-group label="Administración" :active="request()->routeIs('admin.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        @can('invitar_usuarios')<a href="{{ route('admin.users') }}" class="block py-1 hover:text-brand-light">Usuarios</a>@endcan
        @can('administrar_usuarios')<a href="{{ route('admin.monitoreo-ia') }}" class="block py-1 hover:text-brand-light">Monitor IA</a>@endcan
    </x-slot:tooltip>

    @can('invitar_usuarios')
        <x-ui.sidebar-item href="{{ route('admin.users') }}" :active="request()->routeIs('admin.users*')">
            Usuarios
        </x-ui.sidebar-item>
    @endcan
    @can('administrar_usuarios')
        <x-ui.sidebar-item href="{{ route('admin.monitoreo-ia') }}" :active="request()->routeIs('admin.monitoreo-ia')">
            Monitor IA
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany
```

**Step 5: Escribir tests**

Crear `tests/Feature/UserInvitation/GestionUsuariosTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class GestionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin(): User
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');
        return $user;
    }

    public function test_admin_can_access_gestion_usuarios(): void
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Gestión de Usuarios');
    }

    public function test_operador_cannot_access_gestion_usuarios(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/admin/usuarios');

        $response->assertStatus(403);
    }

    public function test_admin_can_send_invitation(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();

        // Crear un team no personal para la invitación
        $team = \App\Models\Team::create([
            'name' => 'Secretaría de Economía',
            'user_id' => $admin->id,
            'personal_team' => false,
        ]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->set('showInviteForm', true)
            ->set('inviteEmail', 'test@gob.mx')
            ->set('inviteName', 'Test User')
            ->set('inviteRole', 'operador')
            ->set('inviteTeamId', $team->id)
            ->call('sendInvitation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'test@gob.mx',
            'name' => 'Test User',
        ]);
    }

    public function test_invitation_requires_valid_email(): void
    {
        $admin = $this->createAdmin();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->set('showInviteForm', true)
            ->set('inviteEmail', 'not-an-email')
            ->set('inviteName', 'Test')
            ->set('inviteRole', 'operador')
            ->set('inviteTeamId', 1)
            ->call('sendInvitation')
            ->assertHasErrors(['inviteEmail']);
    }

    public function test_table_shows_user_status(): void
    {
        $admin = $this->createAdmin();
        User::factory()->withPersonalTeam()->create(['name' => 'Activo User']);
        User::factory()->withPersonalTeam()->invited()->create(['name' => 'Pendiente User']);

        $response = $this->actingAs($admin)->get('/admin/usuarios');

        $response->assertSee('Activo User');
        $response->assertSee('Pendiente User');
    }

    public function test_admin_can_toggle_user_active(): void
    {
        $admin = $this->createAdmin();
        $user = User::factory()->withPersonalTeam()->create(['active' => true]);

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->call('toggleActive', $user->id);

        $user->refresh();
        $this->assertFalse($user->active);
    }

    public function test_admin_cannot_deactivate_self(): void
    {
        $admin = $this->createAdmin();

        \Livewire\Livewire::actingAs($admin)
            ->test(\App\Livewire\Admin\GestionUsuarios::class)
            ->call('toggleActive', $admin->id)
            ->assertStatus(403);
    }
}
```

**Step 6: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=GestionUsuariosTest`
Expected: 7 PASS

**Step 7: Eliminar placeholder**

Eliminar `resources/views/admin/users-placeholder.blade.php` (ya no se usa).

**Step 8: Commit**

```bash
git add app/Livewire/Admin/GestionUsuarios.php resources/views/livewire/admin/gestion-usuarios.blade.php routes/web/admin.php resources/views/components/layout/sidebar-nav.blade.php tests/Feature/UserInvitation/GestionUsuariosTest.php
git rm resources/views/admin/users-placeholder.blade.php
git commit -m "feat(S13): implement user management panel with invitation flow"
```

---

### Task 5: Middleware EnsureUserIsActivated

**Files:**
- Create: `app/Http/Middleware/EnsureUserIsActivated.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/UserInvitation/EnsureUserIsActivatedTest.php`

**Step 1: Crear el middleware**

Crear `app/Http/Middleware/EnsureUserIsActivated.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActivated
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        // Usuario desactivado por admin → cerrar sesión
        if (! $user->active) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Tu cuenta ha sido desactivada. Contacta al administrador.');
        }

        // Usuario que no ha completado onboarding → bloquear acceso
        if (! $user->isActivated()) {
            $allowedRoutes = ['logout', 'activar.*', 'onboarding.*'];

            if ($request->routeIs($allowedRoutes)) {
                return $next($request);
            }

            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->with('status', 'Debes completar la activación de tu cuenta. Revisa tu correo electrónico.');
        }

        return $next($request);
    }
}
```

**Step 2: Registrar en bootstrap/app.php**

En `bootstrap/app.php`, agregar el middleware al stack web. Cambiar la sección `withMiddleware`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
        'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        'ur.aislamiento'     => \App\Http\Middleware\AislamientoMultiUR::class,
    ]);
    $middleware->web(append: [
        \App\Http\Middleware\EnsureUserIsActivated::class,
        \App\Http\Middleware\RequireTwoFactorAuthentication::class,
    ]);
})
```

**IMPORTANTE:** `EnsureUserIsActivated` debe ir ANTES de `RequireTwoFactorAuthentication` en el array. El orden en `append` importa.

**Step 3: Escribir tests**

Crear `tests/Feature/UserInvitation/EnsureUserIsActivatedTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureUserIsActivatedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_activated_user_can_access_dashboard(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::factory()->withPersonalTeam()->create(['active' => false]);
        $user->assignRole('admin');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_pending_user_is_redirected(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();
        $user->assignRole('operador');

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/login');
    }

    public function test_guest_is_not_affected(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
```

**Step 4: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=EnsureUserIsActivatedTest`
Expected: 4 PASS

**Step 5: Commit**

```bash
git add app/Http/Middleware/EnsureUserIsActivated.php bootstrap/app.php tests/Feature/UserInvitation/EnsureUserIsActivatedTest.php
git commit -m "feat(S13): add EnsureUserIsActivated middleware to block unactivated users"
```

---

### Task 6: Flujo de onboarding — Controlador + Vistas

**Files:**
- Create: `app/Http/Controllers/OnboardingController.php`
- Create: `resources/views/onboarding/set-password.blade.php`
- Create: `resources/views/onboarding/setup-2fa.blade.php`
- Create: `resources/views/onboarding/complete.blade.php`
- Modify: `routes/web.php` (agregar rutas de onboarding)
- Test: `tests/Feature/UserInvitation/OnboardingFlowTest.php`

**Step 1: Crear el controlador**

Crear `app/Http/Controllers/OnboardingController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;

class OnboardingController extends Controller
{
    public function showSetPassword(string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        // Si ya tiene contraseña, saltar al 2FA
        if ($user->password !== null) {
            return redirect()->route('activar.2fa', ['token' => $token]);
        }

        return view('onboarding.set-password', compact('user', 'token'));
    }

    public function storePassword(Request $request, string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('activar.2fa', ['token' => $token]);
    }

    public function showSetup2fa(string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        if ($user->password === null) {
            return redirect()->route('activar.show', ['token' => $token]);
        }

        // Habilitar 2FA si no está habilitado (genera secret y QR)
        if (! $user->two_factor_secret) {
            Auth::login($user);
            app(EnableTwoFactorAuthentication::class)($user);
            $user->refresh();
        } else {
            Auth::login($user);
        }

        return view('onboarding.setup-2fa', [
            'user' => $user,
            'token' => $token,
            'qrCodeSvg' => $user->twoFactorQrCodeSvg(),
            'setupKey' => decrypt($user->two_factor_secret),
        ]);
    }

    public function confirm2fa(Request $request, string $token)
    {
        $user = $this->findUserByToken($token);

        if (! $user) {
            return view('onboarding.invalid-token');
        }

        $request->validate([
            'code' => ['required', 'string'],
        ]);

        Auth::login($user);

        try {
            app(ConfirmTwoFactorAuthentication::class)($user, $request->code);
        } catch (\Exception $e) {
            return back()->withErrors(['code' => 'El código ingresado no es válido. Intenta de nuevo.']);
        }

        // Activar la cuenta
        $user->update([
            'activated_at' => now(),
            'invitation_token' => null,
        ]);

        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

        return view('onboarding.complete', [
            'user' => $user,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }

    private function findUserByToken(string $token): ?User
    {
        $user = User::where('invitation_token', $token)->first();

        if (! $user) {
            return null;
        }

        // Ya activado
        if ($user->isActivated()) {
            return null;
        }

        // Token expirado
        if ($user->isInvitationExpired()) {
            return null;
        }

        return $user;
    }
}
```

**Step 2: Crear vista set-password**

Crear `resources/views/onboarding/set-password.blade.php`:

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Bienvenido, {{ $user->name }}</h2>
        <p class="mt-2 text-sm text-gray-600">Establece tu contraseña para activar tu cuenta.</p>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('activar.password', ['token' => $token]) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="password" value="Nueva contraseña" />
            <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autofocus autocomplete="new-password" />
        </div>

        <div>
            <x-label for="password_confirmation" value="Confirmar contraseña" />
            <x-input id="password_confirmation" class="block mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
        </div>

        <div class="bg-amber-50 border border-amber-200 rounded-md p-3">
            <p class="text-sm text-amber-800">
                Después de establecer tu contraseña, deberás configurar la autenticación de dos factores para mayor seguridad.
            </p>
        </div>

        <x-button class="w-full justify-center">
            Continuar
        </x-button>
    </form>
</x-guest-layout>
```

**Step 3: Crear vista setup-2fa**

Crear `resources/views/onboarding/setup-2fa.blade.php`:

```blade
<x-guest-layout>
    <div>
        <h2 class="text-2xl font-bold text-gray-900">Configurar Autenticación de Dos Factores</h2>
        <p class="mt-2 text-sm text-gray-600">Escanea el código QR con tu aplicación de autenticación (Google Authenticator, Authy, etc.).</p>
    </div>

    <div class="mt-6">
        <div class="flex justify-center p-4 bg-white border border-gray-200 rounded-lg">
            {!! $qrCodeSvg !!}
        </div>

        <div class="mt-4 p-3 bg-gray-50 rounded-md">
            <p class="text-xs text-gray-500 mb-1">Clave de configuración manual:</p>
            <p class="text-sm font-mono text-gray-700 break-all">{{ $setupKey }}</p>
        </div>
    </div>

    <x-validation-errors class="mt-4" />

    <form method="POST" action="{{ route('activar.2fa.confirm', ['token' => $token]) }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-label for="code" value="Código de verificación" />
            <x-input id="code" class="block mt-1 w-full" type="text" name="code" inputmode="numeric" required autofocus autocomplete="one-time-code" placeholder="Ingresa el código de 6 dígitos" />
        </div>

        <x-button class="w-full justify-center">
            Verificar y Activar
        </x-button>
    </form>
</x-guest-layout>
```

**Step 4: Crear vista complete**

Crear `resources/views/onboarding/complete.blade.php`:

```blade
<x-guest-layout>
    <div>
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-green-100 mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">¡Tu cuenta está lista!</h2>
        <p class="mt-2 text-sm text-gray-600">Has configurado tu contraseña y autenticación de dos factores correctamente.</p>
    </div>

    @if($recoveryCodes)
        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <p class="text-sm font-semibold text-amber-800 mb-2">Códigos de recuperación</p>
            <p class="text-xs text-amber-700 mb-3">Guarda estos códigos en un lugar seguro. Los necesitarás si pierdes acceso a tu aplicación de autenticación. Solo se muestran una vez.</p>
            <div class="grid grid-cols-2 gap-1 font-mono text-sm bg-white p-3 rounded border border-amber-200">
                @foreach($recoveryCodes as $code)
                    <div class="text-gray-700">{{ $code }}</div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mt-6">
        <a href="{{ route('dashboard') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition">
            Ir al Dashboard
        </a>
    </div>
</x-guest-layout>
```

**Step 5: Crear vista invalid-token**

Crear `resources/views/onboarding/invalid-token.blade.php`:

```blade
<x-guest-layout>
    <div class="text-center">
        <div class="flex items-center justify-center w-12 h-12 rounded-full bg-red-100 mb-4 mx-auto">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900">Enlace inválido o expirado</h2>
        <p class="mt-2 text-sm text-gray-600">Este enlace de activación ya no es válido. Puede que haya expirado o ya hayas activado tu cuenta.</p>
        <p class="mt-4 text-sm text-gray-600">Si necesitas un nuevo enlace, contacta al administrador del sistema.</p>

        <div class="mt-6">
            <a href="{{ route('login') }}" class="text-sm text-brand hover:text-brand-dark font-medium">
                Volver al inicio de sesión
            </a>
        </div>
    </div>
</x-guest-layout>
```

**Step 6: Agregar rutas**

En `routes/web.php`, agregar las rutas de onboarding. Buscar dónde se incluyen las rutas y agregar ANTES del bloque de middleware auth:

```php
// Onboarding — activación de cuenta (sin auth, usa token)
Route::prefix('activar')->name('activar.')->group(function () {
    Route::get('/{token}', [\App\Http\Controllers\OnboardingController::class, 'showSetPassword'])->name('show');
    Route::post('/{token}/password', [\App\Http\Controllers\OnboardingController::class, 'storePassword'])->name('password');
    Route::get('/{token}/2fa', [\App\Http\Controllers\OnboardingController::class, 'showSetup2fa'])->name('2fa');
    Route::post('/{token}/2fa', [\App\Http\Controllers\OnboardingController::class, 'confirm2fa'])->name('2fa.confirm');
});
```

**Step 7: Escribir tests**

Crear `tests/Feature/UserInvitation/OnboardingFlowTest.php`:

```php
<?php

namespace Tests\Feature\UserInvitation;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_valid_token_shows_password_form(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->get(route('activar.show', ['token' => $user->invitation_token]));

        $response->assertStatus(200);
        $response->assertSee('Establece tu contraseña');
    }

    public function test_expired_token_shows_error(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create([
            'invitation_sent_at' => now()->subHours(73),
        ]);

        $response = $this->get(route('activar.show', ['token' => $user->invitation_token]));

        $response->assertSee('Enlace inválido o expirado');
    }

    public function test_invalid_token_shows_error(): void
    {
        $response = $this->get(route('activar.show', ['token' => 'invalid-token']));

        $response->assertSee('Enlace inválido o expirado');
    }

    public function test_can_set_password(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->post(route('activar.password', ['token' => $user->invitation_token]), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ]);

        $response->assertRedirect(route('activar.2fa', ['token' => $user->invitation_token]));

        $user->refresh();
        $this->assertNotNull($user->password);
    }

    public function test_password_requires_confirmation(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->post(route('activar.password', ['token' => $user->invitation_token]), [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'DifferentPass!',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_2fa_page_redirects_if_no_password(): void
    {
        $user = User::factory()->withPersonalTeam()->invited()->create();

        $response = $this->get(route('activar.2fa', ['token' => $user->invitation_token]));

        $response->assertRedirect(route('activar.show', ['token' => $user->invitation_token]));
    }

    public function test_already_activated_token_is_invalid(): void
    {
        $user = User::factory()->withPersonalTeam()->create([
            'activated_at' => now(),
            'invitation_token' => 'some-token',
        ]);

        $response = $this->get(route('activar.show', ['token' => 'some-token']));

        $response->assertSee('Enlace inválido o expirado');
    }
}
```

**Step 8: Correr tests**

Run: `./vendor/bin/sail artisan test --filter=OnboardingFlowTest`
Expected: 7 PASS

**Step 9: Commit**

```bash
git add app/Http/Controllers/OnboardingController.php resources/views/onboarding/ routes/web.php tests/Feature/UserInvitation/OnboardingFlowTest.php
git commit -m "feat(S13): implement onboarding flow with password setup and 2FA"
```

---

### Task 7: Verificación final + suite completa

**Files:**
- Ningún cambio — solo verificación

**Step 1: Correr suite completa**

Run: `./vendor/bin/sail artisan test 2>&1 | tail -10`
Expected: Todos pasan (480 baseline + ~27 nuevos tests), 0 fallos

**Step 2: Verificar que el email se renderiza**

Run: `./vendor/bin/sail artisan test --filter=InvitacionUsuarioServiceTest`
Expected: 4 PASS (confirma que Mailpit recibiría los correos)

**Step 3: Verificar build Vite**

Run: `./vendor/bin/sail npm run build 2>&1 | tail -5`
Expected: Build exitoso

**Step 4: Usar superpowers:finishing-a-development-branch para completar**
