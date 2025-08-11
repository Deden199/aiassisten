<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table) {
            $table->string('provider', 20)->default('stripe');
            $table->string('provider_price_id', 100)->nullable();
            $table->string('interval', 10)->default('month');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->renameColumn('gateway', 'provider');
            $table->renameColumn('gateway_sub_id', 'provider_subscription_id');
            $table->string('provider_customer_id')->nullable()->after('provider_subscription_id');
            $table->string('latest_invoice_id')->nullable()->after('provider_customer_id');
            $table->timestamp('trial_end_at')->nullable()->after('current_period_end');
            $table->timestamp('grace_until')->nullable()->after('trial_end_at');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['provider_customer_id','latest_invoice_id','trial_end_at','grace_until']);
            $table->renameColumn('provider', 'gateway');
            $table->renameColumn('provider_subscription_id', 'gateway_sub_id');
        });

        Schema::table('prices', function (Blueprint $table) {
            $table->dropColumn(['provider','provider_price_id','interval']);
        });
    }
};
