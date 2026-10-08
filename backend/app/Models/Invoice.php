<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'subscription_id',
        'amount',
        'currency',
        'status',
        'invoice_number',
        'invoice_date',
        'due_date',
        'paid_at',
        'payment_method',
        'metadata',
        'pdf_path',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'paid_at' => 'datetime',
            'amount' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function subscription()
    {
        return $this->belongsTo(EnterpriseSubscription::class, 'subscription_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date < now();
    }
}
