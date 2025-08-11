<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'usage_cost')) {
            Schema::table('users', function (Blueprint $t) {
                $t->unsignedBigInteger('usage_cost')->default(0)->after('usage_tokens');
            });
        }
    }
    public function down(): void
    {
        if (Schema::hasColumn('users', 'usage_cost')) {
            Schema::table('users', function (Blueprint $t) {
                $t->dropColumn('usage_cost');
            });
        }
    }
};
