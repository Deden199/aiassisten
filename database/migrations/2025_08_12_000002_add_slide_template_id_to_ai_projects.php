<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_projects', function (Blueprint $table) {
            $table->uuid('slide_template_id')->nullable()->after('status');
            $table->foreign('slide_template_id')->references('id')->on('slide_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ai_projects', function (Blueprint $table) {
            $table->dropForeign(['slide_template_id']);
            $table->dropColumn('slide_template_id');
        });
    }
};
