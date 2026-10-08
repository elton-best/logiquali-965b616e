<?php

namespace App\Http\Controllers\Api;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Controllers\Controller;
use App\Models\Enterprise;
use App\Models\ProviderContract;
use App\Models\ProviderContractTemplate;
use App\Models\ProviderPartner;
use App\Models\ProviderPartnerFile;
use App\Models\Site;
use App\Services\DocumentTypeResolver;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Utils\ExportHeaders;

class ProviderPartnerController extends Controller
{
    public function index(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $search = trim((string) $request->query('search', ''));
        $perPage = min(100, max(1, (int) $request->query('per_page', 20)));

        $query = ProviderPartner::query()
            ->where('enterprise_id', $enterpriseId)
            ->with(['contract', 'files'])
            ->orderBy('designation');

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('designation', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('ifu', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $validated = $this->validateProvider($request);

        $provider = ProviderPartner::create([
            ...$validated,
            'enterprise_id' => $enterpriseId,
            'reference' => $this->generateProviderReference($enterpriseId),
            'created_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $provider->load(['contract', 'files'])], 201);
    }

    public function show(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        return response()->json(['data' => $providerPartner->load(['contract', 'files'])]);
    }

    public function update(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $validated = $this->validateProvider($request, true);
        $providerPartner->update([
            ...$validated,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $providerPartner->fresh()->load(['contract', 'files'])]);
    }

    public function destroy(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        foreach ($providerPartner->files as $file) {
            if (!empty($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        }

        $providerPartner->delete();
        return response()->json(null, 204);
    }

    public function filesIndex(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $files = $providerPartner->files()->get();

        return response()->json([
            'data' => $files->map(fn (ProviderPartnerFile $file) => $this->providerFilePayload($file))->values(),
        ]);
    }

    public function uploadFile(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $validated = $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,csv,png,jpg,jpeg,webp,txt',
            'title' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:1500',
        ]);

        $uploadedFile = $validated['file'];
        $path = $uploadedFile->store(
            "provider-partners/{$enterpriseId}/{$providerPartner->id}/dossier",
            'public',
        );

        $record = ProviderPartnerFile::create([
            'enterprise_id' => $enterpriseId,
            'provider_partner_id' => $providerPartner->id,
            'title' => trim((string) ($validated['title'] ?? '')) ?: null,
            'category' => trim((string) ($validated['category'] ?? 'piece')) ?: 'piece',
            'note' => trim((string) ($validated['note'] ?? '')) ?: null,
            'file_path' => $path,
            'file_name' => $uploadedFile->getClientOriginalName(),
            'file_mime' => $uploadedFile->getClientMimeType(),
            'file_size' => (int) $uploadedFile->getSize(),
            'uploaded_by' => $request->user()?->id,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Pièce ajoutée au dossier prestataire.',
            'data' => $this->providerFilePayload($record),
        ], 201);
    }

    public function destroyFile(Request $request, ProviderPartner $providerPartner, ProviderPartnerFile $providerFile)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);
        abort_unless((int) $providerFile->provider_partner_id === (int) $providerPartner->id, 404);
        abort_unless((int) $providerFile->enterprise_id === (int) $enterpriseId, 404);

        if (!empty($providerFile->file_path)) {
            Storage::disk('public')->delete($providerFile->file_path);
        }

        $providerFile->delete();

        return response()->json(null, 204);
    }

    public function importRows(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $validated = $request->validate([
            'rows' => 'required|array|min:1|max:2000',
            'rows.*.designation' => 'required|string|max:255',
            'rows.*.provider_type' => 'nullable|string|max:64',
            'rows.*.legal_form' => 'nullable|string|max:255',
            'rows.*.service_offers' => 'nullable|string',
            'rows.*.phone_primary' => 'nullable|string|max:64',
            'rows.*.phone_secondary' => 'nullable|string|max:64',
            'rows.*.email' => 'nullable|email|max:255',
            'rows.*.ifu' => 'nullable|string|max:128',
            'rows.*.experience_years' => 'nullable|integer|min:0|max:100',
            'rows.*.evaluation_observation' => 'nullable|string',
        ]);

        [$created, $updated] = $this->persistImportedRows($validated['rows'], $enterpriseId, $request);

        return response()->json([
            'message' => 'Import prestataires terminé.',
            'created' => $created,
            'updated' => $updated,
        ]);
    }

    public function importFile(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        $path = $validated['file']->store('imports/provider-partners');

        try {
            $rows = $this->extractProviderRowsFromFile(Storage::path($path));
            if (count($rows) === 0) {
                return response()->json([
                    'message' => 'Aucune ligne prestataire détectée dans le fichier.',
                    'created' => 0,
                    'updated' => 0,
                ], 422);
            }

            [$created, $updated] = $this->persistImportedRows($rows, $enterpriseId, $request);

            return response()->json([
                'message' => 'Import prestataires terminé.',
                'created' => $created,
                'updated' => $updated,
            ]);
        } finally {
            Storage::delete($path);
        }
    }

    public function exportXlsx(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $search = trim((string) $request->query('search', ''));

        $query = ProviderPartner::query()
            ->where('enterprise_id', $enterpriseId)
            ->orderBy('designation');

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('designation', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhere('ifu', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $providers = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Prestataires');

        $headers = [
            'N°',
            'Référence',
            'Désignation',
            'Type de prestataire',
            'Forme juridique',
            'Offres',
            'Téléphone 1',
            'Téléphone 2',
            'Email',
            'IFU',
            'Années expérience',
            'Observation',
        ];

        $sheet->fromArray($headers, null, 'A1');
        $row = 2;
        foreach ($providers as $index => $provider) {
            $sheet->fromArray([
                $index + 1,
                (string) ($provider->reference ?? ''),
                (string) ($provider->designation ?? ''),
                (string) ($provider->provider_type ?? ''),
                (string) ($provider->legal_form ?? ''),
                (string) ($provider->service_offers ?? ''),
                (string) ($provider->phone_primary ?? ''),
                (string) ($provider->phone_secondary ?? ''),
                (string) ($provider->email ?? ''),
                (string) ($provider->ifu ?? ''),
                $provider->experience_years ?? '',
                (string) ($provider->evaluation_observation ?? ''),
            ], null, 'A' . $row);
            $row++;
        }

        foreach (range('A', 'L') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        $filename = 'base_prestataires_' . now()->format('Y-m-d') . '.xlsx';
        $tempPath = Storage::disk('local')->path('exports/' . $filename);
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        (new Xlsx($spreadsheet))->save($tempPath);

        $siteId = (int) ($request->user()?->site_id ?? 0);
        if ($siteId <= 0 && $enterpriseId > 0) {
            $siteId = (int) Site::query()
                ->where('enterprise_id', $enterpriseId)
                ->orderBy('id')
                ->value('id');
        }
        if ($siteId > 0) {
            app(\App\Services\DocumentSyncService::class)->syncGeneratedProcessDocument([
                'site_id' => $siteId,
                'process_id' => null,
                'process_code' => 'GEN',
                'document_kind' => 'provider_partners_export_xlsx',
                'title' => 'Export base prestataires',
                'description' => 'Export XLSX de la base prestataires.',
                'file_source_path' => $tempPath,
                'file_extension' => 'xlsx',
                'created_by' => $request->user()?->id,
                'type' => DocumentTypeResolver::resolveType('procedure'),
            ]);
        }

        return response()->download(
            $tempPath,
            $filename,
            ExportHeaders::attachmentHeaders($filename, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
        )->deleteFileAfterSend(true);
    }

    private function persistImportedRows(array $rows, int $enterpriseId, Request $request): array
    {
        $created = 0;
        $updated = 0;
        DB::transaction(function () use ($rows, $enterpriseId, $request, &$created, &$updated) {
            foreach ($rows as $row) {
                $lookup = [
                    'enterprise_id' => $enterpriseId,
                    'designation' => trim((string) $row['designation']),
                ];

                if (!empty($row['ifu'])) {
                    $lookup['ifu'] = trim((string) $row['ifu']);
                }

                $provider = ProviderPartner::where($lookup)->first();
                if (!$provider) {
                    $provider = new ProviderPartner();
                    $provider->enterprise_id = $enterpriseId;
                    $provider->reference = $this->generateProviderReference($enterpriseId);
                    $provider->created_by = $request->user()?->id;
                    $created++;
                } else {
                    $updated++;
                }

                $provider->designation = $row['designation'];
                $provider->provider_type = $row['provider_type'] ?? 'autre';
                $provider->legal_form = $row['legal_form'] ?? null;
                $provider->service_offers = $row['service_offers'] ?? null;
                $provider->phone_primary = $row['phone_primary'] ?? null;
                $provider->phone_secondary = $row['phone_secondary'] ?? null;
                $provider->email = $row['email'] ?? null;
                $provider->ifu = $row['ifu'] ?? null;
                $provider->experience_years = $row['experience_years'] ?? null;
                $provider->evaluation_observation = $row['evaluation_observation'] ?? null;
                $provider->updated_by = $request->user()?->id;
                $provider->save();
            }
        });

        return [$created, $updated];
    }

    private function extractProviderRowsFromFile(string $filePath): array
    {
        $extension = strtolower((string) pathinfo($filePath, PATHINFO_EXTENSION));
        if (in_array($extension, ['csv', 'txt'], true)) {
            $rawRows = $this->parseDelimitedRows($filePath);
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rawRows = $worksheet->toArray(null, true, true, false);
        }

        $startIndex = $this->findHeaderRowIndex($rawRows);
        if ($startIndex < 0) {
            return $this->parseLegacyProviderRows($rawRows);
        }

        $headerMap = $this->buildHeaderMap($rawRows[$startIndex] ?? []);
        $rows = array_slice($rawRows, $startIndex + 1);
        $providers = [];

        foreach ($rows as $row) {
            $designation = $this->readStringCell($row, $headerMap, 'designation');
            if ($designation === '') {
                continue;
            }

            $providers[] = [
                'designation' => $designation,
                'provider_type' => $this->normalizeProviderType($this->readStringCell($row, $headerMap, 'provider_type')),
                'legal_form' => $this->readNullableStringCell($row, $headerMap, 'legal_form'),
                'service_offers' => $this->readNullableStringCell($row, $headerMap, 'service_offers'),
                'phone_primary' => $this->readNullableStringCell($row, $headerMap, 'phone_primary'),
                'phone_secondary' => $this->readNullableStringCell($row, $headerMap, 'phone_secondary'),
                'email' => $this->readNullableStringCell($row, $headerMap, 'email'),
                'ifu' => $this->readNullableStringCell($row, $headerMap, 'ifu'),
                'experience_years' => $this->parseNullableInteger($this->readCellByKey($row, $headerMap, 'experience_years')),
                'evaluation_observation' => $this->readNullableStringCell($row, $headerMap, 'evaluation_observation'),
            ];
        }

        return $providers;
    }

    private function parseDelimitedRows(string $filePath): array
    {
        $rows = [];
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return $rows;
        }

        try {
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                return $rows;
            }

            // Detect separator from first non-empty line.
            $delimiter = substr_count($firstLine, ';') >= substr_count($firstLine, ',') ? ';' : ',';
            rewind($handle);

            while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
                if (!is_array($data)) {
                    continue;
                }

                $rows[] = array_map(static fn ($cell) => is_string($cell) ? trim($cell) : $cell, $data);
            }
        } finally {
            fclose($handle);
        }

        return $rows;
    }

    private function parseLegacyProviderRows(array $rawRows): array
    {
        $startIndex = -1;
        foreach ($rawRows as $index => $row) {
            foreach ($row as $cell) {
                if (str_contains($this->normalizeHeader((string) $cell), 'designation')) {
                    $startIndex = $index;
                    break 2;
                }
            }
        }

        if ($startIndex < 0) {
            return [];
        }

        $rows = array_slice($rawRows, $startIndex + 1);
        $providers = [];

        foreach ($rows as $row) {
            $designation = trim((string) ($row[2] ?? ''));
            if ($designation === '') {
                continue;
            }

            $providers[] = [
                'designation' => $designation,
                'provider_type' => $this->normalizeProviderType((string) ($row[3] ?? '')),
                'legal_form' => null,
                'service_offers' => $this->trimToNullable($row[4] ?? null),
                'phone_primary' => $this->trimToNullable($row[5] ?? null),
                'phone_secondary' => $this->trimToNullable($row[6] ?? null),
                'email' => $this->trimToNullable($row[7] ?? null),
                'ifu' => $this->trimToNullable($row[8] ?? null),
                'experience_years' => $this->parseNullableInteger($row[9] ?? null),
                'evaluation_observation' => $this->trimToNullable($row[10] ?? null),
            ];
        }

        return $providers;
    }

    private function findHeaderRowIndex(array $rows): int
    {
        $maxScan = min(count($rows), 20);
        for ($i = 0; $i < $maxScan; $i++) {
            $normalized = array_map(fn ($cell) => $this->normalizeHeader((string) $cell), $rows[$i] ?? []);
            $hasDesignation = collect($normalized)->contains(fn ($text) => str_contains($text, 'designation') || str_contains($text, 'nomprenom') || $text === 'nom');
            $hasType = collect($normalized)->contains(fn ($text) => str_contains($text, 'type'));
            if ($hasDesignation && $hasType) {
                return $i;
            }
        }

        return -1;
    }

    private function buildHeaderMap(array $headerRow): array
    {
        $findIndexByPatterns = function (array $patterns, int $fallback = -1) use ($headerRow): int {
            foreach ($headerRow as $index => $cell) {
                $normalized = $this->normalizeHeader((string) $cell);
                foreach ($patterns as $pattern) {
                    if (str_contains($normalized, $pattern)) {
                        return (int) $index;
                    }
                }
            }

            return $fallback;
        };

        return [
            'designation' => $findIndexByPatterns(['designation', 'nomprenom', 'nom'], 2),
            'provider_type' => $findIndexByPatterns(['typeprestataire', 'typefournisseur', 'type'], 3),
            'legal_form' => $findIndexByPatterns(['formejuridique', 'typeentreprise', 'niveau']),
            'service_offers' => $findIndexByPatterns(['offreservice', 'service', 'offre'], 4),
            'phone_primary' => $findIndexByPatterns(['telephone1', 'contacttelephone1', '1contacttelephone', 'phone1', 'tel1'], 5),
            'phone_secondary' => $findIndexByPatterns(['telephone2', 'contacttelephone2', '2contacttelephone', 'phone2', 'tel2'], 6),
            'email' => $findIndexByPatterns(['mail', 'email'], 7),
            'ifu' => $findIndexByPatterns(['ifu', 'nifu'], 8),
            'experience_years' => $findIndexByPatterns(['anneeexperience', 'experience', 'anciennete'], 9),
            'evaluation_observation' => $findIndexByPatterns(['observation', 'evaluation', 'commentaire'], 10),
        ];
    }

    private function normalizeHeader(string $value): string
    {
        $ascii = Str::lower(Str::ascii($value));
        return preg_replace('/[^a-z0-9]/', '', $ascii) ?? '';
    }

    private function readCellByKey(array $row, array $headerMap, string $key): mixed
    {
        $index = $headerMap[$key] ?? null;
        if (!is_int($index) || $index < 0) {
            return null;
        }

        return $row[$index] ?? null;
    }

    private function readStringCell(array $row, array $headerMap, string $key): string
    {
        return trim((string) ($this->readCellByKey($row, $headerMap, $key) ?? ''));
    }

    private function readNullableStringCell(array $row, array $headerMap, string $key): ?string
    {
        return $this->trimToNullable($this->readCellByKey($row, $headerMap, $key));
    }

    private function trimToNullable(mixed $value): ?string
    {
        $trimmed = trim((string) ($value ?? ''));
        return $trimmed === '' ? null : $trimmed;
    }

    private function normalizeProviderType(string $value): string
    {
        $lower = Str::lower($value);
        if (str_contains($lower, 'morale')) return 'personne_morale';
        if (str_contains($lower, 'physique')) return 'personne_physique';
        return 'autre';
    }

    private function parseNullableInteger(mixed $value): ?int
    {
        if ($value === null) return null;
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }
        $parsed = (int) floor((float) $normalized);
        return $parsed >= 0 ? $parsed : null;
    }

    public function showTemplate(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $template = ProviderContractTemplate::firstOrCreate(
            ['enterprise_id' => $enterpriseId],
            [
                'title' => 'Contrat de prestation de services',
                'content' => $this->defaultTemplateContent(),
                'placeholders' => $this->placeholders(),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        return response()->json(['data' => $template]);
    }

    public function updateTemplate(Request $request)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:20',
        ]);

        $template = ProviderContractTemplate::updateOrCreate(
            ['enterprise_id' => $enterpriseId],
            [
                'title' => $validated['title'],
                'content' => $validated['content'],
                'placeholders' => $this->placeholders(),
                'updated_by' => $request->user()?->id,
                'created_by' => $request->user()?->id,
            ],
        );

        return response()->json(['data' => $template]);
    }

    public function showContract(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $contract = ProviderContract::firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'provider_partner_id' => $providerPartner->id,
            ],
            [
                'status' => 'draft',
                'currency' => 'XOF',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        return response()->json(['data' => $this->contractPayload($contract)]);
    }

    public function upsertContract(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $validated = $request->validate([
            'contract_reference' => 'nullable|string|max:128',
            'template_title' => 'nullable|string|max:255',
            'template_content' => 'nullable|string',
            'filled_content' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'amount' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'payment_terms' => 'nullable|string',
            'status' => 'nullable|in:draft,ready,signed,archived',
            'meta' => 'nullable|array',
        ]);

        $existingContract = ProviderContract::where('enterprise_id', $enterpriseId)
            ->where('provider_partner_id', $providerPartner->id)
            ->first();

        $incomingMeta = is_array($validated['meta'] ?? null) ? $validated['meta'] : [];
        $existingHistory = is_array($existingContract?->meta)
            && is_array($existingContract->meta['history'] ?? null)
            ? $existingContract->meta['history']
            : null;
        if ($existingHistory !== null && !array_key_exists('history', $incomingMeta)) {
            $incomingMeta['history'] = $existingHistory;
        }
        if (!empty($incomingMeta)) {
            $validated['meta'] = $incomingMeta;
        }

        $contract = ProviderContract::updateOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'provider_partner_id' => $providerPartner->id,
            ],
            [
                ...$validated,
                'updated_by' => $request->user()?->id,
                'created_by' => $request->user()?->id,
                'signed_at' => (($validated['status'] ?? null) === 'signed') ? now() : null,
            ],
        );

