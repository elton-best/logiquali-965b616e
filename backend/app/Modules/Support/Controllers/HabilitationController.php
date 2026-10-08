<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Habilitation;
use App\Rules\SecureFileUpload;
use App\Services\FileUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class HabilitationController extends Controller
{
    public function __construct(private FileUploadService $fileService)
    {
        $this->middleware('permission:sst.habilitations.read')->only(['index', 'show']);
        $this->middleware('permission:sst.habilitations.create')->only(['store']);
        $this->middleware('permission:sst.habilitations.update')->only(['update', 'renew']);
        $this->middleware('permission:sst.habilitations.delete')->only(['destroy']);
        $this->middleware('permission:sst.habilitations.manage_alerts')->only(['expiresSoon', 'export']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = Habilitation::with('user')
            ->orderBy('expiry_date', 'asc');

        // Filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('type')) {
            $query->byType($request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('expiring')) {
            $query->expiresSoon($request->input('expiring', 30));
        }

        $habilitations = $query->paginate(20);

        return response()->json($habilitations);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:100',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after:issued_date',
            'issuing_authority' => 'required|string|max:255',
            'certificate' => ['nullable', 'file', new SecureFileUpload(['application/pdf', 'image/jpeg', 'image/png'])],
            'notes' => 'nullable|string'
        ]);

        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            $upload = $this->fileService->uploadSecure(
                $request->file('certificate'),
                'habilitations/certificates'
            );
            $validated['certificate_path'] = $upload['path'];
        }

        $habilitation = Habilitation::create($validated);

        return response()->json([
            'success' => true,
            'data' => $habilitation->load('user'),
            'message' => 'Habilitation créée avec succès'
        ], 201);
    }

    public function show(Habilitation $habilitation): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $habilitation->load('user')
        ]);
    }

    public function update(Request $request, Habilitation $habilitation): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'string|max:100',
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'certificate_number' => 'nullable|string|max:100',
            'issued_date' => 'date',
            'expiry_date' => 'date|after:issued_date',
            'issuing_authority' => 'string|max:255',
            'status' => 'in:active,expired,suspended,renewed',
            'certificate' => ['nullable', 'file', new SecureFileUpload(['application/pdf', 'image/jpeg', 'image/png'])],
            'notes' => 'nullable|string'
        ]);

        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            // Delete old certificate
            if ($habilitation->certificate_path) {
                $this->fileService->deleteFile($habilitation->certificate_path);
            }

            $upload = $this->fileService->uploadSecure(
                $request->file('certificate'),
                'habilitations/certificates'
            );
            $validated['certificate_path'] = $upload['path'];
        }

        $habilitation->update($validated);

        return response()->json([
            'success' => true,
            'data' => $habilitation->load('user'),
            'message' => 'Habilitation mise à jour'
        ]);
    }

    public function destroy(Habilitation $habilitation): JsonResponse
    {
        // Delete certificate file
        if ($habilitation->certificate_path) {
            $this->fileService->deleteFile($habilitation->certificate_path);
        }

        $habilitation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Habilitation supprimée'
        ]);
    }

    public function expiresSoon(Request $request): JsonResponse
    {
        $days = $request->input('days', 30);
        
        $habilitations = Habilitation::with('user')
            ->expiresSoon($days)
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $habilitations,
            'count' => $habilitations->count()
        ]);
    }

    public function renew(Request $request, Habilitation $habilitation): JsonResponse
    {
        $validated = $request->validate([
            'new_expiry_date' => 'required|date|after:today',
            'certificate_number' => 'nullable|string|max:100',
            'issuing_authority' => 'nullable|string|max:255',
            'certificate' => ['nullable', 'file', new SecureFileUpload(['application/pdf', 'image/jpeg', 'image/png'])],
            'notes' => 'nullable|string'
        ]);

        // Handle certificate upload
        if ($request->hasFile('certificate')) {
            $upload = $this->fileService->uploadSecure(
                $request->file('certificate'),
                'habilitations/certificates'
            );
            $validated['certificate_path'] = $upload['path'];
        }

        $habilitation->update([
            'expiry_date' => $validated['new_expiry_date'],
            'certificate_number' => $validated['certificate_number'] ?? $habilitation->certificate_number,
            'issuing_authority' => $validated['issuing_authority'] ?? $habilitation->issuing_authority,
            'certificate_path' => $validated['certificate_path'] ?? $habilitation->certificate_path,
            'status' => 'active',
            'notes' => $validated['notes'] ?? $habilitation->notes
        ]);

        return response()->json([
            'success' => true,
            'data' => $habilitation->load('user'),
            'message' => 'Habilitation renouvelée'
        ]);
    }

    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Habilitation::count(),
            'active' => Habilitation::where('status', 'active')->count(),
            'expired' => Habilitation::where('status', 'expired')->count(),
            'expiring_soon' => Habilitation::expiresSoon(30)->count(),
            'expiring_critical' => Habilitation::expiresSoon(7)->count(),
            'by_type' => Habilitation::selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    public function export(): JsonResponse
    {
        $habilitations = Habilitation::with('user')->get();

        $data = $habilitations->map(function ($habilitation) {
            return [
                'Employé' => $habilitation->user->name,
                'Type' => $habilitation->type,
                'Titre' => $habilitation->title,
                'N° Certificat' => $habilitation->certificate_number,
                'Date émission' => $habilitation->issued_date->format('d/m/Y'),
                'Date expiration' => $habilitation->expiry_date->format('d/m/Y'),
                'Autorité' => $habilitation->issuing_authority,
                'Statut' => $habilitation->status,
                'Jours restants' => $habilitation->getDaysUntilExpiry()
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'filename' => 'habilitations_' . now()->format('Y-m-d') . '.xlsx'
        ]);
    }
}
