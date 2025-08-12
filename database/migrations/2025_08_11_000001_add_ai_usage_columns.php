<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        foreach (['users','tenants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'usage_tokens')) {
                    $table->unsignedBigInteger('usage_tokens')->default(0)->after('updated_at');
                }
                if (!Schema::hasColumn($tableName, 'usage_cost_cents')) {
                    $table->unsignedBigInteger('usage_cost_cents')->default(0)->after('usage_tokens');
                }
            });
        }
    }
    public function down(): void {
        foreach (['users','tenants'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'usage_tokens')) $table->dropColumn('usage_tokens');
                if (Schema::hasColumn($tableName, 'usage_cost_cents')) $table->dropColumn('usage_cost_cents');
            });
        }
    }
};
