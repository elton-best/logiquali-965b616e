<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CommunicationAction extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'communication_type',
        'target_audience',
        'channel',
        'frequency',
        'subject',
        'content',
        'responsible_user_id',
        'planned_date',
        'actual_date',
        'proof_paths',
        'status',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'proof_paths' => 'array',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }
}
