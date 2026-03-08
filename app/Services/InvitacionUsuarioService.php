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
