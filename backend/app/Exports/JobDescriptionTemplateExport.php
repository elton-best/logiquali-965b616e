<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class JobDescriptionTemplateExport implements WithMultipleSheets
{
    public function __construct(
        private readonly int $enterpriseId
    ) {
    }

    /**
     * Retourner les 3 feuilles du template.
     *
     * @return array
     */
    public function sheets(): array
    {
        return [
            new JobDescriptionTemplateDataSheet(),
            new JobDescriptionTemplateInstructionsSheet(),
            new JobDescriptionTemplateCollaboratorsSheet($this->enterpriseId),
        ];
    }
}

