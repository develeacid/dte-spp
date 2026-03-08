# Sprint 17: Ayuda Contextual / Tooltips Metodologicos — Plan de Implementacion

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Crear un sistema de ayuda contextual con tooltips metodologicos para guiar a los usuarios en el llenado de formularios MML/MIR. Los textos de ayuda se extraen de las definiciones ya existentes en los prompts de IA del proyecto.

**Architecture:** Componente Alpine.js `x-ui.tooltip` (refactor del existente), archivo de configuracion `config/glosario.php` con definiciones MML, componente compuesto `x-ui.help-label` (label + icono tooltip), y aplicacion de tooltips en MirEditor, indicadores y arboles problema/objetivos.

**Tech Stack:** Laravel 12, Alpine.js, Tailwind CSS (sin dependencias externas)

---

### Task 1: Refactorizar `x-ui.tooltip` — soporte hover/click, posicionamiento, texto largo

**Files:**
- Modify: `resources/views/components/ui/tooltip.blade.php`
- Test: manual browser verification

**Contexto:** Ya existe `resources/views/components/ui/tooltip.blade.php` con funcionalidad basica (hover, posicion fija a la derecha). Se necesita:
- Soporte para posicionamiento configurable (`top`, `bottom`, `left`, `right`)
- Soporte para click en movil (toggle)
- Texto largo con wrapping (no `whitespace-nowrap`)
- Cerrar al hacer click fuera
- Ancho maximo configurable

**Step 1: Reescribir el componente tooltip**

Reemplazar todo el contenido de `resources/views/components/ui/tooltip.blade.php` con:

```blade
@props([
    'text' => '',
    'position' => 'top',
    'maxWidth' => 'max-w-xs',
])

@php
    $positionClasses = match ($position) {
        'top'    => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
        'bottom' => 'top-full left-1/2 -translate-x-1/2 mt-2',
        'left'   => 'right-full top-1/2 -translate-y-1/2 mr-2',
        'right'  => 'left-full top-1/2 -translate-y-1/2 ml-2',
        default  => 'bottom-full left-1/2 -translate-x-1/2 mb-2',
    };

    $arrowClasses = match ($position) {
        'top'    => 'top-full left-1/2 -translate-x-1/2 border-t-gray-900 border-x-transparent border-b-transparent border-4',
        'bottom' => 'bottom-full left-1/2 -translate-x-1/2 border-b-gray-900 border-x-transparent border-t-transparent border-4',
        'left'   => 'left-full top-1/2 -translate-y-1/2 border-l-gray-900 border-y-transparent border-r-transparent border-4',
        'right'  => 'right-full top-1/2 -translate-y-1/2 border-r-gray-900 border-y-transparent border-l-transparent border-4',
        default  => 'top-full left-1/2 -translate-x-1/2 border-t-gray-900 border-x-transparent border-b-transparent border-4',
    };
@endphp

<div
    x-data="{ show: false }"
    x-on:mouseenter="show = true"
    x-on:mouseleave="show = false"
    x-on:click.away="show = false"
    class="relative inline-flex"
>
    <div x-on:click="show = !show">
        {{ $slot }}
    </div>

    <div
        x-show="show"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 {{ $positionClasses }}"
    >
        <div class="{{ $maxWidth }} rounded-md bg-gray-900 px-3 py-2 text-sm text-white shadow-lg pointer-events-none">
            {{ $text }}
        </div>
        <div class="absolute {{ $arrowClasses }} w-0 h-0"></div>
    </div>
</div>
```

**Verificacion:**
- El componente existente se usa en sidebar (`resources/views/components/layout/sidebar-nav.blade.php`), charts (`resources/views/components/charts/line.blade.php`, `bar-horizontal.blade.php`), y sidebar-group. Verificar que esos usos no se rompan (todos usan `text` + `slot`, que se mantienen).
- Buscar usos existentes: `grep -r "x-ui.tooltip" resources/views/` y confirmar compatibilidad.

