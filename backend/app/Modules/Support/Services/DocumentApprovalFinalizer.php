<?php

namespace App\Modules\Support\Services;

use App\Models\ApplicationScope;
use App\Models\ManagementReview;
use App\Models\QhsePolicy;
use App\Services\PdfGeneratorService;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Régénère le PDF d'un document approuvé sans filigrane BROUILLON
 * et remplace le fichier stocké.
 */
class DocumentApprovalFinalizer
{
    /**
     * Génère le PDF avec filigrane EXPIRÉ en mémoire (pour la preview inline).
     * Ne modifie pas le fichier stocké.
     */
    public function generateObsoletePreview(Document $document): ?string
    {
        $meta = (array) ($document->metadata ?? []);
        $sourceType = (string) ($meta['source_type'] ?? $document->source_type ?? '');
        $sourceModelId = (int) ($meta['source_model_id'] ?? $document->module_id ?? 0);

        if (!$sourceType || !$sourceModelId) {
            return null;
        }

        return match ($sourceType) {
            'application_scope' => $this->regenerateApplicationScopeObsolete($document, $sourceModelId),
            'qhse_policy', 'politique_qhse' => $this->regenerateQhsePolicyObsolete($document, $sourceModelId),
            'management_review' => $this->regenerateManagementReviewObsolete($document, $sourceModelId),
            default => null,
        };
    }

