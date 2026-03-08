# Sprint 14: Risk Mitigation — Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Mitigate eight development-time risks identified in the comprehensive risk analysis: CI/CD pipeline, orphan file cleanup, basic accessibility, N+1 detection, security headers, raw HTML audit, runbook documentation, and final verification.

**Architecture:** This sprint is cross-cutting — it touches CI configuration, model event listeners, a new artisan command, middleware registration, accessibility improvements to layouts, and documentation. No new database migrations are needed.

**Tech Stack:** Laravel 12, PHP 8.2+, PostgreSQL, Jetstream/Livewire 3, Spatie Permission, GitHub Actions, Alpine.js

**Branch:** `feat/S14-risk-mitigation`

**Baseline:** 426 tests, 7 skipped

---

### Task 1: CI/CD — GitHub Actions

**Files:**
- Create: `.github/workflows/ci.yml`

**Step 1: Create `.github/workflows/ci.yml`**

```yaml
name: CI

on:
  push:
    branches: [desarrollo, main]
  pull_request:
    branches: [desarrollo, main]

jobs:
  ci:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: testing
          POSTGRES_PASSWORD: password
          POSTGRES_DB: testing
        ports:
          - 5432:5432
        options: >-
          --health-cmd="pg_isready"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=5

    env:
      DB_CONNECTION: pgsql
      DB_HOST: 127.0.0.1
      DB_PORT: 5432
      DB_DATABASE: testing
      DB_USERNAME: testing
      DB_PASSWORD: password

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo_pgsql, pgsql, mbstring, xml, bcmath, gd, zip
          coverage: none

      - name: Cache Composer packages
        uses: actions/cache@v4
        with:
          path: vendor
          key: composer-${{ hashFiles('composer.lock') }}
          restore-keys: composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --optimize-autoloader

      - name: Copy env
        run: cp .env.example .env

      - name: Generate app key
        run: php artisan key:generate

      - name: Run Pint (code style)
        run: vendor/bin/pint --test

      - name: Composer audit (security)
        run: composer audit

      - name: Run migrations
        run: php artisan migrate --force

      - name: Run tests
        run: php artisan test --parallel
```

**Test:**

```bash
# Validate YAML syntax
php -r "echo yaml_parse_file('.github/workflows/ci.yml') ? 'valid' : 'invalid';" 2>/dev/null || python3 -c "import yaml; yaml.safe_load(open('.github/workflows/ci.yml')); print('valid')"
```

**Commit:**

