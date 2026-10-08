<?php

namespace App\Services\Payment;

use App\Models\User;
use Carbon\Carbon;

interface PaymentGatewayInterface
{
    /**
     * Create a customer in the payment gateway
     */
    public function createCustomer(User $user): string;

    /**
     * Attach a payment method to a customer
     */
    public function attachPaymentMethod(string $customerId, string $token): string;

    /**
     * Charge a customer immediately
     */
    public function charge(string $customerId, int $amount, array $metadata = []): array;

    /**
     * Schedule a future charge
     */
    public function scheduleCharge(string $customerId, int $amount, Carbon $date, array $metadata = []): string;

    /**
     * Cancel a scheduled charge
     */
    public function cancelScheduledCharge(string $scheduleId): bool;

    /**
     * Get payment method details
     */
    public function getPaymentMethod(string $paymentMethodId): array;
}
