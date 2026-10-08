<?php

namespace App\Modules\Leadership\Controllers;

use App\Models\Document;
use App\Models\Enterprise;
use App\Models\Role;
use App\Services\DocumentSyncService;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResponsibilityResource;
use App\Models\Process;
use App\Models\Responsibility;
use App\Models\User;
use App\Services\DocumentTypeResolver;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class ResponsibilityController extends Controller
{
    public function __construct()
    {
        $read = 'permission:leadership.roles_responsabilites.fiche_responsabilite.read'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.read'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_responsabilite.read'
            . '|leadership.roles_responsabilites.fiche_responsabilite.manage';

        $create = 'permission:leadership.roles_responsabilites.fiche_responsabilite.create'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.create'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_responsabilite.create'
            . '|leadership.roles_responsabilites.fiche_responsabilite.manage';

        $update = 'permission:leadership.roles_responsabilites.fiche_responsabilite.update'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.update'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_responsabilite.update'
            . '|leadership.roles_responsabilites.fiche_responsabilite.manage';

        $delete = 'permission:leadership.roles_responsabilites.fiche_responsabilite.delete'
            . '|leadership.manage'
            . '|leadership.roles_responsabilites.delete'
            . '|leadership.roles_responsabilites.manage'
            . '|leadership.roles_responsabilites.fiche_responsabilite.delete'
            . '|leadership.roles_responsabilites.fiche_responsabilite.manage';

        $this->middleware($read)->only(['index', 'show', 'exportPdf', 'exportDocx']);
        $this->middleware($create)->only(['store']);
        $this->middleware($update)->only(['update']);
        $this->middleware($delete)->only(['destroy']);
    }

    public function index()
    {
        $user = auth()->user();
        if (!$user instanceof User) {
            return response()->json(['message' => 'Utilisateur non authentifié'], 401);
        }
        $responsibilities = $this->responsibilitiesBaseQueryForUser($user)
            ->paginate(20);
            
        return ResponsibilityResource::collection($responsibilities);
    }

    public function store(Request $request)
    {
        try {
            Log::info('ResponsibilityController@store - Request data:', $request->all());
            
            $validated = $request->validate([
                'process_id' => 'required|exists:processes,id',
                'user_id' => 'nullable|exists:users,id',
                'level' => 'required|string|max:255',
                'roles' => 'required|string|min:1',
                'deliverables' => 'required|string|min:1',
                'role_title' => 'nullable|string|max:255',
                'responsibilities' => 'nullable|array',
                'authorities' => 'nullable|array',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
            ]);

            Log::info('ResponsibilityController@store - Validated data:', $validated);

            // Récupérer enterprise_id via le processus → site → enterprise
            $process = \App\Models\Process::with('site')->findOrFail($validated['process_id']);
            $validated['enterprise_id'] = $process->site->enterprise_id;

            Log::info('ResponsibilityController@store - Enterprise ID from process:', ['enterprise_id' => $validated['enterprise_id']]);

            $responsibility = Responsibility::create($validated);
            
            Log::info('ResponsibilityController@store - Responsibility created:', ['id' => $responsibility->id]);
            
            return new ResponsibilityResource($responsibility->load(['process', 'user']));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('ResponsibilityController@store - Validation error:', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('ResponsibilityController@store - Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function show(Responsibility $responsibility)
    {
        return new ResponsibilityResource($responsibility->load(['process', 'user']));
    }

    public function update(Request $request, Responsibility $responsibility)
    {
        $validated = $request->validate([
            'process_id' => 'sometimes|exists:processes,id',
            'user_id' => 'nullable|exists:users,id',
            'level' => 'sometimes|string|max:255',
            'roles' => 'nullable|string',
            'deliverables' => 'nullable|string',
            'role_title' => 'nullable|string|max:255',
            'responsibilities' => 'nullable|array',
            'authorities' => 'nullable|array',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $responsibility->update($validated);
        return new ResponsibilityResource($responsibility->load(['process', 'user']));
    }

    public function destroy(Responsibility $responsibility)
    {
        $responsibility->delete();
        return response()->json(null, 204);
    }

    public function exportPdf(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $responsibilities = $this->responsibilitiesBaseQueryForUser($user)
            ->orderBy('process_id')
            ->get();

        if ($responsibilities->isEmpty()) {
            return response()->json([
                'message' => 'Aucune fiche de responsabilité à exporter.',
            ], 422);
        }

        $rows = $responsibilities->map(function (Responsibility $responsibility) {
            return [
                'process' => (string) ($responsibility->process?->name ?? $responsibility->process?->title ?? 'N/A'),
                'level' => (string) ($responsibility->level ?? ''),
                'roles' => (string) ($responsibility->roles ?? ''),
                'deliverables' => (string) ($responsibility->deliverables ?? ''),
            ];
        });

        $html = view('pdf.responsibilities', [
            'rows' => $rows,
            'generatedAt' => now(),
        ])->render();

        $filename = 'fiche_responsabilite_' . now()->format('Ymd_His') . '.pdf';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        Pdf::loadHTML($html)->save($tempPath);

        $document = $this->syncResponsibilityExportInInventory(
            $tempPath,
            'pdf',
            (int) ($user->site_id ?? 0)
        );

        $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function exportDocx(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $responsibilities = $this->responsibilitiesBaseQueryForUser($user)
            ->orderBy('process_id')
            ->get();

        if ($responsibilities->isEmpty()) {
            return response()->json([
                'message' => 'Aucune fiche de responsabilité à exporter.',
            ], 422);
        }

        $phpWord = new PhpWord();
        $section = $phpWord->addSection();
        $section->addTitle('Fiche de responsabilité', 1);
        $section->addText('Date: ' . now()->format('d/m/Y H:i'));
        $section->addTextBreak(1);

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        $headerStyle = ['bold' => true];
        $table->addRow();
        $table->addCell(2600)->addText('Processus', $headerStyle);
        $table->addCell(1600)->addText('Niveau', $headerStyle);
        $table->addCell(4000)->addText('Role et responsabilite', $headerStyle);
        $table->addCell(2800)->addText('Livrables', $headerStyle);

        foreach ($responsibilities as $responsibility) {
            $table->addRow();
            $table->addCell(2600)->addText((string) ($responsibility->process?->name ?? $responsibility->process?->title ?? 'N/A'));
            $table->addCell(1600)->addText((string) ($responsibility->level ?? ''));
            $table->addCell(4000)->addText((string) ($responsibility->roles ?? ''));
            $table->addCell(2800)->addText((string) ($responsibility->deliverables ?? ''));
        }

        $filename = 'fiche_responsabilite_' . now()->format('Ymd_His') . '.docx';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);

        $document = $this->syncResponsibilityExportInInventory(
            $tempPath,
            'docx',
            (int) ($user->site_id ?? 0)
        );

        $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    private function responsibilitiesBaseQueryForUser(User $user)
    {
        $processIds = Process::where('site_id', $user->site_id)->pluck('id');

        return Responsibility::with(['process', 'user'])
            ->whereIn('process_id', $processIds);
    }

    public function generateDraftDocx(Request $request)
    {
        $user = auth()->user();
        if (!$user) return response()->json(['message' => 'Unauthorized'], 401);

        $request->validate([
            'document_type_catalog_id' => 'required|integer|exists:document_type_catalogs,id',
            'process_id' => 'nullable|integer|exists:processes,id',
        ]);

        $responsibilities = $this->responsibilitiesBaseQueryForUser($user)->orderBy('process_id')->get();
        if ($responsibilities->isEmpty()) {
            return response()->json(['message' => 'Aucune fiche de responsabilité à exporter.'], 422);
        }

        $rows = $responsibilities->map(fn (Responsibility $r) => [
            'process' => (string) ($r->process?->name ?? $r->process?->title ?? 'N/A'),
            'level' => (string) ($r->level ?? ''),
            'roles' => (string) ($r->roles ?? ''),
            'deliverables' => (string) ($r->deliverables ?? ''),
        ]);

        $html = view('pdf.responsibilities', ['rows' => $rows, 'generatedAt' => now(), 'isDraft' => true])->render();
        $tempPath = Storage::disk('local')->path('exports/responsibility_draft_' . time() . '.pdf');
        if (!is_dir(dirname($tempPath))) mkdir(dirname($tempPath), 0775, true);
        Pdf::loadHTML($html)->save($tempPath);

        $siteId = (int) ($user->site_id ?? 0);
        $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => $request->integer('process_id') ?: null,
            'process_code' => 'GEN',
            'document_kind' => 'responsibility_sheet',
            'source_type' => 'responsibility_sheet',
            'source_id' => $siteId,
            'title' => 'Fiche de responsabilité',
            'description' => 'Fiche de responsabilité générée automatiquement.',
            'file_source_path' => $tempPath,
            'created_by' => $user->id,
            'type' => DocumentTypeResolver::resolveType('responsibility_sheet'),
            'force_new' => true,
            'store_file' => true,
            'metadata' => ['document_type_catalog_id' => $request->integer('document_type_catalog_id')],
        ]);

        @unlink($tempPath);
        return response()->json(['data' => $document]);
    }

    private function syncResponsibilityExportInInventory(string $filePath, string $extension, int $siteId): ?\App\Models\Document
    {
        if ($siteId <= 0 || !is_file($filePath)) {
            return null;
        }

        return app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
            'site_id' => $siteId,
            'process_id' => null,
            'process_code' => 'GEN',
            'document_kind' => 'responsibility_sheet_' . $extension,
            'title' => 'Fiche de responsabilité',
            'description' => 'Fiche de responsabilité exportée automatiquement.',
            'file_source_path' => $filePath,
            'file_extension' => $extension,
            'created_by' => Auth::id(),
            'type' => DocumentTypeResolver::resolveType('responsibility_sheet'),
            'force_new' => true,
        ]);
    }
}
