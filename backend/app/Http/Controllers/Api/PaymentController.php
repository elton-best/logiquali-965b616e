<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EnterpriseSubscription;
use App\Models\SubscriptionPayment;
use App\Services\InvoiceService;
use App\Services\Payment\BegPayApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private readonly BegPayApiService $begPayApi,
        private readonly InvoiceService $invoiceService,
    ) {
    }

    public function requestPayment(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:enterprise_subscriptions,id'],
            'payment_method' => ['required', 'in:mtn_momo,moov_money,coris_money,yas_money'],
            'phone_number' => ['required', 'string', 'min:6', 'max:20'],
            'description' => ['nullable', 'string', 'max:255'],
            'payer_given_name' => ['nullable', 'string', 'max:120'],
            'payer_family_name' => ['nullable', 'string', 'max:120'],
            'payer_email' => ['nullable', 'email', 'max:190'],
            'indicatif' => ['nullable', 'digits_between:2,4'],
        ]);

        $user = $request->user();
        $subscription = EnterpriseSubscription::with(['offer', 'site'])->findOrFail($validated['subscription_id']);

        if (!$this->canManageSubscription($user, $subscription)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas initier un paiement pour cet abonnement.',
            ], 403);
        }

        if ($subscription->payment_status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Cet abonnement est déjà payé.',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'status' => 'completed',
                ],
            ]);
        }

        $amount = (float) ($subscription->offer?->price ?? 0);
        if ($amount <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Montant d’abonnement invalide.',
            ], 422);
        }

        [$indicatif, $number] = $this->parsePhone($validated['phone_number'], $validated['indicatif'] ?? null);
        $referenceId = (string) Str::uuid();
        $provider = $this->mapProvider($validated['payment_method']);

        $payload = [
            'indicatif' => $indicatif,
            'description' => (string) ($validated['description'] ?? "Abonnement LOGIQUALI {$subscription->ref}"),
            'provider' => $provider,
            'amount' => (int) round($amount),
            'merchant_id' => (string) (
                config('services.begpay.merchant_external_id')
                ?: $subscription->site?->enterprise_id
                ?: $subscription->site_id
            ),
            'number' => $number,
            'referenceId' => $referenceId,
            'payer_given_name' => (string) ($validated['payer_given_name'] ?? ($user->first_name ?? $user->name ?? 'Client')),
            'payer_family_name' => (string) ($validated['payer_family_name'] ?? ($user->last_name ?? 'LOGIQUALI')),
            'payer_email' => (string) ($validated['payer_email'] ?? $user->email),
        ];

        try {
            $providerResponse = $this->begPayApi->requestPayment($payload);

            $payment = SubscriptionPayment::create([
                'subscription_id' => $subscription->id,
                'amount' => $amount,
                'currency' => 'XOF',
                'payment_method' => $validated['payment_method'],
                'payment_date' => now(),
                'status' => 'pending',
                'transaction_id' => $referenceId,
                'notes' => 'Paiement initié via BegPay',
                'processed_by' => $user?->id,
            ]);

            $subscription->update([
                'payment_status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_metadata' => [
                    'gateway' => 'begpay',
                    'reference_id' => $referenceId,
                    'provider' => $provider,
                    'raw_request' => $payload,
                    'raw_response' => $providerResponse,
                ],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Demande de paiement envoyée.',
                'data' => [
                    'reference_id' => $referenceId,
                    'provider' => $provider,
                    'payment_id' => $payment->id,
                    'subscription_id' => $subscription->id,
                    'status' => 'pending',
                    'gateway_response' => $providerResponse,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('BegPay request payment failed', [
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Échec de l’initiation du paiement: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function checkSubscriptionPayment(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => ['required', 'exists:enterprise_subscriptions,id'],
            'reference_id' => ['required', 'string', 'max:190'],
        ]);

        $user = $request->user();
        $subscription = EnterpriseSubscription::with(['offer', 'site'])->findOrFail($validated['subscription_id']);
        if (!$this->canManageSubscription($user, $subscription)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas vérifier ce paiement.',
            ], 403);
        }

        if ($subscription->payment_status === 'completed') {
            return response()->json([
                'success' => true,
                'message' => 'Paiement confirmé.',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'payment_status' => 'completed',
                    'subscription_active' => (bool) $subscription->is_active,
                ],
            ]);
        }

        try {
            $providerPayload = $this->begPayApi->paymentStatus($validated['reference_id']);
            $normalizedStatus = $this->normalizeBegPayStatus($providerPayload);

            $payment = SubscriptionPayment::query()
                ->where('subscription_id', $subscription->id)
                ->where('transaction_id', $validated['reference_id'])
                ->latest('id')
                ->first();

            if ($normalizedStatus === 'completed') {
                DB::transaction(function () use ($subscription, $payment, $providerPayload, $validated, $user): void {
                    $subscription->update([
                        'is_active' => true,
                        'status' => 'active',
                        'payment_status' => 'completed',
                        'last_payment_date' => now()->toDateString(),
                        'payment_metadata' => [
                            'gateway' => 'begpay',
                            'reference_id' => $validated['reference_id'],
                            'raw_status_response' => $providerPayload,
                        ],
                    ]);

                    if ($payment) {
                        $payment->update([
                            'status' => 'completed',
                            'payment_date' => now(),
                            'notes' => 'Paiement confirmé par BegPay',
                            'processed_by' => $user?->id,
                        ]);
                    }

                    $invoice = $this->invoiceService->generateInvoice(
                        $subscription,
                        (float) ($subscription->offer?->price ?? 0),
                        (string) ($subscription->payment_method ?? 'mobile_money')
                    );

                    $invoice->update([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ]);
                });
            } elseif ($normalizedStatus === 'failed') {
                $subscription->update([
                    'payment_status' => 'failed',
                    'status' => 'active',
                    'is_active' => false,
                ]);
                if ($payment) {
                    $payment->update([
                        'status' => 'failed',
                        'notes' => 'Paiement refusé/échoué selon BegPay',
                        'processed_by' => $user?->id,
                    ]);
                }
            } else {
                if ($payment && $payment->status !== 'pending') {
                    $payment->update(['status' => 'pending']);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Statut de paiement récupéré.',
                'data' => [
                    'subscription_id' => $subscription->id,
                    'reference_id' => $validated['reference_id'],
                    'payment_status' => $normalizedStatus,
                    'subscription_active' => (bool) $subscription->fresh()->is_active,
                    'provider_payload' => $providerPayload,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('BegPay payment status check failed', [
                'subscription_id' => $subscription->id,
                'reference_id' => $validated['reference_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Impossible de vérifier le statut du paiement: ' . $e->getMessage(),
            ], 422);
        }
    }

    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => ['required', 'string', 'min:6', 'max:20'],
            'indicatif' => ['nullable', 'digits_between:2,4'],
        ]);

        [$indicatif, $number] = $this->parsePhone($validated['phone_number'], $validated['indicatif'] ?? null);

        try {
            $response = $this->begPayApi->sendOtp($number, $indicatif);
            return response()->json([
                'success' => true,
                'message' => 'OTP envoyé.',
                'data' => $response,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Échec envoi OTP: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Callback provider BegPay (endpoint public attendu: /provider/callback).
     */
    public function providerCallback(Request $request)
    {
        $payload = $request->all();
        $referenceId = (string) (
            $payload['referenceId']
            ?? $payload['reference_id']
            ?? $payload['data']['referenceId']
            ?? $payload['data']['reference_id']
            ?? ''
        );

        if ($referenceId === '') {
            return response()->json([
                'success' => false,
                'message' => 'referenceId manquant',
            ], 422);
        }

        $payment = SubscriptionPayment::query()
            ->where('transaction_id', $referenceId)
            ->latest('id')
            ->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'Paiement introuvable',
            ], 404);
        }

        $subscription = $payment->subscription()->with(['offer', 'site'])->first();
        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'Abonnement introuvable',
            ], 404);
        }

        $normalizedStatus = $this->normalizeBegPayStatus($payload);
        if ($normalizedStatus === 'completed') {
            DB::transaction(function () use ($subscription, $payment, $referenceId, $payload): void {
                $subscription->update([
                    'is_active' => true,
                    'status' => 'active',
                    'payment_status' => 'completed',
                    'last_payment_date' => now()->toDateString(),
                    'payment_metadata' => [
                        'gateway' => 'begpay',
                        'reference_id' => $referenceId,
                        'raw_callback_payload' => $payload,
                    ],
                ]);

                $payment->update([
                    'status' => 'completed',
                    'payment_date' => now(),
                    'notes' => 'Paiement confirmé via callback BegPay',
                ]);

                $invoice = $this->invoiceService->generateInvoice(
                    $subscription,
                    (float) ($subscription->offer?->price ?? 0),
                    (string) ($subscription->payment_method ?? 'mobile_money')
                );
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            });
        } elseif ($normalizedStatus === 'failed') {
            $subscription->update([
                'payment_status' => 'failed',
                'status' => 'payment_failed',
            ]);
            $payment->update([
                'status' => 'failed',
                'notes' => 'Paiement échoué via callback BegPay',
            ]);
        } else {
            $payment->update([
                'status' => 'pending',
                'notes' => 'Callback BegPay reçu (pending)',
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Callback traité',
            'data' => [
                'reference_id' => $referenceId,
                'status' => $normalizedStatus,
            ],
        ]);
    }

    /**
     * Fallback de test manuel conservé pour environnement de dev.
     */
    public function simulate(Request $request)
    {
        $validated = $request->validate([
            'subscription_id' => 'required|exists:enterprise_subscriptions,id',
            'payment_method' => 'required|in:mobile_money,card,bank_transfer,cash',
            'amount' => 'required|numeric|min:0',
        ]);

        $subscription = EnterpriseSubscription::with('offer')->findOrFail($validated['subscription_id']);
        $subscription->update([
            'is_active' => true,
            'status' => 'active',
            'payment_status' => 'completed',
            'last_payment_date' => now()->toDateString(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Paiement simulé validé.',
            'data' => [
                'transaction_id' => 'SIM-' . strtoupper(uniqid()),
                'subscription_id' => $subscription->id,
                'status' => 'completed',
            ],
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $query = SubscriptionPayment::query()->with(['subscription.offer', 'subscription.site']);
        if ($user && $user->user_type !== 'super_admin' && $user->enterprise_id) {
            $query->whereHas('subscription.site', function ($q) use ($user) {
                $q->where('enterprise_id', $user->enterprise_id);
            });
        }

        $payments = $query->latest('payment_date')->limit(200)->get()->map(function (SubscriptionPayment $payment) {
            return [
                'id' => $payment->id,
                'reference' => $payment->ref,
                'transaction_id' => $payment->transaction_id,
                'subscription_ref' => $payment->subscription?->ref,
                'site_name' => $payment->subscription?->site?->name,
                'offer_name' => $payment->subscription?->offer?->name,
                'amount' => (float) $payment->amount,
                'currency' => $payment->currency,
                'payment_method' => $payment->payment_method,
                'status' => $payment->status,
                'paid_at' => optional($payment->payment_date)->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $payments,
        ]);
    }

    private function canManageSubscription($user, EnterpriseSubscription $subscription): bool
    {
        if (!$user) {
            return false;
        }
        if ($user->user_type === 'super_admin') {
            return true;
        }

        return (int) ($subscription->site?->enterprise_id ?? 0) === (int) ($user->enterprise_id ?? -1);
    }

    private function mapProvider(string $paymentMethod): string
    {
        return match ($paymentMethod) {
            'mtn_momo' => 'mtn',
            'moov_money' => 'moov',
            'coris_money' => 'coris',
            'yas_money' => 'yas',
            default => throw new \InvalidArgumentException('Méthode de paiement non supportée'),
        };
    }

    private function parsePhone(string $rawPhone, ?string $indicatif): array
    {
        $normalized = preg_replace('/\D+/', '', $rawPhone) ?? '';
        if ($normalized === '') {
            throw new \InvalidArgumentException('Numéro de téléphone invalide');
        }

        if ($indicatif) {
            if (str_starts_with($normalized, $indicatif)) {
                return [$indicatif, substr($normalized, strlen($indicatif))];
            }

            return [$indicatif, $normalized];
        }

        $possibleIndicatifs = ['229', '228'];
        foreach ($possibleIndicatifs as $code) {
            if (str_starts_with($normalized, $code) && strlen($normalized) > strlen($code)) {
                return [$code, substr($normalized, strlen($code))];
            }
        }

        // fallback par défaut Bénin
        return ['229', $normalized];
    }

    private function normalizeBegPayStatus(array $payload): string
    {
        $statusCandidates = [
            $payload['status'] ?? null,
            $payload['payment_status'] ?? null,
            $payload['data']['status'] ?? null,
            $payload['data']['payment_status'] ?? null,
        ];

        $status = '';
        foreach ($statusCandidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                $status = strtoupper(trim($candidate));
                break;
            }
        }

        if (in_array($status, ['SUCCESS', 'SUCCESSFUL', 'PAID', 'COMPLETED'], true)) {
            return 'completed';
        }
        if (in_array($status, ['FAILED', 'ERROR', 'CANCELLED', 'REJECTED'], true)) {
            return 'failed';
        }

        return 'pending';
    }
}
