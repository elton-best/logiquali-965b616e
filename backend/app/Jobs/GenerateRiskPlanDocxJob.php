<?php

namespace App\Jobs;

use App\Models\Risk;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use App\Events\System\ExportCompleted;

class GenerateRiskPlanDocxJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    public function __construct(
        public int $userId,
        public array $riskIds = []
    ) {}

    public function handle(): void
    {
        $user = User::find($this->userId);

        if (!$user) {
            \Log::error("User {$this->userId} not found for DOCX export job");
            return;
        }

        try {
            // Récupérer les risques
            $query = Risk::with(['process', 'actions']);

            if (!empty($this->riskIds)) {
                $query->whereIn('id', $this->riskIds);
            }

            $risks = $query->orderBy('criticality', 'desc')->get();

            // Créer document PhpWord
            $phpWord = new PhpWord();
            $section = $phpWord->addSection();

            // Titre
            $section->addText(
                'PLAN DE MAÎTRISE DES RISQUES ET OPPORTUNITÉS',
                ['bold' => true, 'size' => 16],
                ['alignment' => 'center']
            );

            $section->addTextBreak(2);

            // Date génération
            $section->addText(
                'Généré le : ' . now()->format('d/m/Y à H:i'),
                ['size' => 10],
                ['alignment' => 'right']
            );

            $section->addTextBreak(1);

            // Tableau des risques
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => '000000',
                'cellMargin' => 80,
            ]);

            // En-têtes
            $table->addRow(500);
            $headers = ['N°', 'Processus', 'Risque', 'Causes', 'P', 'G', 'C', 'Actions'];
            foreach ($headers as $header) {
                $table->addCell(1500, ['bgColor' => 'CCCCCC'])
                    ->addText($header, ['bold' => true, 'size' => 9]);
            }

            // Lignes de données
            foreach ($risks as $index => $risk) {
                $table->addRow();
                $table->addCell(800)->addText($index + 1, ['size' => 9]);
                $table->addCell(2000)->addText($risk->process?->title ?? 'N/A', ['size' => 9]);
                $table->addCell(3000)->addText($risk->title, ['size' => 9]);
                $table->addCell(3000)->addText($risk->root_cause ?? '', ['size' => 9]);
                $table->addCell(600)->addText($risk->probability ?? 0, ['size' => 9]);
                $table->addCell(600)->addText($risk->gravity ?? 0, ['size' => 9]);
                $table->addCell(600)->addText($risk->criticality ?? 0, ['size' => 9, 'bold' => true]);
                
                $actionsText = $risk->actions->pluck('title')->join('; ');
                $table->addCell(3000)->addText($actionsText, ['size' => 8]);
            }

            // Sauvegarder
            $fileName = 'plan_risques_' . now()->format('Y-m-d_His') . '.docx';
            $filePath = storage_path('app/exports/' . $fileName);

            // Créer le dossier si nécessaire
            if (!file_exists(dirname($filePath))) {
                mkdir(dirname($filePath), 0755, true);
            }

            $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
            $objWriter->save($filePath);

            // Déclencher l'event d'export terminé
            event(new ExportCompleted(
                $user,
                $fileName,
                Storage::url('exports/' . $fileName),
                $risks->count(),
                true,
                null,
                'risk_plan_docx'
            ));

            \Log::info("Risk plan DOCX export completed for user {$this->userId}: {$fileName}");

        } catch (\Exception $e) {
            \Log::error("Risk plan DOCX export failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        \Log::error("Risk plan DOCX job failed for user {$this->userId}: " . $exception->getMessage());

        $user = User::find($this->userId);
        if ($user) {
            event(new ExportCompleted(
                $user,
                'plan_risques',
                '',
                0,
                false,
                [$exception->getMessage()],
                'risk_plan_docx'
            ));
        }
    }
}
