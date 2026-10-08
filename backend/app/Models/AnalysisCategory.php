<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalysisCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'code',
        'label',
    ];

    public function contextIssues()
    {
        return $this->hasMany(ContextIssue::class, 'category_id');
    }
}
