<?php

namespace App\Modules\Support\Services;

use App\Models\Process;

use App\Events\Document\DocumentPublished;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentApproval;
use App\Models\DocumentCategory;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DocumentService
{
    public function generateDocumentNumber(int $categoryId, ?int $processId = null): string
    {
        $category = DocumentCategory::findOrFail($categoryId);
        $year = date('Y');
        
        $processCode = 'GEN';
        if ($processId) {
            $process = \App\Models\Process::find($processId);
            $processCode = $process?->code ?? 'GEN';
        }
        
        $lastDocument = Document::where('category_id', $categoryId)
            ->where('document_number', 'like', "{$category->code}-{$processCode}-{$year}-%")
            ->orderByDesc('document_number')
            ->first();
        
        $nextNum = 1;
        if ($lastDocument) {
            $parts = explode('-', $lastDocument->document_number);
            $lastNum = (int) end($parts);
            $nextNum = $lastNum + 1;
        }
        
        return sprintf('%s-%s-%s-%03d', $category->code, $processCode, $year, $nextNum);
    }

    public function createDocument(array $data, ?UploadedFile $file = null): Document
    {
        return DB::transaction(function () use ($data, $file) {
            if (empty($data['document_number'])) {
                $data['document_number'] = $this->generateDocumentNumber(
                    $data['category_id'],
                    $data['process_id'] ?? null
                );
            }
            
            $data['status'] = $data['status'] ?? 'draft';
            $document = Document::create($data);
            
            if (!empty($data['process_ids'])) {
                $processData = [];
                foreach ($data['process_ids'] as $index => $processId) {
                    $processData[$processId] = ['is_primary' => $index === 0];
                }
                $document->processes()->sync($processData);
            }
            
            if ($file) {
                $this->createVersion($document, $file, $data['change_summary'] ?? 'Version initiale');
            }
            
            return $document->fresh(['category', 'workflow', 'currentVersion', 'processes']);
        });
    }

    public function createVersion(Document $document, UploadedFile $file, string $changeSummary, int $userId = null): DocumentVersion
    {
        return DB::transaction(function () use ($document, $file, $changeSummary, $userId) {
            $document->versions()->update(['is_current' => false]);
            
            $lastVersion = $document->versions()->orderByDesc('version_number')->first();
            $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;
            
            $year = date('Y');
            $month = date('m');
            $directory = "documents/{$year}/{$month}/{$document->document_number}";
            $path = $file->store($directory, 'local');
            
            $version = $document->versions()->create([
                'version_number' => $versionNumber,
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'file_mime_type' => $file->getMimeType(),
                'file_original_name' => $file->getClientOriginalName(),
                'change_summary' => $changeSummary,
                'created_by' => $userId ?? auth()->id(),
                'is_current' => true,
            ]);
            
            $document->update(['version' => "v{$versionNumber}"]);
            
            return $version;
        });
    }

    public function initiateApprovalWorkflow(Document $document): void
    {
        $workflow = $document->workflow ?? $document->category->defaultWorkflow()->first();
        
        if (!$workflow || !$workflow->steps) {
            return;
        }
        
        $currentVersion = $document->currentVersion;
        if (!$currentVersion) {
            return;
        }
        
        foreach ($workflow->steps as $step) {
            DocumentApproval::create([
                'document_version_id' => $currentVersion->id,
                'step_order' => $step['order'],
                'role_required' => $step['role'],
                'status' => DocumentApproval::STATUS_PENDING,
            ]);
        }
        
        $document->update(['status' => 'pending_approval']);
    }

    public function processApproval(DocumentApproval $approval, string $action, int $userId, ?string $comments = null): bool
    {
        if (!in_array($action, ['approve', 'reject', 'request_changes'])) {
            return false;
        }

        $shouldNotifyPublication = false;

        DB::transaction(function () use ($approval, $action, $userId, $comments, &$shouldNotifyPublication) {
            match ($action) {
                'approve' => $approval->approve($userId, $comments),
                'reject' => $approval->reject($userId, $comments),
                'request_changes' => $approval->requestChanges($userId, $comments),
            };
            
            $version = $approval->documentVersion;
            $document = $version->document;
            
            $allApprovals = $version->approvals;
            $allApproved = $allApprovals->every(fn($a) => $a->status === DocumentApproval::STATUS_APPROVED);
            $anyRejected = $allApprovals->contains(fn($a) => $a->status === DocumentApproval::STATUS_REJECTED);
            
            if ($allApproved) {
                $wasPublished = is_null($document->published_at);
                $document->update([
                    'status' => 'approved',
                    'published_at' => now(),
                    'approved_at' => now(),
                    'approver_id' => $userId,
                ]);
                $version->update(['published_at' => now()]);
                $shouldNotifyPublication = $wasPublished;
            } elseif ($anyRejected) {
                $document->update(['status' => 'draft']);
            }
        });

        if ($shouldNotifyPublication) {
            $approval->load('documentVersion.document.site');
            $document = $approval->documentVersion->document->refresh();
            $publishedBy = User::find($userId);
            $concernedUsers = $document->site
                ? $document->site->users()->where('is_active', true)->get()
                : collect();

            if ($publishedBy) {
                event(new DocumentPublished($document, $concernedUsers, $publishedBy));
            }
        }
        
        return true;
    }

    public function archiveDocument(Document $document): void
    {
        $document->update([
            'status' => 'obsolete',
            'archived_at' => now(),
            'is_active' => false,
        ]);
    }

    public function logAccess(int $documentVersionId, string $action): void
    {
        DocumentAccessLog::logAccess($documentVersionId, auth()->id(), $action);
    }
}
