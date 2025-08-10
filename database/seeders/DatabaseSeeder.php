<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{Tenant, User, Plan, Price, Subscription};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Tenant demo (slug unik)
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'demo'],
            [
                'id'               => Tenant::where('slug', 'demo')->value('id') ?? (string) Str::uuid(),
                'name'             => 'Demo Tenant',
                'default_locale'   => 'en',
                'default_currency' => 'USD',
                'default_timezone' => 'UTC',
                'is_active'        => true,
            ]
        );

        // Pastikan semua user yang tenant_id-nya null di-assign ke tenant demo
        User::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

        // Admin demo (unik pada (tenant_id, email))
        $admin = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'admin@demo.test'],
            [
                'name'              => 'Demo Admin',
                'password'          => bcrypt('password'),
                'role'              => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Plans
        $free = Plan::updateOrCreate(
            ['code' => 'free'],
            ['features' => ['credits' => 50], 'is_active' => 1]
        );

        $pro = Plan::updateOrCreate(
            ['code' => 'pro'],
            ['features' => ['credits' => 1000], 'is_active' => 1]
        );

        // Prices (unik per plan + currency)
        Price::updateOrCreate(
            ['plan_id' => $pro->id, 'currency' => 'USD'],
            ['amount_cents' => 9900, 'is_active' => 1]
        );
        Price::updateOrCreate(
            ['plan_id' => $pro->id, 'currency' => 'IDR'],
            ['amount_cents' => 1500000, 'is_active' => 1]
        );

        // Subscription per-tenant (unik)
        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id'               => $free->id,
                'status'                => 'active',
                'current_period_start'  => now(),
                'current_period_end'    => now()->addMonth(),
                'cancel_at_period_end'  => false,
            ]
        );
    }
}
