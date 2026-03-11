# Monitoreo IA — Botón "Probar Conexión" Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Agregar un botón "Probar Conexión" en la página `/admin/monitoreo-ia` que ejecuta una llamada real a la API de embeddings y muestra un panel de debug con el resultado.

**Architecture:** Nuevo método `probarConexion()` en el Livewire component `MonitoreoIa` que usa `Http::` de Laravel para llamar al endpoint configurado en `.env`. El resultado se guarda en propiedad `$resultadoConexion` (array|null) y el blade lo renderiza condicionalmente. El botón va en el slot del `x-page.header`.

**Tech Stack:** Livewire 3, Laravel `Http` facade, `x-page.header` slot pattern (ver `resources/views/components/page/header.blade.php`).

---

### Task 1: Método `probarConexion()` en el componente Livewire

**Files:**
- Modify: `app/Livewire/Admin/MonitoreoIa.php`
- Test: `tests/Feature/Admin/MonitoreoIaTest.php`

**Step 1: Escribir los tests que fallan**

Agregar al final de `MonitoreoIaTest` (antes del cierre de clase `}`):

```php
public function test_probar_conexion_returns_success_result(): void
{
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'data' => [['embedding' => array_fill(0, 1536, 0.1)]],
            'usage' => ['prompt_tokens' => 2, 'total_tokens' => 2],
            'model' => 'text-embedding-ada-002',
        ], 200),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(MonitoreoIa::class)
        ->call('probarConexion');

    $component->assertSet('resultadoConexion.estado', 'ok');
    $component->assertSet('resultadoConexion.modelo', 'text-embedding-ada-002');
    $component->assertSet('resultadoConexion.dimensiones', 1536);
    $this->assertArrayHasKey('latencia_ms', $component->get('resultadoConexion'));
    $this->assertArrayHasKey('api_key_preview', $component->get('resultadoConexion'));
    $this->assertArrayHasKey('url', $component->get('resultadoConexion'));
}

public function test_probar_conexion_returns_error_on_api_failure(): void
{
    Http::fake([
        'https://api.openai.com/v1/embeddings' => Http::response([
            'error' => ['message' => 'Incorrect API key provided', 'type' => 'invalid_request_error'],
        ], 401),
    ]);

    $component = Livewire::actingAs($this->admin)
        ->test(MonitoreoIa::class)
        ->call('probarConexion');

    $component->assertSet('resultadoConexion.estado', 'error');
    $this->assertArrayHasKey('mensaje_error', $component->get('resultadoConexion'));
}

public function test_probar_conexion_requires_admin(): void
{
    Livewire::actingAs($this->regularUser)
        ->test(MonitoreoIa::class)
        ->call('probarConexion')
        ->assertForbidden();
}
```

Agregar import al tope del archivo (junto a los existentes):
```php
use Illuminate\Support\Facades\Http;
```

**Step 2: Correr los tests — verificar que fallan**

```bash
./vendor/bin/sail artisan test tests/Feature/Admin/MonitoreoIaTest.php --filter="probar_conexion" -v
```

Esperado: 3 FAILs — "Call to undefined method probarConexion"

**Step 3: Implementar `probarConexion()` en el componente**

En `app/Livewire/Admin/MonitoreoIa.php`:

Agregar import al tope:
```php
use Illuminate\Support\Facades\Http;
```

Agregar propiedad pública después de `$fechaHasta`:
```php
public ?array $resultadoConexion = null;
```

Agregar el método (antes de `render()`):
```php
public function probarConexion(): void
{
    abort_unless(auth()->user()->can('administrar_usuarios'), 403);

    $apiKey  = config('services.embedding.api_key', env('EMBEDDING_API_KEY', ''));
    $apiUrl  = config('services.embedding.url', env('EMBEDDING_API_URL', 'https://api.openai.com/v1/embeddings'));
    $model   = config('services.embedding.model', env('EMBEDDING_MODEL', 'text-embedding-ada-002'));

    $keyPreview = strlen($apiKey) >= 6
        ? '...' . substr($apiKey, -6)
        : '(no configurada)';

    $inicio = microtime(true);

    try {
        $response = Http::withToken($apiKey)
            ->timeout(15)
            ->post($apiUrl, [
                'model' => $model,
                'input' => 'test',
            ]);

        $latencia = (int) round((microtime(true) - $inicio) * 1000);

        if ($response->successful()) {
            $data = $response->json();
            $embedding = $data['data'][0]['embedding'] ?? [];

            $this->resultadoConexion = [
                'estado'        => 'ok',
                'modelo'        => $data['model'] ?? $model,
                'dimensiones'   => count($embedding),
                'latencia_ms'   => $latencia,
                'tokens_usados' => $data['usage']['total_tokens'] ?? 0,
                'costo_usd'     => round(($data['usage']['total_tokens'] ?? 0) * 0.0000001, 8),
                'api_key_preview' => $keyPreview,
                'url'           => $apiUrl,
            ];
        } else {
            $error = $response->json('error.message') ?? $response->body();
            $this->resultadoConexion = [
                'estado'         => 'error',
                'latencia_ms'    => $latencia,
                'api_key_preview' => $keyPreview,
                'url'            => $apiUrl,
                'http_status'    => $response->status(),
                'mensaje_error'  => $error,
            ];
        }
    } catch (\Throwable $e) {
        $latencia = (int) round((microtime(true) - $inicio) * 1000);
        $this->resultadoConexion = [
            'estado'         => 'error',
            'latencia_ms'    => $latencia,
            'api_key_preview' => $keyPreview,
            'url'            => $apiUrl,
            'mensaje_error'  => $e->getMessage(),
        ];
    }
}
```

