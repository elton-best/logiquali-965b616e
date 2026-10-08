<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard.read')->only(['index', 'show', 'download']);
        $this->middleware('permission:dashboard.read')->only(['generate']);
        $this->middleware('permission:dashboard.read')->only(['destroy']);
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get reports for user's enterprise
        $query = Report::query();
        
        if ($user->enterprise_id) {
            $query->where('enterprise_id', $user->enterprise_id);
        }
        
        $reports = $query->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'data' => $reports
        ]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $report = Report::with('user')->findOrFail($id);
        
        // Verify access
        $user = $request->user();
        if ($user->enterprise_id && $report->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $report
        ]);
    }

    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'site_ids' => 'nullable|array',
            'format' => 'nullable|string|in:pdf,excel,csv',
            'include_charts' => 'nullable|boolean',
            'send_email' => 'nullable|boolean',
        ]);

        $user = $request->user();
        
        // Create report record
        $report = Report::create([
            'enterprise_id' => $user->enterprise_id,
            'user_id' => $user->id,
            'type' => $validated['type'],
            'name' => $this->generateReportName($validated['type']),
            'description' => $this->generateReportDescription($validated['type']),
            'parameters' => $validated,
            'format' => $validated['format'] ?? 'pdf',
            'status' => 'generated',
            'generated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => $report
        ], 201);
    }

    public function download(Request $request, string $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        
        // Verify access
        $user = $request->user();
        if ($user->enterprise_id && $report->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        // TODO: Implement actual file download
        return response()->json([
            'success' => false,
            'message' => 'Report file not found'
        ], 404);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $report = Report::findOrFail($id);
        
        // Verify access
        $user = $request->user();
        if ($user->enterprise_id && $report->enterprise_id !== $user->enterprise_id) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied'
            ], 403);
        }

        $report->delete();

        return response()->json([
            'success' => true,
            'message' => 'Report deleted successfully'
        ]);
    }

    private function generateReportName(string $type): string
    {
        $types = [
            'non_conformities' => 'Rapport des Non-Conformités',
            'audits' => 'Rapport des Audits',
            'risks' => 'Rapport des Risques',
            'indicators' => 'Rapport des Indicateurs',
            'compliance' => 'Rapport de Conformité',
            'improvement' => 'Rapport d\'Amélioration Continue'
        ];

        $baseName = $types[$type] ?? 'Rapport QHSE';
        return $baseName . ' - ' . now()->format('d/m/Y');
    }

    private function generateReportDescription(string $type): string
    {
        return 'Rapport généré automatiquement le ' . now()->format('d/m/Y à H:i');
    }
}
