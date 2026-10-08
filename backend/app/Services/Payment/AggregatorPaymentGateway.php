<?php

namespace App\Services\Payment;

use App\Models\User;
use Carbon\Carbon;

class AggregatorPaymentGateway implements PaymentGatewayInterface
{
    public function createCustomer(User $user): string
    {
        return 'begpay_customer_' . $user->id;
    }

    public function attachPaymentMethod(string $customerId, string $token): string
    {
        return 'begpay_pm_' . substr(sha1($customerId . '|' . $token), 0, 16);
    }

    public function charge(string $customerId, int $amount, array $metadata = []): array
    {
        return [
            'id' => 'begpay_charge_' . uniqid(),
            'status' => 'pending',
            'amount' => $amount,
            'metadata' => $metadata,
        ];
    }

    public function scheduleCharge(string $customerId, int $amount, Carbon $date, array $metadata = []): string
    {
        return 'begpay_sched_' . uniqid();
    }

    public function cancelScheduledCharge(string $scheduleId): bool
    {
        return true;
    }

    public function getPaymentMethod(string $paymentMethodId): array
    {
        return [
            'id' => $paymentMethodId,
            'type' => 'mobile_money',
            'card' => null,
        ];
    }
}
