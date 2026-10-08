<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class QRCodeScan extends Model
{
    use HasFactory;

    protected $table = 'qr_code_scans';

    protected $fillable = [
        'document_type',
        'document_id',
        'hash',
        'ip_address',
        'user_agent',
        'country',
        'city',
        'user_id',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'scanned_at' => 'datetime',
        ];
    }

    /**
     * Utilisateur ayant scanné (si authentifié)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope: Pour un document spécifique
     */
    public function scopeForDocument($query, string $type, int $id)
    {
        return $query->where('document_type', $type)
            ->where('document_id', $id);
    }

    /**
     * Scope: Scans récents
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('scanned_at', '>=', now()->subHours($hours));
    }

    /**
     * Scope: Scans suspects (trop nombreux)
     */
    public function scopeSuspicious($query)
    {
        return $query->select('ip_address', DB::raw('COUNT(*) as scan_count'))
            ->where('scanned_at', '>=', now()->subHour())
            ->groupBy('ip_address')
            ->having('scan_count', '>', 50);
    }

    /**
     * Statistiques par pays
     */
    public static function getCountryStats(string $docType = null, int $docId = null): array
    {
        $query = static::select('country', DB::raw('COUNT(*) as count'))
            ->whereNotNull('country')
            ->groupBy('country')
            ->orderByDesc('count');

        if ($docType && $docId) {
            $query->forDocument($docType, $docId);
        }

        return $query->get()->toArray();
    }

    /**
     * Détecter activité suspecte pour un document
     */
    public static function detectAbuse(string $docType, int $docId): bool
    {
        $scansLastHour = static::forDocument($docType, $docId)
            ->where('scanned_at', '>=', now()->subHour())
            ->count();

        return $scansLastHour > 50;
    }

    /**
     * Obtenir les documents les plus scannés
     */
    public static function getMostScanned(int $limit = 10): array
    {
        return static::select('document_type', 'document_id', DB::raw('COUNT(*) as scan_count'))
            ->groupBy('document_type', 'document_id')
            ->orderByDesc('scan_count')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
