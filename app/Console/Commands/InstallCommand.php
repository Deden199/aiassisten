<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Artisan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\License;

class InstallCommand extends Command
{
    protected $signature = 'aiassisten:install';
    protected $description = 'Interactive installer for the application';

    public function handle(): int
    {
        $this->info('== Application Installer ==' );

        // Generate key
        $this->callSilent('key:generate', ['--force'=>true]);

        // Migrate
        $this->call('migrate', ['--force' => true]);

        // Tenant
        $tenantName = $this->ask('Tenant name', 'Default Tenant');
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => $tenantName,
            'slug' => Str::slug($tenantName).'-'.Str::random(5),
        ]);

        // Admin
        $name = $this->ask('Admin name', 'Admin');
        $email = $this->ask('Admin email', 'admin@example.com');
        $password = $this->secret('Admin password (min 8 chars)');
        if (strlen($password) < 8) {
            $this->error('Password too short. Aborting.');
            return self::FAILURE;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'tenant_id' => $tenant->id,
            'is_admin' => true,
        ]);

        // License (grace by default)
        License::create([
            'tenant_id' => $tenant->id,
            'status' => 'grace',
            'activated_at' => now(),
            'grace_until' => now()->addDays((int) config('license.grace_days', 7)),
        ]);

        $this->info('Installation complete. You can log in as '.$email);
        return self::SUCCESS;
    }
}
