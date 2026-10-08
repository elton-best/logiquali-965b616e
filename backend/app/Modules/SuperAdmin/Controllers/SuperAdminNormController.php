<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Norm;
use App\Models\NormVersion;
use App\Models\NormSection;
use App\Services\NormImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;

class SuperAdminNormController extends Controller
{
    protected $importService;

    public function __construct(NormImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Get all norms
     */
    public function index(Request $request)
    {
        try {
            $query = Norm::with(['currentVersion', 'versions']);

            // Filters
            if ($request->has('domain') && $request->domain !== 'all') {
                $query->where('domain', $request->domain);
            }

            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->has('search') && $request->search) {
                $query->where(function ($q) use ($request) {
                    $q->where('code', 'like', '%' . $request->search . '%')
                        ->orWhere('name', 'like', '%' . $request->search . '%');
                });
            }

            $norms = $query->orderBy('created_at', 'desc')->get();

            $normIds = $norms->pluck('id')->filter()->values();
            $enterpriseCounts = collect();

            if ($normIds->isNotEmpty()) {
                $enterpriseCounts = DB::table('norm_offer as no')
                    ->join('enterprise_subscriptions as es', 'es.offer_id', '=', 'no.offer_id')
                    ->join('sites as s', 's.id', '=', 'es.site_id')
                    ->whereIn('no.norm_id', $normIds)
                    ->whereNull('es.deleted_at')
                    ->where('es.is_active', true)
                    ->where('es.start_date', '<=', now())
                    ->where(function ($statusQuery) {
                        $statusQuery
                            ->where(function ($paidQuery) {
                                $paidQuery
                                    ->where(function ($isTrialQuery) {
                                        $isTrialQuery
                                            ->whereNull('es.is_trial')
                                            ->orWhere('es.is_trial', false);
                                    })
                                    ->whereNotNull('es.expiration_date')
                                    ->where('es.expiration_date', '>', now());
                            })
                            ->orWhere(function ($trialQuery) {
                                $trialQuery
                                    ->where('es.is_trial', true)
                                    ->whereNotNull('es.trial_ends_at')
                                    ->where('es.trial_ends_at', '>', now());
                            });
                    })
                    ->selectRaw('no.norm_id, COUNT(DISTINCT s.enterprise_id) as enterprises_count')
                    ->groupBy('no.norm_id')
                    ->pluck('enterprises_count', 'no.norm_id');
            }

            $norms->each(function (Norm $norm) use ($enterpriseCounts) {
                $norm->setAttribute('enterprises_count', (int) ($enterpriseCounts[$norm->id] ?? 0));
            });

            return response()->json(['success' => true, 'data' => $norms]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement des normes.',
                'SUPERADMIN_NORMS_FETCH_ERROR',
                500,
                ['scope' => 'norms_list'],
                $e
            );
        }
    }

