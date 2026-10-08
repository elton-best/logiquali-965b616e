<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'norm_id',
        'title',
        'content',
        'order',
        'parent_id',
    ];

    protected function casts(): array
    {
        return [
            'order' => 'integer',
        ];
    }

    public function norm()
    {
        return $this->belongsTo(Norm::class);
    }

    public function parent()
    {
        return $this->belongsTo(Article::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Article::class, 'parent_id');
    }
}

