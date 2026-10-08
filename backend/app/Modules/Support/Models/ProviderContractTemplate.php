<?php

namespace App\Modules\Support\Models;

use App\Models\Enterprise;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderContractTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'title',
        'content',
        'placeholders',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'placeholders' => 'array',
        ];
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }
}

