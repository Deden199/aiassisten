<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'tenant_id')) {
                $table->uuid('tenant_id')->nullable()->after('id');
            }
            if (! Schema::hasColumn('users', 'role')) {
                $table->string('role', 32)->default('user')->after('password');
            }
            if (! Schema::hasColumn('users', 'usage_tokens')) {
                $table->unsignedBigInteger('usage_tokens')->default(0)->after('role');
            }
            if (! Schema::hasColumn('users', 'usage_cost_cents')) {
                $table->unsignedBigInteger('usage_cost_cents')->default(0)->after('usage_tokens');
            }
            if (! Schema::hasColumn('users', 'plan_id')) {
                $table->unsignedBigInteger('plan_id')->nullable()->after('usage_cost_cents');
            }
            if (! Schema::hasColumn('users', 'locale')) {
                $table->string('locale', 10)->default('en')->after('plan_id');
            }
            if (! Schema::hasColumn('users', 'timezone')) {
                $table->string('timezone', 60)->default('UTC')->after('locale');
            }
            // Bersihkan sisa skema lama
            if (Schema::hasColumn('users', 'is_admin')) {
                $table->dropColumn('is_admin');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'timezone')) $table->dropColumn('timezone');
            if (Schema::hasColumn('users', 'locale')) $table->dropColumn('locale');
            if (Schema::hasColumn('users', 'plan_id')) $table->dropColumn('plan_id');
            if (Schema::hasColumn('users', 'usage_cost_cents')) $table->dropColumn('usage_cost_cents');
            if (Schema::hasColumn('users', 'usage_tokens')) $table->dropColumn('usage_tokens');
            if (Schema::hasColumn('users', 'role')) $table->dropColumn('role');
            // tenant_id sengaja dibiarkan (dipakai tenant scope)
        });
    }
};