    /**
     * Get single norm with all versions and sections
     */
    public function show($id)
    {
        try {
            $norm = Norm::findOrFail($id);
            $this->ensureCurrentVersion($norm);

            $norm->load([
                'currentVersion.sections' => function ($query) {
                    $query->whereNull('parent_id')
                        ->orderBy('order_index')
                        ->with('children.children.children.children.children.children');
                },
                'versions.sections' => function ($query) {
                    $query->whereNull('parent_id')
                        ->orderBy('order_index')
                        ->with('children.children.children.children.children.children');
                }
            ]);

            return response()->json(['success' => true, 'data' => $norm]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Norme non trouvée.', 'SUPERADMIN_NORM_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement de la norme.',
                'SUPERADMIN_NORM_FETCH_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Get version with full tree
     */
    public function getVersion($normId, $versionId)
    {
        try {
            $version = NormVersion::with([
                'sections' => function ($query) {
                    $query->whereNull('parent_id')->orderBy('order_index')->with('children');
                }
            ])
                ->where('norm_id', $normId)
                ->findOrFail($versionId);

            return response()->json(['success' => true, 'data' => $version]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Version non trouvée.', 'SUPERADMIN_NORM_VERSION_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du chargement de la version.',
                'SUPERADMIN_NORM_VERSION_FETCH_ERROR',
                500,
                ['norm_id' => $normId, 'version_id' => $versionId],
                $e
            );
        }
    }

    /**
     * Create norm manually (without Excel)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:norms,code|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'required|in:quality,environment,security,food_safety,integrated,other',
            'version_code' => 'required|string|max:20',
            'publish' => 'sometimes|boolean',
            'structure' => 'nullable|array', // Hierarchical structure
            'structure.*.id' => 'nullable|string',
            'structure.*.type' => 'required|string',
            'structure.*.code' => 'required|string',
            'structure.*.title' => 'required|string',
            'structure.*.content' => 'nullable|string',
            'structure.*.isObligatory' => 'nullable|boolean',
            'structure.*.children' => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            // Create norm
            $shouldPublish = (bool) ($validated['publish'] ?? true);

            $norm = Norm::create([
                'code' => $validated['code'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'domain' => $validated['domain'],
                'status' => $shouldPublish ? 'published' : 'draft',
            ]);

            // Create first version
            $version = NormVersion::create([
                'norm_id' => $norm->id,
                'version_code' => $validated['version_code'],
                'full_code' => $this->buildNormalizedFullCode($validated['code'], $validated['version_code']),
                'published_at' => now(),
                'is_current' => true,
            ]);

            // Set as current
            $norm->update(['current_version_id' => $version->id]);

            // Create structure if provided
            if (!empty($validated['structure'])) {
                $this->createSectionsRecursively($version->id, $validated['structure'], null, 0);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Norme créée avec succès',
                'data' => $norm->load('currentVersion.sections')
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Une erreur est survenue lors de la création de la norme.',
                'SUPERADMIN_NORM_CREATE_ERROR',
                500,
                ['scope' => 'norm_create'],
                $e
            );
        }
    }

    /**
     * Create sections recursively from structure
     */
    private function createSectionsRecursively($versionId, $nodes, $parentId = null, $orderIndex = 0)
    {
        foreach ($nodes as $index => $node) {
            // Get parent for path calculation
            $parent = $parentId ? NormSection::find($parentId) : null;

            // Build path
            $path = NormSection::buildPath($parent, $node['code']);

            // Calculate level
            $level = $parent ? $parent->level + 1 : 1;

            // Create section
            $section = NormSection::create([
                'norm_version_id' => $versionId,
                'parent_id' => $parentId,
                'path' => $path,
                'level' => $level,
                'type' => $node['type'],
                'number' => $node['code'],
                'title' => $node['title'],
                'content' => $node['content'] ?? null,
                'order_index' => $orderIndex + $index,
                'metadata' => [
                    'isObligatory' => $node['isObligatory'] ?? false,
                ],
            ]);

            // Recursively create children
            if (!empty($node['children'])) {
                $this->createSectionsRecursively($versionId, $node['children'], $section->id, 0);
            }
        }
    }

    /**
     * Ensure a norm has a current version (legacy safety).
     */
    private function ensureCurrentVersion(Norm $norm, ?string $requestedVersionCode = null): NormVersion
    {
        if ($norm->currentVersion) {
            return $norm->currentVersion;
        }

        // Reuse existing version if available
        $existingVersion = $norm->versions()->orderByDesc('is_current')->orderByDesc('id')->first();
        if ($existingVersion) {
            if (!$existingVersion->is_current) {
                $existingVersion->update(['is_current' => true]);
            }
            $norm->update(['current_version_id' => $existingVersion->id]);
            return $existingVersion;
        }

        // Create an initial version for legacy norms without any version
        $versionCode = $requestedVersionCode ?: $this->extractVersionCodeFromNormCode($norm->code);

        $createdVersion = NormVersion::create([
            'norm_id' => $norm->id,
            'version_code' => $versionCode,
            'full_code' => $this->buildNormalizedFullCode($norm->code, $versionCode),
            'published_at' => now(),
            'is_current' => true,
        ]);

        $norm->update(['current_version_id' => $createdVersion->id]);

        return $createdVersion;
    }

    /**
     * Try to infer version code from labels like "ISO 9001:2015".
     */
    private function extractVersionCodeFromNormCode(string $normCode): string
    {
        if (preg_match('/:(\d{4})$/', trim($normCode), $matches)) {
            return $matches[1];
        }

        return now()->format('Y');
    }

    /**
     * Import norm from Excel
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'required|in:quality,environment,security,food_safety,integrated,other',
            'version_code' => 'required|string|max:20',
            'action' => 'sometimes|in:create,replace,merge',
        ]);

        try {
            // Check if norm already exists
            $existingNorm = Norm::where('code', $request->code)->first();

            if ($existingNorm && $existingNorm->pdf_file_path) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette norme dispose deja d’un PDF importe. Supprimez ou remplacez le PDF avant tout import Excel.',
                    'error_code' => 'SUPERADMIN_NORM_IMPORT_BLOCKED_BY_PDF',
                    'correlation_id' => (string) (request()?->header('X-Request-Id') ?: Str::uuid()),
                    'norm_id' => $existingNorm->id,
                ], 422);
            }

            if ($existingNorm && !$request->has('action')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Norme existante',
                    'error_code' => 'SUPERADMIN_NORM_IMPORT_ACTION_REQUIRED',
                    'correlation_id' => (string) (request()?->header('X-Request-Id') ?: Str::uuid()),
                    'requires_action' => true,
                    'norm' => $existingNorm
                ], 409);
            }

            // Store file
            $file = $request->file('file');
            $path = $file->store('norms/imports', 'public');
            $fullPath = storage_path('app/public/' . $path);

            // Import
            $norm = $this->importService->importFromExcel(
                $fullPath,
                [
                    'code' => $request->code,
                    'name' => $request->name,
                    'description' => $request->description,
                    'domain' => $request->domain,
                    'version_code' => $request->version_code,
                ],
                $existingNorm,
                $request->get('action', 'create')
            );

            // Validate structure
            $errors = $this->importService->validateStructure($norm->currentVersion);

            $user = Auth::user();
            if ($user) {
                $recordCount = $norm->currentVersion?->sections()->count() ?? 0;
                event(new \App\Events\System\ImportCompleted(
                    $user,
                    $file->getClientOriginalName(),
                    asset('storage/' . ltrim($path, '/')),
                    $recordCount,
                    count($errors) === 0,
                    $errors
                ));
            }

            return response()->json([
                'success' => true,
                'message' => 'Norme importée avec succès',
                'data' => $norm->load(['currentVersion', 'versions']),
                'validation_errors' => $errors
            ], 201);

        } catch (InvalidArgumentException $e) {
            $correlationId = (string) (request()?->header('X-Request-Id') ?: Str::uuid());
            Log::warning('SUPERADMIN_NORM_IMPORT_VALIDATION', [
                'correlation_id' => $correlationId,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'SUPERADMIN_NORM_IMPORT_INVALID_FORMAT',
                'correlation_id' => $correlationId,
            ], 422);
        } catch (\Exception $e) {
            $correlationId = (string) (request()?->header('X-Request-Id') ?: Str::uuid());
            $user = Auth::user();
            if ($user) {
                event(new \App\Events\System\ImportCompleted(
                    $user,
                    $request->file('file')?->getClientOriginalName() ?? 'import_norm',
                    '',
                    0,
                    false,
                    ['Import de norme échoué.']
                ));
            }

            Log::error('SUPERADMIN_NORM_IMPORT_ERROR', [
                'correlation_id' => $correlationId,
                'exception' => [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
                'status' => 500,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Une erreur est survenue lors de l’import de la norme.',
                'error_code' => 'SUPERADMIN_NORM_IMPORT_ERROR',
                'correlation_id' => $correlationId,
            ], 500);
        }
    }

    public function uploadPdf(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        try {
            $norm = Norm::findOrFail($id);
            $file = $request->file('file');

            if ($norm->pdf_file_path) {
                Storage::disk('public')->delete($norm->pdf_file_path);
            }

            $path = $file->store('norms/pdfs', 'public');

            $norm->update([
                'pdf_file_path' => $path,
                'pdf_original_name' => $file->getClientOriginalName(),
                'pdf_uploaded_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF de la norme televerse avec succes.',
                'data' => $norm->fresh(['currentVersion', 'versions']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Norme non trouvée.', 'SUPERADMIN_NORM_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du televersement du PDF de la norme.',
                'SUPERADMIN_NORM_PDF_UPLOAD_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    public function deletePdf($id)
    {
        try {
            $norm = Norm::findOrFail($id);

            if ($norm->pdf_file_path) {
                Storage::disk('public')->delete($norm->pdf_file_path);
            }

            $norm->update([
                'pdf_file_path' => null,
                'pdf_original_name' => null,
                'pdf_uploaded_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'PDF de la norme supprime avec succes.',
                'data' => $norm->fresh(['currentVersion', 'versions']),
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Norme non trouvée.', 'SUPERADMIN_NORM_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression du PDF de la norme.',
                'SUPERADMIN_NORM_PDF_DELETE_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Update norm metadata
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'domain' => 'sometimes|in:quality,environment,security,food_safety,integrated,other',
            'status' => 'sometimes|in:draft,published,archived',
            'version_code' => 'sometimes|string|max:20',
            'structure' => 'nullable|array', // Hierarchical structure
            'structure.*.id' => 'nullable|string',
            'structure.*.type' => 'required|string',
            'structure.*.code' => 'required|string',
            'structure.*.title' => 'required|string',
            'structure.*.content' => 'nullable|string',
            'structure.*.isObligatory' => 'nullable|boolean',
            'structure.*.children' => 'nullable|array',
        ]);

        try {
            $norm = Norm::findOrFail($id);

            if ($request->has('code') && (string) $request->input('code') !== (string) $norm->code) {
                return $this->errorResponse(
                    'Le code ISO ne peut pas être modifié après création.',
                    'SUPERADMIN_NORM_CODE_IMMUTABLE',
                    422
                );
            }

            DB::beginTransaction();

            // Guarantee an editable current version for legacy data
            $currentVersion = $this->ensureCurrentVersion($norm, $validated['version_code'] ?? null);

            // Update basic info
            $updateData = array_intersect_key($validated, array_flip(['name', 'description', 'domain', 'status']));
            if (!empty($updateData)) {
                $norm->update($updateData);
            }

            // Update version code if provided
            if (isset($validated['version_code'])) {
                $currentVersion->update([
                    'version_code' => $validated['version_code'],
                    'full_code' => $this->buildNormalizedFullCode($norm->code, $validated['version_code']),
                ]);
            }

            // Update structure if provided
            if (isset($validated['structure'])) {
                // Delete existing sections
                $currentVersion->sections()->delete();

                // Create new structure
                $this->createSectionsRecursively($currentVersion->id, $validated['structure'], null, 0);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Norme mise à jour',
                'data' => $norm->load('currentVersion.sections')
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse(
                'Une erreur est survenue lors de la mise à jour de la norme.',
                'SUPERADMIN_NORM_UPDATE_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Create/Update section
     */
    public function storeSection(Request $request, $versionId)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:norm_sections,id',
            'type' => 'required|in:chapter,subchapter,paragraph,point,note,annex',
            'number' => 'required|string|max:50',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'references' => 'nullable|array',
        ]);

        try {
            $version = NormVersion::findOrFail($versionId);
            $parentId = $validated['parent_id'] ?? null;

            // Get parent if exists
            $parent = null;
            if ($parentId) {
                $parent = NormSection::findOrFail($parentId);

                // Security/consistency: parent section must belong to the same version
                if ((int) $parent->norm_version_id !== (int) $version->id) {
                    return $this->errorResponse(
                        'Le parent sélectionné appartient à une autre version.',
                        'SUPERADMIN_NORM_SECTION_PARENT_VERSION_MISMATCH',
                        422
                    );
                }
            }

            // Calculate order index
            $lastSection = $version->sections()
                ->where('parent_id', $parentId)
                ->orderBy('order_index', 'desc')
                ->first();

            $orderIndex = $lastSection ? $lastSection->order_index + 1 : 0;

            // Build path
            $path = NormSection::buildPath($parent, $validated['number']);

            // Calculate level
            $level = $parent ? $parent->level + 1 : 1;

            $section = NormSection::create([
                'norm_version_id' => $version->id,
                'parent_id' => $parentId,
                'path' => $path,
                'level' => $level,
                'type' => $validated['type'],
                'number' => $validated['number'],
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'] ?? null,
                'order_index' => $orderIndex,
                'references' => $validated['references'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Section créée',
                'data' => $section
            ], 201);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la création de la section.',
                'SUPERADMIN_NORM_SECTION_CREATE_ERROR',
                500,
                [
                    'version_id' => $versionId,
                    'payload' => $validated,
                ],
                $e
            );
        }
    }

