<?php

namespace App\Services\Docx;

use App\Models\Site;
use App\Models\Process;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;

class ProcessSheetGenerator
{
    private PhpWord $phpWord;
    private $section;
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    public function generate(Process $process, array $documentMeta = []): string
    {
        $this->phpWord = new PhpWord();
        $this->phpWord->setDefaultFontName('Calibri');
        $this->phpWord->setDefaultFontSize(11);

        $this->section = $this->phpWord->addSection([
            'marginLeft' => 1134,
            'marginRight' => 1134,
            'marginTop' => 1134,
            'marginBottom' => 1134,
        ]);

        $enterprise = $process->site?->enterprise
            ?? Site::with('enterprise')->find($process->site_id)?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($this->section, $enterprise, 'process', (int) $process->id, $documentMeta);
        }

        $this->addHeader($process, $documentMeta);
        $this->addGeneralInfo($process);
        $this->addSequences($process);
        $this->addObjectives($process);
        $this->addResources($process);
        $this->addRisksOpportunities($process);

        $tempFile = tempnam(sys_get_temp_dir(), 'process_sheet_') . '.docx';
        $this->phpWord->save($tempFile, 'Word2007');

        return $tempFile;
    }

    private function addHeader(Process $process, array $documentMeta = [])
    {
        $table = $this->section->addTable([
            'borderSize' => 6,
            'borderColor' => '000000',
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        $table->addRow();
        $cell = $table->addCell(5000);
        $cell->addText('FICHE PROCESSUS', ['bold' => true, 'size' => 16], ['alignment' => Jc::CENTER]);
        
        $cell = $table->addCell(5000);
        $cell->addText('Code: ' . ($documentMeta['code'] ?? 'N/A'), ['bold' => true], ['alignment' => Jc::CENTER]);
        $cell->addText('Version: ' . ($documentMeta['version'] ?? '1.0'), [], ['alignment' => Jc::CENTER]);

        $this->section->addTextBreak();
    }

    private function addGeneralInfo(Process $process)
    {
        $this->addSectionTitle('1. INFORMATIONS GÉNÉRALES');

        $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);

        $this->addTableRow($table, 'Nom du processus', $process->title ?? 'N/A');
        $this->addTableRow($table, 'Catégorie', $this->getCategoryLabel($process->category));
        $this->addTableRow($table, 'Pilote', $process->pilot->name ?? 'Non défini');
        $this->addTableRow($table, 'Co-pilote(s)', $this->formatCopilots($process));
        $this->addTableRow($table, 'Finalité', $process->purpose ?? $process->finalite ?? 'Non définie');

        $this->section->addTextBreak();
    }

    private function addSequences(Process $process)
    {
        $this->addSectionTitle('2. SÉQUENCES DU PROCESSUS');

        $sequences = $process->sequences ?? [];
        
        if (empty($sequences)) {
            $this->section->addText('Aucune séquence définie.', ['italic' => true]);
            $this->section->addTextBreak();
            return;
        }

        foreach ($sequences as $index => $seq) {
            $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            
            $table->addRow();
            $cell = $table->addCell(10000, ['gridSpan' => 6, 'bgColor' => 'E7E6E6']);
            $cell->addText('Séquence ' . ($index + 1), ['bold' => true]);

            $table->addRow();
            $table->addCell(1666)->addText('Processus fournisseurs', ['bold' => true]);
            $table->addCell(1666)->addText('Entrées', ['bold' => true]);
            $table->addCell(1666)->addText('Activités', ['bold' => true]);
            $table->addCell(1666)->addText('Sous-activités', ['bold' => true]);
            $table->addCell(1666)->addText('Sorties', ['bold' => true]);
            $table->addCell(1666)->addText('Processus clients', ['bold' => true]);

            $table->addRow();
            $table->addCell(1666)->addText($this->formatArray($seq->supplier_processes ?? []));
            $table->addCell(1666)->addText($seq->input_description ?? '');
            $table->addCell(1666)->addText($seq->activity_description ?? '');
            $table->addCell(1666)->addText($this->formatArray($seq->sub_activities ?? []));
            $table->addCell(1666)->addText($seq->output_description ?? '');
            $table->addCell(1666)->addText($this->formatArray($seq->client_processes ?? []));

            $this->section->addTextBreak();
        }
    }

    private function addObjectives(Process $process)
    {
        $this->addSectionTitle('3. OBJECTIFS ET INDICATEURS');

        $objectives = $process->processObjectives ?? [];
        
        if (empty($objectives)) {
            $this->section->addText('Aucun objectif défini.', ['italic' => true]);
            $this->section->addTextBreak();
            return;
        }

        $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        
        $table->addRow();
        $table->addCell(5000)->addText('Objectif', ['bold' => true]);
        $table->addCell(5000)->addText('Indicateur', ['bold' => true]);

        foreach ($objectives as $obj) {
            $table->addRow();
            $table->addCell(5000)->addText($obj->title ?? '');
            $table->addCell(5000)->addText($obj->indicator->name ?? $obj->indicator_name ?? '');
        }

        $this->section->addTextBreak();
    }

    private function addResources(Process $process)
    {
        $this->addSectionTitle('4. RESSOURCES NÉCESSAIRES');

        $resources = $process->ressources ?? [];
        
        $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
        
        $this->addTableRow($table, 'Ressources humaines', $this->formatArray($resources['human'] ?? []));
        $this->addTableRow($table, 'Ressources technologiques', $this->formatArray($resources['technological'] ?? []));
        $this->addTableRow($table, 'Ressources matérielles', $this->formatArray($resources['material'] ?? []));
        $this->addTableRow($table, 'Ressources documentaires', $this->formatArray($resources['documentary'] ?? []));

        $this->section->addTextBreak();
    }

    private function addRisksOpportunities(Process $process)
    {
        $this->addSectionTitle('5. RISQUES ET OPPORTUNITÉS');

        $items = $process->risksOpportunities ?? [];
        
        if (empty($items)) {
            $this->section->addText('Aucun risque ou opportunité identifié.', ['italic' => true]);
            return;
        }

        $risks = $items->where('type', 'risque');
        $opportunities = $items->where('type', 'opportunite');

        if ($risks->count() > 0) {
            $this->section->addText('Risques:', ['bold' => true, 'color' => 'FF0000']);
            $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            
            foreach ($risks as $risk) {
                $table->addRow();
                $table->addCell(10000)->addText($risk->description ?? $risk->title ?? '');
            }
            $this->section->addTextBreak();
        }

        if ($opportunities->count() > 0) {
            $this->section->addText('Opportunités:', ['bold' => true, 'color' => '00AA00']);
            $table = $this->section->addTable(['borderSize' => 6, 'borderColor' => '000000']);
            
            foreach ($opportunities as $opp) {
                $table->addRow();
                $table->addCell(10000)->addText($opp->description ?? $opp->title ?? '');
            }
        }
    }

    private function addSectionTitle(string $title)
    {
        $this->section->addText($title, ['bold' => true, 'size' => 14, 'color' => '1F4E78']);
        $this->section->addTextBreak();
    }

    private function addTableRow($table, string $label, string $value)
    {
        $table->addRow();
        $table->addCell(3000, ['bgColor' => 'E7E6E6'])->addText($label, ['bold' => true]);
        $table->addCell(7000)->addText($value);
    }

    private function formatArray($array): string
    {
        if (empty($array)) return 'Non défini';
        if (is_string($array)) return $array;
        return implode(', ', $array);
    }

    private function formatCopilots(Process $process): string
    {
        $copilotNames = [];

        if ($process->relationLoaded('copilots') && $process->copilots) {
            $copilotNames = $process->copilots
                ->pluck('name')
                ->filter(fn ($name) => is_string($name) && trim($name) !== '')
                ->map(fn ($name) => trim((string) $name))
                ->values()
                ->all();
        }

        if (empty($copilotNames) && $process->copilot?->name) {
            $copilotNames[] = $process->copilot->name;
        }

        return !empty($copilotNames) ? implode(', ', array_unique($copilotNames)) : 'Non défini';
    }

    private function getCategoryLabel(string $category): string
    {
        return match($category) {
            'pilotage', 'management' => 'Management',
            'operationnel', 'realization' => 'Réalisation',
            'support' => 'Support',
            'mesure_amelioration' => 'Mesure et amélioration',
            default => ucfirst($category),
        };
    }
}
