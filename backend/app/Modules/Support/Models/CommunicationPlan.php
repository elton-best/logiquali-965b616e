<?php

namespace App\Modules\Support\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationPlan extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'year',
        'status',
        'planned_budget',
        'planned_actions',
        'spent_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'planned_budget' => 'decimal:2',
        'planned_actions' => 'integer',
        'spent_amount' => 'decimal:2',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}
