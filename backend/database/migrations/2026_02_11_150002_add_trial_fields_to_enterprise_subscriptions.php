<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            // Vérifier si colonnes n'existent pas déjà
            if (!Schema::hasColumn('enterprise_subscriptions', 'trial_ends_at')) {
                $table->timestamp('trial_ends_at')->nullable()->after('is_trial');
            }
            if (!Schema::hasColumn('enterprise_subscriptions', 'status')) {
                $table->enum('status', ['trial', 'active', 'expired', 'cancelled'])->default('trial')->after('trial_ends_at');
            }
            if (!Schema::hasColumn('enterprise_subscriptions', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'completed', 'failed'])->nullable()->after('status');
            }
            if (!Schema::hasColumn('enterprise_subscriptions', 'subscription_type')) {
                $table->enum('subscription_type', ['primary', 'addon'])->default('primary')->after('payment_status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['trial_ends_at', 'status', 'payment_status', 'subscription_type']);
        });
    }
};
