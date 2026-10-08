<?php

namespace App\Helpers;

use Carbon\Carbon;

class SubscriptionHelper
{
    /**
     * Calcule les mois dus selon règle : 30 jours = 1 mois
     * Si > 15 jours écoulés dans une période de 30 jours, le mois est dû
     */
    public static function calculateMonthsDue(Carbon $expiredDate, Carbon $renewDate, float $monthlyPrice): array
    {
        $totalDays = $expiredDate->diffInDays($renewDate);
        
        // Calcul : diviser par 30 jours, arrondir à l'entier inférieur
        // Si reste > 15 jours, ajouter 1 mois
        $fullMonths = (int) floor($totalDays / 30);
        $remainingDays = $totalDays % 30;
        
        $monthsDue = $fullMonths;
        if ($remainingDays > 15) {
            $monthsDue++;
        }
        
        // Minimum 1 mois (le mois en cours)
        $monthsDue = max(1, $monthsDue);
        
        return [
            'months_due' => $monthsDue,
            'days_elapsed' => $totalDays,
            'amount' => $monthsDue * $monthlyPrice,
            'new_start_date' => $expiredDate->toDateString(),
            'new_expiration_date' => $expiredDate->copy()->addMonths($monthsDue)->toDateString(),
        ];
    }
}
