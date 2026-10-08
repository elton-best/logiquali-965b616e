<?php

namespace App\Services;

use App\Models\Indicateur;
use Illuminate\Support\Facades\DB;

class IndicateurService
{
    public function create(array $data): Indicateur
    {
        return DB::transaction(function () use ($data) {
            $indicateur = Indicateur::create([
                'ref' => $this->generateReference(),
                'site_id' => $data['site_id'],
                'process_id' => $data['process_id'] ?? null,
                'code' => $data['code'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'formula' => $data['formula'] ?? null,
                'unit' => $data['unit'] ?? null,
                'type' => $data['type'] ?? 'kpi',
                'frequency' => $data['frequency'] ?? 'monthly',
                'target_value' => $data['target_value'] ?? null,
                'min_threshold' => $data['min_threshold'] ?? null,
                'max_threshold' => $data['max_threshold'] ?? null,
                'alert_threshold' => $data['alert_threshold'] ?? null,
                'responsible_id' => $data['responsible_id'] ?? null,
            ]);

            if (isset($data['axes'])) {
                $indicateur->syncAxes($data['axes']);
            }

            activity()
                ->performedOn($indicateur)
                ->causedBy(auth()->user())
                ->log('Indicateur créé');

            return $indicateur->load(['axes', 'process']);
        });
    }

    public function update(Indicateur $indicateur, array $data): Indicateur
    {
        $indicateur->update($data);

        if (isset($data['axes'])) {
            $indicateur->syncAxes($data['axes']);
        }

        activity()
            ->performedOn($indicateur)
            ->causedBy(auth()->user())
            ->log('Indicateur mis à jour');

        return $indicateur->fresh();
    }

    public function addValue(Indicateur $indicateur, float $value, ?string $date = null, ?string $note = null): Indicateur
    {
        $indicateur->addValue($value, $date, $note);

        // Vérifier les seuils et créer alertes si nécessaire
        if ($indicateur->needsAlert()) {
            $this->triggerAlert($indicateur, $value);
        }

        activity()
            ->performedOn($indicateur)
            ->causedBy(auth()->user())
            ->log("Valeur ajoutée : {$value} {$indicateur->unit}");

        return $indicateur->fresh();
    }

    public function calculateValue(Indicateur $indicateur, array $inputs = []): ?float
    {
        if (!$indicateur->formula) {
            return null;
        }

        try {
            // Évaluation sécurisée avec FormulaParser
            $result = \App\Utils\FormulaParser::evaluate($indicateur->formula, $inputs);
            
            return $result;
        } catch (\Exception $e) {
            \Log::error("Erreur calcul indicateur {$indicateur->code}: {$e->getMessage()}");
            return null;
        }
    }

    public function getChartData(Indicateur $indicateur, ?int $limit = 12): array
    {
        $data = $indicateur->historical_data ?? [];
        
        // Trier par date et limiter
        usort($data, fn($a, $b) => strcmp($a['date'] ?? '', $b['date'] ?? ''));
        $data = array_slice($data, -$limit);

        return [
            'labels' => array_column($data, 'date'),
            'values' => array_column($data, 'value'),
            'target' => $indicateur->target_value,
            'min_threshold' => $indicateur->min_threshold,
            'max_threshold' => $indicateur->max_threshold,
        ];
    }

    public function checkThresholds(Indicateur $indicateur): array
    {
        $status = 'ok';
        $messages = [];

        if ($indicateur->current_value === null) {
            return ['status' => 'no_data', 'messages' => ['Aucune donnée']];
        }

        if ($indicateur->isAboveThreshold()) {
            $status = 'warning';
            $messages[] = "Seuil maximum dépassé ({$indicateur->max_threshold})";
        }

        if ($indicateur->isBelowThreshold()) {
            $status = 'warning';
            $messages[] = "Seuil minimum non atteint ({$indicateur->min_threshold})";
        }

        if ($indicateur->needsAlert()) {
            $status = 'alert';
            $messages[] = "Alerte : écart significatif de la cible";
        }

        return [
            'status' => $status,
            'messages' => $messages,
            'current_value' => $indicateur->current_value,
            'target_value' => $indicateur->target_value,
        ];
    }

    protected function triggerAlert(Indicateur $indicateur, float $value): void
    {
        // TODO: Implémenter système de notifications
        // Pour l'instant, log seulement
        \Log::warning("Indicateur {$indicateur->code} : Seuil d'alerte atteint", [
            'indicateur_id' => $indicateur->id,
            'value' => $value,
            'target' => $indicateur->target_value,
            'alert_threshold' => $indicateur->alert_threshold,
        ]);

        activity()
            ->performedOn($indicateur)
            ->log("⚠️ Alerte seuil atteint : {$value} {$indicateur->unit}");
    }

    public function getStatistics(array $filters = []): array
    {
        $query = Indicateur::query()->where('status', 'active');

        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['axes'])) {
            $query->withAnyAxe($filters['axes']);
        }

        $total = $query->count();
        $withAlerts = $query->get()->filter->needsAlert()->count();
        $aboveThreshold = $query->get()->filter->isAboveThreshold()->count();
        $belowThreshold = $query->get()->filter->isBelowThreshold()->count();
        $byType = $query->get()->groupBy('type')->map->count();

        return [
            'total' => $total,
            'with_alerts' => $withAlerts,
            'above_threshold' => $aboveThreshold,
            'below_threshold' => $belowThreshold,
            'by_type' => $byType,
        ];
    }

    protected function generateReference(): string
    {
        $year = now()->year;
        $lastIndicateur = Indicateur::where('ref', 'like', "KPI-{$year}-%")
            ->orderBy('ref', 'desc')
            ->first();

        if ($lastIndicateur) {
            $lastNumber = (int) substr($lastIndicateur->ref, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return sprintf('KPI-%d-%03d', $year, $newNumber);
    }
}
