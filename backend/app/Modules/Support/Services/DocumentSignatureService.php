<?php

namespace App\Modules\Support\Services;

use App\Models\DocumentSignature;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DocumentSignatureService
{
    /**
     * Signer un document
     */
    public function signDocument(
        string $documentType,
        int $documentId,
        User $user,
        string $password,
        ?string $documentContent = null
    ): DocumentSignature {
        if (!$user->hasUploadedSignature()) {
            throw new \Exception('Vous devez importer votre signature avant de signer un document.');
        }

        // Vérifier mot de passe
        if (!Hash::check($password, $user->password)) {
            throw new \Exception('Mot de passe incorrect');
        }

        // Vérifier si déjà signé
        $existing = DocumentSignature::forDocument($documentType, $documentId)
            ->where('user_id', $user->id)
            ->active()
            ->first();

        if ($existing) {
            throw new \Exception('Vous avez déjà signé ce document');
        }

        // Créer signature
        return DocumentSignature::create([
            'document_type' => $documentType,
            'document_id' => $documentId,
            'user_id' => $user->id,
            'signature_text' => "Lu et approuvé par {$user->name}",
            'signed_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'document_hash' => $documentContent ? hash('sha256', $documentContent) : null,
            'password_verified' => true,
        ]);
    }

    /**
     * Récupérer signatures d'un document
     */
    public function getDocumentSignatures(string $documentType, int $documentId)
    {
        return DocumentSignature::with('company')
            ->forDocument($documentType, $documentId)
            ->active()
            ->orderBy('signed_at', 'desc')
            ->get();
    }

    /**
     * Vérifier si utilisateur a signé
     */
    public function hasUserSigned(string $documentType, int $documentId, int $userId): bool
    {
        return DocumentSignature::forDocument($documentType, $documentId)
            ->where('user_id', $userId)
            ->active()
            ->exists();
    }

    /**
     * Révoquer signature (admin uniquement)
     */
    public function revokeSignature(int $signatureId, User $revokedBy, string $reason): bool
    {
        $signature = DocumentSignature::findOrFail($signatureId);

        return $signature->update([
            'is_revoked' => true,
            'revoked_at' => now(),
            'revoked_by' => $revokedBy->id,
            'revocation_reason' => $reason,
        ]);
    }
}
