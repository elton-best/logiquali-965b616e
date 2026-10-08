<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VerificationReglementaire;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class VerificationReglementaireController extends Controller
{
    public function index(Request $request)
    {
        $query = VerificationReglementaire::with(['equipement.site']);

        if ($request->has('equipement_id')) {
            $query->where('equipement_id', $request->equipement_id);
        }

        if ($request->has('type_verification')) {
            $query->where('type_verification', $request->type_verification);
        }

        if ($request->has('resultat')) {
            $query->where('resultat', $request->resultat);
        }

        return response()->json($query->orderBy('date_prochaine_verification')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'equipement_id' => 'required|exists:equipements,id',
            'type_verification' => 'required|string',
            'organisme_agree' => 'required|string|max:255',
            'numero_rapport' => 'nullable|string|max:255|unique:verifications_reglementaires',
            'date_verification' => 'required|date',
            'date_prochaine_verification' => 'required|date|after:date_verification',
            'resultat' => 'required|in:conforme,non_conforme,reserve',
            'observations' => 'nullable|string',
            'reserves' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'cout' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('document');

        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('vgp', 'local');
        }

        $verification = VerificationReglementaire::create($data);

        return response()->json($verification->load('equipement'), 201);
    }

    public function show($id)
    {
        $verification = VerificationReglementaire::with('equipement')->findOrFail($id);
        return response()->json($verification);
    }

    public function update(Request $request, $id)
    {
        $verification = VerificationReglementaire::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date_prochaine_verification' => 'sometimes|date',
            'resultat' => 'sometimes|in:conforme,non_conforme,reserve',
            'observations' => 'nullable|string',
            'reserves' => 'nullable|string',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'cout' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('document');

        if ($request->hasFile('document')) {
            if ($verification->document_path) {
                Storage::disk('local')->delete($verification->document_path);
            }
            $data['document_path'] = $request->file('document')->store('vgp', 'local');
        }

        $verification->update($data);

        return response()->json($verification->load('equipement'));
    }

    public function destroy($id)
    {
        $verification = VerificationReglementaire::findOrFail($id);
        
        if ($verification->document_path) {
            Storage::disk('local')->delete($verification->document_path);
        }

        $verification->delete();

        return response()->json(['message' => 'Vérification supprimée avec succès']);
    }

    public function alertes(Request $request)
    {
        $days = $request->input('days', 30);

        $echeanceProche = VerificationReglementaire::with(['equipement.site'])
            ->echeanceProche($days)
            ->get();

        $echues = VerificationReglementaire::with(['equipement.site'])
            ->echue()
            ->get();

        return response()->json([
            'echeance_proche' => $echeanceProche,
            'echues' => $echues,
        ]);
    }

    public function stats(Request $request)
    {
        $total = VerificationReglementaire::count();
        $conformes = VerificationReglementaire::where('resultat', 'conforme')->count();
        $nonConformes = VerificationReglementaire::where('resultat', 'non_conforme')->count();
        $echues = VerificationReglementaire::echue()->count();
        $echeanceProche = VerificationReglementaire::echeanceProche(30)->count();

        return response()->json([
            'total' => $total,
            'conformes' => $conformes,
            'non_conformes' => $nonConformes,
            'echues' => $echues,
            'echeance_proche' => $echeanceProche,
        ]);
    }
}
