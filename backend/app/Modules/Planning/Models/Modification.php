<?php

namespace App\Modules\Planning\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Modification extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'number',
        'date',
        'object',
        'description',
        'objectives',
        'consequences',
        'required_resources',
        'responsible_id',
        'validated_by',
        'validated_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'validated_at' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function responsible()
    {
        return $this->belongsTo(User::class, 'responsible_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

