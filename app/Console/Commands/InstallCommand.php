<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use App\Models\Tenant;
use App\Models\User;
use App\Models\License;

class InstallCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'aiassisten:install';

    /**
     * The console command description.
     */
    protected $description = 'Interactive installer to bootstrap tenant, admin, and license information';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (Tenant::exists()) {
            $this->info('Installation already completed.');
            return self::SUCCESS;
        }

        $this->info('Starting AiAssisten installation...');

        $tenantName = $this->ask('Tenant name');
        $tenant = Tenant::create([
            'name' => $tenantName,
            'slug' => Str::slug($tenantName),
        ]);

        $adminName = $this->ask('Admin name');
        $adminEmail = $this->ask('Admin email');
        $adminPassword = $this->secret('Admin password');
        User::create([
            'tenant_id' => $tenant->id,
            'name' => $adminName,
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'role' => 'admin',
        ]);

        $purchaseCode = $this->ask('Purchase code');
        $defaultDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? config('app.url');
        $domain = $this->ask('Licensed domain', $defaultDomain);

        License::create([
            'tenant_id' => $tenant->id,
            'purchase_code' => hash('sha256', $purchaseCode),
            'domain' => hash('sha256', $domain),
            'activated_at' => now(),
            'status' => 'valid',
        ]);

        $this->info('Installation complete.');
        return self::SUCCESS;
    }
}

