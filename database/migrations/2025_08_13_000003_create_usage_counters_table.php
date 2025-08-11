<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->uuid('user_id');
            $table->timestamp('period_start');
            $table->timestamp('period_end');
            $table->unsignedBigInteger('tokens_used')->default(0);
            $table->unsignedInteger('requests_used')->default(0);
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'period_start']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
