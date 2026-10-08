<?php

namespace App\Modules\Processes\Services;

use App\Models\Document;
use App\Models\Enterprise;
use App\Models\Site;

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
     * Normalize category into ISO 9001 standard: management, realization, support
     */
    public function normalizeCategory(?string $category): string
    {
        $cat = strtolower(trim((string) $category));
        if (in_array($cat, ['management', 'pilotage', 'mesure_amelioration', 'direction', 'strategie'])) {
            return 'management';
        }
        if (in_array($cat, ['support', 'soutien', 'ressource', 'ressources'])) {
            return 'support';
        }
        return 'realization';
    }

    /**
     * Get process cartography data with ISO categories, sequences & interactions
     */
    public function getCartographyData(?int $siteId = null, ?int $enterpriseId = null): array
    {
        $query = Process::query()
            ->whereNull('deleted_at')
            ->where(function ($q) {
                $q->whereNull('status')
                  ->orWhere('status', '!=', 'obsolete');
            })
            ->with([
                'pilot',
                'copilot',
                'copilots',
                'site.enterprise',
                'sequences.responsibleUser',
                'indicators',
                'risksOpportunities'
            ])
            ->orderBy('order')
            ->orderBy('id');

        if ($siteId && $siteId > 0) {
            $query->where('site_id', $siteId);
        } elseif ($enterpriseId && $enterpriseId > 0) {
            $query->where(function ($q) use ($enterpriseId) {
                $q->where('enterprise_id', $enterpriseId)
                  ->orWhereHas('site', function ($sq) use ($enterpriseId) {
                      $sq->where('enterprise_id', $enterpriseId);
                  });
            });
        }

        $processes = $query->get();

        $mappedProcesses = $processes->map(function ($p) {
            $normalizedCategory = $this->normalizeCategory($p->category ?? $p->type);

            $pilotName = null;
            if ($p->pilot) {
                $pilotName = trim(($p->pilot->first_name ?? '') . ' ' . ($p->pilot->last_name ?? '')) ?: $p->pilot->name;
            }
            $department = $p->pilot?->job_title ?? $p->department ?? '';

            $sequences = $p->sequences ?? collect();
            $activities = [];
            $allInputs = [];
            $allOutputs = [];
            $supplierProcessRefs = [];
            $clientProcessRefs = [];

            foreach ($sequences as $seq) {
                if (!empty($seq->activity_description)) {
                    $activities[] = trim($seq->activity_description);
                }
                if (!empty($seq->input_description)) {
                    $allInputs[] = trim($seq->input_description);
                }
                if (!empty($seq->output_description)) {
                    $allOutputs[] = trim($seq->output_description);
                }
                if (is_array($seq->supplier_processes)) {
                    foreach ($seq->supplier_processes as $sp) {
                        if (!empty($sp)) {
                            $supplierProcessRefs[] = is_string($sp) ? trim($sp) : (string) $sp;
                        }
                    }
                }
                if (is_array($seq->client_processes)) {
                    foreach ($seq->client_processes as $cp) {
                        if (!empty($cp)) {
                            $clientProcessRefs[] = is_string($cp) ? trim($cp) : (string) $cp;
                        }
                    }
                }
            }

            $activitiesSummary = !empty($activities)
                ? implode(' ; ', array_unique($activities))
                : ($p->purpose ?? $p->finalite ?? 'Activités opérationnelles du processus');

            $inputsSummary = !empty($allInputs)
                ? implode(' ; ', array_unique($allInputs))
                : 'Exigences et données d’entrée';

            $outputsSummary = !empty($allOutputs)
                ? implode(' ; ', array_unique($allOutputs))
                : 'Livrables et résultats conformes';

            $copilotNames = [];
            if ($p->relationLoaded('copilots') && $p->copilots) {
                $copilotNames = $p->copilots->pluck('name')->filter()->values()->all();
            } elseif ($p->copilot?->name) {
                $copilotNames[] = $p->copilot->name;
            }

            $allResponsibles = array_filter(array_merge(
                $pilotName ? [$pilotName] : [],
                $copilotNames
            ));
            $responsibleDisplay = !empty($allResponsibles)
                ? implode(' & ', $allResponsibles)
                : ($pilotName ?: 'Non assigné');

            return [
                'id' => (int) $p->id,
                'code' => $p->code ?? ('PRC-' . $p->id),
                'title' => $p->title ?? $p->name ?? ('Processus ' . $p->id),
                'name' => $p->title ?? $p->name ?? ('Processus ' . $p->id),
                'category' => $p->category,
                'normalized_category' => $normalizedCategory,
                'status' => $p->status ?? 'active',
                'version' => $p->version ?? '1.0',
                'finalite' => $p->finalite ?? $p->purpose ?? '',
                'pilot_id' => $p->pilot_id,
                'pilot_name' => $pilotName ?: 'Non assigné',
                'responsible_display' => $responsibleDisplay,
                'department' => $department,
                'observation' => $p->observation ?? '',
                'activities_summary' => $activitiesSummary,
                'inputs_summary' => $inputsSummary,
                'outputs_summary' => $outputsSummary,
                'supplier_processes' => array_values(array_unique($supplierProcessRefs)),
                'client_processes' => array_values(array_unique($clientProcessRefs)),
                'interfaces' => $p->interfaces ?? [],
                'interactions' => $p->interactions ?? [],
                'indicators_count' => $p->indicators?->count() ?? 0,
                'risks_count' => $p->risksOpportunities?->count() ?? 0,
                'site_id' => $p->site_id,
                'site_name' => $p->site?->name ?? 'Site Principal',
            ];
        });

        // Compute interactions between processes
        $interactions = $this->buildInteractionsMatrix($mappedProcesses);

        $managementProcesses = $mappedProcesses->where('normalized_category', 'management')->values();
        $realizationProcesses = $mappedProcesses->where('normalized_category', 'realization')->values();
        $supportProcesses = $mappedProcesses->where('normalized_category', 'support')->values();

        $firstSite = $processes->first()?->site;
        $enterprise = $firstSite?->enterprise ?? ($enterpriseId ? \App\Models\Enterprise::find($enterpriseId) : null);

        $existingDoc = \App\Models\Document::query()
            ->when($siteId, fn ($q) => $q->where('site_id', $siteId))
            ->where(function ($q) {
                $q->where('metadata->document_kind', 'process_cartography')
                  ->orWhere('source_type', 'process_cartography');
            })
            ->latest('id')
            ->first();

        return [
            'enterprise_name' => $enterprise?->name ?? 'Système de Management de la Qualité',
            'site_name' => $firstSite?->name ?? 'Tous les sites',
            'total_count' => $mappedProcesses->count(),
            'document' => $existingDoc ? [
                'id' => $existingDoc->id,
                'code' => $existingDoc->code,
                'version' => $existingDoc->version ?? '1.0',
                'workflow_status' => $existingDoc->workflow_status ?? 'draft',
                'title' => $existingDoc->title,
                'effective_date' => optional($existingDoc->effective_date ?? $existingDoc->created_at)->format('d/m/Y'),
            ] : null,
            'categories' => [
                'management' => $managementProcesses,
                'realization' => $realizationProcesses,
                'support' => $supportProcesses,
            ],
            'pilotage' => $managementProcesses,
            'operationnel' => $realizationProcesses,
            'support' => $supportProcesses,
            'mesure_amelioration' => $mappedProcesses->where('category', 'mesure_amelioration')->values(),
            'all_processes' => $mappedProcesses->values(),
            'interactions' => $interactions,
        ];
    }

    /**
     * Build dynamic interactions matrix between processes
     */
    public function buildInteractionsMatrix($processes): array
    {
        $interactions = [];
        $interactionMap = [];
        $processByCode = [];
        $processById = [];

        foreach ($processes as $proc) {
            $processById[$proc['id']] = $proc;
            $processByCode[strtoupper(trim($proc['code']))] = $proc;
            $processByCode[strtoupper(trim($proc['title']))] = $proc;
        }

        // 1. Interactions issues des séquences déclarées
        foreach ($processes as $proc) {
            // Clients (sorties de proc vers client)
            foreach ($proc['client_processes'] as $clientRef) {
                $target = null;
                if (is_numeric($clientRef) && isset($processById[(int) $clientRef])) {
                    $target = $processById[(int) $clientRef];
                } elseif (isset($processByCode[strtoupper(trim($clientRef))])) {
                    $target = $processByCode[strtoupper(trim($clientRef))];
                }

                if ($target && $target['id'] !== $proc['id']) {
                    $key = "{$proc['id']}->{$target['id']}";
                    if (!isset($interactionMap[$key])) {
                        $interactionMap[$key] = true;
                        $interactions[] = [
                            'id' => 'int-' . (count($interactions) + 1),
                            'source_id' => $proc['id'],
                            'source_code' => $proc['code'],
                            'source_title' => $proc['title'],
                            'target_id' => $target['id'],
                            'target_code' => $target['code'],
                            'target_title' => $target['title'],
                            'label' => !empty($proc['outputs_summary']) ? $proc['outputs_summary'] : 'Données et livrables de sortie',
                            'nature' => 'extrant',
                        ];
                    }
                }
            }

            // Fournisseurs (entrées depuis supplier vers proc)
            foreach ($proc['supplier_processes'] as $supplierRef) {
                $source = null;
                if (is_numeric($supplierRef) && isset($processById[(int) $supplierRef])) {
                    $source = $processById[(int) $supplierRef];
                } elseif (isset($processByCode[strtoupper(trim($supplierRef))])) {
                    $source = $processByCode[strtoupper(trim($supplierRef))];
                }

                if ($source && $source['id'] !== $proc['id']) {
                    $key = "{$source['id']}->{$proc['id']}";
                    if (!isset($interactionMap[$key])) {
                        $interactionMap[$key] = true;
                        $interactions[] = [
                            'id' => 'int-' . (count($interactions) + 1),
                            'source_id' => $source['id'],
                            'source_code' => $source['code'],
                            'source_title' => $source['title'],
                            'target_id' => $proc['id'],
                            'target_code' => $proc['code'],
                            'target_title' => $proc['title'],
                            'label' => !empty($proc['inputs_summary']) ? $proc['inputs_summary'] : 'Données et exigences d’entrée',
                            'nature' => 'intrant',
                        ];
                    }
                }
            }
        }

        // 2. Flux ISO 9001 systémiques si peu ou pas d'interactions enregistrées
        if (count($interactions) === 0 && count($processes) > 1) {
            $realization = $processes->where('normalized_category', 'realization')->values();
            $management = $processes->where('normalized_category', 'management')->values();
            $support = $processes->where('normalized_category', 'support')->values();

            // Chaîne de valeur opérationnelle (Réalisation séquentielle)
            for ($i = 0; $i < count($realization) - 1; $i++) {
                $curr = $realization[$i];
                $next = $realization[$i + 1];
                $interactions[] = [
                    'id' => 'int-seq-' . ($i + 1),
                    'source_id' => $curr['id'],
                    'source_code' => $curr['code'],
                    'source_title' => $curr['title'],
                    'target_id' => $next['id'],
                    'target_code' => $next['code'],
                    'target_title' => $next['title'],
                    'label' => 'Transmission des livrables opérationnels',
                    'nature' => 'flux_valeur',
                ];
            }

            // Management vers Réalisation & Support (Politique, Stratégie, Objectifs)
            if (count($management) > 0) {
                $mainMgmt = $management[0];
                if (count($realization) > 0) {
                    $interactions[] = [
                        'id' => 'int-mgmt-real',
                        'source_id' => $mainMgmt['id'],
                        'source_code' => $mainMgmt['code'],
                        'source_title' => $mainMgmt['title'],
                        'target_id' => $realization[0]['id'],
                        'target_code' => $realization[0]['code'],
                        'target_title' => $realization[0]['title'],
                        'label' => 'Politique Qualité, Objectifs & Directives',
                        'nature' => 'pilotage',
                    ];
                }
                if (count($support) > 0) {
                    $interactions[] = [
                        'id' => 'int-mgmt-sup',
                        'source_id' => $mainMgmt['id'],
                        'source_code' => $mainMgmt['code'],
                        'source_title' => $mainMgmt['title'],
                        'target_id' => $support[0]['id'],
                        'target_code' => $support[0]['code'],
                        'target_title' => $support[0]['title'],
                        'label' => 'Allocation des ressources budgétaires & RH',
                        'nature' => 'ressources',
                    ];
                }
            }

            // Support vers Réalisation (Ressources humaines, outillage, SI)
            if (count($support) > 0 && count($realization) > 0) {
                $mainSup = $support[0];
                $interactions[] = [
                    'id' => 'int-sup-real',
                    'source_id' => $mainSup['id'],
                    'source_code' => $mainSup['code'],
                    'source_title' => $mainSup['title'],
                    'target_id' => $realization[0]['id'],
                    'target_code' => $realization[0]['code'],
                    'target_title' => $realization[0]['title'],
                    'label' => 'Mise à disposition du personnel, équipements et SI',
                    'nature' => 'support',
                ];
            }

            // Réalisation vers Management (Revues, Données de performance, Amélioration)
            if (count($realization) > 0 && count($management) > 0) {
                $lastReal = $realization[count($realization) - 1];
                $mainMgmt = $management[0];
                $interactions[] = [
                    'id' => 'int-real-mgmt',
                    'source_id' => $lastReal['id'],
                    'source_code' => $lastReal['code'],
                    'source_title' => $lastReal['title'],
                    'target_id' => $mainMgmt['id'],
                    'target_code' => $mainMgmt['code'],
                    'target_title' => $mainMgmt['title'],
                    'label' => 'Indicateurs de performance, retours clients & opportunités',
                    'nature' => 'retroaction',
                ];
            }
        }

        return $interactions;
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