```bash
git add .github/workflows/ci.yml
git commit -m "$(cat <<'EOF'
ci: add GitHub Actions pipeline (phpunit + pint + composer audit)

Runs on push to desarrollo/main and on PRs. Uses PostgreSQL 16 service
container, PHP 8.2, caches Composer dependencies.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 2: Archivos huerfanos — Event listeners + cleanup command

**Files:**
- Modify: `app/Models/Tracking/AvanceEvidencia.php`
- Modify: `app/Models/Tracking/Avance.php`
- Create: `app/Console/Commands/ReportsCleanup.php`
- Modify: `routes/console.php`
- Create: `tests/Unit/Tracking/OrphanFileCleanupTest.php`

**Step 1: Add `deleting` event to `AvanceEvidencia` model**

In `app/Models/Tracking/AvanceEvidencia.php`, add a `booted` method that registers a `deleting` event to automatically delete the physical file from storage when the model is deleted. This moves the file-deletion responsibility from the Livewire component into the model itself, so files are always cleaned up regardless of how the record is deleted.

Add the `Storage` import:

```php
use Illuminate\Support\Facades\Storage;
```

Add the `booted` method inside the class:

```php
protected static function booted(): void
{
    static::deleting(function (AvanceEvidencia $evidencia) {
        if ($evidencia->ruta_archivo) {
            Storage::disk('local')->delete($evidencia->ruta_archivo);
        }
    });
}
```

**Step 2: Update `EvidenciaAvance` Livewire to remove redundant Storage::delete**

In `app/Livewire/Tracking/EvidenciaAvance.php`, the `eliminar` method currently calls `Storage::disk('local')->delete(...)` before `$evidencia->delete()`. Since the model's `deleting` event now handles this, remove the explicit `Storage::disk('local')->delete($evidencia->ruta_archivo);` line from the `eliminar` method to avoid double-deletion attempts. The method should become:

```php
public function eliminar(int $evidenciaId): void
{
    if ($this->avance->estaCongelado()) {
        abort(403, 'El avance está congelado.');
    }

    $evidencia = $this->avance->evidencias()->findOrFail($evidenciaId);

    $evidencia->delete();

    $this->avance->refresh();
}
```

**Step 3: Add `deleting` event to `Avance` model for cascade file cleanup**

In `app/Models/Tracking/Avance.php`, add a `booted` method that, when an Avance is being deleted, deletes all its evidencias (which in turn triggers their `deleting` events to clean up files), and then removes the evidencias directory:

Add the `Storage` import:

```php
use Illuminate\Support\Facades\Storage;
```

Add the `booted` method inside the class:

```php
protected static function booted(): void
{
    static::deleting(function (Avance $avance) {
        // Delete each evidencia (triggers AvanceEvidencia::deleting → file cleanup)
        $avance->evidencias->each->delete();

        // Remove the directory for this avance's evidence files
        $dir = "evidencias/{$avance->id}";
        if (Storage::disk('local')->exists($dir)) {
            Storage::disk('local')->deleteDirectory($dir);
        }
    });
}
```

**Step 4: Create `ReportsCleanup` command**

Create `app/Console/Commands/ReportsCleanup.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ReportsCleanup extends Command
{
    protected $signature = 'reports:cleanup {--hours= : Override TTL hours from config} {--dry-run : Show what would be deleted}';

    protected $description = 'Delete generated report files older than the configured TTL';

    public function handle(): int
    {
        $disk = config('evaluation.exports.storage_disk', 'local');
        $basePath = config('evaluation.exports.storage_path', 'reportes');
        $ttlHours = (int) ($this->option('hours') ?? config('evaluation.exports.ttl_hours', 24));
        $dryRun = (bool) $this->option('dry-run');

        $cutoff = now()->subHours($ttlHours);

        $this->info("Cleaning reports older than {$ttlHours} hours (before {$cutoff->toDateTimeString()})");

        if ($dryRun) {
            $this->warn('DRY RUN — no files will be deleted.');
        }

        $storage = Storage::disk($disk);
        $files = $storage->files($basePath);
        $deleted = 0;

        foreach ($files as $file) {
            $lastModified = $storage->lastModified($file);

            if ($lastModified < $cutoff->timestamp) {
                if ($dryRun) {
                    $this->line("  Would delete: {$file}");
                } else {
                    $storage->delete($file);
                    $this->line("  Deleted: {$file}");
                }
                $deleted++;
            }
        }

        $action = $dryRun ? 'Would delete' : 'Deleted';
        $this->info("{$action} {$deleted} file(s).");

        return self::SUCCESS;
    }
}
```

**Step 5: Register schedule in `routes/console.php`**

Add after the existing schedule entries:

```php
Schedule::command('reports:cleanup')->dailyAt('03:00');
```

**Step 6: Create unit test `tests/Unit/Tracking/OrphanFileCleanupTest.php`**

```php
<?php

namespace Tests\Unit\Tracking;

