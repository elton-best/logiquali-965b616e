<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignatureWorkflow;
use App\Services\SignatureWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SignatureWorkflowController extends Controller
{
    public function __construct(
        protected SignatureWorkflowService $workflowService
    ) {}

    /**
     * Initier un workflow de signatures
     */
    public function initiate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'document_type' => 'required|string|max:50',
            'document_id' => 'required|integer',
            'workflow_name' => 'required|string|max:100',
            'signers' => 'required|array|min:1',
            'signers.*.user_id' => 'required|exists:users,id',
            'signers.*.role' => 'required|string|max:50',
        ]);

        $workflow = $this->workflowService->initiate(
            $validated['document_type'],
            $validated['document_id'],
            $validated['signers'],
            $validated['workflow_name']
        );

        return response()->json($workflow->load('signatures.user'), 201);
    }

    /**
     * Signer un document
     */
    public function sign(Request $request, DocumentSignatureWorkflow $workflow): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'comments' => 'nullable|string|max:1000',
        ]);

        try {
            $this->workflowService->sign(
                $workflow,
                auth()->user(),
                $validated['password'],
                $validated['comments'] ?? null
            );

            return response()->json([
                'message' => 'Document signé avec succès',
                'workflow' => $workflow->fresh()->load('signatures.user'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Rejeter une signature
     */
    public function reject(Request $request, DocumentSignatureWorkflow $workflow): JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
        ]);

        try {
            $this->workflowService->reject(
                $workflow,
                auth()->user(),
                $validated['reason']
            );

            return response()->json([
                'message' => 'Signature rejetée',
                'workflow' => $workflow->fresh()->load('signatures.user'),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    /**
     * Liste des workflows en attente pour l'utilisateur connecté
     */
    public function pending(): JsonResponse
    {
        $workflows = DocumentSignatureWorkflow::inProgress()
            ->whereHas('signatures', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('status', 'pending')
                    ->whereColumn('signature_order', 'document_signature_workflows.current_step');
            })
            ->with(['signatures.user', 'initiator'])
            ->orderBy('expires_at')
            ->get();

        return response()->json($workflows);
    }

    /**
     * Détails d'un workflow
     */
    public function show(DocumentSignatureWorkflow $workflow): JsonResponse
    {
        return response()->json($workflow->load(['signatures.user', 'initiator']));
    }
}
