<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Ipe;
use App\Models\IpeValeur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class IpeController extends Controller
{
    public function index(Request $request)
    {
        $query = Ipe::with('site')
            ->where('enterprise_id', $request->user()->enterprise_id);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('actif')) {
            $query->where('actif', $request->actif);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'formule_calcul' => 'required|string|max:255',
            'unite' => 'required|string|max:50',
            'valeur_reference' => 'nullable|numeric',
            'objectif_cible' => 'nullable|numeric',
            'date_reference' => 'nullable|date',
            'periodicite' => 'required|in:journalier,hebdomadaire,mensuel,trimestriel,annuel',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['enterprise_id'] = $request->user()->enterprise_id;

        $ipe = Ipe::create($data);

        return response()->json($ipe->load('site'), 201);
    }

    public function show($id)
    {
        $ipe = Ipe::with(['site', 'valeurs'])->findOrFail($id);
        return response()->json($ipe);
    }

    public function update(Request $request, $id)
    {
        $ipe = Ipe::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'valeur_reference' => 'nullable|numeric',
            'objectif_cible' => 'nullable|numeric',
            'actif' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $ipe->update($request->all());

        return response()->json($ipe->load('site'));
    }

    public function destroy($id)
    {
        $ipe = Ipe::findOrFail($id);
        $ipe->delete();

        return response()->json(['message' => 'IPE supprimé']);
    }

    public function addValeur(Request $request, $id)
    {
        $ipe = Ipe::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after:periode_debut',
            'valeur' => 'required|numeric',
            'commentaire' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['ipe_id'] = $id;

        if ($ipe->valeur_reference) {
            $data['ecart_reference'] = $data['valeur'] - $ipe->valeur_reference;
        }

        if ($ipe->objectif_cible) {
            $data['ecart_objectif'] = $data['valeur'] - $ipe->objectif_cible;
        }

        $valeur = IpeValeur::create($data);

        return response()->json($valeur, 201);
    }

    public function valeurs(Request $request, $id)
    {
        $valeurs = IpeValeur::where('ipe_id', $id)
            ->orderBy('periode_debut', 'desc')
            ->get();

        return response()->json($valeurs);
    }

    public function tendance(Request $request, $id)
    {
        $valeurs = IpeValeur::where('ipe_id', $id)
            ->orderBy('periode_debut')
            ->get()
            ->map(function ($v) {
                return [
                    'periode' => $v->periode_debut->format('Y-m'),
                    'valeur' => $v->valeur,
                    'ecart_reference' => $v->ecart_reference,
                    'ecart_objectif' => $v->ecart_objectif,
                ];
            });

        return response()->json($valeurs);
    }

    public function stats(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;

        $total = Ipe::where('enterprise_id', $enterpriseId)->count();
        $actifs = Ipe::where('enterprise_id', $enterpriseId)->actif()->count();

        return response()->json([
            'total' => $total,
            'actifs' => $actifs,
        ]);
    }
}