**Commit:**
```
feat(ui): enhance x-ui.tooltip with positioning, click toggle & text wrap

Resolves DTE-S17-T1

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 2: Crear `config/glosario.php` con definiciones MML

**Files:**
- Create: `config/glosario.php`

**Contexto:** Las definiciones se extraen directamente de los prompts existentes en `resources/views/prompts/mir/` y `resources/views/prompts/mml/`. No inventar definiciones nuevas.

**Step 1: Crear el archivo de configuracion**

Crear `config/glosario.php`:

```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Glosario de terminos MML (Metodologia de Marco Logico)
    |--------------------------------------------------------------------------
    |
    | Definiciones extraidas de la normatividad SHCP/CONEVAL y los prompts
    | de validacion del sistema. Se usan para tooltips de ayuda contextual.
    |
    */

    // ── Niveles MIR ──────────────────────────────────────────────────

    'fin' => 'Nivel superior de la MIR. Describe la contribucion del programa a un objetivo de desarrollo superior. '
        . 'Sintaxis SHCP: "Contribuir a [impacto esperado] mediante [solucion principal]". '
        . 'Debe incluir un impacto claro y medible, y ser una sola oracion.',

    'proposito' => 'Resultado directo esperado sobre la poblacion objetivo. '
        . 'Sintaxis SHCP: "[Poblacion objetivo] + [verbo en presente/participio] + [condicion o resultado esperado]". '
        . 'Describe el cambio en la poblacion, no actividades ni productos.',

    'componente' => 'Bienes o servicios que produce el programa. '
        . 'Sintaxis SHCP: "[Bien o servicio] + [participio pasado (-ado/-ido)]". '
        . 'Ejemplos: "Becas otorgadas", "Talleres de capacitacion realizados". '
        . 'Es un producto terminado, no una accion en proceso.',

    'actividad' => 'Acciones necesarias para producir cada Componente. '
        . 'Sintaxis SHCP: "[Sustantivo deverbal] + [complemento]". '
        . 'Debe iniciar con un sustantivo deverbal (Elaboracion, Diseno, Distribucion), '
        . 'NO con un verbo en infinitivo.',

    // ── Columnas MIR ─────────────────────────────────────────────────

    'resumen_narrativo' => 'Descripcion del objetivo de cada nivel de la MIR. '
        . 'Cada nivel tiene una formula sintactica obligatoria definida por la SHCP. '
        . 'El resumen debe ser una sola oracion clara y concreta.',

    'supuestos' => 'Condiciones externas que deben cumplirse para que la logica causal funcione. '
        . 'Son factores fuera del control del programa que, de no cumplirse, '
        . 'impedirian alcanzar el objetivo del nivel correspondiente.',

    'indicador' => 'Expresion cuantitativa que mide el logro del objetivo de cada nivel. '
        . 'Puede ser de tipo estrategico (mide Fin y Proposito) '
        . 'o de gestion (mide Componentes y Actividades). '
        . 'Debe definir nombre, formula, tipo, dimension y frecuencia.',

    'medios_verificacion' => 'Fuentes de informacion que permiten verificar el valor del indicador. '
        . 'Deben poder proporcionar los datos necesarios para calcular la formula del indicador. '
        . 'Su frecuencia debe ser compatible con la frecuencia del indicador.',

    // ── CREMAA ───────────────────────────────────────────────────────

    'cremaa' => 'Criterios de calidad para indicadores segun CONEVAL: '
        . 'Claro, Relevante, Economico, Monitoreable, Adecuado y Aportante.',

    'cremaa_claro' => 'El indicador es facil de entender. Su nombre y formula son comprensibles sin ambiguedad.',

    'cremaa_relevante' => 'El indicador refleja adecuadamente el objetivo del nivel al que pertenece.',

    'cremaa_economico' => 'El indicador se puede medir sin costo excesivo. Los datos estan disponibles.',

    'cremaa_monitoreable' => 'El indicador es sujeto de verificacion independiente. Se puede auditar.',

    'cremaa_adecuado' => 'El indicador es proporcional al objetivo medido. No es ni muy amplio ni muy estrecho.',

    'cremaa_aportante' => 'El indicador provee informacion util para la toma de decisiones. '
        . 'Tambien llamado "Aportacion marginal".',

    // ── Logica MIR ───────────────────────────────────────────────────

    'logica_vertical' => 'Coherencia de la cadena causal entre niveles de la MIR: '
        . 'las Actividades deben ser suficientes y necesarias para producir sus Componentes; '
        . 'los Componentes deben ser suficientes y necesarios para lograr el Proposito; '
        . 'el Proposito debe contribuir directamente al Fin. '
        . 'No debe haber saltos logicos entre niveles.',

    'logica_horizontal' => 'Consistencia dentro de cada fila de la MIR: '
        . 'el indicador debe medir lo descrito en el Resumen Narrativo; '
        . 'la dimension del indicador debe ser apropiada para el nivel; '
        . 'el medio de verificacion debe proporcionar los datos para calcular el indicador; '
        . 'la frecuencia del medio debe ser compatible con la del indicador.',

    // ── Indicador: atributos ─────────────────────────────────────────

    'tipo_indicador_estrategico' => 'Indicador de tipo estrategico: mide los niveles de Fin y Proposito. '
        . 'Evalua el logro de resultados e impactos.',

    'tipo_indicador_gestion' => 'Indicador de tipo gestion: mide los niveles de Componente y Actividad. '
        . 'Evalua procesos, productos y servicios entregados.',

    'sentido_ascendente' => 'Un valor mayor del indicador refleja un mejor desempeno. '
        . 'Ejemplo: tasa de cobertura, porcentaje de aprobacion.',

    'sentido_descendente' => 'Un valor menor del indicador refleja un mejor desempeno. '
        . 'Ejemplo: tasa de desercion, indice de mortalidad.',

    'sentido_regular' => 'El desempeno optimo se alcanza cuando el indicador se mantiene '
        . 'en un valor o rango especifico, sin que mas o menos sea mejor.',

    // ── Arbol de problemas / objetivos ───────────────────────────────

    'problema_central' => 'Situacion no deseada que el programa busca atender. '
        . 'Debe ser una condicion negativa verificable, no la ausencia de una solucion. '
        . 'No debe contener verbos que impliquen soluciones (implementar, crear, mejorar).',

    'causa_directa' => 'Factor que contribuye directamente a producir el problema central.',

    'causa_indirecta' => 'Factor que contribuye indirectamente, a traves de una causa directa.',

    'efecto_directo' => 'Consecuencia inmediata del problema central.',

    'efecto_indirecto' => 'Consecuencia de segundo orden, derivada de un efecto directo.',

    'formula_indicador' => 'Expresion matematica que define como se calcula el indicador. '
        . 'Usa variables con simbolos (A, B, C...). Ejemplo: "(A / B) x 100".',

];
```

**Commit:**
```
feat(glosario): add config/glosario.php with MML term definitions

