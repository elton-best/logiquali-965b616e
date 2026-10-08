<?php

namespace App\Modules\Leadership\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Norm extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields;

    protected $fillable = [
        'code',
        'name',
        'description',
        'domain',
        'current_version_id',
        'pdf_file_path',
        'pdf_original_name',
        'pdf_uploaded_at',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'domain' => 'string',
        'pdf_uploaded_at' => 'datetime',
    ];

    protected $appends = [
        'pdf_file_url',
        'has_pdf_document',
    ];

    /**
     * Relations
     */
    public function versions()
    {
        return $this->hasMany(NormVersion::class)->orderBy('published_at', 'desc');
    }

    public function currentVersion()
    {
        return $this->belongsTo(NormVersion::class, 'current_version_id');
    }

    public function offers()
    {
        return $this->belongsToMany(Offer::class, 'norm_offer')
            ->withTimestamps();
    }

    public function modules()
    {
        return $this->belongsToMany(Module::class, 'norm_module');
    }

    public function permissionNormMappings()
    {
        return $this->hasMany(PermissionNormMapping::class);
    }

    /**
     * Scopes
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeByDomain($query, $domain)
    {
        return $query->where('domain', $domain);
    }

    /**
     * Helpers
     */
    public function getFullCodeAttribute()
    {
        if ($this->currentVersion) {
            return $this->currentVersion->full_code;
        }
        return $this->code;
    }

    public function isUsedByOffers(): bool
    {
        return $this->offers()->exists();
    }

    public function canBeDeleted(): bool
    {
        return !$this->isUsedByOffers();
    }

    public function getPdfFileUrlAttribute(): ?string
    {
        if (!$this->pdf_file_path) {
            return null;
        }

        return Storage::disk('public')->url($this->pdf_file_path);
    }

    public function getHasPdfDocumentAttribute(): bool
    {
        return !empty($this->pdf_file_path);
    }

    /**
     * Set current version
     */
    public function setCurrentVersion(NormVersion $version)
    {
        // Retirer is_current de toutes les autres versions
        $this->versions()->update(['is_current' => false]);

        // Définir la nouvelle version courante
        $version->update(['is_current' => true]);
        $this->update(['current_version_id' => $version->id]);
    }
}