use App\Models\Tracking\Avance;
use App\Models\Tracking\AvanceEvidencia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrphanFileCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_evidencia_removes_file_from_storage(): void
    {
        Storage::fake('local');

        $avance = Avance::factory()->create();
        $path = "evidencias/{$avance->id}/test-file.pdf";
        Storage::disk('local')->put($path, 'dummy content');

        $evidencia = AvanceEvidencia::factory()->create([
            'avance_id' => $avance->id,
            'ruta_archivo' => $path,
        ]);

        Storage::disk('local')->assertExists($path);

        $evidencia->delete();

        Storage::disk('local')->assertMissing($path);
    }

    public function test_deleting_avance_removes_evidencias_and_directory(): void
    {
        Storage::fake('local');

        $avance = Avance::factory()->create();
        $dir = "evidencias/{$avance->id}";
        $path1 = "{$dir}/file1.pdf";
        $path2 = "{$dir}/file2.pdf";

        Storage::disk('local')->put($path1, 'content1');
        Storage::disk('local')->put($path2, 'content2');

        AvanceEvidencia::factory()->create([
            'avance_id' => $avance->id,
            'ruta_archivo' => $path1,
        ]);
        AvanceEvidencia::factory()->create([
            'avance_id' => $avance->id,
            'ruta_archivo' => $path2,
        ]);

        $avance->delete();

        Storage::disk('local')->assertMissing($path1);
        Storage::disk('local')->assertMissing($path2);
        $this->assertEmpty(Storage::disk('local')->files($dir));
    }
}
```

**Test:**

```bash
./vendor/bin/sail artisan test --filter=OrphanFileCleanupTest
# Expected: 2 passed

./vendor/bin/sail artisan reports:cleanup --dry-run
# Expected: "Cleaning reports older than 24 hours..." output
```

**Commit:**

```bash
git add app/Models/Tracking/AvanceEvidencia.php app/Models/Tracking/Avance.php \
  app/Livewire/Tracking/EvidenciaAvance.php \
  app/Console/Commands/ReportsCleanup.php routes/console.php \
  tests/Unit/Tracking/OrphanFileCleanupTest.php
git commit -m "$(cat <<'EOF'
fix(tracking): prevent orphan files with model events + reports:cleanup

- AvanceEvidencia::deleting event auto-deletes the stored file
- Avance::deleting event cascade-deletes evidencias and their directory
- Remove redundant Storage::delete from EvidenciaAvance Livewire component
- New reports:cleanup command purges expired report files (TTL from config)
- Schedule reports:cleanup daily at 03:00

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 3: Accesibilidad basica

**Files:**
- Modify: `resources/views/layouts/app.blade.php`
- Modify: `resources/views/components/ui/topbar.blade.php`
- Modify: `resources/views/components/ui/sidebar.blade.php`
- Modify: `resources/views/components/modals/confirm.blade.php`
- Create: `tests/Feature/AccessibilityTest.php`

**Step 1: Add skip-to-main link in `resources/views/layouts/app.blade.php`**

Add immediately after the opening `<body>` tag (before `<x-banner />`):

```html
<a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-2 focus:left-2 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded focus:shadow-lg focus:text-brand focus:ring-2 focus:ring-brand">
    Saltar al contenido principal
</a>
```

Add `id="main-content"` to the `<main>` tag:

```html
<main id="main-content">
```

**Step 2: Add `aria-label` to hamburger button in `resources/views/components/ui/topbar.blade.php`**

Change the mobile hamburger button to include `aria-label`:

```html
<button @click="mobileOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700" aria-label="Abrir menu de navegacion">
```

**Step 3: Add `aria-label` to sidebar collapse toggle in `resources/views/components/ui/sidebar.blade.php`**

Change the collapse toggle button (line 96) to include `aria-label`:

```html
<button @click="toggle()" class="hidden lg:flex w-full items-center justify-center py-2 text-gray-400 hover:text-gray-600 transition-colors" aria-label="Colapsar barra lateral" :aria-label="collapsed ? 'Expandir barra lateral' : 'Colapsar barra lateral'">
```

**Step 4: Add `role`, `aria-modal`, `aria-labelledby`, and focus trap to `resources/views/components/modals/confirm.blade.php`**

Update the modal panel `<div>` (inner div, line 30) to add ARIA attributes:

```html
<div x-show="open"
     x-trap.noscroll="open"
     role="dialog"
     aria-modal="true"
     aria-labelledby="confirm-title-{{ $id }}"
     x-transition:enter="ease-out duration-300"
     ...
```

