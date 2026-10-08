<?php

namespace App\Modules\Hse\Models;

use App\Models\Action;
use App\Models\Enterprise;
use App\Models\Process;
use App\Models\Site;
use App\Models\User;
use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkAccident extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $table = 'work_accidents';

    public const TYPES = [
        'accident_avec_arret' => 'Accident de travail avec arrêt',
        'accident_sans_arret' => 'Accident de travail sans arrêt',
        'accident_trajet' => 'Accident de trajet',
        'presqu_accident' => 'Presqu\'accident (Near-miss)',
        'incident_materiel' => 'Incident matériel / Situation dangereuse',
    ];

    public const SEVERITIES = [
        'benin' => 'Bénin',
        'moyen' => 'Moyen',
        'grave' => 'Grave',
        'mortel' => 'Mortel',
    ];

    public const STATUSES = [
        'declare' => 'Déclaré',
        'en_enquete' => 'En cours d\'enquête',
        'actions_en_cours' => 'Actions en cours',
        'resolu' => 'Résolu',
        'cloture' => 'Clôturé',
    ];

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'process_id',
        'duerp_danger_id',
        'type',
        'accident_date',
        'location',
        'victim_name',
        'victim_job_title',
        'victim_seniority_months',
        'circumstances',
        'nature_of_injury',
        'location_of_injury',
        'material_agent',
        'lost_days_count',
        'severity_level',
        'root_cause_analysis',
        'preventive_recommendations',
        'corrective_action_id',
        'investigator_id',
        'investigation_date',
        'status',
        'closure_notes',
        'closed_at',
        'closed_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'accident_date' => 'datetime',
        'investigation_date' => 'date',
        'closed_at' => 'datetime',
        'lost_days_count' => 'integer',
        'victim_seniority_months' => 'integer',
        'root_cause_analysis' => 'array',
    ];

    public function enterprise(): BelongsTo
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function duerpDanger(): BelongsTo
    {
        return $this->belongsTo(DuerpDanger::class, 'duerp_danger_id');
    }

    public function correctiveAction(): BelongsTo
    {
        return $this->belongsTo(Action::class, 'corrective_action_id');
    }

    public function investigator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'investigator_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
