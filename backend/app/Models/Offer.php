<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;




class Offer extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'name',
        'description',
        'is_active',
        'price',
        'duration_months',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'duration_months' => 'integer',
        ];
    }

    public function norms()
    {
        return $this->belongsToMany(Norm::class, 'norm_offer')
            ->withTimestamps();
    }

    public function subscriptions()
    {
        return $this->hasMany(EnterpriseSubscription::class);
    }
}

