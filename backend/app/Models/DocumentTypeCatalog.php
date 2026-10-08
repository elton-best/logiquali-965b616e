<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentTypeCatalog extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'name',
        'abbreviation',
        'description',
        'is_active',
        'display_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function processes()
    {
        return $this->belongsToMany(Process::class, 'document_type_catalog_process')
            ->withTimestamps();
    }
}
