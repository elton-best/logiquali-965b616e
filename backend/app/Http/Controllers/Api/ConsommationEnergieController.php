<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConsommationEnergie;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConsommationEnergieController extends Controller
{
    public function index(Request $request)
    {
        $query = ConsommationEnergie::with(['equipement', 'site'])
            ->where('enterprise_id', $request->user()->enterprise_id);

        if ($request->has('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->has('equipement_id')) {
            $query->where('equipement_id', $request->equipement_id);
        }

        if ($request->has('type_energie')) {
            $query->where('type_energie', $request->type_energie);
        }

        if ($request->has('periode_debut') && $request->has('periode_fin')) {
            $query->periode($request->periode_debut, $request->periode_fin);
        }

        return response()->json($query->orderBy('periode_debut', 'desc')->get());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'site_id' => 'required|exists:sites,id',
            'equipement_id' => 'nullable|exists:equipements,id',
            'periode_debut' => 'required|date',
            'periode_fin' => 'required|date|after:periode_debut',
            'type_energie' => 'required|in:electricite,gaz,fuel,vapeur,air_comprime,autre',
            'valeur_consommation' => 'required|numeric|min:0',
            'unite' => 'required|in:kwh,m3,litres,tonnes',
            'mode_saisie' => 'required|in:releve_reel,estimation,import_compteur',
            'source_donnee' => 'nullable|string|max:255',
            'cout_euro' => 'nullable|numeric|min:0',
            'emission_co2_kg' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->all();
        $data['enterprise_id'] = $request->user()->enterprise_id;

        $consommation = ConsommationEnergie::create($data);

        return response()->json($consommation->load(['equipement', 'site']), 201);
    }

    public function show($id)
    {
        $consommation = ConsommationEnergie::with(['equipement', 'site'])->findOrFail($id);
        return response()->json($consommation);
    }

    public function update(Request $request, $id)
    {
        $consommation = ConsommationEnergie::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'valeur_consommation' => 'sometimes|numeric|min:0',
            'cout_euro' => 'nullable|numeric|min:0',
            'emission_co2_kg' => 'nullable|numeric|min:0',
            'observations' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $consommation->update($request->all());

        return response()->json($consommation->load(['equipement', 'site']));
    }

    public function destroy($id)
    {
        $consommation = ConsommationEnergie::findOrFail($id);
        $consommation->delete();

        return response()->json(['message' => 'Consommation supprimée']);
    }

    public function stats(Request $request)
    {
        $enterpriseId = $request->user()->enterprise_id;
        $siteId = $request->input('site_id');

        $query = ConsommationEnergie::where('enterprise_id', $enterpriseId);
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $totalKwh = $query->where('unite', 'kwh')->sum('valeur_consommation');
        $totalCout = $query->sum('cout_euro');
        $totalCo2 = $query->sum('emission_co2_kg');

        $parType = ConsommationEnergie::where('enterprise_id', $enterpriseId)
            ->selectRaw('type_energie, SUM(valeur_consommation) as total')
            ->groupBy('type_energie')
            ->get();

        return response()->json([
            'total_kwh' => $totalKwh,
            'total_cout' => $totalCout,
            'total_co2_kg' => $totalCo2,
            'par_type' => $parType,
        ]);
    }

    public function importCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,txt',
            'site_id' => 'required|exists:sites,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);
        $siteId = (int) $request->input('site_id');

        if ($enterpriseId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Entreprise introuvable pour cet utilisateur.',
            ], 422);
        }

        $site = Site::query()->where('id', $siteId)->where('enterprise_id', $enterpriseId)->first();
        if (!$site) {
            return response()->json([
                'success' => false,
                'message' => 'Site non autorisé pour cet utilisateur.',
            ], 403);
        }

        if (!empty($user?->site_id) && (int) $user->site_id !== $siteId) {
            return response()->json([
                'success' => false,
                'message' => 'Site non autorisé pour cet utilisateur.',
            ], 403);
        }

        $file = $request->file('file');
        $path = $file->getRealPath();
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de lire le fichier CSV.',
            ], 422);
        }

        $header = fgetcsv($handle, 0, ';');
        $delimiter = ';';

        if (!is_array($header) || count($header) <= 1) {
            rewind($handle);
            $header = fgetcsv($handle, 0, ',');
            $delimiter = ',';
        }

        if (!is_array($header) || count($header) < 5) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'En-têtes CSV invalides.',
                'errors' => ['Colonnes minimales requises: periode_debut, periode_fin, type_energie, valeur_consommation, unite'],
            ], 422);
        }

        $normalize = static function (string $value): string {
            return trim(mb_strtolower($value));
        };

        $headerIndex = [];
        foreach ($header as $idx => $columnName) {
            $key = $normalize((string) $columnName);
            $headerIndex[$key] = $idx;
        }

        $required = ['periode_debut', 'periode_fin', 'type_energie', 'valeur_consommation', 'unite'];
        $missing = array_values(array_filter($required, static fn (string $column) => !array_key_exists($column, $headerIndex)));
        if (!empty($missing)) {
            fclose($handle);
            return response()->json([
                'success' => false,
                'message' => 'Colonnes obligatoires manquantes.',
                'errors' => $missing,
            ], 422);
        }

        $allowedTypes = ['electricite', 'gaz', 'fuel', 'vapeur', 'air_comprime', 'autre'];
        $allowedUnits = ['kwh', 'm3', 'litres', 'tonnes'];
        $allowedModes = ['releve_reel', 'estimation', 'import_compteur'];

        $created = 0;
        $failed = 0;
        $errors = [];
        $report = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rowNumber++;
            if (!is_array($row) || count(array_filter($row, static fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }

            try {
                $value = static function (string $column) use ($headerIndex, $row): ?string {
                    $index = $headerIndex[$column] ?? null;
                    if ($index === null) return null;
                    $raw = $row[$index] ?? null;
                    if ($raw === null) return null;
                    $trimmed = trim((string) $raw);
                    return $trimmed === '' ? null : $trimmed;
                };

                $periodeDebut = $value('periode_debut');
                $periodeFin = $value('periode_fin');
                $typeEnergie = mb_strtolower((string) ($value('type_energie') ?? ''));
                $unite = mb_strtolower((string) ($value('unite') ?? ''));
                $modeSaisie = mb_strtolower((string) ($value('mode_saisie') ?? 'import_compteur'));

                $valeurRaw = str_replace(',', '.', (string) ($value('valeur_consommation') ?? ''));
                $coutRaw = str_replace(',', '.', (string) ($value('cout_euro') ?? ''));
                $co2Raw = str_replace(',', '.', (string) ($value('emission_co2_kg') ?? ''));

                if (!$periodeDebut || !$periodeFin) {
                    throw new \InvalidArgumentException('Période début/fin obligatoire.');
                }
                if (!in_array($typeEnergie, $allowedTypes, true)) {
                    throw new \InvalidArgumentException('type_energie invalide.');
                }
                if (!in_array($unite, $allowedUnits, true)) {
                    throw new \InvalidArgumentException('unite invalide.');
                }
                if (!in_array($modeSaisie, $allowedModes, true)) {
                    throw new \InvalidArgumentException('mode_saisie invalide.');
                }
                if (!is_numeric($valeurRaw) || (float) $valeurRaw < 0) {
                    throw new \InvalidArgumentException('valeur_consommation invalide.');
                }

                ConsommationEnergie::create([
                    'enterprise_id' => $enterpriseId,
                    'site_id' => $siteId,
                    'equipement_id' => null,
                    'periode_debut' => $periodeDebut,
                    'periode_fin' => $periodeFin,
                    'type_energie' => $typeEnergie,
                    'valeur_consommation' => (float) $valeurRaw,
                    'unite' => $unite,
                    'mode_saisie' => $modeSaisie,
                    'source_donnee' => $value('source_donnee') ?? 'import_csv',
                    'cout_euro' => is_numeric($coutRaw) ? (float) $coutRaw : null,
                    'emission_co2_kg' => is_numeric($co2Raw) ? (float) $co2Raw : null,
                    'observations' => $value('observations'),
                ]);

                $created++;
                $report[] = [
                    'row' => $rowNumber,
                    'status' => 'created',
                    'message' => 'Consommation créée',
                ];
            } catch (\Throwable $lineError) {
                $failed++;
                $message = "Ligne {$rowNumber}: {$lineError->getMessage()}";
                $errors[] = $message;
                $report[] = [
                    'row' => $rowNumber,
                    'status' => 'failed',
                    'message' => $message,
                ];
            }
        }

        fclose($handle);

        $total = $created + $failed;
        $httpStatus = $failed > 0 ? 422 : 200;

        return response()->json([
            'success' => $failed === 0,
            'message' => "{$created} consommation(s) importée(s), {$failed} en échec",
            'summary' => [
                'total' => $total,
                'created' => $created,
                'failed' => $failed,
            ],
            'errors' => $errors,
            'report' => $report,
        ], $httpStatus);
    }
}
