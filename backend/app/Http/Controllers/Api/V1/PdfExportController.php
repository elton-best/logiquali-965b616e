<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\PdfGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PdfExportController extends Controller
{
    public function __construct(
        private PdfGeneratorService $pdfGenerator
    ) {}

    public function exportDocument(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|string',
            'document_id' => 'required|integer',
            'content' => 'required|array',
            'content.html' => 'required|string',
            'content.title' => 'required|string',
            'layout' => 'nullable|in:professional,minimal',
            'site_id' => 'nullable|integer|exists:sites,id',
            'process_id' => 'nullable|integer|exists:processes,id',
            'source_updated_at' => 'nullable|date',
        ]);

        $user = Auth::user();
        $enterprise = $user?->enterprise;

        $pdfContent = $this->pdfGenerator->generateDocument(
            $enterprise,
            $validated['document_type'],
            [
                'document_id' => $validated['document_id'],
                'html' => $validated['content']['html'],
                'title' => $validated['content']['title'],
            ],
            ['layout' => $validated['layout'] ?? null]
        );

        $filename = $this->generateFilename($validated['content']['title']);
        $relativePath = 'exports/' . $filename;
        $tempPath = Storage::disk('local')->path($relativePath);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }
        file_put_contents($tempPath, $pdfContent);

        $siteId = (int) ($validated['site_id'] ?? ($user?->site_id ?? 0));
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }

        $document = null;
        if ($siteId > 0) {
            $documentType = (string) $validated['document_type'];
            [$documentKind, $type] = $this->resolveInventoryClassification($documentType);

            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => (int) ($validated['process_id'] ?? 0) ?: null,
                'process_code' => 'GEN',
                'document_kind' => $documentKind,
                'source_type' => $documentType,
                'source_id' => (int) $validated['document_id'],
                'source_updated_at' => $validated['source_updated_at'] ?? null,
                'title' => (string) ($validated['content']['title'] ?? ('Document ' . $validated['document_id'])),
                'description' => 'Document PDF exporté depuis le générateur générique.',
                'file_source_path' => $tempPath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => $type,
                'metadata' => [
                    'source' => 'pdf_export_controller',
                    'document_type' => $documentType,
                    'document_id' => (int) $validated['document_id'],
                ],
                'force_new' => true,
            ]);
        }

        $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function previewDocument(Request $request)
    {
        $validated = $request->validate([
            'document_type' => 'required|string',
            'document_id' => 'nullable|integer',
            'content' => 'required|array',
            'content.html' => 'required|string',
            'content.title' => 'required|string',
            'layout' => 'nullable|in:professional,minimal',
        ]);

        $enterprise = Auth::user()->enterprise;

        $html = $this->pdfGenerator->previewDocument(
            $enterprise,
            $validated['document_type'],
            [
                'document_id' => $validated['document_id'] ?? null,
                'html' => $validated['content']['html'],
                'title' => $validated['content']['title'],
            ],
            ['layout' => $validated['layout'] ?? null]
        );

        return response()->json([
            'html' => $html,
        ]);
    }

    private function generateFilename(string $title): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($title));
        return $slug . '_' . date('Y-m-d') . '.pdf';
    }

    private function resolveInventoryClassification(string $documentType): array
    {
        $normalized = strtolower(trim($documentType));

        return match ($normalized) {
            'process', 'process_sheet', 'fiche_processus' => ['process_sheet_pdf', 'PRD'],
            'procedure', 'procedure_doc', 'procedure_document' => ['procedure_pdf', 'PRC'],
            'policy', 'qhse_policy', 'politique' => ['policy_pdf', 'POL'],
            'form', 'formulaire', 'template', 'modele' => ['form_pdf', 'FOR'],
            default => ['generic_pdf_export', 'ENR'],
        };
    }
}