Definitions extracted from existing SHCP/CONEVAL prompts in
resources/views/prompts/mir/ and resources/views/prompts/mml/.

Resolves DTE-S17-T2

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 3: Crear `x-ui.help-label` — componente compuesto (label + icono tooltip)

**Files:**
- Create: `resources/views/components/ui/help-label.blade.php`

**Contexto:** Este componente combina un `<label>` con un icono de interrogacion que muestra un tooltip al hacer hover/click. Recibe una key del glosario para obtener la definicion automaticamente.

**Step 1: Crear el componente**

Crear `resources/views/components/ui/help-label.blade.php`:

```blade
@props([
    'for' => null,
    'glossary' => null,
    'help' => null,
    'position' => 'top',
])

@php
    $helpText = $help ?? ($glossary ? config("glosario.{$glossary}", '') : '');
@endphp

<label {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 text-sm font-medium text-gray-700']) }} @if($for) for="{{ $for }}" @endif>
    {{ $slot }}

    @if ($helpText)
        <x-ui.tooltip :text="$helpText" :position="$position">
            <span class="inline-flex items-center justify-center h-4 w-4 rounded-full bg-gray-200 text-gray-500 hover:bg-indigo-100 hover:text-indigo-600 cursor-help transition-colors" tabindex="0">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M12 18.75h.007v.008H12v-.008z" />
                </svg>
            </span>
        </x-ui.tooltip>
    @endif
</label>
```

**Uso esperado:**

```blade
{{-- Con key del glosario --}}
<x-ui.help-label glossary="resumen_narrativo">
    Resumen Narrativo
</x-ui.help-label>

{{-- Con texto libre --}}
<x-ui.help-label help="Texto de ayuda personalizado">
    Mi campo
</x-ui.help-label>

{{-- Sin ayuda (se comporta como label normal) --}}
<x-ui.help-label>
    Campo sin ayuda
</x-ui.help-label>
```

