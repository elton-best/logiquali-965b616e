<?php

namespace App\Services\Payment;

use App\Models\User;
use Carbon\Carbon;
use Stripe\StripeClient;

class StripePaymentGateway implements PaymentGatewayInterface
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCustomer(User $user): string
    {
        $customer = $this->stripe->customers->create([
            'email' => $user->email,
            'name' => $user->name,
            'metadata' => [
                'user_id' => $user->id,
                'enterprise_id' => $user->enterprise_id,
            ],
        ]);

        return $customer->id;
    }

    public function attachPaymentMethod(string $customerId, string $token): string
    {
        $paymentMethod = $this->stripe->paymentMethods->attach($token, [
            'customer' => $customerId,
        ]);

        // Set as default payment method
        $this->stripe->customers->update($customerId, [
            'invoice_settings' => [
                'default_payment_method' => $paymentMethod->id,
            ],
        ]);

        return $paymentMethod->id;
    }

    public function charge(string $customerId, int $amount, array $metadata = []): array
    {
        $paymentIntent = $this->stripe->paymentIntents->create([
            'amount' => $amount,
            'currency' => 'xof',
            'customer' => $customerId,
            'metadata' => $metadata,
            'confirm' => true,
        ]);

        return [
            'id' => $paymentIntent->id,
            'status' => $paymentIntent->status,
            'amount' => $paymentIntent->amount,
        ];
    }

    public function scheduleCharge(string $customerId, int $amount, Carbon $date, array $metadata = []): string
    {
        // Stripe doesn't support scheduled charges directly
        // We'll use Laravel's scheduler to trigger the charge
        // Return a unique identifier for tracking
        return 'scheduled_' . uniqid();
    }

    public function cancelScheduledCharge(string $scheduleId): bool
    {
        // Implementation depends on how we store scheduled charges
        return true;
    }

    public function getPaymentMethod(string $paymentMethodId): array
    {
        $paymentMethod = $this->stripe->paymentMethods->retrieve($paymentMethodId);

        return [
            'id' => $paymentMethod->id,
            'type' => $paymentMethod->type,
            'card' => [
                'brand' => $paymentMethod->card->brand ?? null,
                'last4' => $paymentMethod->card->last4 ?? null,
                'exp_month' => $paymentMethod->card->exp_month ?? null,
                'exp_year' => $paymentMethod->card->exp_year ?? null,
            ],
        ];
    }
}
