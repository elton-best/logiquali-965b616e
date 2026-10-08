<?php

namespace App\Http\Controllers;

use App\Services\QRCodeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class QRCodeVerificationController extends Controller
{
    public function __construct(
        protected QRCodeService $qrCodeService
    ) {}

    /**
     * Vérifier un QR code (route publique)
     */
    public function verify(Request $request, string $hash): JsonResponse
    {
        // Rate limiting strict : 10 requêtes par minute par IP
        $key = 'qr-verify:' . $request->ip();
        
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Trop de tentatives. Réessayez dans quelques instants.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, 60);

        // Vérifier le hash
        $document = $this->qrCodeService->verify($hash);

        if (!$document) {
            return response()->json([
                'valid' => false,
                'message' => 'QR Code invalide ou document introuvable',
            ], 404);
        }

        // Tracker le scan
        $this->qrCodeService->track(
            $hash,
            $document['document_type'],
            $document['document_id'],
            $request
        );

        // Détecter abus
        if ($this->qrCodeService->detectAbuse($document['document_type'], $document['document_id'])) {
            Log::warning('QR Code abuse detected', [
                'document_type' => $document['document_type'],
                'document_id' => $document['document_id'],
                'ip' => $request->ip(),
            ]);
        }

        // Récupérer les données publiques du document
        $doc = \App\Models\Document::query()
            ->select(['id', 'title', 'code', 'version', 'workflow_status', 'status', 'approved_at', 'source_type'])
            ->find($document['document_id']);

        $isApproved = $doc?->workflow_status === 'approved';

        // Retourner informations publiques uniquement
        return response()->json([
            'valid' => $isApproved,
            'document_id' => $document['document_id'],
            'document_type' => $document['document_type'],
            'document_title' => $doc?->title ?? $document['document_title'] ?? ('Document ' . $document['document_id']),
            'code' => $doc?->code,
            'version' => $doc?->version,
            'workflow_status' => $doc?->workflow_status,
            'status' => $doc?->status,
            'approved_at' => $doc?->approved_at?->toIso8601String(),
            'enterprise_name' => $document['enterprise_name'] ?? config('app.name'),
            'generated_at' => $document['generated_at'] ?? null,
            'expires_at' => $document['expires_at'] ?? null,
            'scan_count' => $document['scan_count'] ?? 0,
            'message' => $isApproved ? 'Document authentique et approuvé' : 'Document non approuvé',
        ]);
    }

    /**
     * Statistiques de scans (authentifié)
     */
    public function stats(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'nullable|string',
            'document_id' => 'nullable|integer',
        ]);

        $stats = $this->qrCodeService->getStats(
            $validated['document_type'] ?? null,
            $validated['document_id'] ?? null
        );

        return response()->json($stats);
    }
}
