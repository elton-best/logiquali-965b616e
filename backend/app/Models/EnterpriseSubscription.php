<?php

namespace App\Models;

use App\Traits\HasAuditFields;
use App\Traits\HasReference;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class EnterpriseSubscription extends Model
{
    use HasFactory, SoftDeletes, HasAuditFields, HasReference;

    protected $fillable = [
        'ref',
        'offer_id',
        'site_id',
        'start_date',
        'expiration_date',
        'is_active',
        'is_trial',
        'trial_ends_at',
        'status',
        'payment_status',
        'payment_gateway',
        'payment_customer_id',
        'payment_method_id',
        'payment_metadata',
        'subscription_type',
        'payment_method',
        'amount_paid',
        'months_unpaid',
        'arrears_amount',
        'last_payment_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'datetime',
            'expiration_date' => 'datetime',
            'trial_ends_at' => 'datetime',
            'is_active' => 'boolean',
            'is_trial' => 'boolean',
            'amount_paid' => 'decimal:2',
            'arrears_amount' => 'decimal:2',
            'last_payment_date' => 'date',
            'payment_metadata' => 'array',
        ];
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function alerts()
    {
        return $this->hasMany(SubscriptionAlert::class, 'subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class, 'subscription_id');
    }

    /**
     * Get enterprise attribute via site
     */
    public function getEnterpriseAttribute()
    {
        return $this->site?->enterprise ?? null;
    }

    public function getAccessibleModules()
    {
        if (!$this->offer) {
            return collect();
        }

        $normIds = $this->offer->norms->pluck('id');

        return Module::query()
            ->where(function ($query) use ($normIds) {
                // Liaison explicite norme -> module (source nominale)
                $query->whereHas('norms', function ($normQuery) use ($normIds) {
                    $normQuery->whereIn('norms.id', $normIds);
                })
                // Modules marqués communs (socle transversal)
                    ->orWhere('is_common', true)
                // Fallback robuste: déduire le module depuis les sous-modules liés aux normes
                    ->orWhereHas('subModules.norms', function ($subNormQuery) use ($normIds) {
                        $subNormQuery->whereIn('norms.id', $normIds);
                    })
                // Fallback: module avec sous-modules communs
                    ->orWhereHas('subModules', function ($subQuery) {
                        $subQuery->where('is_common', true);
                    });
            })
            ->with('subModules')
            ->active()
            ->orderBy('order')
            ->get();
    }

    public function getAccessibleSubModules()
    {
        if (!$this->offer) {
            return collect();
        }

        $normIds = $this->offer->norms->pluck('id');
        $accessibleModuleIds = $this->getAccessibleModules()->pluck('id');
        $hasIsCommonColumn = Schema::hasColumn('sub_modules', 'is_common');

        return SubModule::where(function ($query) use ($normIds, $accessibleModuleIds, $hasIsCommonColumn) {
                if ($hasIsCommonColumn) {
                    // Sous-modules communs catalogués explicitement
                    $query->where(function ($q) use ($accessibleModuleIds) {
                        $q->where('is_common', true)
                            ->whereIn('module_id', $accessibleModuleIds);
                    })
                    // Sous-modules sans liaison normative explicite: considérés communs
                    ->orWhere(function ($q) use ($accessibleModuleIds) {
                        $q->whereIn('module_id', $accessibleModuleIds)
                            ->whereDoesntHave('norms');
                    })
                    // Sous-modules explicitement liés à une norme souscrite
                    ->orWhere(function ($q) use ($normIds, $accessibleModuleIds) {
                        $q->where('is_common', false)
                            ->whereHas('norms', function ($normQuery) use ($normIds) {
                                $normQuery->whereIn('norms.id', $normIds);
                            })
                            ->whereIn('module_id', $accessibleModuleIds);
                    });

                    return;
                }

                // Compatibilité schéma legacy sans colonne is_common
                $query->where(function ($q) use ($accessibleModuleIds) {
                    $q->whereIn('module_id', $accessibleModuleIds)
                        ->whereDoesntHave('norms');
                })->orWhere(function ($q) use ($normIds, $accessibleModuleIds) {
                    $q->whereHas('norms', function ($normQuery) use ($normIds) {
                        $normQuery->whereIn('norms.id', $normIds);
                    })->whereIn('module_id', $accessibleModuleIds);
                });
            })
            ->with(['module', 'sections'])
            ->active()
            ->orderBy('order')
            ->get();
    }

    public function getAccessibleSections()
    {
        if (!$this->offer) {
            return collect();
        }

        // Récupérer les sous-modules accessibles
        $accessibleSubModuleIds = $this->getAccessibleSubModules()->pluck('id');

        return SubModuleSection::whereIn('sub_module_id', $accessibleSubModuleIds)
            ->with('subModule.module')
            ->active()
            ->orderBy('order')
            ->get();
    }

    /**
     * Vérifier si l'abonnement est actif
     * Logique: Si trial, vérifier trial_ends_at, sinon vérifier expiration_date
     */
    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->start_date && now()->lt($this->start_date)) {
            return false;
        }

        if ($this->is_trial) {
            return $this->trial_ends_at && now() < $this->trial_ends_at;
        }
        
        return $this->expiration_date && now() < $this->expiration_date;
    }

    public function isInTrial(): bool
    {
        return $this->is_trial && $this->status === 'trial' && $this->trial_ends_at > now();
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || ($this->is_trial && $this->trial_ends_at < now());
    }

    public function daysRemaining(): int
    {
        if ($this->is_trial && $this->trial_ends_at) {
            return max(0, now()->diffInDays($this->trial_ends_at, false));
        }
        return max(0, now()->diffInDays($this->expiration_date, false));
    }

    public function canAccessSubModule(int $subModuleId): bool
    {
        return $this->getAccessibleSubModules()->contains('id', $subModuleId);
    }

    public function canAccessSection(int $sectionId): bool
    {
        return $this->getAccessibleSections()->contains('id', $sectionId);
    }

    public function hasArrears(): bool
    {
        return $this->months_unpaid > 0 || $this->arrears_amount > 0;
    }

    public function shouldBlockAccess(): bool
    {
        if (!$this->last_payment_date) {
            return false;
        }
        
        $daysSincePayment = now()->diffInDays($this->last_payment_date);
        return $daysSincePayment > 15 && $this->hasArrears();
    }
}
