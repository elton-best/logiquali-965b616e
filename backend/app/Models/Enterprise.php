<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

class Enterprise extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $appends = [
        'logo_url',
    ];

    protected $fillable = [
        'ref',
        'name',
        'sigle',
        'codification_mode',
        'email',
        'logo_path',
        'registration_number',
        'status',
        'trial_ends_at',
        'approval_status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'suspension_reason',
        'organigram_path',
        'field',
        'phone',
        'address',
        'city',
        'country',
        'industry',
        'size',
        'created_by',
        'owner_user_id',
        'domaine_activite_set',
        // Nouveaux champs documents personnalisés
        'slogan',
        'brand_colors',
        'header_font',
        'rccm_number',
        'ifu_number',
        'cnss_number',
        'fodefca_number',
        'siret',
        'rcs',
        'vat_number',
        'ape_code',
        'legal_form',
        'share_capital',
        'legal_profile_config',
        'address_line_1',
        'address_line_2',
        'postal_code',
        'phone_primary',
        'phone_secondary',
        'phone_types',
        'email_general',
        'email_support',
        'website',
        'social_media',
        'header_footer_config',
    ];

    protected function casts(): array
    {
        return [
            'trial_ends_at' => 'datetime',
            'approved_at' => 'datetime',
            'domaine_activite_set' => 'boolean',
            'brand_colors' => 'array',
            'legal_profile_config' => 'array',
            'phone_types' => 'array',
            'social_media' => 'array',
            'header_footer_config' => 'array',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (empty($this->logo_path)) {
            return null;
        }

        $normalizedPath = preg_replace('#^/?storage/#', '', (string) $this->logo_path) ?? (string) $this->logo_path;
        /** @var FilesystemAdapter $publicDisk */
        $publicDisk = Storage::disk('public');

        return $publicDisk->url($normalizedPath);
    }

    public function sites()
    {
        return $this->hasMany(Site::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function ownerUser()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function subscriptions()
    {
        // Subscriptions are linked to sites, not directly to enterprises
        return $this->hasManyThrough(EnterpriseSubscription::class, Site::class);
    }

    public function documents()
    {
        return $this->hasMany(EnterpriseDocument::class);
    }

    public function sigleHistory()
    {
        return $this->hasMany(EnterpriseSigleHistory::class);
    }

    // Relations transverses passent par les sites
    public function applicationScopes()
    {
        return $this->hasManyThrough(ApplicationScope::class, Site::class);
    }

    public function qhsePolicies()
    {
        return $this->hasManyThrough(QhsePolicy::class, Site::class);
    }

    public function complianceRequirements()
    {
        return $this->hasManyThrough(ComplianceRequirement::class, Site::class);
    }

    public function environmentalAspects()
    {
        return $this->hasManyThrough(EnvironmentalAspect::class, Site::class);
    }

    public function equipment()
    {
        return $this->hasManyThrough(Equipement::class, Site::class);
    }

    public function trainingPlans()
    {
        return $this->hasManyThrough(TrainingPlan::class, Site::class);
    }

    public function communicationActions()
    {
        return $this->hasManyThrough(CommunicationAction::class, Site::class);
    }

    public function operationalControls()
    {
        return $this->hasManyThrough(OperationalControl::class, Site::class);
    }

    public function emergencyProcedures()
    {
        return $this->hasManyThrough(EmergencyProcedure::class, Site::class);
    }

    public function participationRecords()
    {
        return $this->hasManyThrough(ParticipationRecord::class, Site::class);
    }

    public function aiSuggestions()
    {
        return $this->hasManyThrough(AiSuggestion::class, Site::class);
    }

    /**
     * Certifications de l'entreprise
     */
    public function certifications()
    {
        return $this->hasMany(EnterpriseCertification::class);
    }

    /**
     * Certifications actives uniquement
     */
    public function activeCertifications()
    {
        return $this->certifications()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>', now());
            });
    }

    /**
     * Scans QR codes des documents de l'entreprise
     */
    public function qrCodeScans()
    {
        return $this->hasMany(QRCodeScan::class);
    }

    /**
     * Vérifier si l'entreprise a déjà utilisé sa période d'essai
     * Le trial est unique par entreprise (siège social uniquement)
     */
    public function hasUsedTrial(): bool
    {
        $headquarter = $this->sites()->where('is_headquarter', true)->first();

        if (!$headquarter) {
            return false;
        }

        return $headquarter->subscriptions()
            ->where('is_trial', true)
            ->exists();
    }

    /**
     * Vérifier si l'entreprise est en attente
     */
    public function isPending(): bool
    {
        return $this->approval_status === 'pending';
    }

    /**
     * Vérifier si l'entreprise est rejetée
     */
    public function isRejected(): bool
    {
        return $this->approval_status === 'rejected';
    }

    /**
     * Vérifier si l'entreprise est approuvée
     */
    public function isApproved(): bool
    {
        return $this->approval_status === 'approved';
    }

    /**
     * Vérifier si l'entreprise est suspendue
     */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /**
     * Vérifier si l'entreprise peut accéder à la plateforme
     * Vérifie si au moins un site a un abonnement actif ou si la période d'essai est valide
     */
    public function canAccessPlatform(): bool
    {
        // Si l'entreprise n'est pas active, pas d'accès
        if ($this->status !== 'active') {
            return false;
        }

        // Vérifier une période d'essai active via les souscriptions (source de vérité unique)
        $hasActiveTrial = $this->subscriptions()
            ->where('enterprise_subscriptions.is_trial', true)
            ->where('enterprise_subscriptions.is_active', true)
            ->where('enterprise_subscriptions.status', 'trial')
            ->whereNotNull('enterprise_subscriptions.trial_ends_at')
            ->where('enterprise_subscriptions.trial_ends_at', '>', now())
            ->exists();

        if ($hasActiveTrial) {
            return true;
        }

        // Vérifier si au moins un site a un abonnement actif
        return $this->sites()
            ->whereHas('subscriptions', function ($query) {
                $query->where('is_active', true)
                    ->where('expiration_date', '>', now());
            })
            ->exists();
    }

    /**
     * Vérifier si la période d'essai est expirée
     */
    public function isTrialExpired(): bool
    {
        return $this->trial_ends_at && now()->gt($this->trial_ends_at);
    }

    /**
     * Obtenir l'abonnement actif de l'entreprise (via ses sites)
     */
    public function activeSubscription()
    {
        return EnterpriseSubscription::whereHas('site', function ($query) {
            $query->where('enterprise_id', $this->id);
        })
            ->where('is_active', true)
            ->where('expiration_date', '>', now())
            ->first();
    }

    /**
     * Obtenir les informations légales formatées selon le pays
     */
    public function getLegalInfo(): array
    {
        $country = $this->country ?? 'BJ';
        $profile = config("legal_profiles.{$country}", []);

        $info = [];
        foreach ($profile['fields'] ?? [] as $field) {
            if ($this->$field) {
                $label = $profile['labels'][$field] ?? $field;
                $info[$label] = $this->$field;
            }
        }

        return $info;
    }

    /**
     * Obtenir les couleurs de marque
     */
    public function getBrandColors(): array
    {
        return $this->brand_colors ?? [
            'primary' => '#1B5E96',
            'secondary' => '#FF6B00',
            'accent' => '#4CAF50'
        ];
    }

    /**
     * Obtenir la configuration header/footer pour un type de document
     */
    public function getHeaderFooterConfig(?string $docType = null): array
    {
        $config = $this->header_footer_config ?? [];

        if ($docType && isset($config['document_types'][$docType])) {
            return array_merge(
                $config['header'] ?? [],
                $config['footer'] ?? [],
                $config['document_types'][$docType]
            );
        }

        return array_merge(
            $config['header'] ?? [],
            $config['footer'] ?? []
        );
    }

    /**
     * Vérifier si l'entreprise a une certification active
     */
    public function hasActiveCertification(string $code): bool
    {
        return $this->activeCertifications()
            ->whereHas('certification', function ($query) use ($code) {
                $query->where('code', $code);
            })
            ->exists();
    }
}
