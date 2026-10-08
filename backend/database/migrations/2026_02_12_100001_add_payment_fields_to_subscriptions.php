<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->string('payment_gateway')->nullable()->after('payment_status'); // 'stripe' | 'aggregator'
            $table->string('payment_customer_id')->nullable()->after('payment_gateway');
            $table->string('payment_method_id')->nullable()->after('payment_customer_id');
            $table->json('payment_metadata')->nullable()->after('payment_method_id');
            $table->timestamp('trial_reminder_sent_at')->nullable()->after('trial_ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->dropColumn([
                'payment_gateway',
                'payment_customer_id',
                'payment_method_id',
                'payment_metadata',
                'trial_reminder_sent_at'
            ]);
        });
    }
};
