<?php

namespace App\Modules\Support\Services;

use App\Models\Site;
use Illuminate\Validation\ValidationException;

class DocumentCodeGenerationService
{
    public function __construct(
        private CodeGenerationService $codeGenerationService,
        private NomenclatureTemplateService $templateService
    ) {}

    public function generateForSiteAndType(
        int $siteId,
        string $typeAbbreviation,
        ?int $processId = null,
        ?int $configurationId = null,
        ?string $semanticKey = null
    ): array {
        $site = Site::query()->findOrFail($siteId);
        $abbreviation = strtoupper(trim($typeAbbreviation));

        $template = $this->templateService->resolveActiveTemplate($siteId, $abbreviation);

        if (!$template) {
            throw ValidationException::withMessages([
                'nomenclature_template_id' => [
                    "Aucun template de nomenclature publié n'existe pour le type {$abbreviation}.",
                ],
            ]);
        }

        $config = $this->templateService->syncTechnicalConfiguration($template);

        if ($config->structureParts->isEmpty()) {
            throw ValidationException::withMessages([
                'document_type_configuration_id' => [
                    "Le template {$template->name} n'a pas de structure de code publiée.",
                ],
            ]);
        }

        $generation = $this->codeGenerationService->generateCode($config->id, [
            'site_id' => $siteId,
            'enterprise_id' => (int) $site->enterprise_id,
            'process_id' => $processId,
            'year' => now()->year,
            'month' => now()->month,
            'day' => now()->day,
        ]);

        return [
            'code' => $generation['code'],
            'sequence_id' => $generation['sequence_id'] ?? null,
            'document_type_configuration_id' => $config->id,
            'nomenclature_template_id' => $template->id,
            'nomenclature_template_version' => $template->version,
            'is_fallback' => false,
        ];
    }
}
