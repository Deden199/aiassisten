<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id');
            $table->string('provider', 20);
            $table->string('provider_invoice_id', 120);
            $table->unsignedBigInteger('amount_due_cents');
            $table->string('currency', 10);
            $table->string('hosted_url')->nullable();
            $table->string('pdf_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['provider', 'provider_invoice_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
