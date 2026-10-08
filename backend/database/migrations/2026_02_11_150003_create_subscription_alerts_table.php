<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->constrained('enterprise_subscriptions')->onDelete('cascade');
            $table->enum('alert_type', ['trial_30', 'trial_15', 'trial_7', 'trial_3', 'trial_1', 'expired']);
            $table->timestamp('sent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_alerts');
    }
};
