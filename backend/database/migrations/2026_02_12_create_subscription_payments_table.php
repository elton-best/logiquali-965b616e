<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->string('ref')->unique();
            $table->foreignId('subscription_id')->constrained('enterprise_subscriptions')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('EUR');
            $table->string('payment_method', 50)->nullable(); // card, bank_transfer, paypal, stripe
            $table->timestamp('payment_date');
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id')->nullable(); // ID externe (Stripe, PayPal, etc.)
            $table->string('invoice_number', 100)->nullable();
            $table->string('invoice_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->index('subscription_id');
            $table->index('status');
            $table->index('payment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