**Commit:**
```
feat(ui): add x-ui.help-label compound component (label + tooltip icon)

Accepts a `glossary` key to auto-fetch definition from config/glosario.php,
or a `help` prop for custom text. Falls back to plain label if neither is set.

Resolves DTE-S17-T3

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 4: Aplicar tooltips al MirEditor

**Files:**
- Modify: `resources/views/livewire/mml/mir-editor.blade.php`
- Modify: `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`

**Contexto:** La MIR se muestra como una tabla con columnas: Nivel, Resumen Narrativo, Indicadores, Medios de Verificacion, Supuestos. Los tooltips se agregan en los encabezados de la tabla y dentro de las celdas de cada fila.

**Step 1: Agregar tooltips a los encabezados de la tabla MIR**

En `resources/views/livewire/mml/mir-editor.blade.php`, reemplazar los `<th>` del `<thead>` (lineas ~24-29).

Cambiar:
```blade
<th class="w-16 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Nivel</th>
<th class="w-1/4 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Resumen Narrativo</th>
<th class="w-1/3 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Indicadores</th>
<th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Medios de Verificacion</th>
<th class="w-1/6 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Supuestos</th>
```

A:
```blade
<th class="w-16 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">Nivel</th>
<th class="w-1/4 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
    <x-ui.help-label glossary="resumen_narrativo" class="text-xs font-medium uppercase text-gray-500">
        Resumen Narrativo
    </x-ui.help-label>
</th>
<th class="w-1/3 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
    <x-ui.help-label glossary="indicador" class="text-xs font-medium uppercase text-gray-500">
        Indicadores
    </x-ui.help-label>
</th>
<th class="px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
    <x-ui.help-label glossary="medios_verificacion" class="text-xs font-medium uppercase text-gray-500">
        Medios de Verificacion
    </x-ui.help-label>
</th>
<th class="w-1/6 px-3 py-3 text-left text-xs font-medium uppercase text-gray-500">
    <x-ui.help-label glossary="supuestos" class="text-xs font-medium uppercase text-gray-500">
        Supuestos
    </x-ui.help-label>
</th>
```

**Step 2: Agregar tooltip contextual al nivel en mir-nivel-row**

En `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`, agregar un tooltip junto al badge del nivel (linea ~11-13).

Cambiar:
```blade
<span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $tipoEnum?->colorClass() }}">
    {{ $tipoEnum?->label() }}
</span>
```

A:
```blade
<x-ui.tooltip :text="config('glosario.' . $tipoEnum?->value, '')" position="right">
    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold {{ $tipoEnum?->colorClass() }} cursor-help">
        {{ $tipoEnum?->label() }}
    </span>
</x-ui.tooltip>
```

**Step 3: Agregar tooltip a "Validar CREMAA"**

En `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`, agregar tooltip junto al boton CREMAA (~linea 269-277).

Despues del `<div class="flex items-center gap-2">` (linea 269), agregar un tooltip al texto "Validar CREMAA":

Cambiar:
```blade
<div class="flex items-center gap-2">
    <button
        wire:click="validarCremaa({{ $indicador->id }})"
```

A:
```blade
<div class="flex items-center gap-2">
    <x-ui.tooltip :text="config('glosario.cremaa')" position="top">
        <span class="text-xs text-gray-400 cursor-help">?</span>
    </x-ui.tooltip>
    <button
        wire:click="validarCremaa({{ $indicador->id }})"
```

**Step 4: Agregar tooltip a la formula del indicador**

En `resources/views/livewire/mml/partials/mir-nivel-row.blade.php`, agregar tooltip al label "Formula:" (~linea 204).

Cambiar:
```blade
<span class="text-xs font-medium text-gray-500">Formula:</span>
```

A:
```blade
<x-ui.help-label glossary="formula_indicador" class="text-xs font-medium text-gray-500">
    Formula
</x-ui.help-label>
```

**Verificacion:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```
Confirmar que las vistas compilan sin errores.

