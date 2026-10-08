<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Notifications\Subscription\OnboardingCompletedNotification;
use App\Services\InvoiceService;
use App\Services\Payment\AggregatorPaymentGateway;
use App\Services\Payment\PaymentGatewayInterface;
use App\Services\Payment\StripePaymentGateway;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SubscriptionOnboardingController extends Controller
{
    protected SubscriptionService $subscriptionService;
    protected InvoiceService $invoiceService;

    public function __construct(SubscriptionService $subscriptionService, InvoiceService $invoiceService)
    {
        $this->subscriptionService = $subscriptionService;
        $this->invoiceService = $invoiceService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'offer_ids' => 'required|array|min:1',
            'offer_ids.*' => 'exists:offers,id',
            'payment_gateway' => 'required|in:stripe,aggregator',
            'payment_token' => 'required|string',
            'billing_info' => 'required|array',
            'billing_info.name' => 'required|string',
            'billing_info.address' => 'required|string',
            'billing_info.city' => 'required|string',
            'billing_info.postal_code' => 'required|string',
            'billing_info.country' => 'required|string',
        ]);

        $user = Auth::user();
        
        // Get headquarter site
        $site = Site::where('enterprise_id', $user->enterprise_id)
            ->where('is_headquarter', true)
            ->firstOrFail();

        // Check if already completed onboarding
        if ($user->onboarding_completed_at) {
            return response()->json([
                'message' => 'Onboarding déjà complété',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Initialize payment gateway
            $gateway = $this->getPaymentGateway($validated['payment_gateway']);

            // Create customer
            $customerId = $gateway->createCustomer($user);

            // Attach payment method
            $paymentMethodId = $gateway->attachPaymentMethod($customerId, $validated['payment_token']);

            // Create trial subscription(s) using the same canonical business logic as /subscription/subscribe
            $subscriptions = [];
            foreach ($validated['offer_ids'] as $index => $offerId) {
                $offer = \App\Models\Offer::findOrFail($offerId);
                $subscriptions[] = $this->subscriptionService->createTrialSubscription($site, $offer, $index === 0);
            }
            $subscription = $subscriptions[0];

            // Generate trial invoice (0€)
            $invoice = $this->invoiceService->generateTrialInvoice($subscription);

            // Mark onboarding as completed
            $user->update([
                'onboarding_completed_at' => now(),
            ]);

            DB::commit();

            // Send notification
            $user->notify(new OnboardingCompletedNotification($subscription));

            return response()->json([
                'message' => 'Onboarding complété avec succès',
                'subscription' => $subscription->load('offer'),
                'trial_ends_at' => $subscription->trial_ends_at->format('Y-m-d'),
                'invoice' => $invoice,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Erreur lors de l\'onboarding',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function status()
    {
        $user = Auth::user();

        return response()->json([
            'completed' => !is_null($user->onboarding_completed_at),
            'completed_at' => $user->onboarding_completed_at?->format('Y-m-d H:i:s'),
        ]);
    }

    protected function getPaymentGateway(string $gateway): PaymentGatewayInterface
    {
        if ($gateway === 'aggregator') {
            return new AggregatorPaymentGateway();
        }

        if ($gateway === 'stripe') {
            if (class_exists(\Stripe\StripeClient::class)) {
                return new StripePaymentGateway();
            }

            throw new \InvalidArgumentException('Gateway Stripe indisponible sur cette installation. Utilisez aggregator/BegPay.');
        }

        throw new \InvalidArgumentException('Invalid payment gateway');
    }
}
