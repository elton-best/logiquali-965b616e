<?php

namespace App\Services;

use App\Models\Process;
use App\Models\ProcessIndicator;
use App\Models\ProcessRiskOpportunity;
use App\Models\ProcessIsoCoverage;
use App\Models\ProcessReview;
use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ProcessService
{
    /**
     * Generate unique process code
     * Format: P-{CATEGORY_CODE}-{YEAR}-{NUMBER}
     * Ex: P-PIL-2026-001, P-OPE-2026-002
     */
    public function generateProcessCode(string $category, int $siteId): string
    {
        $categoryCode = $this->getCategoryCode($category);
        $year = date('Y');
        
        $query = Process::query();
        if (method_exists(Process::class, 'scopeWithoutEnterpriseScope')) {
            $query = $query->withoutEnterpriseScope();
        }

        // Code must be globally unique; do not scope by site.
        $lastProcess = $query->where('code', 'like', "P-{$categoryCode}-{$year}-%")
            ->orderBy('code', 'desc')
            ->first();
        
        if ($lastProcess) {
            $lastNumber = (int) substr($lastProcess->code, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return sprintf('P-%s-%s-%03d', $categoryCode, $year, $newNumber);
    }

    private function getCategoryCode(string $category): string
    {
        $normalized = \Illuminate\Support\Str::ascii(mb_strtolower(trim($category)));

        return match($normalized) {
            'pilotage', 'management', 'direction' => 'PIL',
            'support', 'soutien' => 'SUP',
            'operationnel', 'operational', 'realisation', 'realization' => 'OPE',
            'mesure_amelioration', 'mesure-amelioration', 'mesure amelioration' => 'MES',
            default => 'GEN',
        };
    }

    /**
     * Create process with all initial data
     */
    public function createProcess(array $data, int $userId): Process
    {
        DB::beginTransaction();
        
        try {
            // Generate code if not provided
            if (!isset($data['code'])) {
                $data['code'] = $this->generateProcessCode(
                    $data['category'] ?? 'operationnel',
                    $data['site_id']
                );
            }

            // Check if process with same code already exists
            $existingProcess = Process::where('code', $data['code'])
                ->where('site_id', $data['site_id'])
                ->first();

            if ($existingProcess) {
                // Update existing process instead of creating new one
                $data['updated_by'] = $userId;
                $existingProcess->update($data);
                DB::commit();
                return $existingProcess->load(['pilot', 'copilot', 'site']);
            }

            // Set defaults
            $data['status'] = $data['status'] ?? 'draft';
            $data['version'] = $data['version'] ?? '1.0';
            $data['created_by'] = $userId;
            
            // Calculate next review date
            if (isset($data['review_frequency_months'])) {
                $data['next_review_at'] = now()->addMonths($data['review_frequency_months']);
            }

            $process = $this->createProcessWithRetry($data);

            DB::commit();
            return $process->load(['pilot', 'copilot', 'site']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update process and create new version if needed
     */
    public function updateProcess(Process $process, array $data, int $userId, bool $createNewVersion = false): Process
    {
        DB::beginTransaction();
        
        try {
            if ($createNewVersion) {
                // Increment version
                $versionParts = explode('.', $process->version);
                $versionParts[0] = (int)$versionParts[0] + 1;
                $data['version'] = implode('.', $versionParts);
                
                // Create review record for old version
                $this->createReviewForVersionChange($process, $userId);
            }

            $data['updated_by'] = $userId;
            $process->update($data);

            DB::commit();
            return $process->load(['pilot', 'copilot', 'site']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create indicator for process
     */
    public function createIndicator(Process $process, array $data, int $userId): ProcessIndicator
    {
        // Generate code if not provided
        if (!isset($data['code'])) {
            $count = $process->indicators()->count() + 1;
            $data['code'] = sprintf('IND-%s-%03d', $process->code, $count);
        }

        $indicator = $process->indicators()->create($data);
        
        return $indicator->load('responsibleUser');
    }

    /**
     * Add indicator value and update status
     */
    public function addIndicatorValue(ProcessIndicator $indicator, array $data, int $userId): void
    {
        $data['recorded_by'] = $userId;
        $indicator->values()->create($data);
        
        // Status is auto-updated via model event
    }

    /**
     * Create risk or opportunity
     */
    public function createRiskOpportunity(Process $process, array $data, int $userId): ProcessRiskOpportunity
    {
        // Generate code if not provided
        if (!isset($data['code'])) {
            $prefix = $data['type'] === 'risque' ? 'R' : 'O';
            $count = $process->risksOpportunities()->where('type', $data['type'])->count() + 1;
            $data['code'] = sprintf('%s-%s-%03d', $prefix, $process->code, $count);
        }

        $data['created_by'] = $userId;
        
        $riskOpp = $process->risksOpportunities()->create($data);
        
        // Criticité is auto-calculated via model event
        
        return $riskOpp->load('responsibleUser');
    }

    /**
     * Add ISO coverage for process
     */
    public function addIsoCoverage(Process $process, array $data): ProcessIsoCoverage
    {
        return $process->isoCoverages()->create($data);
    }

    private function createReviewForVersionChange(Process $process, int $userId): void
    {
        ProcessReview::create([
            'process_id' => $process->id,
            'type' => 'amelioration',
            'review_date' => now(),
            'version_reviewed' => $process->version,
            'led_by' => $userId,
            'decision' => 'approved_with_changes',
            'decision_comment' => 'Nouvelle version créée suite à modification majeure',
            'status' => 'completed',
        ]);
    }

    private function createProcessWithRetry(array $data): Process
    {
        $maxAttempts = 3;
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            try {
                unset($data['ref']);
                return Process::create($data);
            } catch (QueryException $e) {
                $attempt++;

                if ($this->isDuplicateCodeException($e)) {
                    $data['code'] = $this->generateProcessCode(
                        $data['category'] ?? 'operationnel',
                        $data['site_id']
                    );
                }

                if (
                    (!$this->isDuplicateRefException($e) && !$this->isDuplicateCodeException($e))
                    || $attempt >= $maxAttempts
                ) {
                    throw $e;
                }

                // Short backoff to reduce chance of immediate repeated collision.
                usleep(random_int(10_000, 50_000));
            }
        }

        return Process::create($data);
    }

    private function isDuplicateRefException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $message = (string) $e->getMessage();

        return $sqlState === '23505' && str_contains($message, 'processes_ref_unique');
    }

    private function isDuplicateCodeException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? '');
        $message = (string) $e->getMessage();

        return $sqlState === '23505' && str_contains($message, 'processes_code_unique');
    }

    /**
     * Get process cartography data
     */
    public function getCartographyData(int $siteId): array
    {
        $processes = Process::where('site_id', $siteId)
            ->where('status', 'active')
            ->orderBy('category')
            ->orderBy('order')
            ->with(['childProcesses', 'indicators', 'risksOpportunities'])
            ->get();

        return [
            'pilotage' => $processes->where('category', 'pilotage'),
            'operationnel' => $processes->where('category', 'operationnel'),
            'support' => $processes->where('category', 'support'),
            'mesure_amelioration' => $processes->where('category', 'mesure_amelioration'),
        ];
    }

    /**
     * Generate ISO coverage matrix
     */
    public function generateIsoCoverageMatrix(int $siteId, string $norme = '9001'): array
    {
        $processes = Process::where('site_id', $siteId)
            ->whereJsonContains('normes_iso', $norme)
            ->with(['isoCoverages' => function ($query) use ($norme) {
                $query->where('norme', $norme);
            }])
            ->get();

        $matrix = [];
        foreach ($processes as $process) {
            foreach ($process->isoCoverages as $coverage) {
                $matrix[] = [
                    'process' => $process->title,
                    'process_code' => $process->code,
                    'clause' => $coverage->clause_number,
                    'clause_title' => $coverage->clause_title,
                    'coverage' => $coverage->coverage_level,
                    'conformity' => $coverage->conformity_status,
                ];
            }
        }

        return $matrix;
    }

    /**
     * Check if site uses SMI or SMQ
     */
    public function isSMI(int $siteId): bool
    {
        return SystemSetting::get('smq_type', $siteId, 'smq') === 'smi';
    }

    /**
     * Get enabled norms for site
     */
    public function getEnabledNorms(int $siteId): array
    {
        $norms = [];
        
        if (SystemSetting::get('iso_9001_enabled', $siteId, true)) {
            $norms[] = '9001';
        }
        
        if (SystemSetting::get('iso_14001_enabled', $siteId, false)) {
            $norms[] = '14001';
        }
        
        if (SystemSetting::get('iso_45001_enabled', $siteId, false)) {
            $norms[] = '45001';
        }
        
        return $norms;
    }
}
