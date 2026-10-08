<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;
use App\Models\Site;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TrainingPlan extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'year',
        'status',
        'total_budget',
        'planned_formations',
        'spent_amount',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_budget' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'planned_formations' => 'integer',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function sessions()
    {
        return $this->hasMany(TrainingSession::class);
    }
}
