<?php

namespace App\Services;

use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Carbon\Carbon;

class ProcedureDocxGenerator
{
    public function generate(array $payload): array
    {
        $templatePath = resource_path('templates/procedure_canevas_template.docx');
        $processor = new TemplateProcessor($templatePath);

        $title = $payload['title'] ?? 'PROCEDURE';
        $processor->setValue('PROCEDURE_TITRE', strtoupper($title));
        $processor->setValue('PROCEDURE_CODE', (string) ($payload['code'] ?? ''));
        $processor->setValue('PROCEDURE_VERSION', (string) ($payload['version'] ?? '1.0'));
        $processor->setValue('PROCEDURE_DATE', $this->formatDate($payload['date_creation'] ?? null));

        $processor->setValue('OBJET', $this->normalizeValue($payload['objet'] ?? ''));
        $processor->setValue('CHAMP_APPLICATION', $this->normalizeValue($payload['champ_application'] ?? ''));
        $processor->setValue('TERMES_DEFINITIONS', $this->normalizeValue($payload['termes_definitions'] ?? ''));
        $processor->setValue('REFERENCES', $this->normalizeValue($payload['references'] ?? ''));
        $processor->setValue('DIFFUSION', $this->normalizeValue($payload['diffusion'] ?? ''));
        $processor->setValue('ACTEURS', $this->normalizeValue($payload['acteurs'] ?? ''));
        $processor->setValue('SUPPORT_FAITS', $this->normalizeValue($payload['support_faits'] ?? ''));
        $processor->setValue('FORMULAIRE_SUPPORT', $this->normalizeValue($payload['formulaire_support'] ?? ''));
        $processor->setValue('ARCHIVAGE', $this->normalizeValue($payload['archivage'] ?? ''));
        $processor->setValue('MISE_A_JOUR', $this->normalizeValue($payload['mise_a_jour'] ?? ''));

        $this->fillHistorique($processor, $payload);
        $this->fillMiseEnOeuvre($processor, Arr::wrap($payload['mise_en_oeuvre'] ?? []));

        $filename = sprintf('procedure_%s.docx', now()->format('Ymd_His'));
        $safeName = Str::slug(pathinfo($filename, PATHINFO_FILENAME)) . '.docx';
        $tempFile = tempnam(sys_get_temp_dir(), 'procedure_');
        $processor->saveAs($tempFile);

        return [
            'path' => $tempFile,
            'filename' => $safeName,
        ];
    }

    private function fillHistorique(TemplateProcessor $processor, array $payload): void
    {
        $history = $payload['historique'] ?? [];
        if (!is_array($history) || count($history) === 0) {
            $history = [
                [
                    'numero' => 1,
                    'date' => $payload['date_creation'] ?? now()->toDateString(),
                    'redacteur' => $payload['redacteur'] ?? '',
                    'verificateur' => $payload['verificateur'] ?? '',
                    'approbateur' => $payload['approbateur'] ?? '',
                ]
            ];
        }

        $processor->cloneRow('HISTO_NUM', count($history));
        foreach (array_values($history) as $index => $row) {
            $suffix = '#' . ($index + 1);
            $processor->setValue('HISTO_NUM' . $suffix, (string) ($row['numero'] ?? ''));
            $processor->setValue('HISTO_DATE' . $suffix, $this->formatDate($row['date'] ?? null));
            $processor->setValue('HISTO_REDACTEUR' . $suffix, (string) ($row['redacteur'] ?? ''));
            $processor->setValue('HISTO_VERIFICATEUR' . $suffix, (string) ($row['verificateur'] ?? ''));
            $processor->setValue('HISTO_APPROBATEUR' . $suffix, (string) ($row['approbateur'] ?? ''));
        }
    }

    private function fillMiseEnOeuvre(TemplateProcessor $processor, array $rows): void
    {
        if (count($rows) === 0) {
            $rows = [
                [
                    'activite' => '',
                    'details' => '',
                    'responsable' => '',
                    'livrables' => '',
                    'delai' => '',
                ]
            ];
        }

        $processor->cloneRow('MISE_ACTIVITE', count($rows));
        foreach (array_values($rows) as $index => $row) {
            $suffix = '#' . ($index + 1);
            $processor->setValue('MISE_ACTIVITE' . $suffix, (string) ($row['activite'] ?? ''));
            $processor->setValue('MISE_SOUS_ACTIVITE' . $suffix, (string) ($row['details'] ?? ''));
            $processor->setValue('MISE_RESPONSABLE' . $suffix, (string) ($row['responsable'] ?? ''));
            $processor->setValue('MISE_LIVRABLES' . $suffix, (string) ($row['livrables'] ?? ''));
            $processor->setValue('MISE_DELAI' . $suffix, (string) ($row['delai'] ?? ''));
        }
    }

    private function normalizeValue(?string $content): string
    {
        $content = trim((string) $content);
        if ($content === '') {
            return '';
        }
        return preg_replace('/\\r\\n|\\r/', '\"\\n\"', $content);
    }

    private function formatDate($value): string
    {
        if (empty($value)) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    }
}
