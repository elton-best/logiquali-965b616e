<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CertificationCatalog;
use App\Models\EnterpriseCertification;
use App\Models\Enterprise;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class EnterpriseCertificationController extends Controller
{
    /**
     * Liste des certifications du catalogue
     */
    public function catalog(): JsonResponse
    {
        $certifications = CertificationCatalog::active()
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return response()->json($certifications);
    }

    /**
     * Liste des certifications d'une entreprise
     */
    public function index(Enterprise $enterprise): JsonResponse
    {
        $this->authorize('view', $enterprise);

        $certifications = $enterprise->certifications()
            ->with('certification')
            ->orderBy('status')
            ->orderBy('expiry_date')
            ->get();

        return response()->json($certifications);
    }

    /**
     * Ajouter une certification à une entreprise
     */
    public function store(Request $request, Enterprise $enterprise): JsonResponse
    {
        $this->authorize('update', $enterprise);

        $validated = $request->validate([
            'certification_id' => 'required|exists:certifications_catalog,id',
            'certificate_number' => 'nullable|string|max:100',
            'certifying_body' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'scope' => 'nullable|string',
            'logo_path' => 'nullable|string',
        ]);

        $certification = $enterprise->certifications()->create($validated);

        return response()->json($certification->load('certification'), 201);
    }

    /**
     * Mettre à jour une certification
     */
    public function update(Request $request, Enterprise $enterprise, EnterpriseCertification $certification): JsonResponse
    {
        $this->authorize('update', $enterprise);

        if ($certification->enterprise_id !== $enterprise->id) {
            return response()->json(['message' => 'Certification not found'], 404);
        }

        $validated = $request->validate([
            'certificate_number' => 'nullable|string|max:100',
            'certifying_body' => 'nullable|string|max:100',
            'issue_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:issue_date',
            'scope' => 'nullable|string',
            'logo_path' => 'nullable|string',
            'status' => 'in:active,pending_renewal,expired',
        ]);

        $certification->update($validated);

        return response()->json($certification->load('certification'));
    }

    /**
     * Supprimer une certification
     */
    public function destroy(Enterprise $enterprise, EnterpriseCertification $certification): JsonResponse
    {
        $this->authorize('update', $enterprise);

        if ($certification->enterprise_id !== $enterprise->id) {
            return response()->json(['message' => 'Certification not found'], 404);
        }

        $certification->delete();

        return response()->json(['message' => 'Certification deleted successfully']);
    }
}
