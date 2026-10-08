<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    use HasFactory, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'subscription_id',
        'amount',
        'currency',
        'payment_method',
        'payment_date',
        'status',
        'transaction_id',
        'invoice_number',
        'invoice_url',
        'notes',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_date' => 'datetime',
        ];
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(EnterpriseSubscription::class, 'subscription_id');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

