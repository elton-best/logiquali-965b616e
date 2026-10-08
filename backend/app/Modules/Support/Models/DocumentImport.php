<?php

namespace App\Modules\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'user_id',
        'filename',
        'file_path',
        'file_hash',
        'status',
        'validation_results',
        'import_stats',
        'total_rows',
        'valid_rows',
        'invalid_rows',
        'imported_rows',
        'failed_rows',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'validation_results' => 'array',
        'import_stats' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query)
    {
        return $query->where('status', 'failed');
    }

    public function markAsValidating(): void
    {
        $this->update(['status' => 'validating']);
    }

    public function markAsValidated(array $validationResults): void
    {
        $this->update([
            'status' => 'validated',
            'validation_results' => $validationResults,
            'valid_rows' => $validationResults['valid_count'] ?? 0,
            'invalid_rows' => $validationResults['invalid_count'] ?? 0,
        ]);
    }

    public function markAsImporting(): void
    {
        $this->update([
            'status' => 'importing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(array $stats): void
    {
        $this->update([
            'status' => 'completed',
            'import_stats' => $stats,
            'imported_rows' => $stats['imported'] ?? 0,
            'failed_rows' => $stats['failed'] ?? 0,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function markAsRolledBack(): void
    {
        $this->update(['status' => 'rolled_back']);
    }
}
