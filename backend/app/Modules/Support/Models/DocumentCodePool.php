<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentCodePool extends Model
{
    use HasFactory;

    protected $table = 'document_code_pool';

    protected $fillable = [
        'site_id',
        'nomenclature_template_id',
        'document_type',
        'code',
        'status',
        'document_id',
        'reserved_at',
        'used_at',
        'released_at',
        'release_reason',
    ];

    protected function casts(): array
    {
        return [
            'reserved_at' => 'datetime',
            'used_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function nomenclatureTemplate()
    {
        return $this->belongsTo(NomenclatureTemplate::class);
    }

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Scope: codes disponibles
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * Scope: codes réservés
     */
    public function scopeReserved($query)
    {
        return $query->where('status', 'reserved');
    }

    /**
     * Scope: codes utilisés
     */
    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    /**
     * Scope: par site et type de document
     */
    public function scopeForSiteAndType($query, int $siteId, string $documentType)
    {
        return $query->where('site_id', $siteId)
            ->where('document_type', $documentType);
    }

    /**
     * Marquer le code comme réservé
     */
    public function reserve(int $documentId): bool
    {
        return $this->update([
            'status' => 'reserved',
            'document_id' => $documentId,
            'reserved_at' => now(),
        ]);
    }

    /**
     * Marquer le code comme utilisé
     */
    public function markAsUsed(): bool
    {
        return $this->update([
            'status' => 'used',
            'used_at' => now(),
        ]);
    }

    /**
     * Libérer le code (le rendre disponible)
     */
    public function release(string $reason = null): bool
    {
        return $this->update([
            'status' => 'available',
            'document_id' => null,
            'released_at' => now(),
            'release_reason' => $reason,
        ]);
    }
}
