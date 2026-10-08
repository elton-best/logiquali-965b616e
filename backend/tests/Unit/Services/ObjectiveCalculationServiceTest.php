<?php

namespace Tests\Unit\Services;

use App\Modules\Planning\Services\ObjectiveCalculationService;
use PHPUnit\Framework\TestCase;

class ObjectiveCalculationServiceTest extends TestCase
{
    private ObjectiveCalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ObjectiveCalculationService();
    }

    public function test_calculate_current_rate_ignores_future_months(): void
    {
        // Mois courant = 3 (Mars)
        // Janvier = 80, Février = 90, Mars = 70, Avril = 100 (futur, doit être ignoré)
        $realizations = [80, 90, 70, 100, 100, 100, 100, 100, 100, 100, 100, 100];

        $rate = $this->service->calculateCurrentRate($realizations, 'monthly', 3);

        // Moyenne (80 + 90 + 70) / 3 = 240 / 3 = 80.0
        $this->assertEquals(80.0, $rate);
    }

    public function test_calculate_current_rate_excludes_na_months(): void
    {
        // Mois courant = 4 (Avril)
        // M1 = 60, M2 = 'N/A', M3 = 80, M4 = 'NA'
        $realizations = [60, 'N/A', 80, 'NA'];

        $rate = $this->service->calculateCurrentRate($realizations, 'monthly', 4);

        // Moyenne (60 + 80) / 2 = 70.0 (les 2 N/A sont exclus du numérateur et dénominateur)
        $this->assertEquals(70.0, $rate);
    }

    public function test_calculate_current_rate_excludes_empty_or_null_months(): void
    {
        // Mois courant = 4 (Avril)
        // M1 = 50, M2 = null, M3 = '', M4 = 70
        $realizations = [50, null, '', 70];

        $rate = $this->service->calculateCurrentRate($realizations, 'monthly', 4);

        // Moyenne (50 + 70) / 2 = 60.0
        $this->assertEquals(60.0, $rate);
    }

    public function test_calculate_current_rate_caps_values_at_100_percent(): void
    {
        // Mois courant = 2
        // M1 = 150 (doit être plafonné à 100), M2 = 80
        $realizations = [150, 80];

        $rate = $this->service->calculateCurrentRate($realizations, 'monthly', 2);

        // Moyenne (100 + 80) / 2 = 90.0
        $this->assertEquals(90.0, $rate);
    }

    public function test_calculate_current_rate_returns_null_when_no_eligible_months(): void
    {
        // Mois courant = 3, tous les mois <= 3 sont N/A ou null
        $realizations = ['N/A', null, 'NA', 80, 90];

        $rate = $this->service->calculateCurrentRate($realizations, 'monthly', 3);

        $this->assertNull($rate);
    }

    public function test_calculate_current_rate_with_quarterly_frequency(): void
    {
        // Fréquence trimestrielle : Q1 (M1-M3), Q2 (M4-M6), Q3 (M7-M9), Q4 (M10-M12)
        // Mois courant = 5 (Mai, donc dans Q2). Max index trimestriel = 1 (Q1 et Q2 éligibles)
        // Q1 = 70, Q2 = 90, Q3 = 100 (futur, ignoré)
        $realizations = [70, 90, 100, 100];

        $rate = $this->service->calculateCurrentRate($realizations, 'quarterly', 5);

        // Moyenne (70 + 90) / 2 = 80.0
        $this->assertEquals(80.0, $rate);
    }

    public function test_is_month_locked_for_past_months_of_current_year(): void
    {
        $currentMonth = (int) now()->format('n');
        $currentYear = (int) now()->format('Y');

        if ($currentMonth > 1) {
            // Un mois antérieur au mois en cours doit être verrouillé
            $this->assertTrue($this->service->isMonthLocked($currentMonth - 1, $currentYear));
        }

        // Le mois en cours ne doit PAS être verrouillé
        $this->assertFalse($this->service->isMonthLocked($currentMonth, $currentYear));

        // Un mois passé d'une année antérieure doit être verrouillé
        $this->assertTrue($this->service->isMonthLocked(6, $currentYear - 1));
    }

    public function test_calculate_process_summary(): void
    {
        $objectives = [
            (object) [
                'process_id' => 10,
                'process_name' => 'Processus Achat',
                'period_realizations' => [90, 80],
                'measurement_frequency' => 'monthly',
            ],
            (object) [
                'process_id' => 10,
                'process_name' => 'Processus Achat',
                'period_realizations' => [70, 70],
                'measurement_frequency' => 'monthly',
            ],
            (object) [
                'process_id' => 20,
                'process_name' => 'Processus Production',
                'period_realizations' => [40, 40],
                'measurement_frequency' => 'monthly',
            ],
        ];

        $summaries = $this->service->calculateProcessSummary($objectives, 2);

        $this->assertCount(2, $summaries);

        // Processus 10 : Obj1 = 85%, Obj2 = 70% -> Moyenne = 77.5%
        $proc10 = collect($summaries)->firstWhere('process_id', 10);
        $this->assertNotNull($proc10);
        $this->assertEquals(77.5, $proc10['average_rate']);
        $this->assertEquals(2, $proc10['total_objectives']);
        $this->assertEquals(1, $proc10['achieved_count']); // Obj1 >= 80%

        // Processus 20 : Obj3 = 40% -> En vigilance (< 50%)
        $proc20 = collect($summaries)->firstWhere('process_id', 20);
        $this->assertNotNull($proc20);
        $this->assertEquals(40.0, $proc20['average_rate']);
        $this->assertEquals(1, $proc20['warning_count']);
    }

    public function test_calculate_strategic_axis_summary_multi_axis(): void
    {
        $objectives = [
            (object) [
                'strategic_axes' => ['Axe 1 - Satisfaction', 'Axe 2 - Qualité'],
                'period_realizations' => [80, 80],
                'measurement_frequency' => 'monthly',
            ],
            (object) [
                'strategic_axes' => ['Axe 2 - Qualité'],
                'period_realizations' => [60, 60],
                'measurement_frequency' => 'monthly',
            ],
        ];

        $summaries = $this->service->calculateStrategicAxisSummary($objectives, 2);

        // Axe 1 a 1 objectif (80%)
        $axis1 = collect($summaries)->firstWhere('axis', 'Axe 1 - Satisfaction');
        $this->assertEquals(80.0, $axis1['average_rate']);
        $this->assertEquals(1, $axis1['total_objectives']);

        // Axe 2 a 2 objectifs (80% et 60% -> moyenne 70.0%)
        $axis2 = collect($summaries)->firstWhere('axis', 'Axe 2 - Qualité');
        $this->assertEquals(70.0, $axis2['average_rate']);
        $this->assertEquals(2, $axis2['total_objectives']);
    }

    public function test_calculate_norm_summary_multi_norm(): void
    {
        $objectives = [
            (object) [
                'applicable_norms' => ['ISO 9001', 'ISO 14001'],
                'period_realizations' => [90, 90],
                'measurement_frequency' => 'monthly',
            ],
            (object) [
                'applicable_norms' => ['ISO 9001'],
                'period_realizations' => [70, 70],
                'measurement_frequency' => 'monthly',
            ],
        ];

        $summaries = $this->service->calculateNormSummary($objectives, 2);

        // ISO 9001 a 2 objectifs (90% et 70% -> moyenne 80.0%)
        $norm9001 = collect($summaries)->firstWhere('norm', 'ISO 9001');
        $this->assertEquals(80.0, $norm9001['average_rate']);
        $this->assertEquals(2, $norm9001['total_objectives']);

        // ISO 14001 a 1 objectif (90%)
        $norm14001 = collect($summaries)->firstWhere('norm', 'ISO 14001');
        $this->assertEquals(90.0, $norm14001['average_rate']);
        $this->assertEquals(1, $norm14001['total_objectives']);
    }

    public function test_calculate_system_summary(): void
    {
        $objectives = [
            (object) [
                'process_id' => 1,
                'strategic_axes' => ['Axe A'],
                'applicable_norms' => ['ISO 9001'],
                'period_realizations' => [100, 100],
                'measurement_frequency' => 'monthly',
            ],
            (object) [
                'process_id' => 2,
                'strategic_axes' => ['Axe A'],
                'applicable_norms' => ['ISO 9001'],
                'period_realizations' => [50, 50],
                'measurement_frequency' => 'monthly',
            ],
        ];

        $summary = $this->service->calculateSystemSummary($objectives, 2);

        // Moyenne globale : (100 + 50) / 2 = 75.0
        $this->assertEquals(75.0, $summary['system_average_rate']);
        $this->assertEquals(2, $summary['total_objectives']);
        $this->assertEquals(2, $summary['evaluated_objectives']);
        $this->assertEquals(1, $summary['achieved_count']); // 100% >= 80%
        $this->assertEquals(0, $summary['warning_count']);
        $this->assertNotEmpty($summary['by_process']);
        $this->assertNotEmpty($summary['by_axis']);
        $this->assertNotEmpty($summary['by_norm']);
    }
}

