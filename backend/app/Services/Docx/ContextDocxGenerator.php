<?php

namespace App\Services\Docx;

use App\Models\Context;
use App\Models\Site;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class ContextDocxGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    public function generate(int $siteId): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $enterprise = Site::with('enterprise')->find($siteId)?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // Garder le format historique (titre/date/entête)
        $section->addText(
            'CONTEXTE DE L\'ORGANISME',
            ['bold' => true, 'size' => 16, 'color' => '2E74B5'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 320]
        );

        $section->addText(
            'Date: ' . now()->format('d/m/Y'),
            ['size' => 10, 'italic' => true],
            ['alignment' => Jc::RIGHT, 'spaceAfter' => 220]
        );

        $context = Context::query()
            ->where('site_id', $siteId)
            ->where('type', 'swot_pestel')
            ->latest('updated_at')
            ->first();

        if (!$context) {
            $section->addText(
                'Aucune analyse SWOT/PESTEL trouvée pour ce site.',
                ['italic' => true, 'size' => 11],
                ['spaceAfter' => 120]
            );
            return $this->save($phpWord, $siteId);
        }

        $internal = $this->decodeJsonArray($context->swot_weaknesses);
        $external = $this->decodeJsonArray($context->swot_opportunities);
        $majorIssues = $this->decodeJsonArray($context->swot_threats);

        // CORPS adapté au canevas M3-D1 (sans casser le format global existant)
        $section->addText('1. ANALYSE SWOT', ['bold' => true, 'size' => 13], ['spaceAfter' => 140]);
        $this->addSwotMatrixTable(
            $section,
            $this->extractCriteriaItems($internal, 'favorable'),
            $this->extractCriteriaItems($internal, 'unfavorable'),
            $this->extractCriteriaItems($external, 'favorable'),
            $this->extractCriteriaItems($external, 'unfavorable')
        );

        $section->addTextBreak(1);
        $section->addText('2. ANALYSE PESTEL', ['bold' => true, 'size' => 13], ['spaceAfter' => 140]);
        $this->addPestelTable($section, $external);

        $section->addTextBreak(1);
        $section->addText('3. LISTE DES ENJEUX MAJEURS', ['bold' => true, 'size' => 13], ['spaceAfter' => 120]);
        if (count($majorIssues) === 0) {
            $section->addText('Aucun enjeu majeur.', ['italic' => true, 'size' => 11]);
        } else {
            foreach ($majorIssues as $issue) {
                $value = trim((string) $issue);
                if ($value !== '') {
                    $section->addListItem($value, 0, ['size' => 11], ['spaceAfter' => 60]);
                }
            }
        }

        return $this->save($phpWord, $siteId);
    }

    private function addSwotMatrixTable($section, array $forces, array $weaknesses, array $opportunities, array $threats): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        $table->addRow();
        $table->addCell(4800, ['gridSpan' => 2])->addText('ENJEUX INTERNES', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(4800, ['gridSpan' => 2])->addText('ENJEUX EXTERNES', ['bold' => true], ['alignment' => Jc::CENTER]);

        $table->addRow();
        $table->addCell(2400)->addText('FORCES', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2400)->addText('FAIBLESSES', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2400)->addText('OPPORTUNITÉS', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(2400)->addText('MENACES', ['bold' => true], ['alignment' => Jc::CENTER]);

        $table->addRow();
        $forceCell = $table->addCell(2400);
        $weaknessCell = $table->addCell(2400);
        $opportunityCell = $table->addCell(2400);
        $threatCell = $table->addCell(2400);

        $this->addBulletedLinesToCell($forceCell, $forces);
        $this->addBulletedLinesToCell($weaknessCell, $weaknesses);
        $this->addBulletedLinesToCell($opportunityCell, $opportunities);
        $this->addBulletedLinesToCell($threatCell, $threats);
    }

    private function addPestelTable($section, array $external): void
    {
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
        ]);

        $table->addRow();
        $table->addCell(2200)->addText('FACTEURS', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(3800)->addText('Ce qui est favorable', ['bold' => true], ['alignment' => Jc::CENTER]);
        $table->addCell(3800)->addText('Ce qui est défavorable', ['bold' => true], ['alignment' => Jc::CENTER]);

        $rows = [
            'Politique' => 'political',
            'Économique' => 'economic',
            'Sociologique' => 'social',
            'Technologique' => 'technological',
            'Environnemental' => 'environmental',
            'Légal' => 'legal',
        ];

        foreach ($rows as $label => $key) {
            $data = $external[$key] ?? [];
            $favorable = is_array($data['favorable'] ?? null) ? $data['favorable'] : [];
            $unfavorable = is_array($data['unfavorable'] ?? null) ? $data['unfavorable'] : [];

            $table->addRow();
            $table->addCell(2200)->addText($label, ['bold' => true, 'size' => 10], ['alignment' => Jc::CENTER]);
            $favorableCell = $table->addCell(3800);
            $unfavorableCell = $table->addCell(3800);
            $this->addBulletedLinesToCell($favorableCell, $favorable);
            $this->addBulletedLinesToCell($unfavorableCell, $unfavorable);
        }
    }

    private function addBulletedLinesToCell(\PhpOffice\PhpWord\Element\Cell $cell, array $items): void
    {
        $values = array_values(array_filter(array_map(
            static fn ($v) => trim((string) $v),
            $items
        )));

        if (count($values) === 0) {
            $cell->addText('', ['size' => 10]);
            return;
        }

        foreach ($values as $value) {
            $cell->addText('• ' . $value, ['size' => 10], ['spaceAfter' => 40]);
        }
    }

    private function save(PhpWord $phpWord, int $siteId): string
    {
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0775, true);
        }

        $outputPath = storage_path('app/temp/Contexte_Organisme_' . $siteId . '_' . time() . '.docx');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($outputPath);

        return $outputPath;
    }

    private function decodeJsonArray(?string $value): array
    {
        if (!$value) {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function extractCriteriaItems(array $criteria, string $type): array
    {
        $items = [];

        foreach ($criteria as $criterion) {
            if (!is_array($criterion) || !isset($criterion[$type]) || !is_array($criterion[$type])) {
                continue;
            }

            foreach ($criterion[$type] as $entry) {
                $text = trim((string) $entry);
                if ($text !== '') {
                    $items[] = $text;
                }
            }
        }

        return $items;
    }
}
