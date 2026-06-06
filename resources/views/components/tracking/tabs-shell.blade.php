@props([
    'active' => 'dashboard',
    'tabs' => ['dashboard' => 'Dashboard', 'tabla' => 'Tabla'],
    'model' => 'activeTab',
])

<x-page.tabs :tabs="$tabs" :active="$active" :model="$model" />

@php
    $__defined = get_defined_vars();
@endphp

@foreach ($tabs as $key => $label)
    @if ($active === $key)
        @if (isset($__defined[$key]))
            {{ $__defined[$key] }}
        @endif
    @endif
@endforeach