Note: `x-trap` is an Alpine.js plugin that handles focus trapping. It requires the `@alpinejs/focus` plugin. Check if it is already installed; if not, add it.

Update the title div to add the matching id:

```html
<div id="confirm-title-{{ $id }}" class="text-lg font-medium text-gray-900">{{ $title }}</div>
```

**Step 5: Verify Alpine Focus plugin is installed**

Check `resources/js/app.js` and `package.json` for `@alpinejs/focus`. If not present:

```bash
npm install @alpinejs/focus
```

Then in `resources/js/app.js`, register the plugin:

```js
import focus from '@alpinejs/focus';
Alpine.plugin(focus);
```

If Alpine is auto-initialized by Livewire (which manages its own Alpine instance), the focus plugin should be registered via Livewire's Alpine hook instead. Check `resources/js/app.js` for the pattern — Livewire 3 typically uses:

```js
document.addEventListener('livewire:init', () => {
    // Livewire manages Alpine
});
```

If Livewire manages Alpine, register focus via:

```js
import focus from '@alpinejs/focus';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);
});
```

**Step 6: Create `tests/Feature/AccessibilityTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_skip_to_main_link_is_present(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('Saltar al contenido principal');
        $response->assertSee('id="main-content"', false);
    }

    public function test_hamburger_has_aria_label(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertSee('aria-label="Abrir menu de navegacion"', false);
    }
}
```

**Test:**

```bash
./vendor/bin/sail artisan test --filter=AccessibilityTest
# Expected: 2 passed
```

**Commit:**

```bash
git add resources/views/layouts/app.blade.php \
  resources/views/components/ui/topbar.blade.php \
  resources/views/components/ui/sidebar.blade.php \
  resources/views/components/modals/confirm.blade.php \
  resources/js/app.js package.json package-lock.json \
  tests/Feature/AccessibilityTest.php
git commit -m "$(cat <<'EOF'
feat(a11y): add skip link, aria-labels, and focus trap in modals

- Skip-to-main link on app layout (visible on focus)
- aria-label on hamburger button and sidebar collapse toggle
- role=dialog, aria-modal, aria-labelledby on confirm modal
- x-trap.noscroll for focus trapping (requires @alpinejs/focus)

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 4: N+1 detection

**Files:**
- Modify: `composer.json` (dev dependencies)

**Step 1: Install dev dependencies**

```bash
./vendor/bin/sail composer require --dev beyondcode/laravel-query-detector barryvdh/laravel-debugbar
```

**Step 2: Publish configs (optional, for customization)**

```bash
./vendor/bin/sail artisan vendor:publish --provider="BeyondCode\QueryDetector\QueryDetectorServiceProvider"
```

This creates `config/query-detector.php`. The defaults are fine — it logs N+1 queries as warnings in the debug bar and throws exceptions in testing if `QUERY_DETECTOR_EXCEPT` is set.

**Step 3: Verify**

```bash
# Debugbar should auto-register in local env
./vendor/bin/sail artisan package:discover | grep -i "debugbar\|query"
```

**Test:**

```bash
# Verify packages installed
./vendor/bin/sail composer show beyondcode/laravel-query-detector
./vendor/bin/sail composer show barryvdh/laravel-debugbar

# Run full test suite to ensure no regressions
./vendor/bin/sail artisan test
```

**Commit:**

```bash
git add composer.json composer.lock config/query-detector.php
git commit -m "$(cat <<'EOF'
feat(dev): install query-detector and debugbar for N+1 detection

Adds beyondcode/laravel-query-detector and barryvdh/laravel-debugbar
as dev dependencies. Both auto-register in local/development environment.
Query detector alerts on N+1 queries via debugbar panel.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 5: Security headers middleware

**Files:**
- Create: `app/Http/Middleware/SecurityHeaders.php`
- Modify: `bootstrap/app.php`
- Create: `tests/Feature/SecurityHeadersTest.php`

