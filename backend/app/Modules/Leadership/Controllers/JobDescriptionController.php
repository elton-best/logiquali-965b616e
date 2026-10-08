<?php

namespace App\Modules\Leadership\Controllers;

use App\Models\Document;
use App\Models\Site;
use App\Services\DocumentSyncService;
use App\Services\DocumentTypeResolver;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Http\Resources\JobDescriptionResource;
use App\Models\JobDescription;
use App\Models\JobDescriptionHistory;
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\DocumentBrandingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class JobDescriptionController extends Controller
{
    public function __construct()
    {
        $read = 'permission:leadership.roles_responsabilites.fiche_poste.read'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.read'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_poste.read'
            . '|leadership.roles_responsabilites.fiche_poste.manage';

        $create = 'permission:leadership.roles_responsabilites.fiche_poste.create'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.create'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_poste.create'
            . '|leadership.roles_responsabilites.fiche_poste.manage';

        $update = 'permission:leadership.roles_responsabilites.fiche_poste.update'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.update'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_poste.update'
            . '|leadership.roles_responsabilites.fiche_poste.manage';

        $delete = 'permission:leadership.roles_responsabilites.fiche_poste.delete'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.delete'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_poste.delete'
            . '|leadership.roles_responsabilites.fiche_poste.manage';

        $this->middleware($read)->only(['index', 'show', 'downloadPdf']);
        $this->middleware($create)->only(['store']);
        $this->middleware($update)->only(['update']);
        $this->middleware($delete)->only(['destroy']);
    }

    private function canAccessJobDescription(JobDescription $jobDescription): bool
    {
        $currentUser = $this->currentUser();

        if (!$currentUser) {
            return false;
        }

        if ($currentUser->isSuperAdmin()) {
            return true;
        }

        return (int) $jobDescription->enterprise_id === (int) $currentUser->enterprise_id;
    }

    public function index()
    {
        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $jobDescriptions = JobDescription::with(['site', 'user', 'enterprise', 'reportsTo'])
            ->where('enterprise_id', $currentUser->enterprise_id)
            ->paginate(20);
        return JobDescriptionResource::collection($jobDescriptions);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'job_title' => 'required|string|max:255',
            'replacement_job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'reports_to_id' => 'nullable|exists:users,id',
            'mission' => 'required|string',
            'activities' => 'nullable|string',
            'main_activities' => 'nullable|array',
            'secondary_activities' => 'nullable|array',
            'internal_relations' => 'nullable|array',
            'external_relations' => 'nullable|array',
            'work_location' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'travel_required' => 'nullable|boolean',
            'physical_requirements' => 'nullable|string',
            'required_skills' => 'nullable|array',
            'required_experience' => 'nullable|string',
            'required_education' => 'nullable|string',
            'certifications_required' => 'nullable|array',
        ]);

        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $validated['enterprise_id'] = $currentUser->enterprise_id;
        $validated['site_id'] = $currentUser->site_id;
        $validated['activities'] = $validated['activities'] ?? '';

        if (!$this->supportsReplacementJobTitle()) {
            unset($validated['replacement_job_title']);
        }

        if ($invalidReportsTo = $this->validateReportsToScope($validated, $currentUser)) {
            return $invalidReportsTo;
        }

        if (!empty($validated['user_id'])) {
            $targetUser = User::query()
                ->where('id', $validated['user_id'])
                ->where('enterprise_id', $currentUser->enterprise_id)
                ->first();

            if (!$targetUser) {
                return response()->json([
                    'message' => 'Le collaborateur sélectionné doit appartenir à la même entreprise.',
                ], 422);
            }

            if (!empty($targetUser->site_id)) {
                $validated['site_id'] = $targetUser->site_id;
            }

            $this->syncCollaboratorDerivedFields($validated, $targetUser);
        }

        $jobDescription = DB::transaction(function () use ($validated) {
            $jobDescription = JobDescription::create($validated);

            $this->recordFieldHistory($jobDescription, [
                'job_title' => [null, (string) ($jobDescription->job_title ?? '')],
                'replacement_job_title' => [null, (string) ($jobDescription->replacement_job_title ?? '')],
                'employee_signature_data' => [null, (string) ($jobDescription->employee_signature_data ?? '')],
            ], 'create');

            return $jobDescription;
        });

        return new JobDescriptionResource($jobDescription->load(['site', 'user', 'reportsTo']));
    }

    public function show(JobDescription $jobDescription)
    {
        if (!$this->canAccessJobDescription($jobDescription)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new JobDescriptionResource($jobDescription->load(['site', 'user']));
    }

    public function update(Request $request, JobDescription $jobDescription)
    {
        if (!$this->canAccessJobDescription($jobDescription)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'job_title' => 'sometimes|string|max:255',
            'replacement_job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'reports_to_id' => 'nullable|exists:users,id',
            'mission' => 'sometimes|string',
            'activities' => 'nullable|string',
            'main_activities' => 'nullable|array',
            'secondary_activities' => 'nullable|array',
            'internal_relations' => 'nullable|array',
            'external_relations' => 'nullable|array',
            'work_location' => 'nullable|string',
            'work_schedule' => 'nullable|string',
            'travel_required' => 'nullable|boolean',
            'physical_requirements' => 'nullable|string',
            'required_skills' => 'nullable|array',
            'required_experience' => 'nullable|string',
            'required_education' => 'nullable|string',
            'certifications_required' => 'nullable|array',
        ]);

        $currentUser = $this->currentUser();
        if (!$currentUser) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if (!$this->supportsReplacementJobTitle()) {
            unset($validated['replacement_job_title']);
        }

        if ($invalidReportsTo = $this->validateReportsToScope($validated, $currentUser)) {
            return $invalidReportsTo;
        }

        if (array_key_exists('user_id', $validated)) {
            if (!empty($validated['user_id'])) {
                $targetUser = User::query()
                    ->where('id', $validated['user_id'])
                    ->where('enterprise_id', $currentUser->enterprise_id)
                    ->first();

                if (!$targetUser) {
                    return response()->json([
                        'message' => 'Le collaborateur sélectionné doit appartenir à la même entreprise.',
                    ], 422);
                }

                if (!empty($targetUser->site_id)) {
                    $validated['site_id'] = $targetUser->site_id;
                }

                $this->syncCollaboratorDerivedFields($validated, $targetUser);
            } else {
                // Aucun collaborateur sélectionné: vider le snapshot signature.
                $validated['employee_signature_data'] = null;
                $validated['employee_signed_at'] = null;
            }
        }

        DB::transaction(function () use ($jobDescription, $validated) {
            $lockedJobDescription = JobDescription::query()
                ->whereKey($jobDescription->id)
                ->lockForUpdate()
                ->firstOrFail();

            $original = [
                'job_title' => (string) ($lockedJobDescription->job_title ?? ''),
                'replacement_job_title' => (string) ($lockedJobDescription->replacement_job_title ?? ''),
                'employee_signature_data' => (string) ($lockedJobDescription->employee_signature_data ?? ''),
            ];

            $lockedJobDescription->update($validated);

            $changes = [
                'job_title' => [$original['job_title'], (string) ($lockedJobDescription->job_title ?? '')],
                'replacement_job_title' => [$original['replacement_job_title'], (string) ($lockedJobDescription->replacement_job_title ?? '')],
                'employee_signature_data' => [$original['employee_signature_data'], (string) ($lockedJobDescription->employee_signature_data ?? '')],
            ];
            $this->recordFieldHistory($lockedJobDescription, $changes, 'update');

            $jobDescription->refresh();
        });

        return new JobDescriptionResource($jobDescription->load(['site', 'user', 'reportsTo']));
    }

    private function syncCollaboratorDerivedFields(array &$validated, User $targetUser): void
    {
        if (!empty($targetUser->signature_path)) {
            $validated['employee_signature_data'] = $targetUser->signature_url ?? $targetUser->signature_path;
            $validated['employee_signed_at'] = $targetUser->signature_uploaded_at ?? now();
            return;
        }

        $validated['employee_signature_data'] = null;
        $validated['employee_signed_at'] = null;
    }

    private function validateReportsToScope(array $validated, User $currentUser): ?JsonResponse
    {
        if (empty($validated['reports_to_id'])) {
            return null;
        }

        $reportsTo = User::query()
            ->where('id', $validated['reports_to_id'])
            ->where('enterprise_id', $currentUser->enterprise_id)
            ->first();

        if ($reportsTo) {
            return null;
        }

        return response()->json([
            'message' => 'Le responsable sélectionné doit appartenir à la même entreprise.',
        ], 422);
    }

    private function recordFieldHistory(JobDescription $jobDescription, array $changes, string $source): void
    {
        if (!$this->supportsHistoryTable()) {
            return;
        }

        $currentUser = $this->currentUser();
        foreach ($changes as $fieldKey => [$oldValue, $newValue]) {
            $old = trim((string) ($oldValue ?? ''));
            $new = trim((string) ($newValue ?? ''));

            if ($old === $new) {
                continue;
            }

            JobDescriptionHistory::create([
                'job_description_id' => $jobDescription->id,
                'enterprise_id' => $jobDescription->enterprise_id,
                'site_id' => $jobDescription->site_id,
                'field_key' => $fieldKey,
                'old_value' => $old !== '' ? $old : null,
                'new_value' => $new !== '' ? $new : null,
                'changed_by' => $currentUser?->id,
                'change_source' => $source,
                'changed_at' => now(),
            ]);
        }
    }

    public function destroy(JobDescription $jobDescription)
    {
        if (!$this->canAccessJobDescription($jobDescription)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $jobDescription->delete();
        return response()->json(null, 204);
    }

    public function downloadPdf(JobDescription $jobDescription)
    {
        if (!$this->canAccessJobDescription($jobDescription)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Charger les relations
        $jobDescription->load(['user', 'reportsTo', 'site.enterprise']);
        $enterprise = $jobDescription->enterprise ?? $jobDescription->site?->enterprise ?? $this->currentUser()?->enterprise;
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'job_description', $jobDescription->id)
            : null;

        $filename = 'fiche_poste_' . $jobDescription->id . '.pdf';
        $currentUser = $this->currentUser();

        try {
            $pdf = Pdf::loadView('pdf.job-description', [
                'jobDescription' => $jobDescription,
                'branding' => $branding,
            ]);
            $relativePath = 'exports/' . $filename;
            $tempPath = Storage::disk('local')->path($relativePath);
            if (!is_dir(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0775, true);
            }
            $pdf->save($tempPath);

            $siteId = (int) ($jobDescription->site_id ?? 0);
            if ($siteId <= 0 && $jobDescription->enterprise_id) {
                $siteId = (int) \App\Models\Site::query()
                    ->where('enterprise_id', (int) $jobDescription->enterprise_id)
                    ->orderBy('id')
                    ->value('id');
            }
            $document = null;
            if ($siteId > 0) {
                $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                    'site_id' => $siteId,
                    'process_id' => null,
                    'process_code' => 'GEN',
                    'document_kind' => 'job_description_pdf',
                    'source_type' => 'job_description',
                    'source_id' => $jobDescription->id,
                    'source_updated_at' => $jobDescription->updated_at?->toISOString(),
                    'title' => 'Fiche de poste ' . ($jobDescription->job_title ?? '#'.$jobDescription->id),
                    'description' => 'Fiche de poste exportée en PDF.',
                    'file_source_path' => $tempPath,
                    'file_extension' => 'pdf',
                    'created_by' => $currentUser?->id,
                    'type' => 'ENR',
                    'force_new' => true,
                ]);
            }

            $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            if ($document) {
                $response->headers->set('X-Generated-Document-Id', (string) $document->id);
            }
            return $response;
        } catch (\Throwable $exception) {
            Log::warning('Job description PDF branding render failed, retrying without branding', [
                'job_description_id' => $jobDescription->id,
                'error' => $exception->getMessage(),
            ]);
            try {
                $pdf = Pdf::loadView('pdf.job-description', [
                    'jobDescription' => $jobDescription,
                    'branding' => null,
                ]);
                $relativePath = 'exports/' . $filename;
                $tempPath = Storage::disk('local')->path($relativePath);
                if (!is_dir(dirname($tempPath))) {
                    mkdir(dirname($tempPath), 0775, true);
                }
                $pdf->save($tempPath);

                $siteId = (int) ($jobDescription->site_id ?? 0);
                if ($siteId <= 0 && $jobDescription->enterprise_id) {
                    $siteId = (int) \App\Models\Site::query()
                        ->where('enterprise_id', (int) $jobDescription->enterprise_id)
                        ->orderBy('id')
                        ->value('id');
                }
                $document = null;
                if ($siteId > 0) {
                    $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                        'site_id' => $siteId,
                        'process_id' => null,
                        'process_code' => 'GEN',
                        'document_kind' => 'job_description_pdf',
                        'title' => 'Fiche de poste ' . ($jobDescription->job_title ?? '#'.$jobDescription->id),
                        'description' => 'Fiche de poste exportée en PDF.',
                        'file_source_path' => $tempPath,
                        'file_extension' => 'pdf',
                        'created_by' => $currentUser?->id,
                        'type' => 'ENR',
                        'force_new' => true,
                    ]);
                }

                $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
                if ($document) {
                    $response->headers->set('X-Generated-Document-Id', (string) $document->id);
                }
                return $response;
            } catch (\Throwable $fallbackException) {
                Log::error('Job description PDF render failed without branding', [
                    'job_description_id' => $jobDescription->id,
                    'error' => $fallbackException->getMessage(),
                ]);

                return response()->json([
                    'message' => 'Impossible de générer le PDF pour cette fiche de poste.',
                ], 422);
            }
        }
    }

    public function generateDraftDocx(Request $request, JobDescription $jobDescription)
    {
        if (!$this->canAccessJobDescription($jobDescription)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => 'nullable|integer|exists:processes,id',
        ]);

        $jobDescription->load(['user', 'reportsTo', 'site.enterprise']);
        $enterprise = $jobDescription->enterprise ?? $jobDescription->site?->enterprise;
        $siteId = (int) ($jobDescription->site_id ?? $request->user()?->site_id ?? 0);

        $branding = $enterprise
            ? app(\App\Services\DocumentBrandingService::class)->getPdfBranding($enterprise, 'job_description', $jobDescription->id)
            : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.job-description', [
            'jobDescription' => $jobDescription,
            'branding' => $branding,
            'isDraft' => true,
        ]);

        $tempPath = Storage::disk('local')->path('exports/job_desc_draft_' . $jobDescription->id . '_' . time() . '.pdf');
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }
        $pdf->save($tempPath);

        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $request->integer('process_id') ?: null,
            'process_code' => 'GEN',
            'document_kind' => 'job_description',
            'source_type' => 'job_description',
            'source_id' => $jobDescription->id,
            'source_updated_at' => $jobDescription->updated_at?->toISOString(),
            'title' => 'Fiche de poste — ' . ($jobDescription->job_title ?? '#' . $jobDescription->id),
            'description' => 'Fiche de poste générée automatiquement.',
            'file_source_path' => $tempPath,
            'created_by' => $request->user()?->id,
            'type' => \App\Services\DocumentTypeResolver::resolveType('job_description'),
            'force_new' => true,
            'store_file' => true,
            'metadata' => ['document_type_catalog_id' => $request->integer('document_type_catalog_id')],
        ]);

        @unlink($tempPath);

        return response()->json(['data' => $document]);
    }

    private function currentUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    private function supportsReplacementJobTitle(): bool
    {
        static $supportsReplacement = null;

        if ($supportsReplacement !== null) {
            return $supportsReplacement;
        }

        $supportsReplacement = Schema::hasColumn('job_descriptions', 'replacement_job_title');
        return $supportsReplacement;
    }

    private function supportsHistoryTable(): bool
    {
        static $supportsHistory = null;

        if ($supportsHistory !== null) {
            return $supportsHistory;
        }

        $supportsHistory = Schema::hasTable('job_description_histories');
        return $supportsHistory;
    }
}
