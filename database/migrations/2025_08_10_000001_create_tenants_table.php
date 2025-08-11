<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120);
            $table->string('slug', 120)->unique();
            $table->string('default_locale', 10)->default('en');
            $table->string('default_currency', 10)->default('USD');
            $table->string('default_timezone', 60)->default('UTC');
            $table->unsignedBigInteger('monthly_cost_cap_cents')->default(500000); // $5,000.00
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('tenants'); }
};