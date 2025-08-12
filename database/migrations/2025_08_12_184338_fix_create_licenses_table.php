<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (! Schema::hasColumn('licenses','grace_until')) {
                    $table->timestamp('grace_until')->nullable()->after('activated_at');
                }
                if (! Schema::hasColumn('licenses','meta')) {
                    $table->json('meta')->nullable()->after('grace_until');
                }
                if (! Schema::hasColumn('licenses','status')) {
                    $table->string('status', 16)->default('none')->after('domain');
                }
            });
            return;
        }

        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('purchase_code', 191)->nullable();
            $table->string('domain', 191)->nullable();
            $table->string('status', 16)->default('none');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->index(['tenant_id','status']);
        });
    }

    public function down(): void
    {
        // Safe down: do not drop existing table, only drop added columns if present
        if (Schema::hasTable('licenses')) {
            Schema::table('licenses', function (Blueprint $table) {
                if (Schema::hasColumn('licenses','grace_until')) $table->dropColumn('grace_until');
                if (Schema::hasColumn('licenses','meta')) $table->dropColumn('meta');
                if (Schema::hasColumn('licenses','status')) $table->dropColumn('status');
            });
        }
    }
};
