<?php

namespace App\Modules\Evaluation\Controllers;

use App\Models\Document;
use App\Models\Site;
use App\Models\User;
use App\Services\DocumentSyncService;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientSatisfactionForm\StoreClientSatisfactionFormRequest;
use App\Http\Requests\ClientSatisfactionForm\UpdateClientSatisfactionFormRequest;
use App\Http\Resources\ClientSatisfactionFormResource;
use App\Models\ClientSatisfactionForm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Services\DocumentBrandingService;

class ClientSatisfactionFormController extends Controller
{
    /**
     * Liste des fiches de satisfaction
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = ClientSatisfactionForm::with(['site', 'reviewer']);

        // Filtrage par type d'utilisateur
        if ($user->isClientB()) {
            // Client B: uniquement leurs fiches
            $query->where('created_by', $user->id);
        } elseif ($user->enterprise_id) {
            // Client A: fiches des sites de leur entreprise
            $siteIds = \App\Models\Site::where('enterprise_id', $user->enterprise_id)->pluck('id');
            $query->whereIn('site_id', $siteIds);
        }

        // Filtres optionnels
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('satisfaction_level')) {
            $query->where('satisfaction_level', $request->satisfaction_level);
        }

        if ($request->has('year')) {
            $query->whereYear('survey_date', $request->year);
        }

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        // Recherche
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ref', 'like', "%{$search}%")
                    ->orWhere('client_name', 'like', "%{$search}%")
                    ->orWhere('recommendations', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 20);
        $forms = $query->latest('survey_date')->paginate($perPage);

        return ClientSatisfactionFormResource::collection($forms);
    }

    /**
     * Créer une nouvelle fiche
     */
    public function store(StoreClientSatisfactionFormRequest $request)
    {
        $user = $request->user();

        $data = $request->validated();
        $data['created_by'] = $user->id;

        // Si statut non fourni, mettre en brouillon
        if (!isset($data['status'])) {
            $data['status'] = 'draft';
        }

        $form = ClientSatisfactionForm::create($data);
        $form->load(['site', 'reviewer']);

        return new ClientSatisfactionFormResource($form);
    }

    /**
     * Afficher une fiche spécifique
     */
    public function show(ClientSatisfactionForm $clientSatisfactionForm)
    {
        $clientSatisfactionForm->load(['site', 'reviewer']);
        return new ClientSatisfactionFormResource($clientSatisfactionForm);
    }

    /**
     * Mettre à jour une fiche
     */
    public function update(UpdateClientSatisfactionFormRequest $request, ClientSatisfactionForm $clientSatisfactionForm)
    {
        // Vérifier que la fiche est modifiable (pas encore soumise ou examinée)
        if ($clientSatisfactionForm->status === 'reviewed') {
            return response()->json([
                'message' => 'Cette fiche ne peut plus être modifiée car elle a été examinée.',
            ], 403);
        }

        $clientSatisfactionForm->update($request->validated());
        $clientSatisfactionForm->load(['site', 'reviewer']);

        return new ClientSatisfactionFormResource($clientSatisfactionForm);
    }

    /**
     * Supprimer une fiche
     */
    public function destroy(ClientSatisfactionForm $clientSatisfactionForm)
    {
        // Seules les fiches en brouillon peuvent être supprimées
        if ($clientSatisfactionForm->status !== 'draft') {
            return response()->json([
                'message' => 'Seules les fiches en brouillon peuvent être supprimées.',
            ], 403);
        }

        $clientSatisfactionForm->delete();

        return response()->json([
            'message' => 'Fiche supprimée avec succès',
        ]);
    }

    /**
     * Soumettre une fiche
     */
    public function submit(ClientSatisfactionForm $clientSatisfactionForm)
    {
        if ($clientSatisfactionForm->status !== 'draft') {
            return response()->json([
                'message' => 'Cette fiche a déjà été soumise.',
            ], 400);
        }

        $clientSatisfactionForm->submit();
        $clientSatisfactionForm->load(['site', 'reviewer']);

        return new ClientSatisfactionFormResource($clientSatisfactionForm);
    }

    /**
     * Marquer une fiche comme examinée (pour les admins)
     */
    public function review(Request $request, ClientSatisfactionForm $clientSatisfactionForm)
    {
        $request->validate([
            'review_notes' => 'nullable|string',
        ]);

        if ($clientSatisfactionForm->status === 'reviewed') {
            return response()->json([
                'message' => 'Cette fiche a déjà été examinée.',
            ], 400);
        }

        $clientSatisfactionForm->markAsReviewed($request->user()->id);
        $clientSatisfactionForm->load(['site', 'reviewer']);

        return new ClientSatisfactionFormResource($clientSatisfactionForm);
    }

