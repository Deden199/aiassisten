<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantAndAdminSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = \App\Models\Tenant::first() ?: \App\Models\Tenant::create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'default_locale' => 'en',
            'default_currency' => 'USD',
            'default_timezone' => 'UTC',
            'is_active' => true,
        ]);

        // Isi tenant_id untuk semua user yang masih null
        \App\Models\User::whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);

        // (Opsional) buat admin kalau belum ada
        if (!\App\Models\User::where('email', 'admin@example.com')->exists()) {
            \App\Models\User::create([
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'tenant_id' => $tenant->id,
            ]);
        }
    }
}
