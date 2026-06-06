@props([
    'title' => null,
    'subtitle' => null,
    'breadcrumbs' => [],
    'fluid' => false,
])

@php
    $widthClasses = $fluid
        ? 'w-full px-4 sm:px-6 lg:px-8 py-6 space-y-6'
        : 'max-w-7xl mx-auto sm:px-6 lg:px-8 py-6 space-y-6';
@endphp

<div {{ $attributes->merge(['class' => $widthClasses]) }}>

    {{-- 1. Breadcrumb --}}
    @if(!empty($breadcrumbs))
        <nav class="text-sm text-gray-500 flex space-x-1">
            @foreach($breadcrumbs as $crumb)
                @if(!$loop->last)
                    <a href="{{ $crumb['url'] }}" class="hover:text-gray-700">{{ $crumb['label'] }}</a>
                    <span>/</span>
                @else
                    <span class="text-gray-700 font-medium">{{ $crumb['label'] }}</span>
                @endif
            @endforeach
        </nav>
    @endif

    {{-- 2. Header con título y acciones --}}
    @if($title)
        <x-page.header :title="$title" :subtitle="$subtitle">
            @if(!empty($actions))
                {{ $actions }}
            @endif
        </x-page.header>
    @elseif(!empty($actions))
        <div class="flex justify-end">
            {{ $actions }}
        </div>
    @endif

    {{-- 3. Flash message --}}
    @if(session('message') || session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            {{ session('message') ?? session('status') }}
        </div>
    @endif

    {{-- 4. Contenido principal --}}
    <div class="space-y-6">
        {{ $slot }}
    </div>

    {{-- 5. Footer (botones Guardar/Cancelar) --}}
    @if(!empty($footer))
        <div class="mt-8 pt-5 border-t border-gray-200 bg-white rounded-lg p-4 flex justify-end space-x-3 sticky bottom-0 shadow-md">
            {{ $footer }}
        </div>
    @endif

</div>
