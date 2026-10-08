<?php

namespace App\Modules\Leadership\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesGeneratedDocumentContext;
use App\Http\Resources\ContextResource;
use App\Http\Resources\DocumentResource;
use App\Models\Context;
use Illuminate\Http\Request;

class ContextController extends Controller
{
    use ResolvesGeneratedDocumentContext;

    public function index(Request $request)
    {
        $query = Context::with('site');

        // Filter by type (swot, pestel, internal, external)
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }

        // Filter by site_id
        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $contexts = $query->paginate(20);
        return ContextResource::collection($contexts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'type' => 'nullable|string|max:50',
            'category' => 'nullable|in:economic,social,technological,environmental,legal,political,organizational,other,strength,weakness,opportunity,threat',
            'description' => 'nullable|string',
            'impact' => 'nullable|in:positive,negative,neutral',
            'analysis' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            // SWOT fields
            'swot_strengths' => 'nullable|string',
            'swot_weaknesses' => 'nullable|string',
            'swot_opportunities' => 'nullable|string',
            'swot_threats' => 'nullable|string',
            // PESTEL fields
            'pestel_political' => 'nullable|string',
            'pestel_economic' => 'nullable|string',
            'pestel_social' => 'nullable|string',
            'pestel_technological' => 'nullable|string',
            'pestel_environmental' => 'nullable|string',
            'pestel_legal' => 'nullable|string',
        ]);

        // Ajouter valeurs par défaut pour champs NOT NULL
        if (!isset($validated['category'])) {
            $validated['category'] = 'other';
        }
        if (!isset($validated['description'])) {
            $validated['description'] = $validated['title'] ?? 'Analyse de contexte';
        }

        $context = Context::create($validated);
        return new ContextResource($context->load('site'));
    }

    public function show(Context $context)
    {
        return new ContextResource($context->load('site'));
    }

    public function update(Request $request, Context $context)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'type' => 'nullable|string|max:50',
            'category' => 'nullable|in:economic,social,technological,environmental,legal,political,organizational,other,strength,weakness,opportunity,threat',
            'description' => 'nullable|string',
            'impact' => 'nullable|in:positive,negative,neutral',
            'analysis' => 'nullable|string',
            'title' => 'nullable|string|max:255',
            'year' => 'nullable|integer',
            // SWOT fields
            'swot_strengths' => 'nullable|string',
            'swot_weaknesses' => 'nullable|string',
            'swot_opportunities' => 'nullable|string',
            'swot_threats' => 'nullable|string',
            // PESTEL fields
            'pestel_political' => 'nullable|string',
            'pestel_economic' => 'nullable|string',
            'pestel_social' => 'nullable|string',
            'pestel_technological' => 'nullable|string',
            'pestel_environmental' => 'nullable|string',
            'pestel_legal' => 'nullable|string',
        ]);

        $context->update($validated);
        return new ContextResource($context->load('site'));
    }

    public function destroy(Context $context)
    {
        $context->delete();
        return response()->json(null, 204);
    }

    /**
     * Export Context as DOCX
     */
    public function exportDocx(Request $request)
    {
        try {
            $siteId = $request->get('site_id');
            if (!$siteId) {
                return response()->json(['message' => 'site_id requis'], 400);
            }
            $siteId = (int) $siteId;
            $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);

            $generator = new \App\Services\Docx\ContextDocxGenerator();
            $filePath = $generator->generate($siteId);
            
            $site = \App\Models\Site::find($siteId);
            $sourceUpdatedAt = \App\Models\Context::query()
                ->where('site_id', (int) $siteId)
                ->max('updated_at');

            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => $generationContext['process_id'],
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'context',
                'source_type' => 'context',
                'source_id' => (int) $siteId,
                'source_updated_at' => $sourceUpdatedAt,
                'title' => 'Contexte de l’organisme - ' . ($site->name ?? 'Site'),
                'description' => 'Document de contexte généré automatiquement.',
                'file_source_path' => $filePath,
                'created_by' => request()->user()?->id,
                'type' => $generationContext['type'],
                'force_new' => true,
                'metadata' => $generationContext['metadata'],
            ]);

            $filename = 'Contexte_Organisme_' . ($site->name ?? 'Site') . '_' . now()->format('Y-m-d') . '.docx';
            
            return response()
                ->download($filePath, $filename)
                ->header('X-Generated-Document-Id', (string) $document->id)
                ->deleteFileAfterSend(true);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException|\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération du document',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate Context draft document (no download)
     */
    public function generateDraftDocx(Request $request)
    {
        $siteId = (int) $request->get('site_id');
        if ($siteId <= 0) {
            return response()->json(['message' => 'site_id requis'], 400);
        }
        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);

        $generator = new \App\Services\Docx\ContextDocxGenerator();
        $filePath = $generator->generate($siteId);

        $site = \App\Models\Site::find($siteId);
        $sourceUpdatedAt = \App\Models\Context::query()
            ->where('site_id', (int) $siteId)
            ->max('updated_at');

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'context',
            'source_type' => 'context',
            'source_id' => (int) $siteId,
            'source_updated_at' => $sourceUpdatedAt,
            'title' => 'Contexte de l’organisme - ' . ($site->name ?? 'Site'),
            'description' => 'Document de contexte généré automatiquement.',
            'file_source_path' => $filePath,
            'created_by' => request()->user()?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }
}