        return response()->json(['data' => $this->contractPayload($contract)]);
    }

    public function uploadSignedContract(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $validated = $request->validate([
            'file' => 'required|file|mimes:pdf|max:20480',
        ]);

        $contract = ProviderContract::firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'provider_partner_id' => $providerPartner->id,
            ],
            [
                'status' => 'draft',
                'currency' => 'XOF',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        if (!empty($contract->signed_file_path)) {
            Storage::disk('public')->delete($contract->signed_file_path);
        }

        $file = $validated['file'];
        $path = $file->store("provider-contracts/{$enterpriseId}", 'public');

        $contract->update([
            'signed_file_path' => $path,
            'signed_file_name' => $file->getClientOriginalName(),
            'status' => 'signed',
            'signed_at' => now(),
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json(['data' => $this->contractPayload($contract)]);
    }

    public function generateContractPdf(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $contract = ProviderContract::firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'provider_partner_id' => $providerPartner->id,
            ],
            [
                'status' => 'draft',
                'currency' => 'XOF',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        $enterprise = Enterprise::find($enterpriseId);
        $content = (string) ($contract->filled_content
            ?: $this->renderTemplate(
                $contract->template_content ?: $this->defaultTemplateContent(),
                $providerPartner,
                $contract,
                $enterprise,
            ));

        $pdfHtml = $this->renderContractHtml($content, $contract->template_title ?: 'Contrat prestataire');
        $pdf = Pdf::loadHTML($pdfHtml)->setPaper('a4', 'portrait');

        if (!empty($contract->generated_file_path)) {
            Storage::disk('public')->delete($contract->generated_file_path);
        }

        $filename = sprintf(
            'contrat-%s-%s.pdf',
            strtolower((string) $providerPartner->reference),
            now()->format('YmdHis'),
        );
        $path = "provider-contracts/{$enterpriseId}/generated/{$filename}";
        Storage::disk('public')->put($path, $pdf->output());

        $status = $contract->status === 'draft' ? 'ready' : $contract->status;
        $contract->update([
            'filled_content' => $content,
            'generated_file_path' => $path,
            'generated_file_name' => $filename,
            'generated_at' => now(),
            'status' => $status,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'PDF du contrat généré et enregistré.',
            'data' => $this->contractPayload($contract->fresh()),
        ]);
    }

    public function previewContract(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);
        $validated = $request->validate([
            'content' => 'nullable|string',
            'draft' => 'nullable|array',
            'draft.start_date' => 'nullable|date',
            'draft.end_date' => 'nullable|date|after_or_equal:draft.start_date',
            'draft.amount' => 'nullable|numeric|min:0',
            'draft.currency' => 'nullable|string|max:10',
            'draft.payment_terms' => 'nullable|string',
            'draft.contract_reference' => 'nullable|string|max:128',
        ]);

        $template = ProviderContractTemplate::firstOrCreate(
            ['enterprise_id' => $enterpriseId],
            [
                'title' => 'Contrat de prestation de services',
                'content' => $this->defaultTemplateContent(),
                'placeholders' => $this->placeholders(),
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        $contract = ProviderContract::where('provider_partner_id', $providerPartner->id)->first();
        $enterprise = Enterprise::find($enterpriseId);
        $content = $validated['content'] ?? ($contract?->template_content ?: $template->content);
        $draft = is_array($validated['draft'] ?? null) ? $validated['draft'] : [];
        $rendered = $this->renderTemplate($content, $providerPartner, $contract, $enterprise, $draft);

        return response()->json([
            'data' => [
                'template' => $content,
                'rendered' => $rendered,
            ],
        ]);
    }

    public function archiveContract(Request $request, ProviderPartner $providerPartner)
    {
        $enterpriseId = $this->resolveEnterpriseId($request);
        abort_unless($providerPartner->enterprise_id === $enterpriseId, 404);

        $validated = $request->validate([
            'note' => 'nullable|string|max:500',
        ]);

        $contract = ProviderContract::firstOrCreate(
            [
                'enterprise_id' => $enterpriseId,
                'provider_partner_id' => $providerPartner->id,
            ],
            [
                'status' => 'draft',
                'currency' => 'XOF',
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        );

        $hasActiveContract = !empty($contract->signed_file_path)
            || !empty($contract->generated_file_path)
            || !empty($contract->filled_content)
            || !empty($contract->contract_reference);

        abort_if(!$hasActiveContract, 422, "Aucun contrat actif à archiver pour ce prestataire.");

        $meta = is_array($contract->meta) ? $contract->meta : [];
        $history = is_array($meta['history'] ?? null) ? $meta['history'] : [];

        $history[] = [
            'id' => (string) Str::uuid(),
            'archived_at' => now()->toIso8601String(),
            'archived_by' => $request->user()?->id,
            'note' => trim((string) ($validated['note'] ?? '')),
            'status' => $contract->status,
            'contract_reference' => $contract->contract_reference,
            'template_title' => $contract->template_title,
            'template_content' => $contract->template_content,
            'filled_content' => $contract->filled_content,
            'start_date' => optional($contract->start_date)->format('Y-m-d'),
            'end_date' => optional($contract->end_date)->format('Y-m-d'),
            'amount' => $contract->amount,
            'currency' => $contract->currency,
            'payment_terms' => $contract->payment_terms,
            'signed_at' => optional($contract->signed_at)->toIso8601String(),
            'signed_file_path' => $contract->signed_file_path,
            'signed_file_name' => $contract->signed_file_name,
            'generated_at' => optional($contract->generated_at)->toIso8601String(),
            'generated_file_path' => $contract->generated_file_path,
            'generated_file_name' => $contract->generated_file_name,
        ];

        $meta['history'] = $history;

        $contract->update([
            'status' => 'draft',
            'contract_reference' => null,
            'template_title' => null,
            'template_content' => null,
            'filled_content' => null,
            'start_date' => null,
            'end_date' => null,
            'amount' => null,
            'currency' => 'XOF',
            'payment_terms' => null,
            'signed_at' => null,
            'signed_file_path' => null,
            'signed_file_name' => null,
            'generated_at' => null,
            'generated_file_path' => null,
            'generated_file_name' => null,
            'meta' => $meta,
            'updated_by' => $request->user()?->id,
        ]);

        return response()->json([
            'message' => 'Contrat archivé. Vous pouvez importer un nouveau PDF ou générer un nouveau contrat.',
            'data' => $this->contractPayload($contract->fresh()),
        ]);
    }

    public function downloadContract(Request $request, ProviderPartner $providerPartner, string $type)
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_unless(in_array($type, ['generated', 'signed'], true), 404);

        $contract = ProviderContract::where('enterprise_id', $providerPartner->enterprise_id)
            ->where('provider_partner_id', $providerPartner->id)
            ->firstOrFail();

        $path = $type === 'generated' ? $contract->generated_file_path : $contract->signed_file_path;
        abort_if(empty($path), 404, 'Aucun fichier de contrat disponible.');
        abort_unless(Storage::disk('public')->exists($path), 404);

        $downloadName = $type === 'generated'
            ? ($contract->generated_file_name ?: basename($path))
            : ($contract->signed_file_name ?: basename($path));

        $absolutePath = Storage::disk('public')->path($path);
        return response()->file(
            $absolutePath,
            ExportHeaders::attachmentHeaders($downloadName, 'application/pdf', true),
        );
    }

    public function downloadArchivedContract(Request $request, ProviderPartner $providerPartner, string $archiveId, string $type)
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_unless(in_array($type, ['generated', 'signed'], true), 404);

        $contract = ProviderContract::where('enterprise_id', $providerPartner->enterprise_id)
            ->where('provider_partner_id', $providerPartner->id)
            ->firstOrFail();

        $meta = is_array($contract->meta) ? $contract->meta : [];
        $history = is_array($meta['history'] ?? null) ? $meta['history'] : [];
        $entry = collect($history)->first(fn ($item) => is_array($item) && ($item['id'] ?? '') === $archiveId);
        abort_if(!is_array($entry), 404, 'Archive introuvable.');

        $path = $type === 'generated'
            ? ($entry['generated_file_path'] ?? null)
            : ($entry['signed_file_path'] ?? null);

        abort_if(!is_string($path) || trim($path) === '', 404, 'Aucun fichier disponible pour cette archive.');
        abort_unless(Storage::disk('public')->exists($path), 404);

        $downloadName = $type === 'generated'
            ? ($entry['generated_file_name'] ?? basename($path))
            : ($entry['signed_file_name'] ?? basename($path));

        $absolutePath = Storage::disk('public')->path($path);
        return response()->file(
            $absolutePath,
            ExportHeaders::attachmentHeaders($downloadName, 'application/pdf', true),
        );
    }

    public function downloadProviderFile(Request $request, ProviderPartner $providerPartner, ProviderPartnerFile $providerFile)
    {
        abort_unless($request->hasValidSignature(), 401);
        abort_unless((int) $providerFile->provider_partner_id === (int) $providerPartner->id, 404);
        abort_unless((int) $providerFile->enterprise_id === (int) $providerPartner->enterprise_id, 404);
        abort_if(empty($providerFile->file_path), 404, 'Fichier introuvable.');
        abort_unless(Storage::disk('public')->exists($providerFile->file_path), 404);

        $downloadName = $providerFile->file_name ?: basename((string) $providerFile->file_path);
        $mime = $providerFile->file_mime ?: 'application/octet-stream';

        $absolutePath = Storage::disk('public')->path((string) $providerFile->file_path);
        return response()->file(
            $absolutePath,
            ExportHeaders::attachmentHeaders($downloadName, $mime, true),
        );
    }

    private function validateProvider(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'designation' => "{$required}|string|max:255",
            'provider_type' => 'nullable|string|max:64',
            'legal_form' => 'nullable|string|max:255',
            'service_offers' => 'nullable|string',
            'phone_primary' => 'nullable|string|max:64',
            'phone_secondary' => 'nullable|string|max:64',
            'email' => 'nullable|email|max:255',
            'ifu' => 'nullable|string|max:128',
            'experience_years' => 'nullable|integer|min:0|max:100',
            'evaluation_observation' => 'nullable|string',
            'metadata' => 'nullable|array',
        ]);
    }

    private function resolveEnterpriseId(Request $request): int
    {
        $user = $request->user();
        $enterpriseId = (int) ($user?->enterprise_id ?? 0);

        if ($enterpriseId === 0 && !empty($user?->site_id)) {
            $enterpriseId = (int) Site::whereKey($user->site_id)->value('enterprise_id');
        }

        abort_if($enterpriseId === 0, 422, "Aucune entreprise active n'est associée à cet utilisateur.");
        return $enterpriseId;
    }

    private function generateProviderReference(int $enterpriseId): string
    {
        $year = date('Y');
        $prefix = "PREST-{$year}-";

        $last = ProviderPartner::withTrashed()
            ->where('enterprise_id', $enterpriseId)
            ->where('reference', 'like', "{$prefix}%")
            ->orderByDesc('reference')
            ->value('reference');

        $seq = 1;
        if (is_string($last)) {
            $parts = explode('-', $last);
            $seq = ((int) end($parts)) + 1;
        }

        return sprintf('%s%04d', $prefix, $seq);
    }

    private function placeholders(): array
    {
        return [
            '{{enterprise_name}}',
            '{{enterprise_address}}',
            '{{provider_name}}',
            '{{provider_reference}}',
            '{{provider_type}}',
            '{{provider_email}}',
            '{{provider_phone}}',
            '{{provider_ifu}}',
            '{{service_offers}}',
            '{{contract_reference}}',
            '{{start_date}}',
            '{{end_date}}',
            '{{amount}}',
            '{{currency}}',
            '{{payment_terms}}',
            '{{today}}',
        ];
    }

    private function defaultTemplateContent(): string
    {
        return implode("\n", [
            'CONTRAT DE PRESTATION DE SERVICES',
            '',
            'Parties',
            "Entreprise: {{enterprise_name}}",
            "Adresse: {{enterprise_address}}",
            "Prestataire: {{provider_name}} (réf. {{provider_reference}}), type {{provider_type}}, IFU {{provider_ifu}}",
            '',
            'Article 1 - Objet du contrat',
            'Le présent contrat a pour objet la réalisation des prestations suivantes: {{service_offers}}.',
            '',
            'Article 2 - Référence et durée',
            'Référence contrat: {{contract_reference}}',
            'Période: du {{start_date}} au {{end_date}}.',
            '',
            "Article 3 - Obligations générales du Prestataire",
            '- Exécuter les prestations conformément aux exigences convenues.',
            "- Respecter les délais, normes qualité, règles d'hygiène, sécurité et environnement applicables.",
            "- Informer immédiatement l'Entreprise de tout incident pouvant impacter la prestation.",
            '',
            "Article 4 - Obligations de l'Entreprise",
            "- Mettre à disposition les informations et accès nécessaires à la bonne exécution des prestations.",
            '- Valider les livrables dans un délai raisonnable.',
            '',
            'Article 5 - Conditions financières',
            'Montant: {{amount}} {{currency}}.',
            'Conditions de paiement: {{payment_terms}}',
            '',
            'Article 6 - Confidentialité',
            "Les parties s'engagent à préserver la confidentialité des informations échangées dans le cadre du présent contrat.",
            '',
            'Article 7 - Résiliation',
            "En cas de manquement grave non corrigé après mise en demeure, chaque partie peut résilier le contrat dans les conditions prévues par la réglementation en vigueur.",
            '',
            'Article 8 - Litiges et droit applicable',
            "Le présent contrat est régi par le droit applicable dans le pays d'exécution. En cas de différend, les parties privilégient un règlement amiable avant toute saisine juridictionnelle.",
            '',
            'Contact opérationnel Prestataire: {{provider_phone}} / {{provider_email}}.',
            '',
            "Fait le {{today}}.",
            '',
            "Signature de l'Entreprise: ____________________",
            'Nom et fonction: _____________________________',
            '',
            'Signature du Prestataire: _____________________',
            'Nom et fonction: _____________________________',
        ]);
    }

    private function renderTemplate(
        string $content,
        ProviderPartner $provider,
        ?ProviderContract $contract,
        ?Enterprise $enterprise,
        array $draft = [],
    ): string {
        $startDate = $draft['start_date'] ?? null;
        $endDate = $draft['end_date'] ?? null;
        $amount = $draft['amount'] ?? null;
        $currency = $draft['currency'] ?? null;
        $paymentTerms = $draft['payment_terms'] ?? null;
        $contractReference = $draft['contract_reference'] ?? null;

        $replacements = [
            '{{enterprise_name}}' => (string) ($enterprise?->name ?? ''),
            '{{enterprise_address}}' => (string) ($enterprise?->address ?? ''),
            '{{provider_name}}' => (string) $provider->designation,
            '{{provider_reference}}' => (string) $provider->reference,
            '{{provider_type}}' => (string) ($provider->provider_type ?? ''),
            '{{provider_email}}' => (string) ($provider->email ?? ''),
            '{{provider_phone}}' => trim((string) (($provider->phone_primary ?? '') . ' ' . ($provider->phone_secondary ?? ''))),
            '{{provider_ifu}}' => (string) ($provider->ifu ?? ''),
            '{{service_offers}}' => (string) ($provider->service_offers ?? ''),
            '{{contract_reference}}' => (string) ($contractReference ?? $contract?->contract_reference ?? ''),
            '{{start_date}}' => $this->formatDateString($startDate, $contract?->start_date?->format('Y-m-d')),
            '{{end_date}}' => $this->formatDateString($endDate, $contract?->end_date?->format('Y-m-d')),
            '{{amount}}' => (string) ($amount ?? $contract?->amount ?? ''),
            '{{currency}}' => (string) ($currency ?? $contract?->currency ?? 'XOF'),
            '{{payment_terms}}' => (string) ($paymentTerms ?? $contract?->payment_terms ?? ''),
            '{{today}}' => now()->format('d/m/Y'),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    private function formatDateString(mixed $value, ?string $fallback = null): string
    {
        $candidate = $value ?? $fallback;
        if (!is_string($candidate) || trim($candidate) === '') {
            return '';
        }

        try {
            return \Carbon\Carbon::parse($candidate)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $candidate;
        }
    }

    private function contractPayload(ProviderContract $contract): array
    {
        $signedFileUrl = null;
        if ($contract->signed_file_path) {
            $signedFileUrl = URL::temporarySignedRoute(
                'provider-contracts.download',
                now()->addHours(12),
                [
                    'providerPartner' => $contract->provider_partner_id,
                    'type' => 'signed',
                ],
            );
        }

        $generatedFileUrl = null;
        if ($contract->generated_file_path) {
            $generatedFileUrl = URL::temporarySignedRoute(
                'provider-contracts.download',
                now()->addHours(12),
                [
                    'providerPartner' => $contract->provider_partner_id,
                    'type' => 'generated',
                ],
            );
        }

        $history = $this->buildArchiveHistoryPayload($contract);

        return [
            ...$contract->toArray(),
            'signed_file_url' => $signedFileUrl,
            'generated_file_url' => $generatedFileUrl,
            'history' => $history,
        ];
    }

    private function buildArchiveHistoryPayload(ProviderContract $contract): array
    {
        $meta = is_array($contract->meta) ? $contract->meta : [];
        $history = is_array($meta['history'] ?? null) ? $meta['history'] : [];

        $payload = [];
        foreach ($history as $item) {
            if (!is_array($item)) {
                continue;
            }

            $archiveId = (string) ($item['id'] ?? '');
            if ($archiveId === '') {
                continue;
            }

            $signedUrl = null;
            if (!empty($item['signed_file_path'])) {
                $signedUrl = URL::temporarySignedRoute(
                    'provider-contracts.archived.download',
                    now()->addHours(12),
                    [
                        'providerPartner' => $contract->provider_partner_id,
                        'archiveId' => $archiveId,
                        'type' => 'signed',
                    ],
                );
            }

            $generatedUrl = null;
            if (!empty($item['generated_file_path'])) {
                $generatedUrl = URL::temporarySignedRoute(
                    'provider-contracts.archived.download',
                    now()->addHours(12),
                    [
                        'providerPartner' => $contract->provider_partner_id,
                        'archiveId' => $archiveId,
                        'type' => 'generated',
                    ],
                );
            }

            $payload[] = [
                ...$item,
                'signed_file_url' => $signedUrl,
                'generated_file_url' => $generatedUrl,
            ];
        }

        usort($payload, fn ($a, $b) => strcmp((string) ($b['archived_at'] ?? ''), (string) ($a['archived_at'] ?? '')));
        return $payload;
    }

    private function renderContractHtml(string $content, string $title): string
    {
        $escapedTitle = e($title);
        $lines = preg_split('/\r\n|\r|\n/', (string) $content) ?: [];
        $body = '';
        $isListOpen = false;

        foreach ($lines as $line) {
            $trimmed = trim((string) $line);

            if ($trimmed === '') {
                if ($isListOpen) {
                    $body .= '</ul>';
                    $isListOpen = false;
                }
                $body .= '<div class="spacer"></div>';
                continue;
            }

            if (str_starts_with($trimmed, '- ')) {
                if (!$isListOpen) {
                    $body .= '<ul class="bullet-list">';
                    $isListOpen = true;
                }
                $item = e(substr($trimmed, 2));
                $body .= "<li>{$item}</li>";
                continue;
            }

            if ($isListOpen) {
                $body .= '</ul>';
                $isListOpen = false;
            }

            if (preg_match('/^Article\s+\d+/i', $trimmed)) {
                $body .= '<h2 class="article-title">' . e($trimmed) . '</h2>';
                continue;
            }

            if (preg_match('/^Parties$/i', $trimmed)) {
                $body .= '<h2 class="article-title">Parties</h2>';
                continue;
            }

            if (preg_match('/^Fait le /i', $trimmed)) {
                $body .= '<p class="signature-date">' . e($trimmed) . '</p>';
                continue;
            }

            $body .= '<p class="paragraph">' . e($trimmed) . '</p>';
        }

        if ($isListOpen) {
            $body .= '</ul>';
        }

        return <<<HTML
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>{$escapedTitle}</title>
  <style>
    body { margin: 0; background: #f5f7fb; font-family: DejaVu Sans, Arial, sans-serif; color: #111827; }
    .sheet { margin: 18px; padding: 22px 24px; background: #ffffff; border: 1px solid #dbe1ee; border-radius: 12px; }
    .top-band { height: 8px; border-radius: 999px; background: linear-gradient(90deg, #0f766e 0%, #2563eb 100%); margin-bottom: 14px; }
    h1 { margin: 0; font-size: 18px; letter-spacing: 0.03em; text-transform: uppercase; color: #0f172a; }
    .meta { margin-top: 6px; font-size: 11px; color: #475569; }
    .content { margin-top: 18px; }
    .article-title { margin: 14px 0 8px; font-size: 13px; color: #0f766e; border-bottom: 1px solid #dbe1ee; padding-bottom: 3px; }
    .paragraph { margin: 0 0 7px; font-size: 12px; line-height: 1.62; }
    .bullet-list { margin: 4px 0 10px 16px; padding: 0; }
    .bullet-list li { margin: 0 0 5px; font-size: 12px; line-height: 1.55; }
    .signature-date { margin-top: 16px; font-size: 12px; font-weight: 600; }
    .spacer { height: 4px; }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="top-band"></div>
    <h1>{$escapedTitle}</h1>
    <div class="meta">Contrat prestataire - Généré automatiquement</div>
    <div class="content">{$body}</div>
  </div>
</body>
</html>
HTML;
    }

    private function providerFilePayload(ProviderPartnerFile $file): array
    {
        return [
            ...$file->toArray(),
            'download_url' => URL::temporarySignedRoute(
                'provider-files.download',
                now()->addHours(12),
                [
                    'providerPartner' => $file->provider_partner_id,
                    'providerFile' => $file->id,
                ],
            ),
        ];
    }
}
