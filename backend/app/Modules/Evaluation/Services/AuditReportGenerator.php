<?php

namespace App\Modules\Evaluation\Services;

use App\Models\Document;
use App\Models\Site;
use App\Services\DocumentBrandingService;

use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\SimpleType\Jc;
use Illuminate\Support\Facades\Storage;

class AuditReportGenerator
{
    private DocumentBrandingService $brandingService;

    public function __construct()
    {
        $this->brandingService = app(DocumentBrandingService::class);
    }

    /**
     * Génère rapport PDF
     */
    public function generatePdf(Audit $audit): string
    {
        $data = $this->prepareReportData($audit);
        $enterprise = $audit->site?->enterprise;
        if ($enterprise) {
            $data['branding'] = $this->brandingService->getPdfBranding($enterprise, 'audit_report', $audit->id);
        }

        $pdf = Pdf::loadView('reports.audit-report', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 15)
            ->setOption('margin-bottom', 15)
            ->setOption('margin-left', 15)
            ->setOption('margin-right', 15);

        $filename = "audit_{$audit->ref}_{($audit->actual_date?->format('Ymd') ?? 'draft')}.pdf";
        $filepath = "audits/reports/{$filename}";

        Storage::put($filepath, $pdf->output());

        return $filepath;
    }

    /**
     * Génère rapport DOCX (conforme ISO)
     */
    public function generateDocx(Audit $audit): string
    {
        $phpWord = new PhpWord();

        // Styles
        $phpWord->addFontStyle('title', ['name' => 'Arial', 'size' => 18, 'bold' => true, 'color' => '1F4E78']);
        $phpWord->addFontStyle('heading1', ['name' => 'Arial', 'size' => 14, 'bold' => true, 'color' => '2E5C8A']);
        $phpWord->addFontStyle('heading2', ['name' => 'Arial', 'size' => 12, 'bold' => true]);
        $phpWord->addFontStyle('normal', ['name' => 'Calibri', 'size' => 11]);
        $phpWord->addFontStyle('bold', ['name' => 'Calibri', 'size' => 11, 'bold' => true]);

        $phpWord->addParagraphStyle('centered', ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);
        $phpWord->addParagraphStyle('justified', ['alignment' => Jc::BOTH, 'spaceAfter' => 100]);

        $section = $phpWord->addSection();
        $enterprise = $audit->site?->enterprise;
        if ($enterprise) {
            $this->brandingService->applyDocxHeaderFooter($section, $enterprise);
        }

        // En-tête
        $this->addHeader($section, $audit);

        // 1. Informations générales
        $this->addGeneralInfo($section, $audit);

        // 2. Périmètre et objectifs
        $this->addScopeAndObjectives($section, $audit);

        // 3. Équipe d'audit
        $this->addAuditTeam($section, $audit);

        // 4. Constatations
        $this->addFindings($section, $audit);

        // 5. Conclusions et recommandations
        $this->addConclusions($section, $audit);

        // 6. Signatures
        $this->addSignatures($section, $audit);

        // Sauvegarder
        $filename = "rapport_audit_{$audit->ref}_{($audit->actual_date?->format('Ymd') ?? 'draft')}.docx";
        $filepath = storage_path("app/audits/reports/{$filename}");

        Storage::makeDirectory('audits/reports');

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filepath);

