<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use App\Services\DocumentCodeWorkflowService;
use Illuminate\Http\Request;

class DocumentCodeWorkflowController extends Controller
{
    public function __construct(
        private DocumentCodeWorkflowService $workflowService
    ) {}

    public function pendingVerification(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('verify_documents'), 403);

        $siteId = $this->resolveScopedSiteId($request, $user);
        $documents = $this->workflowService->getPendingVerification($siteId);

        return DocumentResource::collection($documents);
    }

    public function pendingApproval(Request $request)
    {
        $user = $request->user();
        abort_unless($user?->can('approve_documents'), 403);

        $siteId = $this->resolveScopedSiteId($request, $user);
        $documents = $this->workflowService->getPendingApproval($siteId);

        return DocumentResource::collection($documents);
    }

    public function verifyCode(Request $request, Document $document)
    {
        $user = $request->user();
        abort_unless($user?->can('verify_documents'), 403);
        $document = $this->findDocumentForUser($document, $user);

        try {
            $verified = $this->workflowService->verifyCode($document, (int) $user->id);
            return (new DocumentResource($verified->load(['site', 'process', 'author', 'category', 'typeConfiguration', 'verifier'])))
                ->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function activateCode(Request $request, Document $document)
    {
        $user = $request->user();
        abort_unless($user?->can('approve_documents'), 403);
        $document = $this->findDocumentForUser($document, $user);

        try {
            $activated = $this->workflowService->activateCode($document, (int) $user->id);
            return (new DocumentResource($activated->load(['site', 'process', 'author', 'category', 'typeConfiguration', 'verifier', 'approver'])))
                ->response();
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function releaseCode(Request $request, Document $document)
    {
        $user = $request->user();
        abort_unless($user?->can('configure_nomenclature'), 403);
        $document = $this->findDocumentForUser($document, $user);

        try {
            $this->workflowService->releaseCode($document, (int) $user->id);
            return response()->json(['message' => 'Code libéré avec succès']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function getWorkflowStats(Request $request)
    {
        $siteId = $this->resolveScopedSiteId($request, $request->user());
        $stats = $this->workflowService->getWorkflowStats($siteId);

        return response()->json(['data' => $stats]);
    }

    private function resolveScopedSiteId(Request $request, ?User $user): ?int
    {
        $requestedSiteId = (int) $request->integer('site_id');
        if ($requestedSiteId <= 0) {
            return (int) ($user?->site_id ?? 0) > 0 ? (int) $user->site_id : null;
        }

        if (!$user || $user->isSuperAdmin()) {
            return $requestedSiteId;
        }

        if ((int) ($user->site_id ?? 0) > 0) {
            abort_unless((int) $user->site_id === $requestedSiteId, 403);
            return $requestedSiteId;
        }

        $siteEnterpriseId = (int) \App\Models\Site::query()
            ->whereKey($requestedSiteId)
            ->value('enterprise_id');

        abort_unless($siteEnterpriseId > 0 && $siteEnterpriseId === (int) ($user->enterprise_id ?? 0), 403);
        return $requestedSiteId;
    }

    private function findDocumentForUser(Document $document, ?User $user): Document
    {
        if (!$user || $user->isSuperAdmin()) {
            return $document;
        }

        if ((int) ($user->site_id ?? 0) > 0) {
            abort_unless((int) $document->site_id === (int) $user->site_id, 403);
            return $document;
        }

        $siteEnterpriseId = (int) ($document->site?->enterprise_id ?? \App\Models\Site::query()
            ->whereKey($document->site_id)
            ->value('enterprise_id'));

        abort_unless($siteEnterpriseId > 0 && $siteEnterpriseId === (int) ($user->enterprise_id ?? 0), 403);
        return $document;
    }
}
