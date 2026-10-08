<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;

use App\Traits\BelongsToEnterprise;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProviderPartner extends Model
{
    use HasFactory, SoftDeletes, BelongsToEnterprise;

    protected $fillable = [
        'enterprise_id',
        'reference',
        'designation',
        'provider_type',
        'legal_form',
        'service_offers',
        'phone_primary',
        'phone_secondary',
        'email',
        'ifu',
        'experience_years',
        'evaluation_observation',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function contract()
    {
        return $this->hasOne(ProviderContract::class);
    }

    public function files()
    {
        return $this->hasMany(ProviderPartnerFile::class)->latest();
    }
}
