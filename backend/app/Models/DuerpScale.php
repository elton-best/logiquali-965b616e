<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DuerpScale extends Model
{
    use HasFactory;

    protected $fillable = ['enterprise_id', 'kind', 'label', 'value', 'color', 'is_active'];
    protected $casts = ['value' => 'integer', 'is_active' => 'boolean'];
}
