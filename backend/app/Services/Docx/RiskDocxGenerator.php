<?php

namespace App\Services\Docx;

use App\Models\Process;
use App\Models\Risk;
use App\Models\Site;
use Illuminate\Support\Collection;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

/**
 * Générateur DOCX pour Plan Maîtrise Risques & Opportunités (M6-D1)
 * 
 * Structure conforme ISO 9001:2015 clause 6.1:
 * - Tableau RISQUES: N°, Processus, Risques, Causes, Éval (P/G/C), Actions, Resp, Délai
 * - Tableau OPPORTUNITÉS: N°, Processus, Opportunités, Gains, Actions, Resp, Délai
 * - Matrice criticité avec code couleur
 */
class RiskDocxGenerator
{
    private PhpWord $phpWord;
    private DocumentBrandingService $brandingService;
    
    public function __construct()
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Calibri');
        $this->phpWord->setDefaultFontSize(10);
        $this->brandingService = app(DocumentBrandingService::class);
    }
    
    /**
     * Génère le plan de maîtrise des risques et opportunités
     */
    public function generate(?int $processId = null, ?int $siteId = null): string
    {
        $section = $this->phpWord->addSection([
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
            'orientation' => 'landscape', // Format paysage pour tableau large
        ]);

        $enterprise = null;
        if ($processId) {
            $enterprise = Process::with('site.enterprise')->find($processId)?->site?->enterprise;
        } elseif ($siteId) {
            $enterprise = Site::with('enterprise')->find($siteId)?->enterprise;
        }
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }
        
        // En-tête
        $this->addHeader($section, $processId);
        
        // Matrice d'évaluation
        $this->addEvaluationMatrix($section);
        
        $section->addPageBreak();
        
        // Tableau des RISQUES
        $this->addRisksTable($section, $processId);
        
        $section->addPageBreak();
        
        // Tableau des OPPORTUNITÉS
        $this->addOpportunitiesTable($section, $processId);
        
        // Sauvegarde
        $filename = storage_path('app/temp/plan_maitrise_risques_' . ($processId ?? 'global') . '_' . time() . '.docx');
        
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($filename);
        
        return $filename;
    }
    
    /**
     * En-tête du document
     */
    private function addHeader($section, ?int $processId): void
    {
        $process = $processId ? Process::find($processId) : null;
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        
        $table->addRow(400);
        $cell1 = $table->addCell(3000);
        $cell1->addText('LOGO ENTREPRISE', ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
        
        $cell2 = $table->addCell(10000);
        $cell2->addText(
            'PLAN DE MAÎTRISE DES RISQUES & OPPORTUNITÉS',
            ['bold' => true, 'size' => 16, 'color' => '0066CC'],
            ['alignment' => Jc::CENTER]
        );
        
        $section->addTextBreak(1);
        
        if ($process) {
            $section->addText(
                'Processus: ' . $process->nom . ' (' . $process->code . ')',
                ['bold' => true, 'size' => 12]
            );
        } else {
            $section->addText(
                'Vue d\'ensemble - Tous les processus',
                ['bold' => true, 'size' => 12]
            );
        }
        
        $section->addText(
            'Date d\'édition: ' . now()->format('d/m/Y'),
            ['size' => 10, 'italic' => true]
        );
        
        $section->addTextBreak(1);
    }
    
    /**
     * Matrice d'évaluation criticité
     */
    private function addEvaluationMatrix($section): void
    {
        $section->addText('MATRICE D\'ÉVALUATION', ['bold' => true, 'size' => 14, 'color' => '0066CC']);
        $section->addTextBreak();
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        
        // Header
        $table->addRow(400);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('Gravité / Probabilité', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('1', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('2', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('3', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('4', ['bold' => true], ['alignment' => Jc::CENTER]);
        
        // Lignes matrice
        for ($gravite = 4; $gravite >= 1; $gravite--) {
            $table->addRow();
            $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText($gravite, ['bold' => true], ['alignment' => Jc::CENTER]);
            
            for ($proba = 1; $proba <= 4; $proba++) {
                $criticite = $gravite * $proba;
                $bgColor = $this->getCriticalityColor($criticite);
                $table->addCell(1500, ['bgColor' => $bgColor])->addText(
                    $criticite,
                    ['bold' => true, 'color' => 'FFFFFF'],
                    ['alignment' => Jc::CENTER]
                );
            }
        }
        
        $section->addTextBreak();
        
        // Légende
        $legendTable = $section->addTable(['borderSize' => 0]);
        $legendTable->addRow();
        $legendTable->addCell(2000)->addText('Légende:', ['bold' => true]);
        $legendTable->addCell(2000, ['bgColor' => '00CC00'])->addText('Faible (1-4)', ['color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $legendTable->addCell(2000, ['bgColor' => 'FF9900'])->addText('Moyen (5-7)', ['color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        $legendTable->addCell(2000, ['bgColor' => 'FF0000'])->addText('Élevé/Critique (8-16)', ['color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
        
        $section->addTextBreak(1);
    }
    
    /**
     * Tableau des risques
     */
    private function addRisksTable($section, ?int $processId): void
    {
        $section->addText('RISQUES IDENTIFIÉS', ['bold' => true, 'size' => 14, 'color' => 'CC0000']);
        $section->addTextBreak();
        
        $risks = $this->getRisks($processId);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);
        
        // Header
        $table->addRow(600);
        $table->addCell(500, ['bgColor' => 'E7E6E6'])->addText('N°', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1800, ['bgColor' => 'E7E6E6'])->addText('PROCESSUS', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2200, ['bgColor' => 'E7E6E6'])->addText('RISQUES', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('CAUSES PROFONDES', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(500, ['bgColor' => 'E7E6E6'])->addText('P', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(500, ['bgColor' => 'E7E6E6'])->addText('G', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(600, ['bgColor' => 'E7E6E6'])->addText('C', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('ACTIONS MAÎTRISE', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1200, ['bgColor' => 'E7E6E6'])->addText('RESP', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(800, ['bgColor' => 'E7E6E6'])->addText('DÉLAI', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('SUIVI', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        
        // Données
        foreach ($risks as $idx => $risk) {
            $criticite = $risk->probability * $risk->severity;
            $bgColor = $this->getCriticalityColor($criticite);
            
            $table->addRow();
            $table->addCell(500)->addText('R' . ($idx + 1), ['size' => 9]);
            $table->addCell(1800)->addText($risk->process->nom ?? 'N/A', ['size' => 9]);
            $table->addCell(2200)->addText($risk->description, ['size' => 9]);
            $table->addCell(2000)->addText($risk->causes ?? 'Non spécifié', ['size' => 9]);
            $table->addCell(500)->addText($risk->probability, ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(500)->addText($risk->severity, ['size' => 9], ['alignment' => Jc::CENTER]);
            $table->addCell(600, ['bgColor' => $bgColor])->addText(
                $criticite,
                ['bold' => true, 'color' => 'FFFFFF', 'size' => 9],
                ['alignment' => Jc::CENTER]
            );
            
            // Actions de maîtrise
            $actions = $risk->actions->pluck('titre')->implode(', ') ?: ($risk->pivot->actions_maitrise ?? 'Aucune');
            $table->addCell(2500)->addText($actions, ['size' => 9]);
            
            // Responsable
            $resp = $risk->responsible->name ?? 'Non assigné';
            $table->addCell(1200)->addText($resp, ['size' => 9]);
            
            // Délai
            $delai = $risk->delai_maitrise ? $risk->delai_maitrise->format('d/m/Y') : 'N/A';
            $table->addCell(800)->addText($delai, ['size' => 9]);
            
            // Suivi
            $suivi = $risk->pivot->suivi ?? $risk->statut ?? 'En cours';
            $table->addCell(1500)->addText($suivi, ['size' => 9]);
        }
        
        if ($risks->isEmpty()) {
            $table->addRow();
            $table->addCell(14600, ['gridSpan' => 11])->addText(
                'Aucun risque identifié',
                ['italic' => true, 'color' => '999999'],
                ['alignment' => Jc::CENTER]
            );
        }
        
        // Statistiques
        $section->addTextBreak();
        $statsTable = $section->addTable(['borderSize' => 0]);
        $statsTable->addRow();
        $statsTable->addCell(3000)->addText('Total risques: ' . $risks->count(), ['bold' => true]);
        $statsTable->addCell(3000)->addText(
            'Risques élevés: ' . $risks->filter(fn($r) => ($r->probability * $r->severity) >= 8)->count(),
            ['bold' => true, 'color' => 'FF0000']
        );
        $statsTable->addCell(3000)->addText(
            'Risques moyens: ' . $risks->filter(fn($r) => ($r->probability * $r->severity) >= 4 && ($r->probability * $r->severity) < 8)->count(),
            ['bold' => true, 'color' => 'FF9900']
        );
        $statsTable->addCell(3000)->addText(
            'Risques faibles: ' . $risks->filter(fn($r) => ($r->probability * $r->severity) < 4)->count(),
            ['bold' => true, 'color' => '00CC00']
        );
    }
    
    /**
     * Tableau des opportunités
     */
    private function addOpportunitiesTable($section, ?int $processId): void
    {
        $section->addText('OPPORTUNITÉS IDENTIFIÉES', ['bold' => true, 'size' => 14, 'color' => '00CC00']);
        $section->addTextBreak();
        
        $opportunities = $this->getOpportunities($processId);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);
        
        // Header
        $table->addRow(600);
        $table->addCell(500, ['bgColor' => 'E7E6E6'])->addText('N°', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2000, ['bgColor' => 'E7E6E6'])->addText('PROCESSUS', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(3000, ['bgColor' => 'E7E6E6'])->addText('OPPORTUNITÉS', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(2500, ['bgColor' => 'E7E6E6'])->addText('GAINS POTENTIELS', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(3000, ['bgColor' => 'E7E6E6'])->addText('ACTIONS EXPLOITATION', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1500, ['bgColor' => 'E7E6E6'])->addText('RESP', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1000, ['bgColor' => 'E7E6E6'])->addText('DÉLAI', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        $table->addCell(1100, ['bgColor' => 'E7E6E6'])->addText('SUIVI', ['bold' => true, 'size' => 9], ['alignment' => Jc::CENTER]);
        
        // Données
        foreach ($opportunities as $idx => $opp) {
            $table->addRow();
            $table->addCell(500)->addText('O' . ($idx + 1), ['size' => 9]);
            $table->addCell(2000)->addText($opp->process->nom ?? 'N/A', ['size' => 9]);
            $table->addCell(3000)->addText($opp->description, ['size' => 9]);
            $table->addCell(2500)->addText($opp->gains_potentiels ?? 'Non spécifié', ['size' => 9]);
            
            $actions = $opp->actions->pluck('titre')->implode(', ') ?: ($opp->pivot->actions_exploitation ?? 'Aucune');
            $table->addCell(3000)->addText($actions, ['size' => 9]);
            
            $resp = $opp->responsible->name ?? 'Non assigné';
            $table->addCell(1500)->addText($resp, ['size' => 9]);
            
            $delai = $opp->delai_exploitation ? $opp->delai_exploitation->format('d/m/Y') : 'N/A';
            $table->addCell(1000)->addText($delai, ['size' => 9]);
            
            $suivi = $opp->pivot->suivi ?? $opp->statut ?? 'En cours';
            $table->addCell(1100)->addText($suivi, ['size' => 9]);
        }
        
        if ($opportunities->isEmpty()) {
            $table->addRow();
            $table->addCell(14600, ['gridSpan' => 8])->addText(
                'Aucune opportunité identifiée',
                ['italic' => true, 'color' => '999999'],
                ['alignment' => Jc::CENTER]
            );
        }
        
        // Statistiques
        $section->addTextBreak();
        $section->addText('Total opportunités: ' . $opportunities->count(), ['bold' => true]);
    }
    
    /**
     * Récupère les risques (global ou par processus)
     */
    private function getRisks(?int $processId): Collection
    {
        $query = Risk::with(['process', 'responsible', 'actions']);
        
        if ($processId) {
            $query->where('process_id', $processId);
        }
        
        return $query->orderByRaw('probability * severity DESC')->get();
    }
    
    /**
     * Récupère les opportunités (global ou par processus)
     */
    private function getOpportunities(?int $processId): Collection
    {
        $query = \App\Models\Opportunity::with(['process', 'responsible', 'actions']);
        
        if ($processId) {
            $query->where('process_id', $processId);
        }
        
        return $query->get();
    }
    
    /**
     * Détermine couleur selon criticité
     */
    private function getCriticalityColor(int $criticite): string
    {
        if ($criticite >= 8) return 'FF0000'; // Rouge
        if ($criticite >= 4) return 'FF9900'; // Orange
        return '00CC00'; // Vert
    }
}
