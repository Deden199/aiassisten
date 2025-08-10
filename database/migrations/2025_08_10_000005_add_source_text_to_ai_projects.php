<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_projects', function (Blueprint $table) {
            $table->longText('source_text')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('ai_projects', function (Blueprint $table) {
            $table->dropColumn('source_text');
        });
    }
};
