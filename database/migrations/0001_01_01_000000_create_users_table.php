<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Multi-tenant & profil user
            $table->uuid('tenant_id')->nullable()->index();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Role & usage/billing
            $table->string('role', 32)->default('user')->index();           // 'admin' / 'user' / dll
            $table->unsignedBigInteger('usage_tokens')->default(0);         // total token AI terpakai
            $table->unsignedBigInteger('usage_cost_cents')->default(0);     // total biaya (cents)
            $table->unsignedBigInteger('plan_id')->nullable()->index();     // relasi plan (nullable, tanpa FK agar aman order migrasi)

            // Preferensi
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 60)->default('UTC');

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
