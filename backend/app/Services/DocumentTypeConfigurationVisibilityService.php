<?php

namespace App\Services;

use App\Models\DocumentTypeConfiguration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class DocumentTypeConfigurationVisibilityService
{
    /**
     * Récupère les configurations visibles pour un utilisateur
     */
    public function getVisibleConfigurations(User $user, array $filters = []): Collection
    {
        $query = DocumentTypeConfiguration::query()
            ->with(['structureParts', 'site', 'enterprise']);

        // Filtres de visibilité basés sur le rôle
        if (!$user->hasRole('super-admin')) {
            $query->where(function ($q) use ($user) {
                // Configurations de l'entreprise de l'utilisateur
                $q->where('enterprise_id', $user->enterprise_id)
                  ->orWhereNull('enterprise_id'); // Configurations globales
                
                // Si l'utilisateur a un site, inclure les configs du site
                if ($user->site_id) {
                    $q->orWhere('site_id', $user->site_id);
                }
            });
        }

        // Filtres additionnels
        if (isset($filters['site_id'])) {
            $query->where('site_id', $filters['site_id']);
        }

        if (isset($filters['enterprise_id'])) {
            $query->where('enterprise_id', $filters['enterprise_id']);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', "%{$filters['search']}%")
                  ->orWhere('abbreviation', 'like', "%{$filters['search']}%");
            });
        }

        if (isset($filters['scope'])) {
            $query->where('scope', $filters['scope']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Vérifie si un utilisateur peut voir une configuration
     */
    public function canView(User $user, DocumentTypeConfiguration $config): bool
    {
        // Super admin peut tout voir
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Configuration globale (sans entreprise ni site)
        if (!$config->enterprise_id && !$config->site_id) {
            return true;
        }

        // Configuration de l'entreprise de l'utilisateur
        if ($config->enterprise_id && (int) $config->enterprise_id === (int) $user->enterprise_id) {
            return true;
        }

        // Configuration du site de l'utilisateur (uniquement si site non null)
        if ($config->site_id && $user->site_id && (int) $config->site_id === (int) $user->site_id) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur peut modifier une configuration
     */
    public function canEdit(User $user, DocumentTypeConfiguration $config): bool
    {
        // Super admin peut tout modifier
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // Vérifier la permission de base
        if (!$this->canView($user, $config)) {
            return false;
        }

        // Vérifier la permission configure_nomenclature
        try {
            return (bool) $user->hasPermissionTo('configure_nomenclature');
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Partage une configuration avec des sites
     */
    public function shareWithSites(DocumentTypeConfiguration $config, array $siteIds, string $conflictStrategy = 'skip'): array
    {
        $results = [
            'success' => [],
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($siteIds as $siteId) {
                try {
                    // Vérifier si une config avec la même abbreviation existe déjà pour ce site
                    $existing = DocumentTypeConfiguration::where('site_id', $siteId)
                        ->where('abbreviation', $config->abbreviation)
                        ->where('enterprise_id', $config->enterprise_id)
                        ->first();

                    if ($existing && $conflictStrategy === 'skip') {
                        $results['success'][] = [
                            'site_id' => $siteId,
                            'config_id' => $existing->id,
                            'already_exists' => true,
                            'action' => 'skipped',
                        ];
                        continue;
                    }

                    if ($existing && $conflictStrategy === 'override') {
                        $existing->fill([
                            'name' => $config->name,
                            'description' => $config->description,
                            'scope' => 'site',
                            'is_active' => (bool) $config->is_active,
                            'abbreviation_length' => $config->abbreviation_length,
                        ]);
                        $existing->save();

                        $existing->structureParts()->delete();
                        foreach ($config->structureParts as $part) {
                            $sharedPart = $part->replicate();
                            $sharedPart->document_type_configuration_id = $existing->id;
                            $sharedPart->save();
                        }

                        $results['success'][] = [
                            'site_id' => $siteId,
                            'config_id' => $existing->id,
                            'already_exists' => true,
                            'action' => 'overridden',
                        ];
                        continue;
                    }

                    $sharedConfig = $config->replicate();
                    $sharedConfig->site_id = $siteId;
                    $sharedConfig->name = $config->name;
                    // Adapter l'enterprise_id au site cible
                    $siteEnterpriseId = DB::table('sites')->where('id', $siteId)->value('enterprise_id');
                    if ($siteEnterpriseId) {
                        $sharedConfig->enterprise_id = $siteEnterpriseId;
                    }
                    $sharedConfig->save();

                    foreach ($config->structureParts as $part) {
                        $sharedPart = $part->replicate();
                        $sharedPart->document_type_configuration_id = $sharedConfig->id;
                        $sharedPart->save();
                    }

                    $results['success'][] = [
                        'site_id' => $siteId,
                        'config_id' => $sharedConfig->id,
                        'action' => 'created',
                    ];
                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'site_id' => $siteId,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Partage une configuration avec des entreprises
     */
    public function shareWithEnterprises(DocumentTypeConfiguration $config, array $enterpriseIds): array
    {
        $results = [
            'success' => [],
            'errors' => []
        ];

        DB::beginTransaction();

        try {
            foreach ($enterpriseIds as $enterpriseId) {
                try {
                    $sharedConfig = $config->replicate();
                    $sharedConfig->enterprise_id = $enterpriseId;
                    $sharedConfig->site_id = null;
                    $sharedConfig->name = $config->name . ' (Partagé)';
                    $sharedConfig->save();

                    foreach ($config->structureParts as $part) {
                        $sharedPart = $part->replicate();
                        $sharedPart->document_type_configuration_id = $sharedConfig->id;
                        $sharedPart->save();
                    }

                    $results['success'][] = [
                        'enterprise_id' => $enterpriseId,
                        'config_id' => $sharedConfig->id
                    ];

                } catch (\Exception $e) {
                    $results['errors'][] = [
                        'enterprise_id' => $enterpriseId,
                        'error' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }

        return $results;
    }

    /**
     * Récupère les statistiques de visibilité
     */
    public function getVisibilityStats(User $user): array
    {
        $configs = $this->getVisibleConfigurations($user);

        return [
            'total' => $configs->count(),
            'by_scope' => [
                'global' => $configs->filter(fn($c) => !$c->enterprise_id && !$c->site_id)->count(),
                'enterprise' => $configs->filter(fn($c) => $c->enterprise_id && !$c->site_id)->count(),
                'site' => $configs->filter(fn($c) => $c->site_id)->count(),
            ],
            'by_status' => [
                'active' => $configs->where('is_active', true)->count(),
                'inactive' => $configs->where('is_active', false)->count(),
            ],
            'by_type' => $configs->groupBy('document_type_id')->map->count()->toArray()
        ];
    }

    /**
     * Récupère les sites disponibles pour le partage
     */
    public function getAvailableSitesForSharing(User $user, DocumentTypeConfiguration $config): Collection
    {
        $query = DB::table('sites')->select('id', 'name', 'enterprise_id');

        if (!$user->hasRole('super-admin')) {
            $query->where('enterprise_id', $user->enterprise_id);
        }

        // Exclure le site de la configuration actuelle
        if ($config->site_id) {
            $query->where('id', '!=', $config->site_id);
        }

        // Exclure les sites qui ont déjà cette configuration
        $existingSiteIds = DocumentTypeConfiguration::where('abbreviation', $config->abbreviation)
            ->whereNotNull('site_id')
            ->pluck('site_id')
            ->toArray();

        if (!empty($existingSiteIds)) {
            $query->whereNotIn('id', $existingSiteIds);
        }

        return collect($query->get());
    }

    /**
     * Récupère les entreprises disponibles pour le partage
     */
    public function getAvailableEnterprisesForSharing(User $user, DocumentTypeConfiguration $config): Collection
    {
        if (!$user->hasRole('super-admin')) {
            return collect([]);
        }

        $query = DB::table('enterprises')->select('id', 'name');

        // Exclure l'entreprise de la configuration actuelle
        if ($config->enterprise_id) {
            $query->where('id', '!=', $config->enterprise_id);
        }

        // Exclure les entreprises qui ont déjà cette configuration
        $existingEnterpriseIds = DocumentTypeConfiguration::where('abbreviation', $config->abbreviation)
            ->whereNotNull('enterprise_id')
            ->pluck('enterprise_id')
            ->toArray();

        if (!empty($existingEnterpriseIds)) {
            $query->whereNotIn('id', $existingEnterpriseIds);
        }

        return collect($query->get());
    }

    /**
     * Applique les filtres avancés
     */
    public function applyAdvancedFilters(User $user, array $filters): Collection
    {
        $configs = $this->getVisibleConfigurations($user, $filters);

        // Filtre par date de création
        if (isset($filters['created_from'])) {
            $configs = $configs->filter(fn($c) => $c->created_at >= $filters['created_from']);
        }

        if (isset($filters['created_to'])) {
            $configs = $configs->filter(fn($c) => $c->created_at <= $filters['created_to']);
        }

        // Filtre par nombre de documents utilisant la config
        if (isset($filters['min_documents'])) {
            $configs = $configs->filter(function ($c) use ($filters) {
                $count = DB::table('documents')
                    ->where('document_type_configuration_id', $c->id)
                    ->count();
                return $count >= $filters['min_documents'];
            });
        }

        // Tri
        if (isset($filters['sort_by'])) {
            $direction = $filters['sort_direction'] ?? 'asc';
            $configs = $direction === 'desc' 
                ? $configs->sortByDesc($filters['sort_by'])
                : $configs->sortBy($filters['sort_by']);
        }

        return $configs;
    }
}
