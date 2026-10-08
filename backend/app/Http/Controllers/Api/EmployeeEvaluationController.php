<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeEvaluationResource;
use App\Models\EmployeeEvaluation;
use Illuminate\Http\Request;
use App\Services\EmployeeEvaluationPdfGenerator;
use App\Services\ProcedureEvaluationPipDocxGenerator;
use App\Services\ProviderEvaluationPdfGenerator;

class EmployeeEvaluationController extends Controller
{
    public function index()
    {
        $evaluations = EmployeeEvaluation::with(['site', 'user', 'evaluator'])->paginate(20);
        return EmployeeEvaluationResource::collection($evaluations);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'user_id' => 'required|exists:users,id',
            'evaluator_id' => 'required|exists:users,id',
            'period' => 'required|string|max:255',
            'year' => 'required|integer|min:2020|max:2100',
            'scores' => 'required|array',
            'total_score' => 'nullable|numeric',
            'comments' => 'nullable|string',
            'm13_d3_traceability' => 'nullable|array',
            'm13_d6_traceability' => 'nullable|array',
        ]);

        $evaluation = EmployeeEvaluation::create($validated);
        return new EmployeeEvaluationResource($evaluation->load(['site', 'user', 'evaluator']));
    }

    public function show(EmployeeEvaluation $employeeEvaluation)
    {
        return new EmployeeEvaluationResource($employeeEvaluation->load(['site', 'user', 'evaluator']));
    }

    public function update(Request $request, EmployeeEvaluation $employeeEvaluation)
    {
        $validated = $request->validate([
            'site_id' => 'sometimes|exists:sites,id',
            'user_id' => 'sometimes|exists:users,id',
            'evaluator_id' => 'sometimes|exists:users,id',
            'period' => 'sometimes|string|max:255',
            'year' => 'sometimes|integer|min:2020|max:2100',
            'scores' => 'sometimes|array',
            'total_score' => 'nullable|numeric',
            'comments' => 'nullable|string',
            'm13_d3_traceability' => 'nullable|array',
            'm13_d6_traceability' => 'nullable|array',
        ]);

        $employeeEvaluation->update($validated);
        return new EmployeeEvaluationResource($employeeEvaluation->load(['site', 'user', 'evaluator']));
    }

    public function destroy(EmployeeEvaluation $employeeEvaluation)
    {
        $employeeEvaluation->delete();
        return response()->json(null, 204);
    }

    public function exportPdf(
        EmployeeEvaluation $employeeEvaluation,
        EmployeeEvaluationPdfGenerator $generator
    ) {
        $employeeEvaluation->loadMissing(['site.enterprise', 'user', 'evaluator']);
        $filePath = $generator->generate(
            $employeeEvaluation,
            false,
            $employeeEvaluation->site?->enterprise
        );
        $user = auth()->user();
        $filename = basename($filePath);
        $document = null;
        if ((int) ($employeeEvaluation->site_id ?? 0) > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $employeeEvaluation->site_id,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'employee_evaluation_pdf',
                'source_type' => 'employee_evaluation',
                'source_id' => $employeeEvaluation->id,
                'source_updated_at' => $employeeEvaluation->updated_at?->toISOString(),
                'title' => 'Évaluation du personnel #' . $employeeEvaluation->id,
                'description' => 'Formulaire d’évaluation du personnel exporté en PDF.',
                'file_source_path' => $filePath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => 'ENR',
                'force_new' => true,
            ]);
        }

        $response = response()->download($filePath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function exportAnonymousPdf(
        EmployeeEvaluation $employeeEvaluation,
        EmployeeEvaluationPdfGenerator $generator
    ) {
        $employeeEvaluation->loadMissing(['site.enterprise', 'user', 'evaluator']);
        $filePath = $generator->generate(
            $employeeEvaluation,
            true,
            $employeeEvaluation->site?->enterprise
        );
        $user = auth()->user();
        $filename = basename($filePath);
        $document = null;
        if ((int) ($employeeEvaluation->site_id ?? 0) > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $employeeEvaluation->site_id,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'employee_evaluation_anonymous_pdf',
                'title' => 'Évaluation du personnel anonyme #' . $employeeEvaluation->id,
                'description' => 'Formulaire d’évaluation du personnel anonymisé exporté en PDF.',
                'file_source_path' => $filePath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => 'ENR',
                'force_new' => true,
            ]);
        }

        $response = response()->download($filePath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function exportProviderPdf(
        Request $request,
        ProviderEvaluationPdfGenerator $generator
    ) {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'evaluation_period' => 'nullable|string|max:255',
            'overall_score' => 'nullable|numeric',
            'criteria_scores' => 'nullable|array',
            'strengths' => 'nullable|array',
            'improvements' => 'nullable|array',
            'anonymize' => 'nullable|boolean',
        ]);

        $filePath = $generator->generate(
            $validated,
            (bool) ($validated['anonymize'] ?? false),
            $request->user()?->enterprise
        );
        $filename = basename($filePath);

        $user = $request->user();
        $siteId = (int) ($user?->site_id ?? 0);
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        $document = null;
        if ($siteId > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'provider_evaluation_pdf',
                'title' => 'Évaluation prestataire ' . ($validated['name'] ?? 'N/A'),
                'description' => 'Formulaire d’évaluation prestataire exporté en PDF.',
                'file_source_path' => $filePath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => 'ENR',
                'force_new' => true,
            ]);
        }

        $response = response()->download($filePath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }

    public function downloadProcedureTemplate(ProcedureEvaluationPipDocxGenerator $generator)
    {
        $filePath = $generator->generate(auth()->user()?->enterprise);

        $user = auth()->user();
        $siteId = (int) ($user?->site_id ?? 0);
        if ($siteId <= 0 && $user?->enterprise_id) {
            $siteId = (int) \App\Models\Site::query()
                ->where('enterprise_id', (int) $user->enterprise_id)
                ->orderBy('id')
                ->value('id');
        }
        $document = null;
        if ($siteId > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'evaluation_pip_procedure_docx',
                'title' => 'Procédure évaluation PIP',
                'description' => 'Procédure DOCX d’évaluation PIP générée automatiquement.',
                'file_source_path' => $filePath,
                'file_extension' => 'docx',
                'created_by' => $user?->id,
                'type' => 'PRC',
                'force_new' => true,
            ]);
        }

        $response = response()->download($filePath, basename($filePath))->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }
}