**Step 1: Create `app/Http/Middleware/SecurityHeaders.php`**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; "
            . "script-src 'self' 'unsafe-inline' 'unsafe-eval'; "
            . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com; "
            . "img-src 'self' data:; "
            . "connect-src 'self'; "
            . "frame-ancestors 'none'"
        );

        return $response;
    }
}
```

> **Note:** `unsafe-inline` and `unsafe-eval` are needed for Livewire and Alpine.js. In a future sprint these can be replaced with nonce-based CSP once Livewire supports it.

**Step 2: Register in `bootstrap/app.php`**

Add `\App\Http\Middleware\SecurityHeaders::class` to the `web` middleware stack, appended after the existing entries:

```php
$middleware->web(append: [
    \App\Http\Middleware\EnsureUserIsActivated::class,
    \App\Http\Middleware\RequireTwoFactorAuthentication::class,
    \App\Http\Middleware\SecurityHeaders::class,
]);
```

**Step 3: Create `tests/Feature/SecurityHeadersTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->assertHeader('Content-Security-Policy');
    }

    public function test_csp_header_blocks_frame_ancestors(): void
    {
        $user = User::factory()->withPersonalTeam()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
    }
}
```

**Test:**

```bash
./vendor/bin/sail artisan test --filter=SecurityHeadersTest
# Expected: 2 passed
```

**Commit:**

```bash
git add app/Http/Middleware/SecurityHeaders.php bootstrap/app.php \
  tests/Feature/SecurityHeadersTest.php
git commit -m "$(cat <<'EOF'
feat(security): add SecurityHeaders middleware (CSP, X-Frame-Options, etc.)

Appends security headers to all web responses:
- Content-Security-Policy (self + inline for Livewire/Alpine compatibility)
- X-Frame-Options: DENY
- X-Content-Type-Options: nosniff
- Referrer-Policy: strict-origin-when-cross-origin
- Permissions-Policy: camera=(), microphone=(), geolocation=()

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 6: Audit raw HTML

**Files:**
- Possibly modify: various `.blade.php` files

**Step 1: Grep all `{!! !!}` usages across blade files**

```bash
grep -rn '{!!' resources/views/ --include='*.blade.php'
```

**Audit results (from codebase research):**

| File | Line | Usage | Verdict |
|------|------|-------|---------|
| `resources/views/policy.blade.php:9` | `{!! $policy !!}` | Jetstream policy page — `$policy` comes from markdown file rendered by Jetstream. **Safe.** |
| `resources/views/terms.blade.php:9` | `{!! $terms !!}` | Jetstream terms page — same pattern. **Safe.** |
| `resources/views/components/input.blade.php:3` | `{!! $attributes->merge([...]) !!}` | Blade component attribute bag — standard Laravel pattern. **Safe.** |
| `resources/views/components/checkbox.blade.php:1` | `{!! $attributes->merge([...]) !!}` | Blade component attribute bag. **Safe.** |
| `resources/views/livewire/evaluation/evaluacion-programa.blade.php:214` | `{!! nl2br(e($analisisIa)) !!}` | Uses `e()` to escape first, then `nl2br()` to convert newlines. **Safe.** |
| `resources/views/onboarding/setup-2fa.blade.php:9` | `{!! $qrCodeSvg !!}` | QR code SVG generated by Jetstream/Fortify. **Safe** (server-generated, no user input). |
| `resources/views/auth/register.blade.php:39` | `{!! __('I agree to...') !!}` | Translation string with HTML links — standard Jetstream pattern. **Safe.** |
| `resources/views/profile/two-factor-authentication-form.blade.php:42` | `{!! $this->user->twoFactorQrCodeSvg() !!}` | Jetstream-generated SVG. **Safe.** |

**Step 2: Document findings**

All 8 usages are safe:
- 3 are Blade `$attributes->merge()` (framework pattern)
- 2 are Jetstream policy/terms pages (markdown → HTML, admin-controlled)
- 2 are QR code SVGs (server-generated, no user input)
- 1 uses `e()` before `nl2br()` (properly escaped)

No changes needed. Create a brief comment in the commit noting the audit was completed.

