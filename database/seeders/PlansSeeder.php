<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plan;
use App\Models\Price;

class PlansSeeder extends Seeder
{
    public function run(): void
    {
        $basic = Plan::firstOrCreate(
            ['code' => 'basic'],
            ['features' => ['Up to 3 projects','Email support'], 'is_active' => true]
        );
        $pro = Plan::firstOrCreate(
            ['code' => 'pro'],
            ['features' => ['Unlimited projects','Priority support'], 'is_active' => true]
        );

        Price::firstOrCreate([
            'plan_id' => $basic->id, 'interval' => 'monthly', 'currency' => 'USD',
            'amount_cents' => 900, 'is_active' => true
        ], ['provider' => 'manual']);

        Price::firstOrCreate([
            'plan_id' => $pro->id, 'interval' => 'monthly', 'currency' => 'USD',
            'amount_cents' => 1900, 'is_active' => true
        ], ['provider' => 'manual']);

        Price::firstOrCreate([
            'plan_id' => $pro->id, 'interval' => 'yearly', 'currency' => 'USD',
            'amount_cents' => 19000, 'is_active' => true
        ], ['provider' => 'manual']);
    }
}
