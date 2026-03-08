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
