<?php

namespace App\Services;

use App\Models\EnterpriseSubscription;
use App\Models\Invoice;
use Illuminate\Support\Str;

class InvoiceService
{
    public function generateTrialInvoice(EnterpriseSubscription $subscription): Invoice
    {
        $invoiceNumber = $this->generateInvoiceNumber();

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'amount' => 0.00,
            'currency' => 'XOF',
            'status' => 'paid',
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'due_date' => now(),
            'paid_at' => now(),
            'payment_method' => 'trial',
            'metadata' => [
                'type' => 'trial',
                'trial_duration' => '90 days',
                'note' => 'Période d\'essai gratuite - Aucun montant facturé',
            ],
        ]);
    }

    public function generateInvoice(
        EnterpriseSubscription $subscription,
        float $amount,
        string $paymentMethod = null
    ): Invoice {
        $invoiceNumber = $this->generateInvoiceNumber();

        return Invoice::create([
            'subscription_id' => $subscription->id,
            'amount' => $amount,
            'currency' => 'XOF',
            'status' => 'pending',
            'invoice_number' => $invoiceNumber,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'payment_method' => $paymentMethod,
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $count = Invoice::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->count() + 1;

        return sprintf('INV-%s%s-%04d', $year, $month, $count);
    }
}
