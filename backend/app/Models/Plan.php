<?php

namespace App\Models;

use App\Traits\BelongsToEnterprise;
use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference, BelongsToEnterprise;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'type',
        'title',
        'year',
        'content',
        'file_path',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'content' => 'array',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}

