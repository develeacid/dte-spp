{{-- Navigation structure — role-based visibility via @can --}}

{{-- Inicio --}}
<x-ui.sidebar-item href="{{ route('dashboard') }}" :active="request()->routeIs('dashboard')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M2.25 12l8.954-8.955a1.126 1.126 0 011.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/></svg>
    </x-slot:icon>
    Inicio
</x-ui.sidebar-item>

{{-- Planeación --}}
@canany(['crear_programa', 'editar_mir'])
<x-ui.sidebar-group label="Planeación" :active="request()->routeIs('mml.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('mml.programas') }}" class="block py-1 hover:text-brand-light">Programas</a>
        <a href="{{ route('mml.importaciones') }}" class="block py-1 hover:text-brand-light">Importaciones</a>
    </x-slot:tooltip>

    @can('crear_programa')
        <x-ui.sidebar-item href="{{ route('mml.programas') }}" :active="request()->routeIs('mml.programas') || request()->routeIs('mml.etapa*') || request()->routeIs('mml.mir')">
            Programas
        </x-ui.sidebar-item>
        <x-ui.sidebar-item href="{{ route('mml.importaciones') }}" :active="request()->routeIs('mml.importar*') || request()->routeIs('mml.importaciones')">
            Importaciones
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany

{{-- Seguimiento --}}
@canany(['revisar_avance', 'capturar_avance', 'ver_sabana_captura', 'ver_concentrado_captura'])
<x-ui.sidebar-group label="Seguimiento" :active="request()->routeIs('tracking.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        @can('revisar_avance')<a href="{{ route('tracking.panel') }}" class="block py-1 hover:text-brand-light">Panel</a>@endcan
        @can('capturar_avance')<a href="{{ route('tracking.pendientes') }}" class="block py-1 hover:text-brand-light">Mis Indicadores</a>@endcan
        @can('ver_sabana_captura')<a href="{{ route('tracking.sabana-captura') }}" class="block py-1 hover:text-brand-light">Sábana de Captura</a>@endcan
        @can('ver_concentrado_captura')<a href="{{ route('tracking.concentrado-captura') }}" class="block py-1 hover:text-brand-light">Concentrado</a>@endcan
    </x-slot:tooltip>

    @can('revisar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.panel') }}" :active="request()->routeIs('tracking.panel')">
            Panel
        </x-ui.sidebar-item>
    @endcan
    @can('capturar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.pendientes') }}" :active="request()->routeIs('tracking.pendientes')">
            Mis Indicadores
        </x-ui.sidebar-item>
    @endcan
    @can('revisar_avance')
        <x-ui.sidebar-item href="{{ route('tracking.vencidos') }}" :active="request()->routeIs('tracking.vencidos')">
            Vencidos
        </x-ui.sidebar-item>
    @endcan
    @can('ver_sabana_captura')
        <x-ui.sidebar-item href="{{ route('tracking.sabana-captura') }}" :active="request()->routeIs('tracking.sabana-captura')">
            Sábana de Captura
        </x-ui.sidebar-item>
    @endcan
    @can('ver_concentrado_captura')
        <x-ui.sidebar-item href="{{ route('tracking.concentrado-captura') }}" :active="request()->routeIs('tracking.concentrado-captura')">
            Concentrado
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany

