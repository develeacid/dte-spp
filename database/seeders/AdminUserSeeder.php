<?php

namespace Database\Seeders;

use App\Enums\SystemRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'ele.leader@gmail.com'],
            [
                'name' => 'Ele Leader',
                'password' => Hash::make('LseRdlP0P'),
                'email_verified_at' => now(),
                'activated_at' => now(),
                'active' => true,
            ]
        );

        // Personal team requerido por Jetstream
        if (! $user->ownedTeams()->exists()) {
            $team = Team::create([
                'user_id' => $user->id,
                'name' => 'Personal',
                'personal_team' => true,
            ]);
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        if (! $user->hasRole(SystemRole::ADMIN->value)) {
            $user->assignRole(SystemRole::ADMIN->value);
        }

        $this->command->info('Admin listo: ele.leader@gmail.com');
    }
}