**Commit:**
```
feat(mir): apply contextual tooltips to MIR editor table and fields

Add glossary-backed tooltips to table headers (Resumen Narrativo,
Indicadores, Medios de Verificacion, Supuestos), nivel badges,
CREMAA validation, and formula labels.

Resolves DTE-S17-T4

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 5: Aplicar tooltips a formularios de arbol de problemas/objetivos

**Files:**
- Modify: `resources/views/livewire/mml/definicion-problema.blade.php`
- Modify: `resources/views/livewire/mml/arbol-problema-builder.blade.php`
- Modify: `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`

**Step 1: Tooltip en Definicion del Problema (Etapa 1)**

En `resources/views/livewire/mml/definicion-problema.blade.php`, reemplazar el label del problema central (~linea 32-34).

Cambiar:
```blade
<label for="descripcion" class="block text-sm font-medium text-gray-700">
    Descripcion del problema
</label>
```

A:
```blade
<x-ui.help-label for="descripcion" glossary="problema_central" class="block text-sm font-medium text-gray-700">
    Descripcion del problema
</x-ui.help-label>
```

**Step 2: Tooltip en encabezado del Arbol del Problema (Etapa 2)**

En `resources/views/livewire/mml/arbol-problema-builder.blade.php`, agregar tooltip al titulo "Efectos" (~linea 40).

Cambiar:
```blade
<h3 class="text-lg font-semibold text-gray-900 mb-3">Efectos</h3>
```

A:
```blade
<h3 class="text-lg font-semibold text-gray-900 mb-3">
    <x-ui.help-label glossary="efecto_directo" class="text-lg font-semibold text-gray-900">
        Efectos
    </x-ui.help-label>
</h3>
```

Buscar tambien el encabezado "Causas" en el mismo archivo y aplicar tooltip equivalente con key `causa_directa`.

**Step 3: Tooltip en Arbol de Objetivos (Etapa 3)**

En `resources/views/livewire/mml/arbol-objetivos-builder.blade.php`, buscar encabezados "Problema (Original)" y agregar tooltip descriptivo:

Cambiar:
```blade
<h3 class="text-sm font-semibold text-red-700 uppercase tracking-wide">Problema (Original)</h3>
```

A:
```blade
<h3 class="text-sm font-semibold text-red-700 uppercase tracking-wide">
    <x-ui.help-label glossary="problema_central" class="text-sm font-semibold text-red-700 uppercase tracking-wide">
        Problema (Original)
    </x-ui.help-label>
</h3>
```

**Verificacion:**
```bash
./vendor/bin/sail artisan view:cache 2>&1 | head -5
./vendor/bin/sail artisan view:clear
```

**Commit:**
```
feat(mml): apply contextual tooltips to problem/objective tree forms

Add glossary tooltips to Etapa 1 (problema central label),
Etapa 2 (efectos/causas headers), and Etapa 3 (problem/objective headers).

Resolves DTE-S17-T5

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```

---

### Task 6: Tests y verificacion final

**Files:**
- Create: `tests/Feature/Components/TooltipComponentTest.php`
- Create: `tests/Feature/Components/HelpLabelComponentTest.php`
- Create: `tests/Unit/GlosarioConfigTest.php`

**Step 1: Test del componente tooltip**

Crear `tests/Feature/Components/TooltipComponentTest.php`:

```php
<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class TooltipComponentTest extends TestCase
{
    /** @test */
    public function tooltip_renders_with_text(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Texto de ayuda"><span>Trigger</span></x-ui.tooltip>'
        );

        $view->assertSee('Texto de ayuda');
        $view->assertSee('Trigger');
    }

    /** @test */
    public function tooltip_renders_with_top_position(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" position="top"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('bottom-full');
    }

    /** @test */
    public function tooltip_renders_with_right_position(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" position="right"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('left-full');
    }

    /** @test */
    public function tooltip_renders_with_custom_max_width(): void
    {
        $view = $this->blade(
            '<x-ui.tooltip text="Help" maxWidth="max-w-md"><span>T</span></x-ui.tooltip>'
        );

        $view->assertSee('max-w-md');
    }
}
```

**Step 2: Test del componente help-label**

Crear `tests/Feature/Components/HelpLabelComponentTest.php`:

```php
<?php

namespace Tests\Feature\Components;

use Tests\TestCase;

