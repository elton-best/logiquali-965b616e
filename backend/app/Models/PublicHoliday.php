<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicHoliday extends Model
{
    protected $table = 'public_holidays';

    protected $fillable = [
        'enterprise_id',
        'country_code',
        'date',
        'name',
        'recurring',
    ];

    protected $casts = [
        'date' => 'date',
        'recurring' => 'boolean',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function scopeByEnterprise($query, $enterpriseId)
    {
        return $query->where('enterprise_id', $enterpriseId);
    }

    public function scopeByCountry($query, $countryCode)
    {
        return $query->where('country_code', $countryCode);
    }

    public function scopeRecurring($query)
    {
        return $query->where('recurring', true);
    }

    public function scopeNonRecurring($query)
    {
        return $query->where('recurring', false);
    }
}