    /**
     * Update section
     */
    public function updateSection(Request $request, $sectionId)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string',
            'references' => 'nullable|array',
        ]);

        try {
            $section = NormSection::findOrFail($sectionId);
            $section->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Section mise à jour',
                'data' => $section
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la mise à jour de la section.',
                'SUPERADMIN_NORM_SECTION_UPDATE_ERROR',
                500,
                ['section_id' => $sectionId],
                $e
            );
        }
    }

    /**
     * Delete section
     */
    public function deleteSection($sectionId)
    {
        try {
            $section = NormSection::findOrFail($sectionId);

            // Check if has children
            if ($section->hasChildren()) {
                return $this->errorResponse(
                    'Impossible de supprimer une section avec des sous-sections.',
                    'SUPERADMIN_NORM_SECTION_DELETE_BLOCKED_CHILDREN',
                    422
                );
            }

            $section->delete();

            return response()->json([
                'success' => true,
                'message' => 'Section supprimée'
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression de la section.',
                'SUPERADMIN_NORM_SECTION_DELETE_ERROR',
                500,
                ['section_id' => $sectionId],
                $e
            );
        }
    }

    /**
     * Build normalized full code without duplicating version suffix.
     */
    private function buildNormalizedFullCode(string $normCode, string $versionCode): string
    {
        $trimmedCode = trim($normCode);

        if (preg_match('/:\d{4}$/', $trimmedCode)) {
            return preg_replace('/:\d{4}$/', ':' . $versionCode, $trimmedCode);
        }

        return $trimmedCode . ':' . $versionCode;
    }

    /**
     * Publish norm
     */
    public function publish($id)
    {
        try {
            $norm = Norm::findOrFail($id);
            $norm->update(['status' => 'published']);

            return response()->json([
                'success' => true,
                'message' => 'Norme publiée',
                'data' => $norm
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la publication de la norme.',
                'SUPERADMIN_NORM_PUBLISH_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Archive norm
     */
    public function archive($id)
    {
        try {
            $norm = Norm::findOrFail($id);
            $norm->update(['status' => 'archived']);

            return response()->json([
                'success' => true,
                'message' => 'Norme archivée',
                'data' => $norm
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de l’archivage de la norme.',
                'SUPERADMIN_NORM_ARCHIVE_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Unarchive norm (archived -> published)
     */
    public function unarchive($id)
    {
        try {
            $norm = Norm::findOrFail($id);
            $norm->update(['status' => 'published']);

            return response()->json([
                'success' => true,
                'message' => 'Norme désarchivée',
                'data' => $norm
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du désarchivage de la norme.',
                'SUPERADMIN_NORM_UNARCHIVE_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Delete norm
     */
    public function destroy($id)
    {
        try {
            $norm = Norm::findOrFail($id);

            // Check if used by offers
            if (!$norm->canBeDeleted()) {
                $offersCount = $norm->offers()->count();
                return $this->errorResponse(
                    "Impossible de supprimer cette norme car elle est utilisée par {$offersCount} offre(s)",
                    'SUPERADMIN_NORM_DELETE_BLOCKED_OFFERS',
                    422
                );
            }

            // Delete related data first
            // Delete all versions and their sections
            foreach ($norm->versions as $version) {
                $version->sections()->delete();
                $version->delete();
            }

            // Delete the norm
            $norm->delete();

            return response()->json([
                'success' => true,
                'message' => 'Norme supprimée avec succès'
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Norme non trouvée.', 'SUPERADMIN_NORM_NOT_FOUND', 404);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de la suppression de la norme.',
                'SUPERADMIN_NORM_DELETE_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Download Excel template for norm import
     */
    public function downloadTemplate()
    {
        try {
            $filePath = public_path('templates/norme_iso_template.xlsx');

            if (!file_exists($filePath)) {
                // Create template if doesn't exist
                $this->createExcelTemplate($filePath);
            }

            return response()->download($filePath, 'norme_iso_template.xlsx', \App\Utils\ExportHeaders::attachmentHeaders('norme_iso_template.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'));
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors du téléchargement du template.',
                'SUPERADMIN_NORM_TEMPLATE_DOWNLOAD_ERROR',
                500,
                ['scope' => 'norm_template_download'],
                $e
            );
        }
    }

    /**
     * Create Excel template
     */
    private function createExcelTemplate($filePath)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Structure hierarchique');
        $platformName = (string) config('app.name', 'BestQHSE');

        // Headers (format structure hiérarchique)
        $headers = ['Niveau', 'Numéro', 'Type', 'Titre', 'Contenu', 'Références'];
        $sheet->fromArray($headers, null, 'A1');

        // Style headers
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
        ];
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);

        // Example data
        $examples = [
            [1, '1', 'chapter', 'Domaine d\'application', 'Le présent document spécifie...', ''],
            [2, '1.1', 'subchapter', 'Généralités', 'Ce document peut être utilisé par...', ''],
            [1, '2', 'chapter', 'Références normatives', 'Les documents suivants sont cités...', 'ISO 9000:2015'],
            [1, '3', 'chapter', 'Termes et définitions', 'Pour les besoins du présent document...', ''],
            [2, '3.1', 'paragraph', 'Définition 1', 'Première définition...', ''],
        ];
        $sheet->fromArray($examples, null, 'A2');

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Marquage plateforme pour les documents générés côté Super Admin
        $headerFooter = $sheet->getHeaderFooter();
        $headerFooter->setOddHeader('&L' . str_replace('&', '&&', $platformName) . '&RTemplate Import Norme ISO');
        $headerFooter->setOddFooter('&L' . str_replace('&', '&&', $platformName) . '&RPage &P/&N');

        // 2e onglet: format "Clause" (compatible ISO 9001 détaillée)
        $clauseSheet = $spreadsheet->createSheet();
        $clauseSheet->setTitle('Format clause');
        $clauseHeaders = [
            'Clause Numéro',
            'Titre de la Clause',
            'Contenu de la Clause (Texte de la Norme)',
        ];
        $clauseSheet->fromArray($clauseHeaders, null, 'A1');
        $clauseSheet->getStyle('A1:C1')->applyFromArray($headerStyle);

        $clauseExamples = [
            ['4', 'Contexte de l’organisme', 'L’organisme doit déterminer les enjeux internes et externes...'],
            ['4.1', 'Compréhension de l’organisme et de son contexte', 'L’organisme doit surveiller et revoir les informations...'],
            ['4.2', 'Compréhension des besoins et attentes des parties intéressées', 'L’organisme doit déterminer...'],
            ['5', 'Leadership', 'La direction doit démontrer son leadership et son engagement...'],
            ['6', 'Planification', 'Lors de la planification du système de management de la qualité...'],
        ];
        $clauseSheet->fromArray($clauseExamples, null, 'A2');

        foreach (range('A', 'C') as $col) {
            $clauseSheet->getColumnDimension($col)->setAutoSize(true);
        }
        $clauseHeaderFooter = $clauseSheet->getHeaderFooter();
        $clauseHeaderFooter->setOddHeader('&L' . str_replace('&', '&&', $platformName) . '&RTemplate Import Clause');
        $clauseHeaderFooter->setOddFooter('&L' . str_replace('&', '&&', $platformName) . '&RPage &P/&N');

        // Ensure directory exists
        $directory = dirname($filePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filePath);
    }

    /**
     * Export norm to CSV
     */
    public function exportCsv($id)
    {
        try {
            $norm = Norm::with(['currentVersion.sections'])->findOrFail($id);
            $platformName = (string) config('app.name', 'BestQHSE');

            if (!$norm->currentVersion) {
                return $this->errorResponse(
                    'Aucune version publiée.',
                    'SUPERADMIN_NORM_CURRENT_VERSION_NOT_FOUND',
                    404
                );
            }

            $csvData = [];
            $csvData[] = ['Plateforme', $platformName, '', '', ''];
            $csvData[] = ['', '', '', '', ''];
            $csvData[] = ['Type de ligne', 'Code', 'Titre', 'Contenu', 'Notes'];

            // Header row with norm info
            $csvData[] = [
                'Norme',
                $norm->code,
                $norm->name,
                $norm->description ?? '',
                'Version ' . $norm->currentVersion->version_code
            ];

            // Get all sections ordered by code
            $sections = $norm->currentVersion->sections()
                ->orderBy('order_index')
                ->get();

            foreach ($sections as $section) {
                $type = $this->getSectionType((string) $section->number);

                $csvData[] = [
                    $type,
                    $section->number,
                    $section->title,
                    $section->content ?? '',
                    ''
                ];
            }

            // Create CSV content
            $output = fopen('php://temp', 'r+');
            foreach ($csvData as $row) {
                fputcsv($output, $row);
            }
            rewind($output);
            $csvContent = stream_get_contents($output);
            fclose($output);

            $filename = str_replace([':', ' '], ['_', '_'], $norm->code) . '_export.csv';

            return response($csvContent, 200)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Une erreur est survenue lors de l’export de la norme.',
                'SUPERADMIN_NORM_EXPORT_ERROR',
                500,
                ['norm_id' => $id],
                $e
            );
        }
    }

    /**
     * Determine section type from code
     */
    private function getSectionType($code)
    {
        $parts = explode('.', $code);
        $count = count($parts);

        if ($count === 1) {
            return 'Chapitre';
        } elseif ($count === 2) {
            return 'Section';
        } elseif ($count === 3) {
            return 'Sous-section';
        } else {
            return 'Paragraphe';
        }
    }

    /**
     * Build full code without duplicating version when norm code already contains it.
     */
    private function buildFullCode(string $normCode, string $versionCode): string
    {
        if (str_ends_with($normCode, ':' . $versionCode)) {
            return $normCode;
        }

        return $normCode . ':' . $versionCode;
    }

    private function errorResponse(
        string $message,
        string $errorCode,
        int $status,
        array $context = [],
        ?\Throwable $exception = null
    ) {
        $request = request();
        $correlationId = (string) ($request?->header('X-Request-Id') ?: Str::uuid());

        if ($status >= 500 || $exception) {
            Log::error($errorCode, array_merge($context, [
                'correlation_id' => $correlationId,
                'status' => $status,
                'exception' => $exception ? [
                    'class' => $exception::class,
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                ] : null,
            ]));
        }

        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $errorCode,
            'correlation_id' => $correlationId,
        ], $status);
    }
}
