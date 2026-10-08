<?php

namespace App\Modules\Support\Services;

use App\Models\QRCodeScan;
use App\Models\QRCodeSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class QRCodeService
{
    /**
     * Révoquer tous les QR codes actifs liés à un document.
     */
    public function revokeForDocument(int $docId): void
    {
        QRCodeSignature::query()
            ->where('document_id', $docId)
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    /**
     * Générer un QR code lié à la version approuvée du document.
     * Retourne le hash du nouveau QR code.
     */
    public function generateApproved(\App\Models\Document $document): string
    {
        $this->revokeForDocument($document->id);

        $hash = $this->generate($document->source_type ?? 'document', $document->id, [
            'document_title' => $document->title,
            'enterprise_name' => $document->site?->enterprise?->name ?? config('app.name'),
            'approved_at' => $document->approved_at?->toIso8601String(),
            'version' => $document->version,
        ]);

        // Stocker le hash dans metadata pour récupération rapide sans JOIN
        $meta = (array) ($document->metadata ?? []);
        $meta['approved_qr_hash'] = $hash;
        $document->updateQuietly(['metadata' => $meta]);

        return $hash;
    }

    /**
     * Générer un hash sécurisé pour QR code
     */
    public function generate(string $docType, int $docId, array $metadata = []): string
    {
        $ttlMinutes = max(1, (int) config('documents.qr_signature_ttl_minutes', 43200));
        $hash = $this->generateUniqueHash($docType, $docId);

        QRCodeSignature::create([
            'document_type' => $docType,
            'document_id' => $docId,
            'hash' => $hash,
            'generated_by' => Auth::id(),
            'expires_at' => now()->addMinutes($ttlMinutes),
            'meta' => $metadata,
        ]);

        return $hash;
    }

    /**
     * Obtenir l'URL de vérification
     */
    public function getVerificationUrl(string $hash): string
    {
        return url("/verify/{$hash}");
    }

    /**
     * Vérifier un hash et retourner les informations du document
     */
    public function verify(string $hash): ?array
    {
        $signature = QRCodeSignature::query()
            ->where('hash', $hash)
            ->whereNull('revoked_at')
            ->first();

        if (!$signature) {
            return null;
        }

        if ($signature->expires_at && now()->greaterThan($signature->expires_at)) {
            return null;
        }

        $meta = $signature->meta ?? [];

        return [
            'document_type' => $signature->document_type,
            'document_id' => $signature->document_id,
            'hash' => $hash,
            'document_title' => (string) ($meta['document_title'] ?? ('Document ' . $signature->document_id)),
            'enterprise_name' => (string) ($meta['enterprise_name'] ?? config('app.name')),
            'generated_at' => $signature->created_at?->toIso8601String(),
            'expires_at' => $signature->expires_at?->toIso8601String(),
            'workflow_status' => (string) (($meta['workflow_status'] ?? $meta['status'] ?? 'approved')),
            'status' => (string) ($meta['status'] ?? 'approved'),
            'approved_at' => $meta['approved_at'] ?? null,
            'scan_count' => QRCodeScan::query()->where('hash', $hash)->count(),
        ];
    }

    /**
     * Tracker un scan de QR code
     */
    public function track(string $hash, string $docType, int $docId, Request $request): void
    {
        $geoData = $this->getGeoData($request->ip());

        QRCodeScan::create([
            'document_type' => $docType,
            'document_id' => $docId,
            'hash' => $hash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'country' => $geoData['country'] ?? null,
            'city' => $geoData['city'] ?? null,
            'user_id' => Auth::id(),
            'scanned_at' => now(),
        ]);
    }

    /**
     * Détecter un abus (trop de scans)
     */
    public function detectAbuse(string $docType, int $docId): bool
    {
        return QRCodeScan::detectAbuse($docType, $docId);
    }

    /**
     * Obtenir les statistiques de scans
     */
    public function getStats(?string $docType = null, ?int $docId = null): array
    {
        $query = QRCodeScan::query();

        if ($docType && $docId) {
            $query->forDocument($docType, $docId);
        }

        $totalScans = (clone $query)->count();
        $countryStats = QRCodeScan::getCountryStats($docType, $docId);

        return [
            'total_scans' => $totalScans,
            'unique_ips' => (clone $query)->distinct('ip_address')->count('ip_address'),
            'countries' => collect($countryStats)->pluck('count', 'country')->all(),
            'recent_scans' => (clone $query)
                ->orderByDesc('scanned_at')
                ->limit(20)
                ->get(['id', 'document_type', 'document_id', 'hash', 'ip_address', 'user_agent', 'country', 'city', 'scanned_at'])
                ->toArray(),
            'scans_today' => (clone $query)->whereDate('scanned_at', today())->count(),
            'scans_this_week' => (clone $query)->where('scanned_at', '>=', now()->startOfWeek())->count(),
            'scans_this_month' => (clone $query)->whereMonth('scanned_at', now()->month)->count(),
            'by_country' => $countryStats,
            'most_scanned' => QRCodeScan::getMostScanned(10),
        ];
    }

    private function generateUniqueHash(string $docType, int $docId): string
    {
        do {
            $data = implode('|', [
                $docType,
                $docId,
                now()->timestamp,
                Str::random(32),
            ]);
            $hash = hash_hmac('sha256', $data, config('app.key'));
        } while (QRCodeSignature::query()->where('hash', $hash)->exists());

        return $hash;
    }

    /**
     * Obtenir les données géographiques depuis l'IP
     */
    protected function getGeoData(string $ip): array
    {
        // Utiliser un service de géolocalisation (geoip2, ipapi, etc.)
        // Pour l'instant, retour basique
        try {
            if (\function_exists('geoip_country_code_by_name')) {
                return [
                    'country' => \geoip_country_code_by_name($ip),
                    'city' => null,
                ];
            }
        } catch (\Exception $e) {
            // Ignorer les erreurs de géolocalisation
        }

        return ['country' => null, 'city' => null];
    }
}