    /**
     * Statistiques des fiches de satisfaction
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        $query = ClientSatisfactionForm::query();

        // Filtrage par type d'utilisateur
        if ($user->isClientB()) {
            $query->where('created_by', $user->id);
        } elseif ($user->enterprise_id) {
            $siteIds = \App\Models\Site::where('enterprise_id', $user->enterprise_id)->pluck('id');
            $query->whereIn('site_id', $siteIds);
        }
        // info(" User ID: " . $user->id . " Type: " . $user->user_type);
        // info(" Requête SQL: " . $query->get());
        // info("Nombre de fiches: " . $query->count());
        // Filtrer par année si fournie
        $year = $request->get('year', date('Y'));
        if ($year !== null && $year !== '') {
            $query->whereYear('survey_date', $year);
        }

        if ($request->filled('site_id')) {
            $query->where('site_id', (int) $request->site_id);
        }

        // Calcul des statistiques

        $statistics = [
            'total' => $query->count(),
            'total_forms' => $query->count(),
            'drafts' => $query->clone()->where('status', 'draft')->count(),
            'submitted' => $query->clone()->where('status', 'submitted')->count(),
            'reviewed' => $query->clone()->where('status', 'reviewed')->count(),
            'by_status' => $query->clone()->select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status'),
            'by_satisfaction_level' => $query->clone()->select('satisfaction_level', DB::raw('count(*) as count'))
                ->groupBy('satisfaction_level')
                ->pluck('count', 'satisfaction_level'),
            'average_score' => round($query->clone()->avg('total_score') ?? 0, 2),
            'average_percentage' => round($query->clone()->avg('satisfaction_percentage') ?? 0, 2),
            'highest_score' => $query->clone()->max('total_score') ?? 0,
            'lowest_score' => $query->clone()->min('total_score') ?? 0,
            'trend' => 0, // Calculé plus tard
            'criteria_averages' => [
                'amabilite_ecoute' => round($query->clone()->avg('amabilite_ecoute') ?? 0, 2),
                'disponibilite_spontaneite' => round($query->clone()->avg('disponibilite_spontaneite') ?? 0, 2),
                'rapidite_traitement' => round($query->clone()->avg('rapidite_traitement') ?? 0, 2),
                'respect_delais' => round($query->clone()->avg('respect_delais') ?? 0, 2),
                'conformite_produits' => round($query->clone()->avg('conformite_produits') ?? 0, 2),
                'traitement_reclamations' => round($query->clone()->avg('traitement_reclamations') ?? 0, 2),
            ],
            'monthly_trend' => $query->clone()
                ->select(
                    DB::raw('EXTRACT(MONTH FROM survey_date) as month'),
                    DB::raw('COUNT(*) as total'),
                    DB::raw('AVG(satisfaction_percentage) as avg_satisfaction')
                )
                ->groupBy(DB::raw('EXTRACT(MONTH FROM survey_date)'))
                ->orderBy('month')
                ->get()
                ->map(function ($item) {
                    return [
                        'month' => (int) $item->month,
                        'total' => (int) $item->total,
                        'avg_satisfaction' => round($item->avg_satisfaction ?? 0, 2),
                    ];
                }),
        ];

        // info(" liste des statistiques: " . json_encode($statistics));

        return response()->json($statistics);
    }

    /**
     * Exporter une fiche de satisfaction en PDF
     */
    public function exportPdf(Request $request, $id)
    {
        $user = $request->user();

        $form = ClientSatisfactionForm::with(['site', 'reviewer'])->findOrFail($id);

        // Vérifier les autorisations
        if ($user->isClientB() && $form->created_by !== $user->id) {
            abort(403, 'Accès non autorisé');
        }

        $enterprise = $form->site?->enterprise ?? $user?->enterprise;
        $branding = $enterprise
            ? app(DocumentBrandingService::class)->getPdfBranding($enterprise, 'client_satisfaction_form', (int) $form->id)
            : null;

        $pdf = Pdf::loadView('pdf.satisfaction-form', [
            'form' => $form,
            'branding' => $branding,
        ]);

        $filename = 'Fiche_Satisfaction_' . $form->ref . '.pdf';
        $relativePath = 'exports/' . $filename;
        $tempPath = Storage::disk('local')->path($relativePath);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }
        $pdf->save($tempPath);

        $document = null;
        if ((int) $form->site_id > 0) {
            $document = app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => (int) $form->site_id,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'client_satisfaction_form_pdf',
                'source_type' => 'client_satisfaction_form',
                'source_id' => $form->id,
                'source_updated_at' => $form->updated_at?->toISOString(),
                'title' => 'Fiche de satisfaction ' . ($form->ref ?? '#'.$form->id),
                'description' => 'Fiche de satisfaction client exportée en PDF.',
                'file_source_path' => $tempPath,
                'file_extension' => 'pdf',
                'created_by' => $user?->id,
                'type' => 'ENR',
                'force_new' => true,
            ]);
        }

        $response = response()->download($tempPath, $filename)->deleteFileAfterSend(true);
        if ($document) {
            $response->headers->set('X-Generated-Document-Id', (string) $document->id);
        }
        return $response;
    }
}
