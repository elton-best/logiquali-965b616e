<?php

namespace App\Modules\Support\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CodificationElement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CodificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:support.documents.read')->only(['index', 'show']);
        $this->middleware('permission:support.documents.configure_nomenclature')->only(['store']);
        $this->middleware('permission:support.documents.configure_nomenclature')->only(['update']);
        $this->middleware('permission:support.documents.configure_nomenclature')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $query = CodificationElement::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->boolean('actif'));
        }

        if ($user?->isSuperAdmin() && $request->filled('enterprise_id')) {
            $query->withoutGlobalScope('enterprise')
                ->where('enterprise_id', $request->integer('enterprise_id'));
        }

        return response()->json($query->orderBy('type')->orderBy('code')->get());
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:categorie,localisation',
            'code' => 'required|string|max:10',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
            'actif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $enterpriseId = $user?->enterprise_id;
        if (!$enterpriseId) {
            return response()->json([
                'message' => 'Entreprise introuvable pour cet utilisateur.',
            ], 422);
        }

        $exists = CodificationElement::where('enterprise_id', $enterpriseId)
            ->where('type', $request->type)
            ->where('code', $request->code)
            ->exists();

        if ($exists) {
            return response()->json(['message' => 'Ce code existe déjà pour ce type'], 409);
        }

        $element = CodificationElement::create([
            ...$request->all(),
            'enterprise_id' => $enterpriseId,
        ]);

        return response()->json($element, 201);
    }

    public function show($id)
    {
        $element = CodificationElement::findOrFail($id);
        return response()->json($element);
    }

    public function update(Request $request, $id)
    {
        $element = CodificationElement::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'type' => 'sometimes|in:categorie,localisation',
            'code' => 'sometimes|string|max:10',
            'libelle' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'actif' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('code') || $request->has('type')) {
            $exists = CodificationElement::where('enterprise_id', $element->enterprise_id)
                ->where('type', $request->type ?? $element->type)
                ->where('code', $request->code ?? $element->code)
                ->where('id', '!=', $id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Ce code existe déjà pour ce type'], 409);
            }
        }

        $element->update($request->all());

        return response()->json($element);
    }

    public function destroy($id)
    {
        $element = CodificationElement::findOrFail($id);

        if ($element->equipementsCategorie()->exists() || $element->equipementsLocalisation()->exists()) {
            return response()->json(['message' => 'Impossible de supprimer cet élément car il est utilisé'], 409);
        }

        $element->delete();

        return response()->json(['message' => 'Élément supprimé avec succès']);
    }
}
