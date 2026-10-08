<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Dashboard extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'process_id',
        'year',
        'period',
        'indicators',
        'actions',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'indicators' => 'array',
            'actions' => 'array',
        ];
    }

    public function process()
    {
        return $this->belongsTo(Process::class);
    }
}

