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

        $service = new InvitacionUsuarioService;
        $service->invitar($this->inviteEmail, $this->inviteName, $this->inviteRole, (int) $this->inviteTeamId);

        $this->showInviteForm = false;
        $this->reset(['inviteEmail', 'inviteName', 'inviteRole', 'inviteTeamId']);

        session()->flash('message', 'Invitación enviada correctamente.');
    }

    public function resendInvitation(int $userId): void
    {
        $user = User::findOrFail($userId);

        abort_unless($user->isPendingActivation() || $user->isInvitationExpired(), 403);

        $service = new InvitacionUsuarioService;
        $service->reenviarInvitacion($user);

        session()->flash('message', 'Invitación reenviada a '.$user->email);
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

        $total = User::count();
        $activos = User::whereNotNull('activated_at')->where('active', true)->count();
        $pendientes = User::whereNull('activated_at')->whereNotNull('invitation_token')->count();
        $inactivos = User::where('active', false)->count();

        $kpis = [
            ['label' => 'Total usuarios', 'value' => $total],
            ['label' => 'Activos', 'value' => $activos, 'color' => 'green'],
            ['label' => 'Pendientes', 'value' => $pendientes, 'color' => 'amber'],
            ['label' => 'Inactivos', 'value' => $inactivos, 'color' => 'red'],
        ];

        return view('livewire.admin.gestion-usuarios', [
            'usuarios' => $query->paginate(15),
            'kpis' => $kpis,
        ]);
    }
}
