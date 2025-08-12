<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('slide_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->nullable();
            $table->string('name');
            $table->json('palette')->nullable();
            $table->json('font')->nullable();
            $table->json('layout')->nullable();
            $table->json('background_default')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('slide_templates');
    }
};
