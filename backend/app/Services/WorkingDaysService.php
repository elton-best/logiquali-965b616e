<?php

namespace App\Services;

use App\Models\PublicHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class WorkingDaysService
{
    /**
     * Vérifier si une date est un jour ouvré
     * (exclut weekends et jours fériés)
     */
    public function isWorkingDay(Carbon $date, int $enterpriseId): bool
    {
        // Samedi (6) ou dimanche (0)
        if ($date->isWeekend()) {
            return false;
        }

        // Vérifier si c'est un jour férié
        return !$this->isPublicHoliday($date, $enterpriseId);
    }

    /**
     * Vérifier si une date est un jour férié
     */
    public function isPublicHoliday(Carbon $date, int $enterpriseId): bool
    {
        $cacheKey = "public_holiday:{$enterpriseId}:{$date->format('Y-m-d')}";

        return Cache::remember($cacheKey, now()->endOfDay(), function () use ($date, $enterpriseId) {
            // Chercher un jour férié exact ou récurrent
            return PublicHoliday::where('enterprise_id', $enterpriseId)
                ->where(function ($query) use ($date) {
                    // Jour exact
                    $query->where('date', $date->format('Y-m-d'));

                    // Jour récurrent (même mois-jour chaque année)
                    $query->orWhere(function ($q) use ($date) {
                        $q->where('recurring', true)
                            ->whereMonth('date', $date->month)
                            ->whereDay('date', $date->day);
                    });
                })
                ->exists();
        });
    }

    /**
     * Calculer si aujourd'hui est dans la fenêtre d'activation du bouton "Suivi"
     * basée sur la fréquence et la date limite
     */
    public function isInWindow(
        ?Carbon $deadline,
        string $frequency,
        Carbon $today = null,
        int $enterpriseId = null
    ): bool {
        $today = $today ?? Carbon::now();
        
        // Pas de deadline = pas de fenêtre
        if (!$deadline) {
            return false;
        }

        // Fenêtres en jours ouvrés selon la fréquence
        $windowDays = match ($frequency) {
            'ponctuelle' => 10,
            'hebdomadaire' => 3,
            'mensuelle' => 5,
            'trimestrielle' => 10,
            'semestrielle' => 15,
            'annuelle' => 20,
            default => 10,
        };

        $startDate = $this->addWorkingDays($deadline, -$windowDays, $enterpriseId);
        $endDate = $this->addWorkingDays($deadline, $windowDays, $enterpriseId);

        return $today->between($startDate, $endDate);
    }

    /**
     * Ajouter ou soustraire N jours ouvrés à une date
     * Retourne une date décalée du nombre exact de jours ouvrés
     */
    public function addWorkingDays(
        Carbon $date,
        int $days,
        ?int $enterpriseId = null
    ): Carbon {
        $result = $date->clone();
        $step = $days < 0 ? -1 : 1;
        $remaining = abs($days);

        while ($remaining > 0) {
            $result->addDay($step);
            
            if ($enterpriseId && !$this->isWorkingDay($result, $enterpriseId)) {
                // C'est un weekend ou un jour férié, on ne compte pas
                continue;
            } elseif (!$enterpriseId) {
                // Pas d'enterprise, vérifier juste weekends
                if ($result->isWeekend()) {
                    continue;
                }
            }

            $remaining--;
        }

        return $result;
    }

    /**
     * Compter le nombre de jours ouvrés entre deux dates
     */
    public function countWorkingDays(
        Carbon $startDate,
        Carbon $endDate,
        ?int $enterpriseId = null
    ): int {
        $count = 0;
        $current = $startDate->clone();

        while ($current <= $endDate) {
            if ($this->isWorkingDay($current, $enterpriseId ?? 1)) {
                $count++;
            }
            $current->addDay();
        }

        return $count;
    }
}
