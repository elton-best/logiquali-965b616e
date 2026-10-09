<?php

namespace App\Modules\Leadership\Models;

use App\Models\CompetenceRequise;
use App\Models\Enterprise;
use App\Models\Site;
use App\Models\User;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobDescription extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'site_id',
        'user_id',
        'job_title',
        'replacement_job_title',
        'department',
        'reports_to_id',
        'mission',
        'activities',
        'main_activities',
        'secondary_activities',
        'functional_relations',
        'internal_relations',
        'external_relations',
        'hierarchical_superior',
        'work_conditions',
        'work_environment',
        'work_location',
        'work_schedule',
        'travel_required',
        'physical_requirements',
        'required_level',
        'current_level',
        'required_skills',
        'required_experience',
        'required_education',
        'certifications_required',
        'professional_qualities',
        'employee_signature_data',
        'employee_signed_at',
        'manager_signature_data',
        'manager_signed_at',
        'ceo_signature_data',
        'ceo_signed_at',
        'ceo_user_id',
        'last_updated',
    ];

    protected function casts(): array
    {
        return [
            'last_updated' => 'date',
            'main_activities' => 'array',
            'secondary_activities' => 'array',
            'internal_relations' => 'array',
            'external_relations' => 'array',
            'required_skills' => 'array',
            'certifications_required' => 'array',
            'travel_required' => 'boolean',
            'employee_signed_at' => 'datetime',
            'manager_signed_at' => 'datetime',
            'ceo_signed_at' => 'datetime',
        ];
    }

    /**
     * REQ-7.2-03 : Fiche considérée signée uniquement si les 2 signatures sont présentes.
     */
    public function getIsFullySignedAttribute(): bool
    {
        $hasEmployee = !empty($this->employee_signature_data) || !empty($this->employee_signed_at);
        $hasCeo = !empty($this->ceo_signature_data) || !empty($this->ceo_signed_at) || !empty($this->manager_signed_at);
        return $hasEmployee && $hasCeo;
    }

    public function ceoUser()
    {
        return $this->belongsTo(\App\Models\User::class, 'ceo_user_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reportsTo()
    {
        return $this->belongsTo(User::class, 'reports_to_id');
    }

    public function competencesRequises()
    {
        return $this->hasMany(CompetenceRequise::class);
    }

    public function history()
    {
        return $this->hasMany(JobDescriptionHistory::class)
            ->orderByDesc('changed_at')
            ->orderByDesc('id');
    }
}
