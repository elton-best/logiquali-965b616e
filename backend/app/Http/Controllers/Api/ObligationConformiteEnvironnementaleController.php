<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObligationConformiteEnvironnementale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ObligationConformiteEnvironnementaleController extends Controller
{
    public function index(Request $request)
    {
        $query = ObligationConformiteEnvironnementale::with('site')
            ->where('enterprise_id', $request->user()->enterprise_id);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        if ($request->has('statut_conformite')) {
            $query->where('statut_conformite', $request->statut_conformite);
        }

        return response()->json($query->orderBy('date_prochain_controle')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'type' => 'required|in:reglementaire,volontaire,contractuelle',
            'reference' => 'required|string|max:255',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'autorite_competente' => 'nullable|string|max:255',
            'date_application' => 'nullable|date',
            'periodicite_controle' => 'nullable|in:mensuel,trimestriel,semestriel,annuel,ponctuel',
            'date_prochain_controle' => 'nullable|date',
            'statut_conformite' => 'required|in:conforme,non_conforme,en_cours,non_applicable',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('document');
        $data['enterprise_id'] = $request->user()->enterprise_id;

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('obligations_environnementales', 'local');
        }

        $obligation = ObligationConformiteEnvironnementale::create($data);

        return response()->json($obligation->load('site'), 201);
    }

    public function show($id)
    {
        $obligation = ObligationConformiteEnvironnementale::with('site')->findOrFail($id);
        return response()->json($obligation);
    }

    public function update(Request $request, $id)
    {
        $obligation = ObligationConformiteEnvironnementale::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date_prochain_controle' => 'nullable|date',
            'statut_conformite' => 'sometimes|in:conforme,non_conforme,en_cours,non_applicable',
            'preuves_conformite' => 'nullable|string',
            'actions_correctives' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('document');

        if ($request->hasFile('document')) {
            if ($obligation->document_path) {
                Storage::disk('local')->delete($obligation->document_path);
            }
            $data['document_path'] = $request->file('document')->store('obligations_environnementales', 'local');
        }

        $obligation->update($data);

        return response()->json($obligation->load('site'));
    }

    public function destroy($id)
    {
        $obligation = ObligationConformiteEnvironnementale::findOrFail($id);
        
        if ($obligation->document_path) {
            Storage::disk('local')->delete($obligation->document_path);
        }

        $obligation->delete();

        return response()->json(['message' => 'Obligation supprimée']);
    }

    public function alertes(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;
        $days = $request->input('days', 30);

        $echeanceProche = ObligationConformiteEnvironnementale::with('site')
            ->where('enterprise_id', $enterpriseId)
            ->echeanceProche($days)
            ->get();

        $nonConformes = ObligationConformiteEnvironnementale::with('site')
            ->where('enterprise_id', $enterpriseId)
            ->nonConforme()
            ->get();

        return response()->json([
            'echeance_proche' => $echeanceProche,
            'non_conformes' => $nonConformes,
        ]);
    }

    public function stats(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;

        $total = ObligationConformiteEnvironnementale::where('enterprise_id', $enterpriseId)->count();
        $conformes = ObligationConformiteEnvironnementale::where('enterprise_id', $enterpriseId)
            ->where('statut_conformite', 'conforme')->count();
        $nonConformes = ObligationConformiteEnvironnementale::where('enterprise_id', $enterpriseId)
            ->where('statut_conformite', 'non_conforme')->count();

        return response()->json([
            'total' => $total,
            'conformes' => $conformes,
            'non_conformes' => $nonConformes,
        ]);
    }
}