        return "audits/reports/{$filename}";
    }

    /**
     * En-tête rapport
     */
    protected function addHeader($section, Audit $audit): void
    {
        $section->addText(
            'RAPPORT D\'AUDIT INTERNE',
            'title',
            'centered'
        );

        $section->addText(
            $audit->title,
            'heading1',
            'centered'
        );

        $section->addText(
            "Référence : {$audit->ref}",
            'normal',
            'centered'
        );

        $section->addTextBreak(2);
    }

    /**
     * Informations générales
     */
    protected function addGeneralInfo($section, Audit $audit): void
    {
        $section->addText('1. INFORMATIONS GÉNÉRALES', 'heading1');
        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80,
            'width' => 100 * 50
        ]);

        $this->addTableRow($table, 'Site audité', $audit->site->name);
        $this->addTableRow($table, 'Type d\'audit', ucfirst($audit->type));
        $this->addTableRow($table, 'Date prévue', $audit->planned_date?->format('d/m/Y'));
        $this->addTableRow($table, 'Date de réalisation', $audit->actual_date?->format('d/m/Y'));
        $this->addTableRow($table, 'Responsable d\'audit', $audit->leadAuditor?->name);

        $processNames = $audit->auditedProcesses->pluck('title')->implode(', ');
        $this->addTableRow($table, 'Processus audités', $processNames ?: 'N/A');

        $section->addTextBreak();
    }

    /**
     * Périmètre et objectifs
     */
    protected function addScopeAndObjectives($section, Audit $audit): void
    {
        $section->addText('2. PÉRIMÈTRE ET OBJECTIFS', 'heading1');
        $section->addTextBreak();

        $section->addText('2.1 Périmètre', 'heading2');
        $section->addText($audit->scope ?: 'Non défini', 'normal', 'justified');
        $section->addTextBreak();

        $section->addText('2.2 Objectifs', 'heading2');
        $section->addText($audit->objectives ?: 'Non défini', 'normal', 'justified');
        $section->addTextBreak();

        if ($audit->risk_based_criteria) {
            $section->addText('2.3 Critères basés sur les risques', 'heading2');
            $section->addText($audit->risk_based_criteria, 'normal', 'justified');
            $section->addTextBreak();
        }
    }

    /**
     * Équipe d'audit
     */
    protected function addAuditTeam($section, Audit $audit): void
    {
        $section->addText('3. ÉQUIPE D\'AUDIT', 'heading1');
        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 80
        ]);

        // En-tête
        $table->addRow();
        $table->addCell(3000)->addText('Nom', 'bold');
        $table->addCell(2000)->addText('Rôle', 'bold');
        $table->addCell(3000)->addText('Domaine d\'expertise', 'bold');

        // Auditeurs
        foreach ($audit->auditors as $auditor) {
            $table->addRow();
            $table->addCell(3000)->addText($auditor->name, 'normal');
            $role = $auditor->pivot->role === 'lead' ? 'Responsable' : ucfirst($auditor->pivot->role);
            $table->addCell(2000)->addText($role, 'normal');
            $table->addCell(3000)->addText($auditor->pivot->expertise_areas ?? '', 'normal');
        }

        $section->addTextBreak();

        // Audités
        $auditeeNames = $audit->auditees->pluck('name')->implode(', ');
        $section->addText('Personnes auditées : ' . ($auditeeNames ?: 'N/A'), 'normal');
        $section->addTextBreak();
    }

    /**
     * Constatations
     */
    protected function addFindings($section, Audit $audit): void
    {
        $section->addText('4. CONSTATATIONS', 'heading1');
        $section->addTextBreak();

        $findings = $audit->findings()->orderBy('severity', 'desc')->get();

        if ($findings->isEmpty()) {
            $section->addText('Aucune constatation enregistrée.', 'normal');
            $section->addTextBreak();
            return;
        }

        // Statistiques
        $majorCount = $findings->where('type', 'nc_major')->count();
        $minorCount = $findings->where('type', 'nc_minor')->count();
        $obsCount = $findings->where('type', 'observation')->count();
        $oppCount = $findings->where('type', 'opportunity')->count();

        $section->addText(
            "Total : {$findings->count()} constatation(s) - NC Majeures: {$majorCount}, NC Mineures: {$minorCount}, Observations: {$obsCount}, Opportunités: {$oppCount}",
            'bold'
        );
        $section->addTextBreak();

        // Détails par type
        foreach (['nc_major', 'nc_minor', 'observation', 'opportunity'] as $type) {
            $typedFindings = $findings->where('type', $type);

            if ($typedFindings->isEmpty())
                continue;

            $typeLabel = match ($type) {
                'nc_major' => '4.1 Non-Conformités Majeures',
                'nc_minor' => '4.2 Non-Conformités Mineures',
                'observation' => '4.3 Observations',
                'opportunity' => '4.4 Opportunités d\'Amélioration'
            };

            $section->addText($typeLabel, 'heading2');
            $section->addTextBreak();

            foreach ($typedFindings as $index => $finding) {
                $num = $index + 1;
                $section->addText("{$num}. {$finding->title}", 'bold');
                $section->addText("Clause ISO : {$finding->clause_iso}", 'normal');
                $section->addText("Description : {$finding->description}", 'normal');

                if ($finding->evidence) {
                    $section->addText("Preuve : {$finding->evidence}", 'normal');
                }

                if ($finding->root_cause) {
                    $section->addText("Cause racine : {$finding->root_cause}", 'normal');
                }

                $section->addTextBreak();
            }
        }
    }

    /**
     * Conclusions
     */
    protected function addConclusions($section, Audit $audit): void
    {
        $section->addText('5. CONCLUSIONS ET RECOMMANDATIONS', 'heading1');
        $section->addTextBreak();

        $section->addText('5.1 Taux de conformité', 'heading2');
        $conformityRate = $audit->conformity_rate ?? 0;
        $section->addText("Taux de conformité global : {$conformityRate}%", 'bold');
        $section->addTextBreak();

        $section->addText('5.2 Conclusion générale', 'heading2');
        $section->addText($audit->conclusion ?: 'En attente de finalisation', 'normal', 'justified');
        $section->addTextBreak();
    }

    /**
     * Signatures
     */
    protected function addSignatures($section, Audit $audit): void
    {
        $section->addText('6. SIGNATURES', 'heading1');
        $section->addTextBreak();

        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '999999',
            'cellMargin' => 200
        ]);

        $table->addRow();
        $table->addCell(4500)->addText('Responsable d\'audit', 'bold');
        $table->addCell(4500)->addText('Responsable du site audité', 'bold');

        $table->addRow(1500);
        $table->addCell(4500)->addText($audit->leadAuditor?->name ?? '', 'normal');
        $table->addCell(4500)->addText($audit->site->manager_name ?? '', 'normal');

        $table->addRow();
        $table->addCell(4500)->addText('Date : ' . now()->format('d/m/Y'), 'normal');
        $table->addCell(4500)->addText('Date : ', 'normal');

        $section->addTextBreak(2);
        $section->addText(
            'Document généré automatiquement le ' . now()->format('d/m/Y à H:i'),
            ['size' => 9, 'italic' => true],
            'centered'
        );
    }

    /**
     * Ajouter une ligne de tableau
     */
    protected function addTableRow($table, string $label, string $value): void
    {
        $table->addRow();
        $table->addCell(3500)->addText($label, 'bold');
        $table->addCell(6000)->addText($value, 'normal');
    }

    /**
     * Préparer données pour PDF
     */
    protected function prepareReportData(Audit $audit): array
    {
        return [
            'audit' => $audit->load([
                'site',
                'leadAuditor',
                'auditors',
                'auditees',
                'auditedProcesses',
                'findings.process',
                'nonConformities'
            ]),
            'generatedAt' => now()->format('d/m/Y H:i'),
        ];
    }
}
