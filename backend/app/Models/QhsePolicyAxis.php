<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QhsePolicyAxis extends Model
{
    use HasFactory, HasAuditFields;

    protected $fillable = [
        'qhse_policy_id',
        'axis_name',
        'axis_order',
    ];

    protected function casts(): array
    {
        return [
            'axis_order' => 'integer',
        ];
    }

    public function policy()
    {
        return $this->belongsTo(QhsePolicy::class, 'qhse_policy_id');
    }
}
