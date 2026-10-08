<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProcessReview extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PLANNED = 'planned';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'site_id',
        'process_id',
        'title',
        'type',
        'review_date',
        'version_reviewed',
        'led_by',
        'closed_by',
        'coverage_start_date',
        'coverage_end_date',
        'started_at',
        'ended_at',
        'participants',
        'participants_presence',
        'role_assignments',
        'action_responsibles',
        'other_participants',
        'objectives',
        'findings',
        'strengths',
        'weaknesses',
        'opportunities_improvement',
        'decision',
        'decision_comment',
        'action_items',
        'next_review_date',
        'status',
        'identification',
        'sections',
        'metrics_snapshot',
        'attachments',
        'synthesis_data',
        'pip_data',
        'risk_data',
        'opportunity_data',
        'quality_objectives_data',
        'quality_activities_data',
        'operational_activities_data',
        'compliance_data',
        'non_conformity_data',
        'leadership_data',
        'duerp_data',
        'other_notes',
        'conclusion',
        'report_pdf_path',
        'report_docx_path',
        'report_generated_at',
    ];

    protected $casts = [
        'review_date' => 'date',
        'next_review_date' => 'date',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'identification' => 'array',
        'sections' => 'array',
        'metrics_snapshot' => 'array',
        'participants' => 'array',
        'participants_presence' => 'array',
        'role_assignments' => 'array',
        'action_responsibles' => 'array',
        'action_items' => 'array',
        'attachments' => 'array',
        'synthesis_data' => 'array',
        'pip_data' => 'array',
        'risk_data' => 'array',
        'opportunity_data' => 'array',
        'quality_objectives_data' => 'array',
        'quality_activities_data' => 'array',
        'operational_activities_data' => 'array',
        'compliance_data' => 'array',
        'non_conformity_data' => 'array',
        'leadership_data' => 'array',
        'duerp_data' => 'array',
        'report_generated_at' => 'datetime',
    ];

    // Relations
    public function process()
    {
        return $this->belongsTo(Process::class);
    }

    public function leader()
    {
        return $this->belongsTo(User::class, 'led_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['completed', 'closed']);
    }

    public function scopePlanned($query)
    {
        return $query->where('status', 'planned');
    }

    public function scopeApproved($query)
    {
        return $query->where('decision', 'approved');
    }

    public function isClosed(): bool
    {
        return in_array((string) $this->status, ['completed', 'closed'], true);
    }
}
