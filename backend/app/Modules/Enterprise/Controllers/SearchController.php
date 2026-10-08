<?php

namespace App\Modules\Enterprise\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Process;
use App\Models\Risk;
use App\Models\NonConformity;
use App\Models\Action;
use App\Models\Audit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    /**
     * Recherche globale full-text avec Laravel Scout
     */
    public function globalSearch(Request $request): JsonResponse
    {
        $query = $request->input('q', '');
        $types = $request->input('types', ['documents', 'processes', 'risks', 'non_conformities']);
        
        // Convert types to array if it's a string (from query param)
        if (is_string($types)) {
            $types = explode(',', $types);
        }
        
        $limit = $request->input('limit', 10);

        if (empty($query)) {
            return response()->json([
                'query' => '',
                'results' => [],
                'total' => 0,
            ]);
        }

        $results = [];

        // Documents
        if (in_array('documents', $types)) {
            $documents = Document::search($query)
                ->take($limit)
                ->get()
                ->map(function ($doc) {
                    return [
                        'type' => 'document',
                        'id' => $doc->id,
                        'ref' => $doc->ref,
                        'title' => $doc->title,
                        'code' => $doc->code,
                        'status' => $doc->status,
                        'version' => $doc->version,
                        'url' => "/company/documents/{$doc->id}",
                    ];
                });

            $results = array_merge($results, $documents->toArray());
        }

        // Processus
        if (in_array('processes', $types)) {
            $processes = Process::search($query)
                ->take($limit)
                ->get()
                ->map(function ($proc) {
                    return [
                        'type' => 'process',
                        'id' => $proc->id,
                        'ref' => $proc->ref,
                        'title' => $proc->title,
                        'code' => $proc->code,
                        'pilot' => $proc->pilot?->name,
                        'url' => "/company/processes/{$proc->id}",
                    ];
                });

            $results = array_merge($results, $processes->toArray());
        }

        // Risques
        if (in_array('risks', $types)) {
            $risks = Risk::search($query)
                ->take($limit)
                ->get()
                ->map(function ($risk) {
                    return [
                        'type' => 'risk',
                        'id' => $risk->id,
                        'ref' => $risk->ref,
                        'title' => $risk->title,
                        'criticality' => $risk->criticality,
                        'probability' => $risk->probability,
                        'gravity' => $risk->gravity,
                        'url' => "/company/risks",
                    ];
                });

            $results = array_merge($results, $risks->toArray());
        }

        // Non-Conformités
        if (in_array('non_conformities', $types)) {
            $ncs = NonConformity::search($query)
                ->take($limit)
                ->get()
                ->map(function ($nc) {
                    return [
                        'type' => 'non_conformity',
                        'id' => $nc->id,
                        'ref' => $nc->ref,
                        'title' => $nc->title,
                        'source' => $nc->source,
                        'gravity' => $nc->gravity,
                        'url' => "/company/nonconformities/{$nc->id}",
                    ];
                });

            $results = array_merge($results, $ncs->toArray());
        }

        return response()->json([
            'query' => $query,
            'results' => $results,
            'total' => count($results),
            'types_searched' => $types,
        ]);
    }

    /**
     * Recherche avancée avec filtres
     */
    public function advancedSearch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'required|string|in:documents,processes,risks,non_conformities',
            'filters' => 'nullable|array',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $query = $validated['query'];
        $type = $validated['type'];
        $filters = $validated['filters'] ?? [];
        $limit = $validated['limit'] ?? 20;

        $model = match($type) {
            'documents' => Document::class,
            'processes' => Process::class,
            'risks' => Risk::class,
            'non_conformities' => NonConformity::class,
        };

        $builder = $model::search($query);

        // Appliquer filtres
        if (isset($filters['status'])) {
            $builder->where('status', $filters['status']);
        }

        if (isset($filters['process_id'])) {
            $builder->where('process_id', $filters['process_id']);
        }

        if (isset($filters['created_after'])) {
            $builder->where('created_at', '>=', $filters['created_after']);
        }

        $results = $builder->paginate($limit);

        return response()->json($results);
    }
}
