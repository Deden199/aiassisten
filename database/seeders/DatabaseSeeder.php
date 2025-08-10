<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{Tenant,User,Plan,Price,Subscription};

class DatabaseSeeder extends Seeder {
    public function run(): void {
        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'Demo Tenant',
            'slug' => 'demo',
            'default_locale' => 'en',
            'default_currency' => 'USD',
            'default_timezone' => 'UTC',
        ]);

        $admin = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Admin',
            'email' => 'admin@demo.test',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $free = Plan::create(['id'=>(string)Str::uuid(),'code'=>'free','features'=>['credits'=>50],'is_active'=>1]);
        $pro  = Plan::create(['id'=>(string)Str::uuid(),'code'=>'pro','features'=>['credits'=>1000],'is_active'=>1]);

        Price::create(['id'=>(string)Str::uuid(),'plan_id'=>$pro->id,'currency'=>'USD','amount_cents'=>9900]);
        Price::create(['id'=>(string)Str::uuid(),'plan_id'=>$pro->id,'currency'=>'IDR','amount_cents'=>1500000]);

        Subscription::create(['id'=>(string)Str::uuid(),'tenant_id'=>$tenant->id,'plan_id'=>$free->id,'status'=>'active']);
    }
}