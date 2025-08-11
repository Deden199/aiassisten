<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // total token AI terpakai
            if (!Schema::hasColumn('users', 'usage_tokens')) {
                $table->unsignedBigInteger('usage_tokens')->default(0)->after('remember_token');
            }
            // total biaya dalam cents (hindari float/decimal)
            if (!Schema::hasColumn('users', 'usage_cost_cents')) {
                $table->unsignedBigInteger('usage_cost_cents')->default(0)->after('usage_tokens');
            }
            // (opsional) jumlah request AI
            if (!Schema::hasColumn('users', 'usage_requests')) {
                $table->unsignedBigInteger('usage_requests')->default(0)->after('usage_cost_cents');
            }
            // (opsional) kapan terakhir pakai AI
            if (!Schema::hasColumn('users', 'last_ai_used_at')) {
                $table->timestamp('last_ai_used_at')->nullable()->after('usage_requests');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'last_ai_used_at')) {
                $table->dropColumn('last_ai_used_at');
            }
            if (Schema::hasColumn('users', 'usage_requests')) {
                $table->dropColumn('usage_requests');
            }
            if (Schema::hasColumn('users', 'usage_cost_cents')) {
                $table->dropColumn('usage_cost_cents');
            }
            if (Schema::hasColumn('users', 'usage_tokens')) {
                $table->dropColumn('usage_tokens');
            }
        });
    }
};
