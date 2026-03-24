<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class EnsureSuperAdminCommand extends Command
{
    protected $signature = 'app:ensure-superadmin';

    protected $description = 'Ensure the super admin user exists and has the correct role';

    public function handle(): int
    {
        $email = (string) config('app.superadmin_email');

        if (empty($email)) {
            $this->warn('SUPER_ADMIN_EMAIL non configure dans .env ou config/app.php.');

            return Command::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            $changed = false;

            if (! $user->hasRole('super_admin')) {
                $user->assignRole('super_admin');
                $this->info('Role super_admin assigne.');
                $changed = true;
            }

            if (! $user->is_active) {
                $user->update(['is_active' => true]);
                $this->info('Compte active.');
                $changed = true;
            }

            if ($user->email_verified_at === null) {
                $user->update(['email_verified_at' => now()]);
                $this->info('Email verifie.');
                $changed = true;
            }

            $this->info($changed
                ? "Superadmin {$email} mis a jour."
                : "Superadmin {$email} OK (aucune modification)."
            );
        } else {
            $password = (string) config('app.admin_password', bin2hex(random_bytes(16)));

            $user = User::create([
                'name' => (string) config('app.admin_name', 'Super Admin'),
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('super_admin');

            $this->info("Superadmin {$email} cree avec succes.");
        }

        return Command::SUCCESS;
    }
}
