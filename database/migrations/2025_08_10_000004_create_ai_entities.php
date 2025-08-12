<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 160);
            $table->string('source_filename')->nullable();
            $table->string('source_disk', 60)->default('local');
            $table->string('source_path')->nullable();
            $table->string('language', 10)->default('en');
            $table->string('status', 30)->default('draft');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id','user_id','created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('project_id');
            $table->string('type', 40);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->string('status', 30)->default('queued');
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['tenant_id','project_id','type','created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('ai_projects')->cascadeOnDelete();
        });

        Schema::create('ai_task_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('task_id');
            $table->string('locale', 10);
            $table->longText('payload');
            $table->string('file_disk', 60)->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index(['task_id','locale']);
            $table->foreign('task_id')->references('id')->on('ai_tasks')->cascadeOnDelete();
        });

        Schema::create('usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('task_id')->nullable();
            $table->string('event', 60);
            $table->json('data')->nullable();
            $table->unsignedBigInteger('cost_cents')->default(0);
            $table->unsignedInteger('tokens_in')->default(0);
            $table->unsignedInteger('tokens_out')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['tenant_id','created_at']);
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('task_id')->references('id')->on('ai_tasks')->nullOnDelete();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 80);
            $table->string('ip', 64)->nullable();
            $table->string('user_agent')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });

        Schema::create('licenses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->unique();
            $table->string('purchase_code', 120);
            $table->string('domain', 190);
            $table->timestamp('activated_at')->nullable();
            $table->string('status', 30)->default('valid');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('usage_logs');
        Schema::dropIfExists('ai_task_versions');
        Schema::dropIfExists('ai_tasks');
        Schema::dropIfExists('ai_projects');
    }
};
