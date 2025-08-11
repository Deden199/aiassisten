<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('usage_counters')) return;

        Schema::create('usage_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // SESUAIKAN dengan tipe tenants.id (jika tenants.id BIGINT → ganti foreignId)
            $table->uuid('tenant_id')->nullable();

            // SESUAIKAN dengan tipe users.id (umumnya BIGINT UNSIGNED)
            $table->foreignId('user_id');

            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('requests_used')->default(0);
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->timestamps();

            $table->unique(['user_id','period_start','period_end']);

            if (Schema::hasTable('tenants')) {
                $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
            }
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('usage_counters')) {
            Schema::table('usage_counters', function (Blueprint $t) {
                try { $t->dropForeign(['tenant_id']); } catch (\Throwable $e) {}
                try { $t->dropForeign(['user_id']); } catch (\Throwable $e) {}
            });
        }
        Schema::dropIfExists('usage_counters');
    }
};
