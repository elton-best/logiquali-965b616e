<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('is_active');
            $table->string('payment_method')->nullable()->after('is_trial');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['is_trial', 'payment_method', 'amount_paid']);
        });
    }
};
