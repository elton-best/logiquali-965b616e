<?php

namespace App\Console\Commands;

use App\Models\Plan;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ArchivePlanSM extends Command
{
    protected $signature = 'plans:archive-sm {--year= : Year to archive (defaults to current year)}';

    protected $description = 'Archive the current SM plan and seed the next year plan';

    public function handle(): int
    {
        $year = (int) ($this->option('year') ?: now()->year);
        $nextYear = $year + 1;

        $plansBySite = Plan::query()
            ->where('type', 'smq')
            ->where('year', $year)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('site_id');

        if ($plansBySite->isEmpty()) {
            $this->info("Aucun plan SMQ trouvé pour {$year}.");

            return self::SUCCESS;
        }

        $archivedCount = 0;
        $createdCount = 0;

        DB::transaction(function () use ($plansBySite, $year, $nextYear, &$archivedCount, &$createdCount): void {
            foreach ($plansBySite as $siteId => $sitePlans) {
                $latestPlan = $sitePlans->first();
                if (!$latestPlan instanceof Plan) {
                    continue;
                }

                foreach ($sitePlans as $plan) {
                    if ($plan->status !== 'archived') {
                        $plan->update(['status' => 'archived']);
                    }

                    $archivedCount++;
                }

                $nextPlan = Plan::query()->firstOrCreate(
                    [
                        'site_id' => (int) $siteId,
                        'type' => 'smq',
                        'year' => $nextYear,
                    ],
                    [
                        'title' => $this->buildNextYearTitle((string) $latestPlan->title, $year, $nextYear),
                        'content' => $this->buildNextYearContent($latestPlan->content),
                        'file_path' => null,
                        'status' => 'draft',
                    ],
                );

                if ($nextPlan->wasRecentlyCreated) {
                    $createdCount++;
                }
            }
        });

        $this->info("Plans archivés: {$archivedCount}, plans créés: {$createdCount}");

        return self::SUCCESS;
    }

    private function buildNextYearTitle(string $title, int $year, int $nextYear): string
    {
        $replaced = Str::replaceFirst((string) $year, (string) $nextYear, $title);
        if ($replaced !== $title) {
            return $replaced;
        }

        return trim($title . ' ' . $nextYear);
    }

    private function buildNextYearContent(mixed $content): ?array
    {
        return is_array($content) ? $content : null;
    }
}
