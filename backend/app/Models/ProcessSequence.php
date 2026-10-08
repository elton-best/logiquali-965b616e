<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcessSequence extends Model
{
    use HasFactory, HasAuditFields;

    protected $fillable = [
        'process_id',
        'sequence_order',
        'input_description',
        'activity_description',
        'sub_activities',
        'output_description',
        'supplier_processes',
        'client_processes',
        'responsible_user_id',
        'duration_minutes',
    ];

    protected function casts(): array
    {
        return [
            'sequence_order' => 'integer',
            'duration_minutes' => 'integer',
            'sub_activities' => 'array',
            'supplier_processes' => 'array',
            'client_processes' => 'array',
        ];
    }

    // Relations

    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function documents()
    {
        return $this->belongsToMany(Document::class, 'document_process_sequence')
            ->withPivot('is_mandatory')
            ->withTimestamps();
    }

    // Helpers

    public function hasDocuments()
    {
        return $this->documents()->exists();
    }

    public function hasDuration()
    {
        return !is_null($this->duration_minutes);
    }

    public function getDurationFormatted()
    {
        if (!$this->duration_minutes) {
            return null;
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        }

        return "{$minutes}min";
    }
}
