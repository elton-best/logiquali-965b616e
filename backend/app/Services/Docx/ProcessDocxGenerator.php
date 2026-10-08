<?php

namespace App\Services\Docx;

use App\Models\Process;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ProcessDocxGenerator
{
    public function generate(Process $process): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Titre
        $section->addText(
            'FICHE PROCESSUS',
            ['bold' => true, 'size' => 16, 'color' => '2E74B5'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 240]
        );

        $section->addText(
            $process->title,
            ['bold' => true, 'size' => 14],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 480]
        );

        // Informations générales
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Code', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($process->code ?? '', ['size' => 10]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Référence', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($process->ref ?? '', ['size' => 10]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Catégorie', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($this->getCategoryLabel($process->category), ['size' => 10]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Pilote', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($process->pilot?->name ?? '', ['size' => 10]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Co-pilote', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($process->copilot?->name ?? '', ['size' => 10]);
        
        $table->addRow();
        $table->addCell(3000)->addText('Finalité', ['bold' => true, 'size' => 10]);
        $table->addCell(7000)->addText($process->finalite ?? '', ['size' => 10]);

        $section->addTextBreak();

        // Diagramme Tortue
        if (!empty($process->turtle_diagram)) {
            $section->addText('DIAGRAMME TORTUE', ['bold' => true, 'size' => 12, 'color' => '2E74B5'], ['spaceAfter' => 240]);
            
            $turtleTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            
            if (!empty($process->turtle_diagram['inputs'])) {
                $turtleTable->addRow();
                $turtleTable->addCell(3000)->addText('Entrées', ['bold' => true, 'size' => 10]);
                $turtleTable->addCell(7000)->addText($process->turtle_diagram['inputs'], ['size' => 10]);
            }
            
            if (!empty($process->turtle_diagram['outputs'])) {
                $turtleTable->addRow();
                $turtleTable->addCell(3000)->addText('Sorties', ['bold' => true, 'size' => 10]);
                $turtleTable->addCell(7000)->addText($process->turtle_diagram['outputs'], ['size' => 10]);
            }
            
            if (!empty($process->turtle_diagram['resources'])) {
                $turtleTable->addRow();
                $turtleTable->addCell(3000)->addText('Ressources', ['bold' => true, 'size' => 10]);
                $turtleTable->addCell(7000)->addText($process->turtle_diagram['resources'], ['size' => 10]);
            }
            
            if (!empty($process->turtle_diagram['methods'])) {
                $turtleTable->addRow();
                $turtleTable->addCell(3000)->addText('Méthodes', ['bold' => true, 'size' => 10]);
                $turtleTable->addCell(7000)->addText($process->turtle_diagram['methods'], ['size' => 10]);
            }
            
            if (!empty($process->turtle_diagram['indicators'])) {
                $turtleTable->addRow();
                $turtleTable->addCell(3000)->addText('Indicateurs', ['bold' => true, 'size' => 10]);
                $turtleTable->addCell(7000)->addText($process->turtle_diagram['indicators'], ['size' => 10]);
            }
            
            $section->addTextBreak();
        }

        // Activités
        if ($process->activities && $process->activities->count() > 0) {
            $section->addText('ACTIVITÉS', ['bold' => true, 'size' => 12, 'color' => '2E74B5'], ['spaceAfter' => 240]);
            
            $actTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            $actTable->addRow(500);
            $actTable->addCell(1000)->addText('N°', ['bold' => true, 'size' => 10]);
            $actTable->addCell(4000)->addText('Activité', ['bold' => true, 'size' => 10]);
            $actTable->addCell(2500)->addText('Responsable', ['bold' => true, 'size' => 10]);
            $actTable->addCell(2500)->addText('Description', ['bold' => true, 'size' => 10]);
            
            foreach ($process->activities as $index => $activity) {
                $actTable->addRow();
                $actTable->addCell(1000)->addText($index + 1, ['size' => 10]);
                $actTable->addCell(4000)->addText($activity->name ?? '', ['size' => 10]);
                $actTable->addCell(2500)->addText($activity->responsible ?? '', ['size' => 10]);
                $actTable->addCell(2500)->addText($activity->description ?? '', ['size' => 10]);
            }
            
            $section->addTextBreak();
        }

        // Risques et Opportunités
        $risksCount = $process->risks ? $process->risks->count() : 0;
        $oppsCount = $process->opportunities ? $process->opportunities->count() : 0;
        
        if ($risksCount > 0 || $oppsCount > 0) {
            $section->addText('RISQUES ET OPPORTUNITÉS', ['bold' => true, 'size' => 12, 'color' => '2E74B5'], ['spaceAfter' => 240]);
            
            $roTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            $roTable->addRow(500);
            $roTable->addCell(2000)->addText('Type', ['bold' => true, 'size' => 10]);
            $roTable->addCell(4000)->addText('Description', ['bold' => true, 'size' => 10]);
            $roTable->addCell(2000)->addText('Criticité', ['bold' => true, 'size' => 10]);
            $roTable->addCell(2000)->addText('Statut', ['bold' => true, 'size' => 10]);
            
            if ($risksCount > 0) {
                foreach ($process->risks as $risk) {
                    $roTable->addRow();
                    $roTable->addCell(2000)->addText('Risque', ['size' => 10, 'color' => 'D32F2F']);
                    $roTable->addCell(4000)->addText($risk->description ?? '', ['size' => 10]);
                    $roTable->addCell(2000)->addText($risk->criticality ?? '', ['size' => 10]);
                    $roTable->addCell(2000)->addText($risk->status ?? '', ['size' => 10]);
                }
            }
            
            if ($oppsCount > 0) {
                foreach ($process->opportunities as $opp) {
                    $roTable->addRow();
                    $roTable->addCell(2000)->addText('Opportunité', ['size' => 10, 'color' => '388E3C']);
                    $roTable->addCell(4000)->addText($opp->description ?? '', ['size' => 10]);
                    $roTable->addCell(2000)->addText($opp->priority ?? '', ['size' => 10]);
                    $roTable->addCell(2000)->addText($opp->status ?? '', ['size' => 10]);
                }
            }
            
            $section->addTextBreak();
        }

        // Objectifs
        if ($process->objectives && $process->objectives->count() > 0) {
            $section->addText('OBJECTIFS', ['bold' => true, 'size' => 12, 'color' => '2E74B5'], ['spaceAfter' => 240]);
            
            $objTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999', 'cellMargin' => 80]);
            $objTable->addRow(500);
            $objTable->addCell(5000)->addText('Objectif', ['bold' => true, 'size' => 10]);
            $objTable->addCell(2500)->addText('Cible', ['bold' => true, 'size' => 10]);
            $objTable->addCell(2500)->addText('Échéance', ['bold' => true, 'size' => 10]);
            
            foreach ($process->objectives as $objective) {
                $objTable->addRow();
                $objTable->addCell(5000)->addText($objective->title ?? '', ['size' => 10]);
                $objTable->addCell(2500)->addText($objective->target ?? '', ['size' => 10]);
                $objTable->addCell(2500)->addText(
                    $objective->deadline ? \Carbon\Carbon::parse($objective->deadline)->format('d/m/Y') : '',
                    ['size' => 10]
                );
            }
        }

        // Générer le fichier
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }
        $outputPath = storage_path('app/temp/Processus_' . $process->code . '_' . time() . '.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return $outputPath;
    }

    private function getCategoryLabel(?string $category): string
    {
        $labels = [
            'pilotage' => 'Pilotage',
            'support' => 'Support',
            'operationnel' => 'Opérationnel',
            'mesure_amelioration' => 'Mesure & Amélioration',
        ];
        return $labels[$category] ?? $category ?? 'Non défini';
    }
}
