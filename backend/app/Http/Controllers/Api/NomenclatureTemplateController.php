<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NomenclatureTemplate;
use App\Services\NomenclatureTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NomenclatureTemplateController extends Controller
{
    public function __construct(
        private NomenclatureTemplateService $templateService
    ) {}

    /**
     * Liste des templates de nomenclature
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $siteId = $request->input('site_id', $user->site_id);

        $query = NomenclatureTemplate::with(['documentTypeCatalog', 'processCatalog', 'publisher'])
            ->withCount('documents')
            ->where('site_id', $siteId);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->boolean('active_only')) {
            $query->where('is_active', true);
        }

        $templates = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'data' => $templates,
            'meta' => [
                'total' => $templates->count(),
            ],
        ]);
    }

    /**
     * Détails d'un template
     */
    public function show(Request $request, int $id)
    {
        $template = NomenclatureTemplate::with(['documentTypeCatalog', 'processCatalog', 'publisher'])
            ->findOrFail($id);

        // Vérifier l'accès (même site)
        $user = $request->user();
        if ($template->site_id !== $user->site_id && $user->user_type !== 'super_admin') {
            abort(403, 'Accès non autorisé.');
        }

        return response()->json(['data' => $template]);
    }

    /**
     * Valider la structure d'un template (sans création)
     */
    public function validateTemplate(Request $request)
    {
        $request->validate([
            'format_structure' => 'required|array',
        ]);

        $structure = $request->input('format_structure', []);
        $hasSequence = collect($structure)->contains(fn ($part) =>
            ($part['type'] ?? '') === 'token' && in_array($part['token'] ?? '', ['SEQUENCE', 'NUMERO', 'SEQ'])
        );

        if (!$hasSequence) {
            return response()->json([
                'errors' => ['format_structure' => ['La structure doit contenir un token de séquence (SEQUENCE).']],
                'message' => 'La structure doit contenir un token de séquence.',
            ], 422);
        }

        return response()->json([
            'data' => [
                'valid' => true,
                'structure' => $structure,
            ],
        ]);
    }

    /**
     * Simuler des exemples de codes générés
     */
    public function simulateSamples(Request $request)
    {
        $request->validate([
            'format_structure' => 'required|array',
            'sample_count' => 'nullable|integer|min:1|max:10',
            'context' => 'nullable|array',
        ]);

        $structure = $request->input('format_structure', []);
        $count = $request->input('sample_count', 3);
        $context = $request->input('context', []);
        $separator = $request->input('separator', '-');
        $hasExplicitSeparators = collect($structure)->contains(fn ($part) => ($part['type'] ?? '') === 'separator');

        $samples = [];
        for ($i = 1; $i <= $count; $i++) {
            $parts = [];
            foreach ($structure as $part) {
                $type = $part['type'] ?? '';
                $length = $part['length'] ?? 3;
                $value = $part['value'] ?? null;

                if ($type === 'separator') {
                    $parts[] = $value ?? '-';
                } elseif ($type === 'token') {
                    $token = $part['token'] ?? '';
                    if (in_array($token, ['SEQUENCE', 'NUMERO', 'SEQ'])) {
                        $parts[] = str_pad((string) $i, $length, '0', STR_PAD_LEFT);
                    } elseif (isset($context[strtolower($token)])) {
                        $parts[] = $context[strtolower($token)];
                    } else {
                        $parts[] = strtoupper($token);
                    }
                } elseif ($type === 'sequential_number') {
                    $parts[] = str_pad((string) $i, $length, '0', STR_PAD_LEFT);
                } elseif (isset($context[$type])) {
                    $parts[] = $context[$type];
                } elseif ($type === 'year') {
                    $parts[] = date('Y');
                } elseif ($type === 'month') {
                    $parts[] = date('m');
                } elseif ($type === 'site_code') {
                    $parts[] = $context['site_code'] ?? 'SIT';
                } elseif ($type === 'custom' || $type === 'free_text') {
                    $parts[] = $value ?? str_repeat('X', max(1, $length));
                } elseif ($type === 'document_type') {
                    $parts[] = $context['document_type'] ?? str_pad('XXX', max(1, $length), 'X');
                } elseif ($type === 'process_code') {
                    $parts[] = $context['process_code'] ?? 'RH';
                } else {
                    $parts[] = str_repeat('?', max(1, $length));
                }
            }
            $samples[] = [
                'code' => $hasExplicitSeparators ? implode('', $parts) : implode($separator, $parts),
                'index' => $i,
            ];
        }

        return response()->json(['data' => $samples]);
    }

    /**
     * Créer un nouveau template
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $this->normalizeTemplatePayload($request);
        
        $validated = $request->validate([
            'site_id' => 'required|integer|exists:sites,id',
            'document_type_catalog_id' => 'nullable|integer|exists:document_type_catalogs,id',
            'process_catalog_id' => 'nullable|integer|exists:process_catalogs,id',
            'name' => 'required|string|max:180',
            'version' => 'nullable|integer|min:1',
            'status' => 'nullable|string|in:draft,published,archived',
            'description' => 'nullable|string',
            'separator' => 'nullable|string|max:5',
            'format_structure' => 'required|array',
            'format_structure.*.order' => 'nullable|integer|min:1',
            'format_structure.*.type' => 'required|string',
            'format_structure.*.label' => 'nullable|string|max:100',
            'format_structure.*.length' => 'nullable|integer|min:1|max:20',
            'format_structure.*.value' => 'nullable|string|max:50',
            'format_structure.*.editable' => 'nullable|boolean',
            'format_structure.*.auto' => 'nullable|boolean',
            'format_structure.*.token' => 'nullable|string',
            'builder_config' => 'nullable|array',
        ]);

        // Vérifier l'accès au site
        if ($validated['site_id'] !== $user->site_id && $user->user_type !== 'super_admin') {
            abort(403, 'Accès non autorisé.');
        }

        // Ajouter l'enterprise_id depuis le site
        $site = \App\Models\Site::findOrFail($validated['site_id']);
        $validated['enterprise_id'] = $site->enterprise_id;
        $validated['separator'] = $validated['separator'] ?? '-';

        try {
            $template = $this->templateService->createOrUpdate($validated);

            Log::info("Template de nomenclature créé", [
                'template_id' => $template->id,
                'site_id' => $template->site_id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Template créé avec succès.',
                'data' => $template->fresh(['documentTypeCatalog', 'processCatalog']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation échouée.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la création du template", [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la création du template.',
            ], 500);
        }
    }

    /**
     * Mettre à jour un template
     */
    public function update(Request $request, int $id)
    {
        $user = $request->user();
        $template = NomenclatureTemplate::findOrFail($id);
        $this->normalizeTemplatePayload($request, true);

        // Vérifier l'accès
        if ($template->site_id !== $user->site_id && $user->user_type !== 'super_admin') {
            abort(403, 'Accès non autorisé.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:180',
            'description' => 'nullable|string',
            'separator' => 'sometimes|string|max:5',
            'format_structure' => 'sometimes|array',
            'format_structure.*.order' => 'required_with:format_structure|integer|min:1',
            'format_structure.*.type' => 'required_with:format_structure|string',
            'format_structure.*.label' => 'required_with:format_structure|string|max:100',
            'format_structure.*.length' => 'required_with:format_structure|integer|min:1|max:20',
            'format_structure.*.value' => 'nullable|string|max:50',
            'format_structure.*.editable' => 'required_with:format_structure|boolean',
            'format_structure.*.auto' => 'nullable|boolean',
            'builder_config' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:draft,published,archived',
        ]);

        try {
            $updatedTemplate = $this->templateService->createOrUpdate($validated, $id);

            Log::info("Template de nomenclature mis à jour", [
                'template_id' => $updatedTemplate->id,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Template mis à jour avec succès.',
                'data' => $updatedTemplate->fresh(['documentTypeCatalog', 'processCatalog']),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation échouée.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("Erreur lors de la mise à jour du template", [
                'template_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour du template.',
            ], 500);
        }
    }

    /**
     * Supprimer un template
     */
    public function destroy(Request $request, int $id)
    {
        $user = $request->user();
        $template = NomenclatureTemplate::findOrFail($id);

        // Vérifier l'accès
        if ($template->site_id !== $user->site_id && $user->user_type !== 'super_admin') {
            abort(403, 'Accès non autorisé.');
        }

        // Vérifier qu'aucun document n'utilise ce template
        $documentsCount = \App\Models\Document::where('nomenclature_template_id', $id)->count();
        if ($documentsCount > 0) {
            $hasApprovedDocs = \App\Models\Document::where('nomenclature_template_id', $id)
                ->where(function ($q) {
                    $q->where('workflow_status', 'approved')
                      ->orWhere('status', 'approved');
                })
                ->exists();

            if ($hasApprovedDocs) {
                $template->update([
                    'status' => 'archived',
                    'is_active' => false,
                ]);

                Log::info("Template de nomenclature archivé car utilisé par des documents validés", [
                    'template_id' => $id,
                    'user_id' => $user->id,
                ]);

                return response()->json([
                    'message' => 'Le template est utilisé par des documents validés. Il a été archivé au lieu d\'être supprimé.',
                    'data' => $template->fresh(['documentTypeCatalog', 'processCatalog']),
                ]);
            }

            return response()->json([
                'message' => "Impossible de supprimer ce template. {$documentsCount} document(s) l'utilisent.",
            ], 409);
        }

        $template->delete();

        Log::info("Template de nomenclature supprimé", [
            'template_id' => $id,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Template supprimé avec succès.',
        ]);
    }

    /**
     * Prévisualiser un code généré
     */
    public function previewCode(Request $request)
    {
        $validated = $request->validate([
            'format_structure' => 'required|array',
            'separator' => 'nullable|string|max:5',
            'context' => 'nullable|array',
        ]);

        try {
            $preview = $this->templateService->generatePreviewExample(
                $validated['format_structure'],
                $validated['separator'] ?? '-'
            );

            return response()->json([
                'data' => [
                    'preview' => $preview,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la génération de la prévisualisation.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    private function normalizeTemplatePayload(Request $request, bool $partial = false): void
    {
        $payload = $request->all();

        if (empty($payload['site_id']) && $request->user()?->site_id) {
            $payload['site_id'] = $request->user()->site_id;
        }

        if (empty($payload['name'])) {
            $payload['name'] = $payload['nom']
                ?? $payload['format']
                ?? ($partial ? null : 'Template documentaire');
        }

        if (empty($payload['format_structure'])) {
            $payload['format_structure'] = $payload['parts']
                ?? data_get($payload, 'namingPattern.parts')
                ?? null;
        }

        if (array_key_exists('actif', $payload) && !array_key_exists('is_active', $payload)) {
            $payload['is_active'] = (bool) $payload['actif'];
        }

        if (empty($payload['status'])) {
            $payload['status'] = $payload['is_active'] ?? true ? 'published' : 'draft';
        }

        if (empty($payload['separator'])) {
            $payload['separator'] = $this->detectSeparator($payload['format_structure'] ?? [], (string) ($payload['format'] ?? ''));
        }

        if (!empty($payload['format_structure']) && is_array($payload['format_structure'])) {
            $payload['format_structure'] = $this->normalizeFormatStructure($payload['format_structure']);
        }

        $request->replace(array_filter(
            $payload,
            fn ($value) => !($partial && $value === null)
        ));
    }

    private function normalizeFormatStructure(array $structure): array
    {
        $normalized = [];
        $order = 1;

        foreach ($structure as $part) {
            if (!is_array($part)) {
                continue;
            }

            $type = $part['type'] ?? 'token';
            $token = strtoupper((string) ($part['token'] ?? $part['value'] ?? ''));

            if ($type === 'separator') {
                $normalized[] = [
                    'order' => $order++,
                    'type' => 'separator',
                    'label' => $part['label'] ?? 'Séparateur',
                    'length' => 1,
                    'value' => (string) ($part['value'] ?? '-'),
                    'editable' => true,
                    'auto' => false,
                ];
                continue;
            }

            $mappedType = match ($token) {
                'TYPE', 'DOC_TYPE', 'DOC_TYPE_ABBR' => 'document_type',
                'PROCESSUS', 'PROCESS', 'PROCESS_ABBR' => 'process_code',
                'YEAR' => 'year',
                'MONTH' => 'month',
                'NUMERO', 'SEQUENCE', 'SEQ' => 'sequential_number',
                default => $type,
            };

            $normalized[] = [
                'order' => $part['order'] ?? $order++,
                'type' => $mappedType,
                'token' => in_array($token, ['NUMERO', 'SEQUENCE', 'SEQ'], true) ? 'SEQUENCE' : ($token ?: null),
                'label' => $part['label'] ?? $this->labelForPart($mappedType),
                'length' => (int) ($part['length'] ?? ($mappedType === 'sequential_number' ? 3 : 3)),
                'value' => $mappedType === 'custom' ? ($part['value'] ?? null) : null,
                'editable' => (bool) ($part['editable'] ?? !in_array($mappedType, ['sequential_number'], true)),
                'auto' => (bool) ($part['auto'] ?? in_array($mappedType, ['sequential_number', 'year', 'month'], true)),
            ];
        }

        return $normalized;
    }

    private function detectSeparator(array $structure, string $format): string
    {
        foreach ($structure as $part) {
            if (is_array($part) && ($part['type'] ?? null) === 'separator' && !empty($part['value'])) {
                return (string) $part['value'];
            }
        }

        if (str_contains($format, '/')) {
            return '/';
        }

        if (str_contains($format, '_')) {
            return '_';
        }

        return '-';
    }

    private function labelForPart(string $type): string
    {
        return match ($type) {
            'document_type' => 'Type de document',
            'process_code' => 'Code processus',
            'year' => 'Année',
            'month' => 'Mois',
            'sequential_number' => 'Numéro séquentiel',
            default => 'Partie du code',
        };
    }
}
