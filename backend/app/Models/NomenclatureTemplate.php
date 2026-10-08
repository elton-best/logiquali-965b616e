<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NomenclatureTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'enterprise_id',
        'site_id',
        'document_type_catalog_id',
        'process_catalog_id',
        'name',
        'version',
        'status',
        'format_structure',
        'separator',
        'preview_example',
        'builder_config',
        'description',
        'is_active',
        'published_at',
        'published_by',
    ];

    protected $casts = [
        'format_structure' => 'array',
        'builder_config' => 'array',
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function documentTypeCatalog()
    {
        return $this->belongsTo(DocumentTypeCatalog::class);
    }

    public function processCatalog()
    {
        return $this->belongsTo(ProcessCatalog::class);
    }

    public function publisher()
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'nomenclature_template_id');
    }
}
