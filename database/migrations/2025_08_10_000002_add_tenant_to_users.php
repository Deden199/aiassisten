<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('tenant_id')->nullable()->after('id');
            $table->enum('role', ['admin','user'])->default('user')->after('password');
            $table->string('locale', 10)->nullable()->after('role');
            $table->string('timezone', 60)->nullable()->after('locale');
            $table->string('currency', 10)->nullable()->after('timezone');
            $table->unsignedInteger('credits')->default(0)->after('currency');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->unique(['tenant_id', 'email']);
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['tenant_id', 'email']);
            $table->dropConstrainedForeignId('tenant_id');
            $table->dropColumn(['tenant_id','role','locale','timezone','currency','credits']);
        });
    }
};