<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EvaluationCriteria;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class EvaluationCriteriaController extends Controller
{
    /**
     * Liste des critères d'évaluation
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $enterpriseId = $user->enterprise_id;

        $query = EvaluationCriteria::where('enterprise_id', $enterpriseId)
            ->with(['site', 'createdBy', 'updatedBy']);

        // Filtres
        if ($request->has('form_type') && $request->form_type) {
            $query->forFormType($request->form_type);
        }

        if ($request->has('category') && $request->category) {
            $query->forCategory($request->category);
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->is_active, FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->has('site_id') && $request->site_id) {
            $query->where(function ($q) use ($request) {
                $q->where('site_id', $request->site_id)
                    ->orWhereNull('site_id'); // Inclure les critères globaux
            });
        }

        // Tri
        $query->ordered();

        // Pagination
        $perPage = $request->input('per_page', 50);
        $criteria = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $criteria->items(),
            'meta' => [
                'current_page' => $criteria->currentPage(),
                'last_page' => $criteria->lastPage(),
                'per_page' => $criteria->perPage(),
                'total' => $criteria->total(),
            ],
        ]);
    }

    /**
     * Afficher un critère
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();
        $criteria = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->with(['site', 'createdBy', 'updatedBy'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $criteria,
        ]);
    }

    /**
     * Créer un nouveau critère
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'scale_type' => 'nullable|in:numeric,stars,percentage,custom',
            'scale_min' => 'nullable|integer|min:0',
            'scale_max' => 'nullable|integer|min:1',
            'scale_labels' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0|max:100',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'form_type' => 'required|in:satisfaction_client,satisfaction_personnel,performance_personnel,evaluation_personnel,evaluation_auditeur,satisfaction_fournisseur,performance_fournisseur,evaluation_fournisseur,audit_interne,custom',
            'display_order' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['enterprise_id'] = $user->enterprise_id;
        $data['created_by'] = $user->id;

        $criteria = EvaluationCriteria::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Critère créé avec succès',
            'data' => $criteria->load(['site', 'createdBy']),
        ], 201);
    }

    /**
     * Mettre à jour un critère
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();
        $criteria = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'nullable|string|max:20',
            'description' => 'nullable|string',
            'category' => 'nullable|string|max:100',
            'scale_type' => 'nullable|in:numeric,stars,percentage,custom',
            'scale_min' => 'nullable|integer|min:0',
            'scale_max' => 'nullable|integer|min:1',
            'scale_labels' => 'nullable|array',
            'weight' => 'nullable|numeric|min:0|max:100',
            'is_mandatory' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'form_type' => 'nullable|in:satisfaction_client,satisfaction_personnel,performance_personnel,evaluation_personnel,evaluation_auditeur,satisfaction_fournisseur,performance_fournisseur,evaluation_fournisseur,audit_interne,custom',
            'display_order' => 'nullable|integer|min:0',
            'site_id' => 'nullable|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['updated_by'] = $user->id;

        $criteria->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Critère mis à jour avec succès',
            'data' => $criteria->fresh(['site', 'createdBy', 'updatedBy']),
        ]);
    }

    /**
     * Supprimer un critère
     */
    public function destroy(int $id): JsonResponse
    {
        $user = Auth::user();
        $criteria = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        $criteria->delete();

        return response()->json([
            'success' => true,
            'message' => 'Critère supprimé avec succès',
        ]);
    }

    /**
     * Obtenir les critères par défaut pour un type de formulaire
     */
    public function defaults(Request $request): JsonResponse
    {
        $formType = $request->input('form_type', 'satisfaction_client');

        $defaults = match ($formType) {
            'satisfaction_client' => EvaluationCriteria::getDefaultSatisfactionClientCriteria(),
            'satisfaction_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'performance_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'evaluation_auditeur' => EvaluationCriteria::getDefaultAuditorEvaluationCriteria(),
            'evaluation_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'satisfaction_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'performance_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'evaluation_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'audit_interne' => EvaluationCriteria::getDefaultInternalAuditCriteria(),
            default => [],
        };

        return response()->json([
            'success' => true,
            'data' => $defaults,
            'form_type' => $formType,
        ]);
    }

    /**
     * Initialiser les critères par défaut pour une entreprise
     */
    public function initializeDefaults(Request $request): JsonResponse
    {
        $user = Auth::user();
        $formType = $request->input('form_type');

        if (!$formType) {
            return response()->json([
                'success' => false,
                'message' => 'Le type de formulaire est requis',
            ], 422);
        }

        // Vérifier si des critères existent déjà pour ce type
        $existingCount = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->where('form_type', $formType)
            ->count();

        if ($existingCount > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Des critères existent déjà pour ce type de formulaire',
                'existing_count' => $existingCount,
            ], 409);
        }

        $defaults = match ($formType) {
            'satisfaction_client' => EvaluationCriteria::getDefaultSatisfactionClientCriteria(),
            'satisfaction_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'performance_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'evaluation_auditeur' => EvaluationCriteria::getDefaultAuditorEvaluationCriteria(),
            'evaluation_personnel' => EvaluationCriteria::getDefaultEvaluationPersonnelCriteria(),
            'satisfaction_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'performance_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'evaluation_fournisseur' => EvaluationCriteria::getDefaultSupplierEvaluationCriteria(),
            'audit_interne' => EvaluationCriteria::getDefaultInternalAuditCriteria(),
            default => [],
        };

        if (empty($defaults)) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun critère par défaut disponible pour ce type',
            ], 404);
        }

        $created = [];
        foreach ($defaults as $index => $default) {
            $criteria = EvaluationCriteria::create([
                'enterprise_id' => $user->enterprise_id,
                'name' => $default['name'],
                'code' => $default['code'],
                'category' => $default['category'],
                'scale_labels' => $default['scale_labels'],
                'form_type' => $formType,
                'display_order' => $index,
                'created_by' => $user->id,
            ]);
            $created[] = $criteria;
        }

        return response()->json([
            'success' => true,
            'message' => count($created) . ' critères créés avec succès',
            'data' => $created,
        ], 201);
    }

    /**
     * Réorganiser l'ordre des critères
     */
    public function reorder(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'criteria' => 'required|array',
            'criteria.*.id' => 'required|integer|exists:evaluation_criteria,id',
            'criteria.*.display_order' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->criteria as $item) {
            EvaluationCriteria::where('id', $item['id'])
                ->where('enterprise_id', $user->enterprise_id)
                ->update(['display_order' => $item['display_order']]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Ordre mis à jour avec succès',
        ]);
    }

    /**
     * Dupliquer un critère
     */
    public function duplicate(int $id): JsonResponse
    {
        $user = Auth::user();
        $original = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        $copy = $original->replicate(['ref']);
        $copy->name = $original->name . ' (copie)';
        $copy->created_by = $user->id;
        $copy->updated_by = null;
        $copy->save();

        return response()->json([
            'success' => true,
            'message' => 'Critère dupliqué avec succès',
            'data' => $copy->load(['site', 'createdBy']),
        ], 201);
    }

    /**
     * Liste des catégories existantes
     */
    public function categories(Request $request): JsonResponse
    {
        $user = Auth::user();

        $categories = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Activer/Désactiver un critère
     */
    public function toggleActive(int $id): JsonResponse
    {
        $user = Auth::user();
        $criteria = EvaluationCriteria::where('enterprise_id', $user->enterprise_id)
            ->findOrFail($id);

        $criteria->is_active = !$criteria->is_active;
        $criteria->updated_by = $user->id;
        $criteria->save();

        return response()->json([
            'success' => true,
            'message' => $criteria->is_active ? 'Critère activé' : 'Critère désactivé',
            'data' => $criteria,
        ]);
    }
}