**Test:**

```bash
# Re-verify no new unescaped output has been introduced
grep -rn '{!!' resources/views/ --include='*.blade.php' | wc -l
# Expected: 8 (all accounted for above)
```

**Commit:**

```bash
git commit --allow-empty -m "$(cat <<'EOF'
audit(security): verify all {!! !!} raw HTML usages in blade templates

Audited 8 occurrences of unescaped output across blade files:
- 3x $attributes->merge() — standard Blade component pattern
- 2x Jetstream policy/terms — admin-controlled markdown content
- 2x QR code SVGs — server-generated, no user input
- 1x nl2br(e($var)) — properly escaped before rendering
All usages verified safe. No changes required.

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 7: Runbook documentation

**Files:**
- Modify: `README.md` (if exists, otherwise create)
- Create: `SETUP.md`
- Create: `COMMANDS.md`

**Step 1: Create/Update `README.md`**

```markdown
# DTE-SPP 2026

Sistema de Planeación Programática — Dirección Técnica de Evaluación.

## Stack

- **Backend:** Laravel 12, PHP 8.2+, PostgreSQL 16
- **Frontend:** Livewire 3, Alpine.js, Tailwind CSS
- **Auth:** Jetstream (Teams) + Spatie Permission
- **Dev:** Docker via Laravel Sail

## Quick start

```bash
# Clone and setup
git clone <repo-url> && cd dte-spp-2026
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate --seed
npm install && npm run build
```

See [SETUP.md](SETUP.md) for detailed installation and [COMMANDS.md](COMMANDS.md) for artisan commands.

## Testing

```bash
./vendor/bin/sail artisan test            # all tests
./vendor/bin/sail artisan test --parallel  # parallel execution
```

## CI/CD

GitHub Actions runs on push to `desarrollo` and `main`:
- PHPUnit test suite
- Laravel Pint code style check
- Composer security audit

## License

Proprietary — Gobierno del Estado.
```

**Step 2: Create `SETUP.md`**

```markdown
# Setup Guide

## Prerequisites

- Docker Desktop (or Docker Engine + Docker Compose)
- Node.js 18+ and npm
- Git

## Initial Setup

1. **Clone the repository:**
   ```bash
   git clone <repo-url>
   cd dte-spp-2026
   ```

2. **Copy environment file:**
   ```bash
   cp .env.example .env
   ```

3. **Start Sail containers:**
   ```bash
   ./vendor/bin/sail up -d
   ```
   This starts PostgreSQL, Redis, and the PHP application container.

4. **Install PHP dependencies:**
   ```bash
   ./vendor/bin/sail composer install
   ```

5. **Generate application key:**
   ```bash
   ./vendor/bin/sail artisan key:generate
   ```

6. **Run migrations and seed:**
   ```bash
   ./vendor/bin/sail artisan migrate --seed
   ```

7. **Install and build frontend assets:**
   ```bash
   npm install
   npm run build     # production build
   npm run dev       # or start Vite dev server
   ```

8. **Access the application:**
   Open http://localhost in your browser.

## Environment Variables

Key variables in `.env`:

| Variable | Description | Default |
|----------|-------------|---------|
| `APP_EJERCICIO_FISCAL` | Fiscal year | Current year |
| `REPORT_INSTITUCION` | Institution name for reports | Gobierno del Estado |
| `REPORT_DEPENDENCIA` | Agency name for reports | Secretaría de Planeación |
| `EVAL_PESO_FIN` | Weight for Fin level | 0.40 |
| `EVAL_PESO_PROPOSITO` | Weight for Proposito level | 0.30 |
| `EVAL_PESO_COMPONENTE` | Weight for Componente level | 0.20 |
| `EVAL_PESO_ACTIVIDAD` | Weight for Actividad level | 0.10 |

## Stopping the Environment

```bash
./vendor/bin/sail down        # stop containers
./vendor/bin/sail down -v     # stop and remove volumes (destroys DB data)
```
```

**Step 3: Create `COMMANDS.md`**

```markdown
# Artisan Commands Reference

