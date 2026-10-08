<?php

namespace App\Services\Docx;

use App\Models\QhsePolicy;
use App\Services\DocumentBrandingService;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class QhsePolicyDocxGenerator
{
    public function __construct(
        protected DocumentBrandingService $brandingService
    ) {
    }

    public function generate(QhsePolicy $policy): string
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'marginLeft' => 1200,
            'marginRight' => 1200,
            'marginTop' => 1200,
            'marginBottom' => 1200,
        ]);

        $enterprise = $policy->enterprise ?? $policy->site?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise, 'qhse_policy', $policy->id);
        }

        $section->addText(
            'POLITIQUE QHSE',
            ['bold' => true, 'size' => 18, 'color' => '2E3B55'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 260]
        );

        $meta = $section->addTable(['borderSize' => 6, 'borderColor' => 'D9D9D9']);
        $meta->addRow();
        $meta->addCell(2600)->addText('Version', ['bold' => true, 'size' => 10]);
        $meta->addCell(7000)->addText((string) ($policy->version ?: '1.0'), ['size' => 10]);
        $meta->addRow();
        $meta->addCell(2600)->addText('Date d\'effet', ['bold' => true, 'size' => 10]);
        $meta->addCell(7000)->addText(optional($policy->effective_date)->format('d/m/Y') ?: 'N/A', ['size' => 10]);
        $meta->addRow();
        $meta->addCell(2600)->addText('Statut', ['bold' => true, 'size' => 10]);
        $meta->addCell(7000)->addText($policy->status === 'validated' ? 'Validée' : 'Brouillon', ['size' => 10]);
        $section->addTextBreak(1);

        $this->addParagraphSection($section, 'Mission', $policy->mission);
        $this->addParagraphSection($section, 'Vision', $policy->vision);
        $this->addListSection($section, 'Valeurs', $policy->values ?? []);
        $this->addListSection($section, 'Engagements', $policy->commitments ?? []);
        $this->addParagraphSection($section, 'Politique Qualité', $policy->quality_policy);
        $this->addParagraphSection($section, 'Politique Environnementale', $policy->environmental_policy);
        $this->addParagraphSection($section, 'Politique Santé & Sécurité', $policy->health_safety_policy);

        $section->addTextBreak(2);
        $section->addText(
            'Document généré le ' . now()->format('d/m/Y H:i'),
            ['italic' => true, 'size' => 9, 'color' => '666666'],
            ['alignment' => Jc::CENTER]
        );

        $filename = 'politique_qhse_v' . ($policy->version ?: '1.0') . '_' . now()->format('Ymd_His') . '.docx';
        $path = storage_path('app/temp/' . $filename);
        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    protected function addParagraphSection($section, string $title, ?string $content): void
    {
        if (!$content) {
            return;
        }

        $section->addText($title, ['bold' => true, 'size' => 13, 'color' => '2E3B55'], ['spaceAfter' => 120]);
        $section->addText($content, ['size' => 11], ['spaceAfter' => 220]);
    }

    protected function addListSection($section, string $title, array $items): void
    {
        $cleanItems = array_values(array_filter($items, fn ($item) => is_string($item) && trim($item) !== ''));
        if (empty($cleanItems)) {
            return;
        }

        $section->addText($title, ['bold' => true, 'size' => 13, 'color' => '2E3B55'], ['spaceAfter' => 120]);
        foreach ($cleanItems as $item) {
            $section->addListItem($item, 0, ['size' => 11]);
        }
        $section->addTextBreak(1);
    }
}
