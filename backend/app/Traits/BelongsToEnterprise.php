<?php

namespace App\Traits;

use App\Models\Site;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

trait BelongsToEnterprise
{
    /**
     * Boot du trait - Ajoute les scopes et événements
     */
    protected static function bootBelongsToEnterprise(): void
    {
        $modelInstance = new static();
        $table = $modelInstance->getTable();
        try {
            $hasTable = Schema::hasTable($table);
            $hasEnterpriseId = $hasTable && Schema::hasColumn($table, 'enterprise_id');
            $hasSiteId = $hasTable && Schema::hasColumn($table, 'site_id');
        } catch (Throwable) {
            $hasEnterpriseId = false;
            $hasSiteId = false;
        }

        // Auto-fill enterprise_id lors de la création
        static::creating(function (Model $model) use ($hasEnterpriseId, $hasSiteId) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Exception pour super_admin
                if ($user->isSuperAdmin()) {
                    return;
                }

                // Auto-fill enterprise_id only when column exists.
                if ($hasEnterpriseId && !$model->getAttribute('enterprise_id')) {
                    $model->setAttribute('enterprise_id', $user->enterprise_id);
                }

                // Auto-fill site_id si pas défini et colonne existante.
                if ($hasSiteId && !$model->getAttribute('site_id') && $user->site_id) {
                    $model->site_id = $user->site_id;
                }
            }
        });

        // Global scope pour filtrer par site OU entreprise
        static::addGlobalScope('enterprise', function (Builder $builder) use ($hasEnterpriseId, $hasSiteId) {
            if (auth()->check()) {
                $user = auth()->user();
                
                // Exception pour super_admin - voit tout
                if ($user->isSuperAdmin()) {
                    return;
                }
                
                // Prefer enterprise_id filtering when available.
                if ($hasEnterpriseId) {
                    // Admin entreprise - voit tous les sites de son entreprise
                    if ($user->isEnterpriseAdmin() && $user->enterprise_id) {
                        $builder->where('enterprise_id', $user->enterprise_id);
                        return;
                    }

                    // Utilisateur normal - voit seulement son site si disponible,
                    // sinon filtre entreprise.
                    if ($hasSiteId && $user->site_id) {
                        $builder->where('site_id', $user->site_id);
                        return;
                    }

                    if ($user->enterprise_id) {
                        $builder->where('enterprise_id', $user->enterprise_id);
                    }
                    return;
                }

                // Fallback for models without enterprise_id but with site_id.
                if ($hasSiteId) {
                    if ($user->isEnterpriseAdmin() && $user->enterprise_id) {
                        $siteIds = Site::query()
                            ->where('enterprise_id', $user->enterprise_id)
                            ->pluck('id');
                        $builder->whereIn('site_id', $siteIds);
                        return;
                    }

                    if ($user->site_id) {
                        $builder->where('site_id', $user->site_id);
                    }
                }
            }
        });
    }

    /**
     * Relation vers l'entreprise
     */
    public function enterprise()
    {
        return $this->belongsTo(\App\Models\Enterprise::class);
    }

    /**
     * Scope pour désactiver le filtrage entreprise (admin uniquement)
     */
    public function scopeWithoutEnterpriseScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope('enterprise');
    }

    /**
     * Scope pour forcer une entreprise spécifique
     */
    public function scopeForEnterprise(Builder $query, int $enterpriseId): Builder
    {
        return $query->withoutGlobalScope('enterprise')->where('enterprise_id', $enterpriseId);
    }
}