## Application Commands

### Cascade / Periodos

| Command | Schedule | Description |
|---------|----------|-------------|
| `mir:abrir-periodos` | Daily 06:00 | Opens capture periods whose start date has passed |
| `mir:cerrar-vencidos` | Daily 23:00 | Marks overdue avances as VENCIDO |

### Tracking / Reports

| Command | Schedule | Description |
|---------|----------|-------------|
| `reports:cleanup` | Daily 03:00 | Deletes generated report files older than TTL (default: 24h) |
| `reports:cleanup --hours=48` | — | Override TTL hours |
| `reports:cleanup --dry-run` | — | Preview what would be deleted |

### AI / Embeddings

| Command | Schedule | Description |
|---------|----------|-------------|
| `app:embeddings-generate` | Daily 02:00 | Generates embeddings for new/updated records |
| `app:embeddings-queue` | — | Processes embeddings queue |
| `app:calcular-indice-eficacia` | — | Calculates efficacy index for programs |

### Maintenance

| Command | Schedule | Description |
|---------|----------|-------------|
| `llm:cleanup-logs` | Monthly | Purges detailed LLM logs older than 90 days (preserves monthly summaries) |
| `llm:cleanup-logs --days=60` | — | Override retention period |
| `llm:cleanup-logs --dry-run` | — | Preview what would be deleted |

## Development Commands

```bash
# Run tests
./vendor/bin/sail artisan test
./vendor/bin/sail artisan test --parallel
./vendor/bin/sail artisan test --filter=ClassName

# Code style
./vendor/bin/pint              # fix
./vendor/bin/pint --test       # check only

# Clear caches
./vendor/bin/sail artisan optimize:clear

# Fresh migration with seed
./vendor/bin/sail artisan migrate:fresh --seed
```

## Scheduled Tasks Overview

All scheduled commands are registered in `routes/console.php`:

| Time | Command |
|------|---------|
| 02:00 | `app:embeddings-generate` |
| 03:00 | `reports:cleanup` |
| 06:00 | `mir:abrir-periodos` |
| 23:00 | `mir:cerrar-vencidos` |
| Monthly | `llm:cleanup-logs` |
```

**Test:**

```bash
# Verify files exist and are valid markdown
head -1 README.md SETUP.md COMMANDS.md
```

**Commit:**

```bash
git add README.md SETUP.md COMMANDS.md
git commit -m "$(cat <<'EOF'
docs: add runbook documentation (README, SETUP, COMMANDS)

- README.md: project overview, quick start, stack, CI info
- SETUP.md: step-by-step installation guide with env vars reference
- COMMANDS.md: all artisan commands with schedules and usage examples

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```

---

### Task 8: Verificacion final

**Files:**
- No new files

**Step 1: Run the full test suite**

```bash
./vendor/bin/sail artisan test --parallel
```

**Expected:** All existing tests pass (baseline: 426 passed, 7 skipped) plus the new tests from this sprint:

- `OrphanFileCleanupTest` — 2 tests
- `AccessibilityTest` — 2 tests
- `SecurityHeadersTest` — 2 tests

**Total expected:** ~432 passed, 7 skipped.

**Step 2: Run Pint to verify code style**

```bash
./vendor/bin/pint --test
```

**Step 3: Run composer audit**

```bash
composer audit
```

**Step 4: Verify schedule registration**

```bash
./vendor/bin/sail artisan schedule:list
```

Expected output should include `reports:cleanup` at 03:00 alongside existing scheduled commands.

**Step 5: Verify new middleware is active**

```bash
./vendor/bin/sail artisan about | grep -i middleware
```

Or simply hit `/dashboard` and check response headers include `X-Frame-Options: DENY`.

**Commit:**

No commit for this task — it is a verification step only. If Pint reports issues, fix them first:

```bash
./vendor/bin/pint
git add -A
git commit -m "$(cat <<'EOF'
style: fix code style issues found during final verification

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
EOF
)"
```
