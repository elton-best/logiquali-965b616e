<?php

namespace App\Modules\Hse\Controllers;

use App\Http\Controllers\Controller;
use App\Models\EpiCatalogue;
use App\Models\EpiStock;
use App\Models\EpiMouvement;
use App\Models\EpiAttribution;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EpiController extends Controller
{
    // Catalogue
    public function catalogue()
    {
        return response()->json(EpiCatalogue::all());
    }

    public function storeCatalogue(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'categorie' => 'required|in:tete,yeux_visage,ouie,voies_respiratoires,mains_bras,pieds_jambes,corps,chute_hauteur',
            'designation' => 'required|string|max:255',
            'reference_fabricant' => 'nullable|string|max:255',
            'norme_ce' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $epi = EpiCatalogue::create($request->all());
        return response()->json($epi, 201);
    }

    // Stocks
    public function stocks(Request $request)
    {
        $query = EpiStock::with(['epiCatalogue', 'site'])
            ->where('enterprise_id', $request->user()->enterprise_id);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('alerte')) {
            $query->alerteSeuil();
        }

        return response()->json($query->get());
    }

    public function storeStock(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'epi_catalogue_id' => 'required|exists:epi_catalogue,id',
            'taille' => 'nullable|string|max:50',
            'quantite_stock' => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:0',
            'prix_unitaire' => 'nullable|numeric|min:0',
            'emplacement_stockage' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['enterprise_id'] = $request->user()->enterprise_id;

        $stock = EpiStock::create($data);

        return response()->json($stock->load(['epiCatalogue', 'site']), 201);
    }

    public function updateStock(Request $request, $id)
    {
        $stock = EpiStock::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'quantite_stock' => 'sometimes|integer|min:0',
            'seuil_alerte' => 'sometimes|integer|min:0',
            'prix_unitaire' => 'nullable|numeric|min:0',
            'emplacement_stockage' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stock->update($request->all());

        return response()->json($stock->load(['epiCatalogue', 'site']));
    }

    // Mouvements
    public function mouvements(Request $request, $stockId)
    {
        $mouvements = EpiMouvement::with('user')
            ->where('epi_stock_id', $stockId)
            ->orderBy('date_mouvement', 'desc')
            ->get();

        return response()->json($mouvements);
    }

    public function storeMouvement(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epi_stock_id' => 'required|exists:epi_stocks,id',
            'type' => 'required|in:entree,sortie,inventaire,rebut',
            'quantite' => 'required|integer|min:1',
            'user_id' => 'nullable|exists:users,id',
            'motif' => 'nullable|string|max:255',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $stock = EpiStock::findOrFail($request->epi_stock_id);

            $mouvement = EpiMouvement::create([
                ...$request->all(),
                'date_mouvement' => now(),
            ]);

            // Mise à jour stock
            if ($request->type === 'entree' || $request->type === 'inventaire') {
                $stock->increment('quantite_stock', $request->quantite);
            } elseif ($request->type === 'sortie' || $request->type === 'rebut') {
                $stock->decrement('quantite_stock', $request->quantite);
            }

            DB::commit();

            return response()->json($mouvement->load('user'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'enregistrement du mouvement'], 500);
        }
    }

    // Attributions
    public function attributions(Request $request)
    {
        $query = EpiAttribution::with(['epiStock.epiCatalogue', 'user']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->get());
    }

    public function storeAttribution(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'epi_stock_id' => 'required|exists:epi_stocks,id',
            'user_id' => 'required|exists:users,id',
            'quantite_attribuee' => 'required|integer|min:1',
            'date_renouvellement_prevue' => 'nullable|date',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        DB::beginTransaction();
        try {
            $stock = EpiStock::findOrFail($request->epi_stock_id);

            if ($stock->quantite_stock < $request->quantite_attribuee) {
                return response()->json(['error' => 'Stock insuffisant'], 422);
            }

            $attribution = EpiAttribution::create([
                ...$request->all(),
                'date_attribution' => now(),
                'statut' => 'en_cours',
            ]);

            // Créer mouvement sortie
            EpiMouvement::create([
                'epi_stock_id' => $request->epi_stock_id,
                'type' => 'sortie',
                'quantite' => $request->quantite_attribuee,
                'user_id' => $request->user_id,
                'motif' => 'Attribution EPI',
                'date_mouvement' => now(),
            ]);

            $stock->decrement('quantite_stock', $request->quantite_attribuee);

            DB::commit();

            return response()->json($attribution->load(['epiStock.epiCatalogue', 'user']), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Erreur lors de l\'attribution'], 500);
        }
    }

    public function stats(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;

        $totalStocks = EpiStock::where('enterprise_id', $enterpriseId)->count();
        $alertes = EpiStock::where('enterprise_id', $enterpriseId)->alerteSeuil()->count();
        $attributionsActives = EpiAttribution::whereHas('epiStock', function ($q) use ($enterpriseId) {
            $q->where('enterprise_id', $enterpriseId);
        })->where('statut', 'en_cours')->count();

        return response()->json([
            'total_stocks' => $totalStocks,
            'alertes_seuil' => $alertes,
            'attributions_actives' => $attributionsActives,
        ]);
    }
}
