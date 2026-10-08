<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'provider_partner_id',
        'contract_reference',
        'template_title',
        'template_content',
        'filled_content',
        'start_date',
        'end_date',
        'amount',
        'currency',
        'payment_terms',
        'signed_at',
        'signed_file_path',
        'signed_file_name',
        'generated_file_path',
        'generated_file_name',
        'generated_at',
        'status',
        'meta',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'signed_at' => 'datetime',
            'generated_at' => 'datetime',
            'meta' => 'array',
            'amount' => 'decimal:2',
        ];
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function providerPartner()
    {
        return $this->belongsTo(ProviderPartner::class);
    }
}
