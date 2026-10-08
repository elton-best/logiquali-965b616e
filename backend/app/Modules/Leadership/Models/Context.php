<?php

namespace App\Modules\Leadership\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Context extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'site_id',
        'type',
        'category',
        'description',
        'impact',
        'analysis',
        'year',
        'title',
        'swot_strengths',
        'swot_weaknesses',
        'swot_opportunities',
        'swot_threats',
        'pestel_political',
        'pestel_economic',
        'pestel_social',
        'pestel_technological',
        'pestel_environmental',
        'pestel_legal',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }
}