class HelpLabelComponentTest extends TestCase
{
    /** @test */
    public function help_label_renders_with_glossary_key(): void
    {
        $view = $this->blade(
            '<x-ui.help-label glossary="fin">Fin</x-ui.help-label>'
        );

        $view->assertSee('Fin');
        $view->assertSee(config('glosario.fin'));
    }

    /** @test */
    public function help_label_renders_with_custom_help_text(): void
    {
        $view = $this->blade(
            '<x-ui.help-label help="Custom help">Label</x-ui.help-label>'
        );

        $view->assertSee('Custom help');
    }

    /** @test */
    public function help_label_renders_without_tooltip_when_no_help(): void
    {
        $view = $this->blade(
            '<x-ui.help-label>Plain Label</x-ui.help-label>'
        );

        $view->assertSee('Plain Label');
        $view->assertDontSee('cursor-help');
    }

    /** @test */
    public function help_label_renders_for_attribute(): void
    {
        $view = $this->blade(
            '<x-ui.help-label for="campo1" glossary="fin">Fin</x-ui.help-label>'
        );

        $view->assertSee('for="campo1"', false);
    }
}
```

**Step 3: Test del config glosario**

Crear `tests/Unit/GlosarioConfigTest.php`:

```php
<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class GlosarioConfigTest extends TestCase
{
    private array $glosario;

    protected function setUp(): void
    {
        parent::setUp();
        $this->glosario = require __DIR__ . '/../../config/glosario.php';
    }

    /** @test */
    public function glosario_has_all_mir_level_keys(): void
    {
        $requiredKeys = ['fin', 'proposito', 'componente', 'actividad'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
            $this->assertNotEmpty($this->glosario[$key], "Empty value for: {$key}");
        }
    }

    /** @test */
    public function glosario_has_all_mir_column_keys(): void
    {
        $requiredKeys = ['resumen_narrativo', 'supuestos', 'indicador', 'medios_verificacion'];

        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_all_cremaa_keys(): void
    {
        $cremaaKeys = [
            'cremaa', 'cremaa_claro', 'cremaa_relevante', 'cremaa_economico',
            'cremaa_monitoreable', 'cremaa_adecuado', 'cremaa_aportante',
        ];

        foreach ($cremaaKeys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing CREMAA key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_logic_keys(): void
    {
        $this->assertArrayHasKey('logica_vertical', $this->glosario);
        $this->assertArrayHasKey('logica_horizontal', $this->glosario);
    }

    /** @test */
    public function glosario_has_sentido_keys(): void
    {
        $keys = ['sentido_ascendente', 'sentido_descendente', 'sentido_regular'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function glosario_has_tree_keys(): void
    {
        $keys = ['problema_central', 'causa_directa', 'causa_indirecta', 'efecto_directo', 'efecto_indirecto'];

        foreach ($keys as $key) {
            $this->assertArrayHasKey($key, $this->glosario, "Missing key: {$key}");
        }
    }

    /** @test */
    public function all_values_are_non_empty_strings(): void
    {
        foreach ($this->glosario as $key => $value) {
            $this->assertIsString($value, "Value for '{$key}' should be string");
            $this->assertNotEmpty($value, "Value for '{$key}' should not be empty");
        }
    }
}
```

**Step 4: Ejecutar todos los tests**

```bash
./vendor/bin/sail artisan test tests/Unit/GlosarioConfigTest.php
./vendor/bin/sail artisan test tests/Feature/Components/TooltipComponentTest.php
./vendor/bin/sail artisan test tests/Feature/Components/HelpLabelComponentTest.php
./vendor/bin/sail artisan test --parallel
```

**Verificacion visual (manual):**
1. Navegar a una MIR existente y verificar que los tooltips aparecen al hover sobre encabezados y badges de nivel
2. En movil (o dev tools responsive), verificar que los tooltips aparecen al tap
3. Navegar a Etapa 1, 2 y 3 para verificar tooltips en arboles
4. Verificar que los tooltips del sidebar siguen funcionando correctamente (regresion)

**Commit:**
```
test(tooltips): add tests for tooltip, help-label components and glosario config

Resolves DTE-S17-T6

Co-Authored-By: Claude Opus 4.6 <noreply@anthropic.com>
```