    public function finalize(Document $document): void
    {
        $meta = (array) ($document->metadata ?? []);
        $sourceType = (string) ($meta['source_type'] ?? $document->source_type ?? '');
        $sourceModelId = (int) ($meta['source_model_id'] ?? $document->module_id ?? 0);

        if (!$sourceType || !$sourceModelId) {
            return;
        }

        try {
            $pdfContent = match ($sourceType) {
                'application_scope' => $this->regenerateApplicationScope($document, $sourceModelId),
                'qhse_policy', 'politique_qhse' => $this->regenerateQhsePolicy($document, $sourceModelId),
                'management_review' => $this->regenerateManagementReview($document, $sourceModelId),
                'context_swot', 'swot_pestel' => $this->regenerateContext($document, $sourceModelId),
                'stakeholder_register' => $this->regenerateStakeholders($document, $sourceModelId),
                default => null,
            };

            if (!$pdfContent) return;

            $storedPath = $document->file_path;
            if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->put($storedPath, $pdfContent);
            }
        } catch (\Throwable $e) {
            Log::warning('DocumentApprovalFinalizer: failed to regenerate PDF', [
                'document_id' => $document->id,
                'source_type' => $sourceType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function regenerateApplicationScope(Document $document, int $scopeId): ?string
    {
        $scope = \App\Models\ApplicationScope::find($scopeId);
        $enterprise = $scope?->site?->enterprise;
        if (!$scope || !$enterprise) return null;

        $html = view('pdf.application-scope', ['scope' => $scope, 'document' => $document])->render();

        return app(PdfGeneratorService::class)->generateDocument(
            $enterprise,
            'application_scope',
            ['document_id' => $document->id, 'title' => $document->title, 'code' => $document->code, 'version' => $document->version ?? '1.0', 'html' => $html],
            ['is_draft' => false]
        );
    }

    private function regenerateQhsePolicy(Document $document, int $policyId): ?string
    {
        $policy = \App\Models\QhsePolicy::find($policyId);
        $enterprise = $policy?->site?->enterprise;
        if (!$policy || !$enterprise) return null;

        $html = view('pdf.qhse-policy', ['policy' => $policy, 'document' => $document])->render();

        return app(PdfGeneratorService::class)->generateDocument(
            $enterprise,
            'qhse_policy',
            ['document_id' => $document->id, 'title' => $document->title, 'code' => $document->code, 'version' => $document->version ?? '1.0', 'html' => $html],
            ['is_draft' => false]
        );
    }

    private function regenerateManagementReview(Document $document, int $reviewId): ?string
    {
        $review = \App\Models\ManagementReview::with(['site.enterprise', 'chairman'])->find($reviewId);
        $enterprise = $review?->site?->enterprise;
        if (!$review || !$enterprise) return null;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.management-review', [
            'review' => $review,
            'isDraft' => false,
        ])->output();
    }

    /**
     * Régénère le PDF d'un document obsolète avec le filigrane EXPIRÉ.
     */
    public function finalizeObsolete(Document $document): void
    {
        $meta = (array) ($document->metadata ?? []);
        $sourceType = (string) ($meta['source_type'] ?? $document->source_type ?? '');
        $sourceModelId = (int) ($meta['source_model_id'] ?? $document->module_id ?? 0);

        if (!$sourceType || !$sourceModelId) {
            return;
        }

        try {
            $pdfContent = match ($sourceType) {
                'application_scope' => $this->regenerateApplicationScopeObsolete($document, $sourceModelId),
                'qhse_policy', 'politique_qhse' => $this->regenerateQhsePolicyObsolete($document, $sourceModelId),
                'management_review' => $this->regenerateManagementReviewObsolete($document, $sourceModelId),
                'context_swot', 'swot_pestel' => $this->regenerateContextObsolete($document, $sourceModelId),
                'stakeholder_register' => $this->regenerateStakeholdersObsolete($document, $sourceModelId),
                default => null,
            };

            if (!$pdfContent) return;

            $storedPath = $document->file_path;
            if ($storedPath && Storage::disk('public')->exists($storedPath)) {
                Storage::disk('public')->put($storedPath, $pdfContent);
            }
        } catch (\Throwable $e) {
            Log::warning('DocumentApprovalFinalizer: failed to regenerate obsolete PDF', [
                'document_id' => $document->id,
                'source_type' => $sourceType,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function regenerateApplicationScopeObsolete(Document $document, int $scopeId): ?string
    {
        $scope = \App\Models\ApplicationScope::find($scopeId);
        $enterprise = $scope?->site?->enterprise;
        if (!$scope || !$enterprise) return null;

        $html = view('pdf.application-scope', ['scope' => $scope, 'document' => $document])->render();

        return app(PdfGeneratorService::class)->generateDocument(
            $enterprise,
            'application_scope',
            ['document_id' => $document->id, 'title' => $document->title, 'code' => $document->code, 'version' => $document->version ?? '1.0', 'html' => $html],
            ['is_draft' => false, 'is_expired' => true]
        );
    }

    private function regenerateQhsePolicyObsolete(Document $document, int $policyId): ?string
    {
        $policy = \App\Models\QhsePolicy::find($policyId);
        $enterprise = $policy?->site?->enterprise;
        if (!$policy || !$enterprise) return null;

        $html = view('pdf.qhse-policy', ['policy' => $policy, 'document' => $document])->render();

        return app(PdfGeneratorService::class)->generateDocument(
            $enterprise,
            'qhse_policy',
            ['document_id' => $document->id, 'title' => $document->title, 'code' => $document->code, 'version' => $document->version ?? '1.0', 'html' => $html],
            ['is_draft' => false, 'is_expired' => true]
        );
    }

    private function regenerateManagementReviewObsolete(Document $document, int $reviewId): ?string
    {
        $review = \App\Models\ManagementReview::with(['site.enterprise', 'chairman'])->find($reviewId);
        if (!$review) return null;

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.management-review', [
            'review' => $review,
            'isDraft' => false,
            'isExpired' => true,
        ])->output();
    }

    private function regenerateContextObsolete(Document $document, int $contextId): ?string
    {
        return null;
    }

    private function regenerateStakeholdersObsolete(Document $document, int $siteId): ?string
    {
        return null;
    }

    private function regenerateContext(Document $document, int $contextId): ?string
    {
        // ContextController utilise un générateur DOCX, pas encore de vue PDF dédiée
        return null;
    }

    private function regenerateStakeholders(Document $document, int $siteId): ?string
    {
        // StakeholderController utilise un générateur DOCX, pas encore de vue PDF dédiée
        return null;
    }
}