**Step 4: Correr los tests — verificar que pasan**

```bash
./vendor/bin/sail artisan test tests/Feature/Admin/MonitoreoIaTest.php --filter="probar_conexion" -v
```

Esperado: 3 PASSs

**Step 5: Correr suite completa — verificar sin regresiones**

```bash
./vendor/bin/sail artisan test tests/Feature/Admin/MonitoreoIaTest.php -v
```

Esperado: todos pasan.

**Step 6: Commit**

```bash
git add app/Livewire/Admin/MonitoreoIa.php tests/Feature/Admin/MonitoreoIaTest.php
git commit -m "feat(admin): add probarConexion method to MonitoreoIa"
```

---

### Task 2: Botón y panel de debug en el blade

**Files:**
- Modify: `resources/views/livewire/admin/monitoreo-ia.blade.php`

**Step 1: Agregar el botón en el header**

Reemplazar el bloque del header (líneas 1-4 del blade):

```blade
<x-page.header>
    <x-slot name="title">Monitoreo de IA</x-slot>

    <button
        wire:click="probarConexion"
        wire:loading.attr="disabled"
        wire:target="probarConexion"
        class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50"
    >
        <svg wire:loading.remove wire:target="probarConexion" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
        </svg>
        <svg wire:loading wire:target="probarConexion" class="h-4 w-4 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span wire:loading.remove wire:target="probarConexion">Probar conexión</span>
        <span wire:loading wire:target="probarConexion">Probando...</span>
    </button>
</x-page.header>
```

**Step 2: Agregar el panel de debug**

Agregar inmediatamente después del bloque del header (antes de `{{-- Filtros de periodo --}}`):

```blade
@if($resultadoConexion !== null)
    <div class="mb-6 rounded-lg border {{ $resultadoConexion['estado'] === 'ok' ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }} p-4">
        <div class="flex items-start gap-3">
            @if($resultadoConexion['estado'] === 'ok')
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
            @else
                <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
            @endif

            <div class="flex-1">
                <p class="text-sm font-semibold {{ $resultadoConexion['estado'] === 'ok' ? 'text-green-800' : 'text-red-800' }}">
                    {{ $resultadoConexion['estado'] === 'ok' ? 'Conexión exitosa' : 'Error de conexión' }}
                </p>

                <dl class="mt-2 grid grid-cols-2 gap-x-6 gap-y-1 text-sm sm:grid-cols-3 lg:grid-cols-4">
                    <div>
                        <dt class="font-medium text-gray-600">URL</dt>
                        <dd class="text-gray-800 truncate">{{ $resultadoConexion['url'] }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-600">API Key</dt>
                        <dd class="font-mono text-gray-800">{{ $resultadoConexion['api_key_preview'] }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-gray-600">Latencia</dt>
                        <dd class="text-gray-800">{{ $resultadoConexion['latencia_ms'] }} ms</dd>
                    </div>

                    @if($resultadoConexion['estado'] === 'ok')
                        <div>
                            <dt class="font-medium text-gray-600">Modelo</dt>
                            <dd class="text-gray-800">{{ $resultadoConexion['modelo'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Dimensiones</dt>
                            <dd class="text-gray-800">{{ number_format($resultadoConexion['dimensiones']) }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Tokens usados</dt>
                            <dd class="text-gray-800">{{ $resultadoConexion['tokens_usados'] }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-gray-600">Costo estimado</dt>
                            <dd class="text-gray-800">${{ $resultadoConexion['costo_usd'] }}</dd>
                        </div>
                    @else
                        @if(isset($resultadoConexion['http_status']))
                            <div>
                                <dt class="font-medium text-gray-600">HTTP Status</dt>
                                <dd class="text-gray-800">{{ $resultadoConexion['http_status'] }}</dd>
                            </div>
                        @endif
                        <div class="col-span-2">
                            <dt class="font-medium text-gray-600">Error</dt>
                            <dd class="text-red-700">{{ $resultadoConexion['mensaje_error'] }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <button wire:click="$set('resultadoConexion', null)" class="text-gray-400 hover:text-gray-600">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z"/>
                </svg>
            </button>
        </div>
    </div>
@endif
```

**Step 3: Verificar en browser**

Abrir `http://srv1476074.hstgr.cloud/admin/monitoreo-ia` con `ele.admin@gmail.com`.
- Verificar que el botón "Probar conexión" aparece en el header
- Presionar el botón — debe mostrar spinner mientras carga
- Verificar que el panel verde aparece con los datos correctos
- Verificar que la X cierra el panel

**Step 4: Commit**

```bash
git add resources/views/livewire/admin/monitoreo-ia.blade.php
git commit -m "feat(admin): add connection test panel UI to MonitoreoIa"
```

---

### Task 3: Deploy a producción

**Step 1: Push y pull en VPS**

```bash
git push origin desarrollo
ssh dte-spp-vps "cd /var/www/dte-spp && git pull origin desarrollo"
```

**Step 2: Limpiar caché**

```bash
ssh dte-spp-vps "cd /var/www/dte-spp && docker compose -f docker-compose.prod.yml exec laravel.test php artisan optimize:clear"
```

**Step 3: Verificar en producción**

Abrir `http://srv1476074.hstgr.cloud/admin/monitoreo-ia` — probar el botón y verificar que devuelve respuesta exitosa con 1536 dimensiones.
