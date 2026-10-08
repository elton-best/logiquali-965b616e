<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EquipementTransferHistory extends Model
{
    use HasFactory;

    protected $table = 'equipement_transfer_history';

    protected $fillable = [
        'equipement_id',
        'enterprise_id',
        'previous_site_id',
        'previous_localisation_id',
        'new_site_id',
        'new_localisation_id',
        'transfer_reason_code',
        'transfer_notes',
        'is_verified',
        'verified_by',
        'verified_at',
        'verification_notes',
        'transferred_by',
        'transferred_at',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
            'transferred_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // Relationships
    public function equipement()
    {
        return $this->belongsTo(Equipement::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function previousSite()
    {
        return $this->belongsTo(Site::class, 'previous_site_id');
    }

    public function previousLocalisation()
    {
        return $this->belongsTo(CodificationElement::class, 'previous_localisation_id');
    }

    public function newSite()
    {
        return $this->belongsTo(Site::class, 'new_site_id');
    }

    public function newLocalisation()
    {
        return $this->belongsTo(CodificationElement::class, 'new_localisation_id');
    }

    public function transferredBy()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function reasonCode()
    {
        return $this->belongsTo(TransferReasonCode::class, 'transfer_reason_code', 'code');
    }

    public function transferReason()
    {
        return $this->belongsTo(TransferReasonCode::class, 'transfer_reason_code', 'code');
    }

    public function transferredByUser()
    {
        return $this->belongsTo(User::class, 'transferred_by');
    }

    public function verifiedByUser()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeForEquipement($query, $equipementId)
    {
        return $query->where('equipement_id', $equipementId);
    }

    public function scopeForEnterprise($query, $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopePending($query)
    {
        return $query->where('is_verified', false);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('transferred_at');
    }

    public function scopeByReason($query, $reasonCode)
    {
        return $query->where('transfer_reason_code', $reasonCode);
    }
}
