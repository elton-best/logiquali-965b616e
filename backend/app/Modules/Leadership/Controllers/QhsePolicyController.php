<?php

namespace App\Modules\Leadership\Controllers;

use App\Models\Document;
use App\Models\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Concerns\ResolvesGeneratedDocumentContext;
use App\Http\Resources\DocumentResource;
use App\Models\QhsePolicy;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Notifications\PolicyCreatedNotification;
use App\Services\DocumentBrandingService;
use App\Services\DocumentSyncService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class QhsePolicyController extends Controller
{
    use ResolvesGeneratedDocumentContext;

    public function __construct()
    {
        $read = 'permission:leadership.politique.read'
            . '|leadership.manage'
            . '|leadership.politique.read'
            . '|leadership.politique.manage';

        $create = 'permission:leadership.politique.create'
            . '|leadership.manage'
            . '|leadership.politique.create'
            . '|leadership.politique.manage';

        $update = 'permission:leadership.politique.update'
            . '|leadership.manage'
            . '|leadership.politique.update'
            . '|leadership.politique.manage';

        $delete = 'permission:leadership.politique.delete'
            . '|leadership.manage'
            . '|leadership.politique.delete'
            . '|leadership.politique.manage';

        $manage = 'permission:leadership.politique.validate'
            . '|leadership.politique.manage';

        $this->middleware($read)->only(['index', 'current', 'history', 'exportPdf', 'exportDocx']);
        $this->middleware($create)->only(['store']);
        $this->middleware($update)->only(['update']);
        $this->middleware($delete)->only(['destroy']);
        $this->middleware($manage)->only(['validate', 'submitForReview']);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $policies = QhsePolicy::where('enterprise_id', $user->enterprise_id)
            ->when($request->filled('site_id'), fn ($query) => $query->where('site_id', (int) $request->integer('site_id')))
            ->orderBy('is_current', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($policies->map(fn (QhsePolicy $policy) => $this->serializePolicy($policy)));
    }

    public function current(Request $request)
    {
        $user = $request->user();

        $policy = QhsePolicy::where('enterprise_id', $user->enterprise_id)
            ->when($request->filled('site_id'), fn ($query) => $query->where('site_id', (int) $request->integer('site_id')))
            ->where('is_current', true)
            ->first();

        return response()->json($this->serializePolicy($policy));
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'site_id' => [
                'nullable',
                Rule::exists('sites', 'id')->where(fn ($query) => $query->where('enterprise_id', $user->enterprise_id)),
            ],
            'mission' => 'required|string',
            'vision' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*' => 'string',
            'axes' => 'nullable|array',
            'axes.*' => 'string',
            'commitments' => 'nullable|array',
            'commitments.*' => 'string',
            'quality_policy' => 'nullable|string',
            'environmental_policy' => 'nullable|string',
            'health_safety_policy' => 'nullable|string',
            'effective_date' => 'nullable|date',
        ]);

        $siteId = (int) ($validated['site_id'] ?? $user->site_id);
        unset($validated['site_id']);
        $axes = $this->normalizeAxes($validated['axes'] ?? []);
        unset($validated['axes']);

        // Marquer les anciennes politiques comme non courantes
        QhsePolicy::where('enterprise_id', $user->enterprise_id)
            ->update(['is_current' => false]);

        $policy = QhsePolicy::create([
            ...$validated,
            'enterprise_id' => $user->enterprise_id,
            'site_id' => $siteId,
            'is_current' => true,
            'status' => 'draft',
            'version' => $this->getNextVersion($user->enterprise_id),
        ]);
        if ($this->hasPolicyAxesTable()) {
            $policy->syncAxes($axes);
            $policy->load('axisEntries');
        }

        if ($this->hasPolicyAxesTable()) {
            $policy->syncAxes($axes);
            $policy->load('axisEntries');
        }

        $user->notify(new PolicyCreatedNotification($policy));
        app(DocumentSyncService::class)->syncPolicy($policy, $user->id ?? null);

        return response()->json($this->serializePolicy($policy), 201);
    }

    public function update(Request $request, QhsePolicy $qhsePolicy)
    {
        $user = $request->user();
        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        $validated = $request->validate([
            'site_id' => [
                'nullable',
                Rule::exists('sites', 'id')->where(fn ($query) => $query->where('enterprise_id', $user->enterprise_id)),
            ],
            'mission' => 'sometimes|string',
            'vision' => 'nullable|string',
            'values' => 'nullable|array',
            'values.*' => 'string',
            'axes' => 'nullable|array',
            'axes.*' => 'string',
            'commitments' => 'nullable|array',
            'commitments.*' => 'string',
            'quality_policy' => 'nullable|string',
            'environmental_policy' => 'nullable|string',
            'health_safety_policy' => 'nullable|string',
            'effective_date' => 'nullable|date',
            'status' => 'sometimes|in:draft,en_revision',
        ]);
        $axes = null;
        if (array_key_exists('axes', $validated)) {
            $axes = $this->normalizeAxes($validated['axes'] ?? []);
            $validated['axes'] = $axes;
        }

        $versionedFields = [
            'mission',
            'vision',
            'values',
            'axes',
            'commitments',
            'quality_policy',
            'environmental_policy',
            'health_safety_policy',
            'effective_date',
        ];

        $mustBumpVersion = false;
        foreach ($versionedFields as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }

            if ($qhsePolicy->{$field} != $validated[$field]) {
                $mustBumpVersion = true;
                break;
            }
        }

        if ($mustBumpVersion) {
            $validated['version'] = $this->incrementMinorVersion($qhsePolicy->version);
        }

        if (array_key_exists('site_id', $validated)) {
            $validated['site_id'] = (int) $validated['site_id'];
        }

        $axes = null;
        if (array_key_exists('axes', $validated)) {
            $axes = $this->normalizeAxes($validated['axes'] ?? []);
            $validated['axes'] = $axes;
        }
        if (array_key_exists('site_id', $validated)) {
            $validated['site_id'] = (int) $validated['site_id'];
        }

        $versionedFields = [
            'mission',
            'vision',
            'values',
            'axes',
            'commitments',
            'quality_policy',
            'environmental_policy',
            'health_safety_policy',
            'effective_date',
        ];

        $mustBumpVersion = false;
        foreach ($versionedFields as $field) {
            if (!array_key_exists($field, $validated)) {
                continue;
            }

            if ($qhsePolicy->{$field} != $validated[$field]) {
                $mustBumpVersion = true;
                break;
            }
        }

        if ($mustBumpVersion) {
            $validated['version'] = $this->incrementMinorVersion($qhsePolicy->version);
        }

        $qhsePolicy->update($validated);
        if (is_array($axes)) {
            $qhsePolicy->syncAxes($axes);
        }
        if ($this->hasPolicyAxesTable()) {
            $qhsePolicy->load('axisEntries');
        }

        if (is_array($axes)) {
            $qhsePolicy->syncAxes($axes);
        }
        if ($this->hasPolicyAxesTable()) {
            $qhsePolicy->load('axisEntries');
        }

        $freshPolicy = $qhsePolicy->fresh();
        app(DocumentSyncService::class)->syncPolicy($freshPolicy, $user->id ?? null);

        return response()->json($this->serializePolicy($freshPolicy));
    }

    public function validate(Request $request, QhsePolicy $qhsePolicy)
    {
        $user = $request->user();
        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        if ($qhsePolicy->status !== 'en_revision') {
            return response()->json([
                'message' => 'La politique doit être en révision avant validation.',
            ], 422);
        }

        $qhsePolicy->update([
            'status' => 'validated',
            'validated_by_direction_at' => now(),
            'validated_by_user_id' => $user->id,
        ]);
        app(DocumentSyncService::class)->syncPolicy($qhsePolicy->fresh(), $user->id ?? null);
        if ($this->hasPolicyAxesTable()) {
            $qhsePolicy->load('axisEntries');
        }

        if ($this->hasPolicyAxesTable()) {
            $qhsePolicy->load('axisEntries');
        }

        return response()->json($this->serializePolicy($qhsePolicy));
    }

    public function submitForReview(Request $request, QhsePolicy $qhsePolicy)
    {
        $user = $request->user();
        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        if ($qhsePolicy->status === 'validated') {
            return response()->json([
                'message' => 'La politique est déjà validée.',
            ], 422);
        }

        if ($qhsePolicy->status !== 'en_revision') {
            $qhsePolicy->update([
                'status' => 'en_revision',
            ]);
        }

        app(DocumentSyncService::class)->syncPolicy($qhsePolicy->fresh(), $user->id ?? null);

        return response()->json($this->serializePolicy($qhsePolicy->fresh()));
    }

    public function exportPdf(QhsePolicy $qhsePolicy)
    {
        $request = request();
        $user = $request->user();

        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        $enterprise = $qhsePolicy->site?->enterprise ?? $user?->enterprise;
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'qhse_policy', $qhsePolicy->id)
            : null;

        $pdf = Pdf::loadView('pdf.qhse-policy', [
            'policy' => $qhsePolicy,
            'branding' => $branding,
        ]);
        $filename = 'politique_qhse_v'.$qhsePolicy->version.'.pdf';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }
        $pdf->save($tempPath);

        $siteId = (int) ($qhsePolicy->site_id ?? 0);
        if ($siteId <= 0) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $qhsePolicy->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId <= 0) {
            return response()->json(['message' => 'Aucun site disponible pour générer le document.'], 422);
        }
        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);
        $document = null;
        if ($siteId > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => $generationContext['process_id'],
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'qhse_policy_pdf',
                'source_type' => 'qhse_policy',
                'source_id' => $qhsePolicy->id,
                'source_updated_at' => $qhsePolicy->updated_at?->toISOString(),
                'title' => 'Politique QHSE v' . ($qhsePolicy->version ?? '1.0'),
                'description' => 'Version PDF de la politique QHSE générée automatiquement.',
                'file_source_path' => $tempPath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => $generationContext['type'],
                'force_new' => true,
                'metadata' => $generationContext['metadata'],
            ]);
        }

        $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function exportDocx(QhsePolicy $qhsePolicy)
    {
        $request = request();
        $user = $request->user();

        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        $qhsePolicy->loadMissing(['enterprise', 'site.enterprise']);
        $path = app(\App\Services\Docx\QhsePolicyDocxGenerator::class)->generate($qhsePolicy);

        $siteId = (int) ($qhsePolicy->site_id ?? 0);
        if ($siteId <= 0) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $qhsePolicy->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId <= 0) {
            return response()->json(['message' => 'Aucun site disponible pour générer le document.'], 422);
        }
        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);
        $document = null;
        if ($siteId > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => $generationContext['process_id'],
                'process_code' => $generationContext['process_code'],
                'process_name' => $generationContext['process_name'],
                'document_kind' => 'qhse_policy_docx',
                'source_type' => 'qhse_policy',
                'source_id' => $qhsePolicy->id,
                'source_updated_at' => $qhsePolicy->updated_at?->toISOString(),
                'title' => 'Politique QHSE v' . ($qhsePolicy->version ?? '1.0'),
                'description' => 'Version DOCX de la politique QHSE générée automatiquement.',
                'file_source_path' => $path,
                'created_by' => $user?->id,
                'type' => $generationContext['type'],
                'force_new' => true,
                'metadata' => $generationContext['metadata'],
            ]);
        }

        return response()
            ->download($path, 'politique_qhse_v'.$qhsePolicy->version.'.docx')
            ->header('X-Generated-Document-Id', $document ? (string) $document->id : '')
            ->deleteFileAfterSend(true);
    }

    public function generateDraftDocx(Request $request, QhsePolicy $qhsePolicy)
    {
        $user = $request->user();

        if ((int) $qhsePolicy->enterprise_id !== (int) $user->enterprise_id) {
            abort(403);
        }

        $qhsePolicy->loadMissing(['enterprise', 'site.enterprise']);
        $path = app(\App\Services\Docx\QhsePolicyDocxGenerator::class)->generate($qhsePolicy);

        $siteId = (int) ($qhsePolicy->site_id ?? 0);
        if ($siteId <= 0) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $qhsePolicy->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId <= 0) {
            return response()->json(['message' => 'Aucun site disponible pour générer le document.'], 422);
        }

        $generationContext = $this->resolveGeneratedDocumentContext($request, $siteId);
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $generationContext['process_id'],
            'process_code' => $generationContext['process_code'],
            'process_name' => $generationContext['process_name'],
            'document_kind' => 'qhse_policy_docx',
            'source_type' => 'qhse_policy',
            'source_id' => $qhsePolicy->id,
            'source_updated_at' => $qhsePolicy->updated_at?->toISOString(),
            'title' => 'Politique QHSE v' . ($qhsePolicy->version ?? '1.0'),
            'description' => 'Version DOCX de la politique QHSE générée automatiquement.',
            'file_source_path' => $path,
            'created_by' => $user?->id,
            'type' => $generationContext['type'],
            'force_new' => true,
            'store_file' => true,
            'metadata' => $generationContext['metadata'],
        ]);

        return (new DocumentResource($document->load(['site', 'process', 'author'])))->response();
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $policies = QhsePolicy::where('enterprise_id', $user->enterprise_id)
            ->when($request->filled('site_id'), fn ($query) => $query->where('site_id', (int) $request->integer('site_id')))
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($policies->map(fn (QhsePolicy $policy) => $this->serializePolicy($policy)));
    }

    private function getNextVersion(int $enterpriseId): string
    {
        $lastPolicy = QhsePolicy::where('enterprise_id', $enterpriseId)
            ->orderBy('version', 'desc')
            ->first();

        if (!$lastPolicy) {
            return '1.0';
        }

        $parts = explode('.', $lastPolicy->version);
        $parts[0] = (int)$parts[0] + 1;
        
        return implode('.', $parts);
    }

    private function incrementMinorVersion(string $version): string
    {
        $parts = explode('.', $version);
        $major = isset($parts[0]) ? (int) $parts[0] : 1;
        $minor = isset($parts[1]) ? (int) $parts[1] : 0;

        return "{$major}." . ($minor + 1);
    }

    private function serializePolicy(?QhsePolicy $policy): ?array
    {
        if (!$policy) {
            return null;
        }

        $axes = [];
        if ($this->hasPolicyAxesTable()) {
            $policy->loadMissing('axisEntries');
            $axes = $policy->axisEntries
                ->pluck('axis_name')
                ->map(fn ($axis) => trim((string) $axis))
                ->filter(fn ($axis) => $axis !== '')
                ->values()
                ->all();
        }

        if (empty($axes)) {
            $axes = $this->normalizeAxes($policy->axes ?? []);
        }

        return [
            'id' => $policy->id,
            'enterprise_id' => $policy->enterprise_id,
            'site_id' => $policy->site_id,
            'version' => $policy->version,
            'is_current' => (bool) $policy->is_current,
            'effective_date' => $policy->effective_date?->toDateString(),
            'mission' => $policy->mission,
            'vision' => $policy->vision,
            'values' => is_array($policy->values) ? $policy->values : [],
            'axes' => $axes,
            'commitments' => is_array($policy->commitments) ? $policy->commitments : [],
            'quality_policy' => $policy->quality_policy,
            'environmental_policy' => $policy->environmental_policy,
            'health_safety_policy' => $policy->health_safety_policy,
            'status' => $policy->status,
            'validated_by_direction_at' => $policy->validated_by_direction_at?->toISOString(),
            'validated_by_user_id' => $policy->validated_by_user_id,
            'created_at' => $policy->created_at?->toISOString(),
            'updated_at' => $policy->updated_at?->toISOString(),
        ];
    }

    private function normalizeAxes(mixed $axes): array
    {
        if (!is_array($axes)) {
            return [];
        }

        return array_values(array_filter(
            array_map(fn ($axis) => trim((string) $axis), $axes),
            fn ($axis) => $axis !== ''
        ));
    }

    private function hasPolicyAxesTable(): bool
    {
        static $hasTable = null;
        if ($hasTable !== null) {
            return $hasTable;
        }

        $hasTable = Schema::hasTable('qhse_policy_axes');
        return $hasTable;
    }
}
