<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DemoWallets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetSuperAdminCommand extends Command
{
    protected $signature = 'admin:reset {--email=admin@emmaus.fr}';

    protected $description = 'Réinitialise le compte Super Admin (mot de passe: password)';

    public function handle(): int
    {
        $email = $this->option('email');

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'wallet_address' => DemoWallets::ADMIN,
            ]
        );

        $this->info("Super Admin prêt : {$user->email}");
        $this->info('Mot de passe : password');
        $this->info('Rôle : '.$user->role->label());

        return self::SUCCESS;
    }
}
