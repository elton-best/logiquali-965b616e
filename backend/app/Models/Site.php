<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'enterprise_id',
        'manager_id',
        'name',
        'location',
        'city',
        'phone',
        'email',
        'is_headquarter',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_headquarter' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function enterprise()
    {
        return $this->belongsTo(Enterprise::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function processes()
    {
        return $this->hasMany(Process::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(EnterpriseSubscription::class);
    }

    /**
     * Récupère l'abonnement actif (relation au singulier pour eager loading)
     */
    public function subscription()
    {
        return $this->hasOne(EnterpriseSubscription::class)
            ->where('is_active', true)
            ->where('expiration_date', '>', now())
            ->latestOfMany('start_date');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }

    public function stakeholders()
    {
        return $this->hasMany(Stakeholder::class);
    }

    public function contexts()
    {
        return $this->hasMany(Context::class);
    }

    public function strategicAxes()
    {
        return $this->hasMany(StrategicAxis::class);
    }

    public function plans()
    {
        return $this->hasMany(Plan::class);
    }

    public function teamMembers()
    {
        return $this->hasMany(TeamMember::class);
    }

    /**
     * Vérifie si le site a AU MOINS une souscription active
     * TOUTES les souscriptions doivent être actives (pas de période de grâce)
     */
    public function hasActiveSubscription(): bool
    {
        return $this->getActiveSubscriptions()->isNotEmpty();
    }

    /**
     * Récupère TOUTES les souscriptions actives du site
     */
    public function getActiveSubscriptions()
    {
        return $this->subscriptions()
            ->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where(function ($query) {
                $query->where(function ($paid) {
                    $paid->where(function ($paidStatus) {
                        $paidStatus->whereNull('is_trial')
                            ->orWhere('is_trial', false);
                    })->whereNotNull('expiration_date')
                        ->where('expiration_date', '>', now());
                })->orWhere(function ($trial) {
                    $trial->where('is_trial', true)
                        ->whereNotNull('trial_ends_at')
                        ->where('trial_ends_at', '>', now());
                });
            })
            ->with(['offer.norms'])
            ->get();
    }

    /**
     * Récupère le statut global de couverture des normes du site.
     * Règle métier: si une norme déjà souscrite n'est plus couverte aujourd'hui,
     * l'accès dashboard doit être bloqué.
     */
    public function getNormCoverageStatus(): array
    {
        $subscriptions = $this->subscriptions()
            ->with(['offer.norms'])
            ->orderBy('start_date')
            ->get();

        if ($subscriptions->isEmpty()) {
            return [
                'has_any_subscription' => false,
                'can_access_dashboard' => false,
                'active_norms' => [],
                'expired_norms' => [],
                'blocking_reasons' => ['Aucune norme souscrite'],
            ];
        }

        $normRecords = [];
        foreach ($subscriptions as $subscription) {
            foreach ($subscription->offer?->norms ?? [] as $norm) {
                $normRecords[$norm->id]['norm'] = $norm;
                $normRecords[$norm->id]['subscriptions'][] = $subscription;
            }
        }

        $activeNorms = [];
        $expiredNorms = [];
        $blockingReasons = [];

        foreach ($normRecords as $record) {
            $norm = $record['norm'];
            $normSubscriptions = collect($record['subscriptions']);

            $isCoveredNow = $normSubscriptions->contains(function ($subscription) {
                return $subscription->isActive();
            });

            if ($isCoveredNow) {
                $activeNorms[] = [
                    'id' => $norm->id,
                    'code' => $norm->code,
                    'name' => $norm->name,
                ];
                continue;
            }

            $latest = $normSubscriptions->sortByDesc('expiration_date')->first();
            $expiredNorms[] = [
                'id' => $norm->id,
                'code' => $norm->code,
                'name' => $norm->name,
                'last_expiration_date' => optional($latest?->expiration_date)?->toISOString(),
            ];

            $blockingReasons[] = "Norme expirée non renouvelée: {$norm->code}";
        }

        return [
            'has_any_subscription' => true,
            // Accès autorisé tant qu'au moins une norme reste active.
            'can_access_dashboard' => !empty($activeNorms),
            'active_norms' => $activeNorms,
            'expired_norms' => $expiredNorms,
            'blocking_reasons' => !empty($activeNorms)
                ? []
                : $blockingReasons,
        ];
    }



    /**
     * Scope pour filtrer les sites avec toutes souscriptions actives
     */
    public function scopeWithActiveSubscription($query)
    {
        return $query->whereHas('subscriptions', function ($q) {
            $q->where('is_active', true)
                ->where('start_date', '<=', now())
                ->where('expiration_date', '>', now());
        });
    }


}
