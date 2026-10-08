<?php

namespace App\Modules\Support\Services;

use App\Models\Document;
use App\Models\DocumentCodePool;
use App\Models\DocumentCodeRelease;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DocumentCodeRecyclingService
{
    /**
     * Vérifie si un code est disponible pour une entreprise
     */
    public function isCodeAvailable(string $code, int $enterpriseId): bool
    {
        // Un code est indisponible dès qu'il est déjà porté par un document actif
        // de l'entreprise (soft-deleted exclus).
        $usedByDocument = Document::where('code', $code)
            ->whereHas('site', fn ($q) => $q->where('enterprise_id', $enterpriseId))
            ->exists();

        if ($usedByDocument) {
            return false;
        }

        // Vérifier si le code a été réutilisé récemment (dans les 6 derniers mois)
        $recentlyReused = DocumentCodeRelease::where('code', $code)
            ->where('entreprise_id', $enterpriseId)
            ->whereNotNull('reused_at')
            ->where('reused_at', '>=', now()->subMonths(6))
            ->exists();

        if ($recentlyReused) {
            return false;
        }

        // Vérifier dans le pool de codes
        $poolEntry = DocumentCodePool::where('code', $code)
            ->whereHas('site', fn ($q) => $q->where('enterprise_id', $enterpriseId))
            ->first();

        if ($poolEntry) {
            return $poolEntry->status === 'available';
        }

        return true;
    }

    /**
     * Libère un code pour recyclage (API publique)
     */
    public function releaseCode(
        string $code,
        int $enterpriseId,
        ?int $originalDocumentId,
        string $reason,
        int $releasedBy
    ): DocumentCodeRelease {
        return DB::transaction(function () use ($code, $enterpriseId, $originalDocumentId, $reason, $releasedBy) {
            $release = DocumentCodeRelease::create([
                'code' => $code,
                'entreprise_id' => $enterpriseId,
                'original_document_id' => $originalDocumentId,
                'release_reason' => $reason,
                'released_by' => $releasedBy,
                'released_at' => now(),
            ]);

            Log::info('Code document libéré', [
                'code' => $code,
                'enterprise_id' => $enterpriseId,
                'reason' => $reason,
                'released_by' => $releasedBy,
            ]);

            return $release;
        });
    }

    /**
     * Récupère les codes disponibles pour une entreprise
     */
    public function getAvailableCodes(int $enterpriseId): array
    {
        return DocumentCodeRelease::where('entreprise_id', $enterpriseId)
            ->whereNull('reused_at')
            ->orderBy('released_at', 'desc')
            ->get()
            ->map(fn ($release) => [
                'code' => $release->code,
                'released_at' => $release->released_at,
                'reason' => $release->release_reason,
            ])
            ->toArray();
    }

    /**
     * Récupère l'historique d'un code
     */
    public function getCodeHistory(string $code, int $enterpriseId): array
    {
        return DocumentCodeRelease::where('code', $code)
            ->where('entreprise_id', $enterpriseId)
            ->orderBy('released_at', 'desc')
            ->get()
            ->map(fn ($release) => [
                'code' => $release->code,
                'released_at' => $release->released_at,
                'reused_at' => $release->reused_at,
                'reason' => $release->release_reason,
                'released_by' => $release->released_by,
            ])
            ->toArray();
    }

    /**
     * Réserver un code pour un document
     */
    public function reserveCode(string $code, int $siteId, string $documentType, int $documentId): DocumentCodePool
    {
        return DB::transaction(function () use ($code, $siteId, $documentType, $documentId) {
            $poolEntry = DocumentCodePool::where('code', $code)
                ->where('site_id', $siteId)
                ->lockForUpdate()
                ->first();

            if ($poolEntry) {
                if ($poolEntry->status === 'available') {
                    $poolEntry->reserve($documentId);
                    return $poolEntry;
                }
                throw new \Exception("Le code {$code} n'est pas disponible.");
            }

            return DocumentCodePool::create([
                'site_id' => $siteId,
                'document_type' => $documentType,
                'code' => $code,
                'status' => 'reserved',
                'document_id' => $documentId,
                'reserved_at' => now(),
            ]);
        });
    }

    /**
     * Marquer un code comme utilisé
     */
    public function markCodeAsUsed(int $documentId): bool
    {
        $poolEntry = DocumentCodePool::where('document_id', $documentId)
            ->where('status', 'reserved')
            ->first();

        if (!$poolEntry) {
            return false;
        }

        return $poolEntry->markAsUsed();
    }

    /**
     * Libère un code par document ID (usage interne - compatibilité ancienne signature)
     */
    public function releaseCodeByDocumentId(int $documentId, string $reason = 'rejection'): bool
    {
        $document = Document::find($documentId);
        if (
            $document
            && (
                (string) ($document->workflow_status ?? '') === 'approved'
                || (string) ($document->status ?? '') === 'approved'
            )
        ) {
            return false;
        }

        $poolEntry = DocumentCodePool::where('document_id', $documentId)
            ->whereIn('status', ['reserved', 'used'])
            ->first();

        if (!$poolEntry) {
            return false;
        }

        return $poolEntry->release($reason);
    }

    /**
     * Obtenir le prochain code disponible (recyclé ou nouveau)
     */
    public function getNextAvailableCode(int $siteId, string $documentType): ?string
    {
        return DB::transaction(function () use ($siteId, $documentType) {
            $availableCode = DocumentCodePool::forSiteAndType($siteId, $documentType)
                ->available()
                ->orderBy('code')
                ->lockForUpdate()
                ->first();

            return $availableCode?->code;
        });
    }

    /**
     * Nettoyer les réservations expirées
     */
    public function cleanupExpiredReservations(int $hoursThreshold = 24): int
    {
        $threshold = now()->subHours($hoursThreshold);

        return DB::transaction(function () use ($threshold) {
            $expiredReservations = DocumentCodePool::reserved()
                ->where('reserved_at', '<', $threshold)
                ->get();

            $count = 0;
            foreach ($expiredReservations as $reservation) {
                $document = Document::find($reservation->document_id);
                if (!$document || $document->workflow_status === 'draft') {
                    $reservation->release('expired_reservation');
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Traiter la décision de rejet
     */
    public function handleRejectionDecision(int $documentId, bool $releaseCode): bool
    {
        if (!$releaseCode) {
            return true;
        }
        return $this->releaseCodeByDocumentId($documentId, 'submitter_decision_after_rejection');
    }

    /**
     * Statistiques du pool
     */
    public function getPoolStats(int $siteId, ?string $documentType = null): array
    {
        $query = DocumentCodePool::where('site_id', $siteId);
        if ($documentType) {
            $query->where('document_type', $documentType);
        }

        $total = $query->count();
        $available = (clone $query)->available()->count();
        $reserved = (clone $query)->reserved()->count();
        $used = (clone $query)->used()->count();

        return [
            'total' => $total,
            'available' => $available,
            'reserved' => $reserved,
            'used' => $used,
            'recycling_rate' => $total > 0 ? round(($available / $total) * 100, 2) : 0,
        ];
    }
}