{{-- Presupuesto --}}
@canany(['ver_datos_financieros', 'gestionar_presupuesto', 'capturar_avance_financiero', 'exportar_cuenta_publica'])
<x-ui.sidebar-group label="Presupuesto" :active="request()->routeIs('presupuesto.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        @can('ver_datos_financieros')<a href="{{ route('presupuesto.panel') }}" class="block py-1 hover:text-brand-light">Panel</a>@endcan
        @can('gestionar_presupuesto')<a href="{{ route('presupuesto.partidas') }}" class="block py-1 hover:text-brand-light">Partidas</a>@endcan
        @can('exportar_cuenta_publica')<a href="{{ route('presupuesto.cuenta-publica') }}" class="block py-1 hover:text-brand-light">Cuenta Pública</a>@endcan
    </x-slot:tooltip>

    @can('ver_datos_financieros')
        <x-ui.sidebar-item href="{{ route('presupuesto.panel') }}" :active="request()->routeIs('presupuesto.panel')">
            Panel
        </x-ui.sidebar-item>
    @endcan
    @can('gestionar_presupuesto')
        <x-ui.sidebar-item href="{{ route('presupuesto.partidas') }}" :active="request()->routeIs('presupuesto.partidas*')">
            Partidas
        </x-ui.sidebar-item>
    @endcan
    @can('capturar_avance_financiero')
        <x-ui.sidebar-item href="{{ route('presupuesto.panel') }}" :active="request()->routeIs('presupuesto.captura*')">
            Captura Avance
        </x-ui.sidebar-item>
    @endcan
    @can('gestionar_presupuesto')
        <x-ui.sidebar-item href="{{ route('presupuesto.importar') }}" :active="request()->routeIs('presupuesto.importar')">
            Importar
        </x-ui.sidebar-item>
    @endcan
    @can('exportar_cuenta_publica')
        <x-ui.sidebar-item href="{{ route('presupuesto.cuenta-publica') }}" :active="request()->routeIs('presupuesto.cuenta-publica')">
            Cuenta Pública
        </x-ui.sidebar-item>
    @endcan
</x-ui.sidebar-group>
@endcanany

{{-- Jurídico --}}
@can('ver_sustento_legal')
<x-ui.sidebar-group label="Jurídico" :active="request()->routeIs('juridico.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.97zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.97z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('juridico.dashboard') }}" class="block py-1 hover:text-brand-light">Panel</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('juridico.dashboard') }}" :active="request()->routeIs('juridico.dashboard')">
        Panel
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan

{{-- Catálogos --}}
@can('gestionar_catalogos')
<x-ui.sidebar-group label="Catálogos" :active="request()->routeIs('cascade.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('cascade.ped.index') }}" class="block py-1 hover:text-brand-light">Plan Estatal</a>
        <a href="{{ route('cascade.programas-derivados.index') }}" class="block py-1 hover:text-brand-light">Prog. Derivados</a>
        <a href="{{ route('cascade.alineacion.index') }}" class="block py-1 hover:text-brand-light">Alineación</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('cascade.ped.index') }}" :active="request()->routeIs('cascade.ped.*')">
        Plan Estatal
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('cascade.programas-derivados.index') }}" :active="request()->routeIs('cascade.programas-derivados.*')">
        Programas Derivados
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('cascade.alineacion.index') }}" :active="request()->routeIs('cascade.alineacion.*')">
        Matriz de Alineación
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan

{{-- Reportes --}}
@can('exportar_reportes')
<x-ui.sidebar-group label="Reportes" :active="request()->routeIs('evaluation.*') || request()->routeIs('datos-abiertos.*')">
    <x-slot:icon>
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
    </x-slot:icon>
    <x-slot:tooltip>
        <a href="{{ route('evaluation.transversal') }}" class="block py-1 hover:text-brand-light">Transversal</a>
        <a href="{{ route('evaluation.datos-abiertos.diccionario') }}" class="block py-1 hover:text-brand-light">Datos Abiertos</a>
    </x-slot:tooltip>

    <x-ui.sidebar-item href="{{ route('evaluation.transversal') }}" :active="request()->routeIs('evaluation.transversal')">
        Transversal
    </x-ui.sidebar-item>
    <x-ui.sidebar-item href="{{ route('evaluation.datos-abiertos.diccionario') }}" :active="request()->routeIs('evaluation.datos-abiertos.*')">
        Datos Abiertos
    </x-ui.sidebar-item>
</x-ui.sidebar-group>
@endcan

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
