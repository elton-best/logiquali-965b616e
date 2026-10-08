<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->integer('months_unpaid')->default(0)->after('payment_status');
            $table->decimal('arrears_amount', 10, 2)->default(0)->after('months_unpaid');
            $table->date('last_payment_date')->nullable()->after('arrears_amount');
        });
    }

    public function down(): void
    {
        Schema::table('enterprise_subscriptions', function (Blueprint $table) {
            $table->dropColumn(['months_unpaid', 'arrears_amount', 'last_payment_date']);
        });
    }
};
