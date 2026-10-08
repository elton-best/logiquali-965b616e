<?php

namespace App\Services;

use App\Models\Process;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class ProcessDocxGenerator
{
    /**
     * Generate Turtle Diagram (Fiche Processus) DOCX
     */
    public function generateTurtleDiagram(Process $process): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1000,
            'marginRight' => 1000,
            'marginTop' => 1000,
            'marginBottom' => 1000,
        ]);

        // Header
        $headerStyle = ['bold' => true, 'size' => 16, 'color' => '2E3B55'];
        $section->addText('FICHE PROCESSUS - DIAGRAMME TORTUE', $headerStyle, ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        // Informations générales
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'width' => 100 * 50,
            'unit' => 'pct',
        ]);

        // Row 1: Intitulé
        $table->addRow();
        $table->addCell(3000)->addText('Intitulé:', ['bold' => true]);
        $table->addCell(7000)->addText($process->title ?? '');

        // Row 2: Code
        $table->addRow();
        $table->addCell(3000)->addText('Code:', ['bold' => true]);
        $table->addCell(7000)->addText($process->ref ?? '');

        // Row 3: Pilote
        $table->addRow();
        $table->addCell(3000)->addText('Pilote:', ['bold' => true]);
        $table->addCell(7000)->addText($process->pilote ?? 'N/A');

        // Row 4: Type
        $table->addRow();
        $table->addCell(3000)->addText('Type:', ['bold' => true]);
        $table->addCell(7000)->addText(ucfirst($process->category ?? ''));

        // Row 5: Finalité
        $table->addRow();
        $table->addCell(3000)->addText('Finalité:', ['bold' => true]);
        $table->addCell(7000)->addText($process->finalite ?? '');

        $section->addTextBreak(1);

        // Turtle Diagram Table (3x3)
        $section->addText('DIAGRAMME TORTUE', ['bold' => true, 'size' => 14], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);

        $turtleTable = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '2E3B55',
        ]);

        // Row 1: Ressources
        $turtleTable->addRow(1500);
        $cell = $turtleTable->addCell(3333);
        $cell->addText('RESSOURCES HUMAINES', ['bold' => true, 'color' => 'FFFFFF'], [
            'alignment' => Jc::CENTER,
            'bgColor' => '4472C4',
        ]);
        $cell->addText($this->formatList($process->ressources_humaines ?? ''));

        $cell = $turtleTable->addCell(3333);
        $cell->addText('RESSOURCES TECHNOLOGIQUES', ['bold' => true, 'color' => 'FFFFFF'], [
            'alignment' => Jc::CENTER,
            'bgColor' => '70AD47',
        ]);
        $cell->addText($this->formatList($process->ressources_techno ?? ''));

        $cell = $turtleTable->addCell(3333);
        $cell->addText('RESSOURCES DOCUMENTAIRES', ['bold' => true, 'color' => 'FFFFFF'], [
            'alignment' => Jc::CENTER,
            'bgColor' => 'FFC000',
        ]);
        $cell->addText($this->formatList($process->ressources_doc ?? ''));

        // Row 2: Entrées / Activités / Sorties
        $turtleTable->addRow(2000);
        
        $cell = $turtleTable->addCell(3333);
        $cell->addText('ENTRÉES', ['bold' => true], ['alignment' => Jc::CENTER]);
        $cell->addText($this->formatList($process->entrees ?? ''));

        $cell = $turtleTable->addCell(3333);
        $cell->addText('ACTIVITÉS PRINCIPALES', ['bold' => true, 'color' => 'FFFFFF'], [
            'alignment' => Jc::CENTER,
            'bgColor' => 'E7E6E6',
        ]);
        $cell->addText($this->formatList($process->activites ?? ''), [], ['alignment' => Jc::CENTER]);

        $cell = $turtleTable->addCell(3333);
        $cell->addText('SORTIES', ['bold' => true], ['alignment' => Jc::CENTER]);
        $cell->addText($this->formatList($process->sorties ?? ''));

        // Row 3: Indicateurs
        $turtleTable->addRow(1500);
        $cell = $turtleTable->addCell(10000, ['gridSpan' => 3]);
        $cell->addText('INDICATEURS DE PERFORMANCE', ['bold' => true, 'color' => 'FFFFFF'], [
            'alignment' => Jc::CENTER,
            'bgColor' => '5B9BD5',
        ]);
        $cell->addText($this->formatList($process->indicateurs ?? ''));

        $section->addTextBreak(1);

        // Risques & Opportunités
        $section->addText('RISQUES & OPPORTUNITÉS', ['bold' => true, 'size' => 12]);
        $risksText = $process->risks()->count() > 0 
            ? implode("\n", $process->risks()->pluck('title')->toArray())
            : 'Aucun risque associé';
        $section->addText($risksText);

        // Save to temp file
        $fileName = 'fiche_processus_' . ($process->ref ?? 'process') . '_' . now()->format('Y-m-d') . '.docx';
        $tempPath = storage_path('app/temp/' . $fileName);
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    /**
     * Format list items (comma/newline separated to bullet points)
     */
    private function formatList(?string $text): string
    {
        if (!$text) {
            return 'N/A';
        }

        $items = preg_split('/[,\n]+/', $text);
        $formatted = [];
        
        foreach ($items as $item) {
            $item = trim($item);
            if ($item) {
                $formatted[] = '• ' . $item;
            }
        }

        return implode("\n", $formatted) ?: 'N/A';
    }
}
