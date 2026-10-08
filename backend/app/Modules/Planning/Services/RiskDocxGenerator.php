<?php

namespace App\Modules\Planning\Services;

use App\Models\Risk;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\IOFactory;

class RiskDocxGenerator
{
    /**
     * Generate Plan Maîtrise Risques et Opportunités DOCX
     */
    public function generateRiskPlan(Collection $risks): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 800,
            'marginRight' => 800,
            'marginTop' => 1000,
            'marginBottom' => 1000,
            'orientation' => 'landscape', // Landscape for wide table
        ]);

        // Header
        $headerStyle = ['bold' => true, 'size' => 16, 'color' => '2E3B55'];
        $section->addText(
            'PLAN DE MAÎTRISE DES RISQUES ET OPPORTUNITÉS',
            $headerStyle,
            ['alignment' => Jc::CENTER]
        );
        $section->addTextBreak(1);

        // Info
        $section->addText('Date d\'édition: ' . now()->format('d/m/Y'), ['italic' => true]);
        $section->addText('Total risques: ' . $risks->count(), ['italic' => true]);
        $section->addTextBreak(1);

        // Main table
        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ];

        $table = $section->addTable($tableStyle);

        // Header row
        $table->addRow(800, ['tblHeader' => true]);
        $headerBg = ['bgColor' => '4472C4'];
        $headerFont = ['bold' => true, 'color' => 'FFFFFF', 'size' => 10];

        $table->addCell(500, $headerBg)->addText('N°', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1500, $headerBg)->addText('PROCESSUS', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2500, $headerBg)->addText('RISQUE/OPPORTUNITÉ', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1500, $headerBg)->addText('CAUSES', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(800, $headerBg)->addText('PROB', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(800, $headerBg)->addText('GRAV', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1000, $headerBg)->addText('CRITICITÉ', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(2000, $headerBg)->addText('ACTIONS MAÎTRISE', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1200, $headerBg)->addText('RESPONSABLE', $headerFont, ['alignment' => Jc::CENTER]);
        $table->addCell(1000, $headerBg)->addText('DÉLAI', $headerFont, ['alignment' => Jc::CENTER]);

        // Data rows
        $index = 1;
        foreach ($risks as $risk) {
            $table->addRow();
            
            // N°
            $table->addCell(500)->addText($index++, [], ['alignment' => Jc::CENTER]);
            
            // PROCESSUS
            $processName = $risk->process->name ?? 'N/A';
            $table->addCell(1500)->addText($processName, ['size' => 9]);
            
            // RISQUE/OPPORTUNITÉ
            $title = $risk->title ?? 'Sans titre';
            $type = $risk->type === 'opportunity' ? '[OPP]' : '[RSQ]';
            $table->addCell(2500)->addText($type . ' ' . $title, ['size' => 9]);
            
            // CAUSES
            $causes = $risk->causes ?? 'Non spécifiées';
            $table->addCell(1500)->addText($causes, ['size' => 8]);
            
            // PROB
            $probColor = $this->getProbabilityColor($risk->probability);
            $table->addCell(800, ['bgColor' => $probColor])
                ->addText($risk->probability ?? 1, ['size' => 10, 'bold' => true], ['alignment' => Jc::CENTER]);
            
            // GRAV
            $gravColor = $this->getGravityColor($risk->gravity);
            $table->addCell(800, ['bgColor' => $gravColor])
                ->addText($risk->gravity ?? 1, ['size' => 10, 'bold' => true], ['alignment' => Jc::CENTER]);
            
            // CRITICITÉ
            $criticality = ($risk->probability ?? 1) * ($risk->gravity ?? 1);
            $critColor = $this->getCriticalityColor($criticality);
            $critLevel = $this->getCriticalityLevel($criticality);
            $table->addCell(1000, ['bgColor' => $critColor])
                ->addText($critLevel, ['size' => 9, 'bold' => true, 'color' => 'FFFFFF'], ['alignment' => Jc::CENTER]);
            
            // ACTIONS MAÎTRISE
            $mitigation = $risk->mitigation_plan ?? 'Aucune action définie';
            $table->addCell(2000)->addText($mitigation, ['size' => 8]);
            
            // RESPONSABLE
            $responsible = $risk->responsible->name ?? 'Non assigné';
            $table->addCell(1200)->addText($responsible, ['size' => 9]);
            
            // DÉLAI
            $deadline = $risk->deadline ? $risk->deadline->format('d/m/Y') : 'N/A';
            $table->addCell(1000)->addText($deadline, ['size' => 9], ['alignment' => Jc::CENTER]);
        }

        // Legend
        $section->addTextBreak(2);
        $section->addText('LÉGENDE DES COULEURS:', ['bold' => true, 'size' => 11]);
        
        $legendTable = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        
        $legendTable->addRow();
        $legendTable->addCell(2000, ['bgColor' => '92D050'])->addText('Faible (1-3)', ['color' => '000000']);
        $legendTable->addCell(2000, ['bgColor' => 'FFFF00'])->addText('Moyen (4-7)', ['color' => '000000']);
        $legendTable->addCell(2000, ['bgColor' => 'FFC000'])->addText('Élevé (8-11)', ['color' => '000000']);
        $legendTable->addCell(2000, ['bgColor' => 'FF0000'])->addText('Critique (12-16)', ['color' => 'FFFFFF']);

        // Save
        $fileName = 'plan_risques_' . now()->format('Y-m-d_His') . '.docx';
        $tempPath = storage_path('app/temp/' . $fileName);
        
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempPath);

        return $tempPath;
    }

    private function getProbabilityColor(int $prob): string
    {
        if ($prob <= 2) return 'D9EAD3';
        if ($prob <= 3) return 'FFF2CC';
        if ($prob <= 4) return 'FCE4D6';
        return 'F4CCCC';
    }

    private function getGravityColor(int $grav): string
    {
        if ($grav <= 2) return 'D9EAD3';
        if ($grav <= 3) return 'FFF2CC';
        if ($grav <= 4) return 'FCE4D6';
        return 'F4CCCC';
    }

    private function getCriticalityColor(int $crit): string
    {
        if ($crit <= 3) return '92D050';
        if ($crit <= 7) return 'FFFF00';
        if ($crit <= 11) return 'FFC000';
        return 'FF0000';
    }

    private function getCriticalityLevel(int $crit): string
    {
        if ($crit <= 3) return 'FAIBLE';
        if ($crit <= 7) return 'MOYEN';
        if ($crit <= 11) return 'ÉLEVÉ';
        return 'CRITIQUE';
    }
}
