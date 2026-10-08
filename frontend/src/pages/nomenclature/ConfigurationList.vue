<template>
  <v-container class="py-6" max-width="1200">

    <!-- Header -->
    <div class="d-flex align-center justify-space-between mb-6 flex-wrap gap-3">
      <div>
        <h1 class="text-h5 font-weight-bold">Configuration des nomenclatures</h1>
        <p class="text-body-2 text-medium-emphasis mt-1">
          Gérez les structures de codes documentaires par type et portée
        </p>
      </div>
      <div class="d-flex gap-2">
        <v-btn
          prepend-icon="mdi-filter-outline"
          variant="outlined"
          @click="showAdvancedFilters = !showAdvancedFilters"
        >
          Filtres avancés
        </v-btn>
        <v-btn color="primary" prepend-icon="mdi-plus" :disabled="legacyReadonly" @click="openWizard()">
          Nouvelle configuration
        </v-btn>
      </div>
    </div>

    <v-alert class="mb-4" density="comfortable" type="warning" variant="tonal">
      Cette page est en lecture seule. La configuration officielle se fait via la modale
      <strong>Nomenclatures</strong> (documents).
    </v-alert>

    <!-- Stats -->
    <v-row v-if="visibilityStats" class="mb-4" dense>
      <v-col cols="6" sm="3">
        <v-card class="pa-3 text-center" color="primary" variant="tonal">
          <div class="text-h5 font-weight-bold">{{ visibilityStats.total }}</div>
          <div class="text-caption">Total</div>
        </v-card>
      </v-col>
      <v-col cols="6" sm="3">
        <v-card class="pa-3 text-center" color="success" variant="tonal">
          <div class="text-h5 font-weight-bold">{{ visibilityStats.by_status.active }}</div>
          <div class="text-caption">Actives</div>
        </v-card>
      </v-col>
      <v-col cols="6" sm="3">
        <v-card class="pa-3 text-center" color="info" variant="tonal">
          <div class="text-h5 font-weight-bold">{{ visibilityStats.by_scope.enterprise }}</div>
          <div class="text-caption">Entreprise</div>
        </v-card>
      </v-col>
      <v-col cols="6" sm="3">
        <v-card class="pa-3 text-center" color="warning" variant="tonal">
          <div class="text-h5 font-weight-bold">{{ visibilityStats.by_scope.site }}</div>
          <div class="text-caption">Site</div>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filtres avancés -->
    <v-expand-transition>
      <v-card v-if="showAdvancedFilters" class="mb-4 pa-4" variant="outlined">
        <v-row dense>
          <v-col cols="12" sm="4">
            <v-select
              v-model="filters.is_active"
              clearable
              density="compact"
              :items="[{ title: 'Tous', value: undefined }, { title: 'Actives', value: true }, { title: 'Inactives', value: false }]"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-select
              v-model="filters.scope"
              clearable
              density="compact"
              :items="[{ title: 'Toutes portées', value: undefined }, { title: 'Global', value: 'global' }, { title: 'Entreprise', value: 'enterprise' }, { title: 'Site', value: 'site' }]"
              label="Portée"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" sm="4">
            <v-text-field
              v-model="filters.search"
              clearable
              density="compact"
              label="Recherche"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card>
    </v-expand-transition>

    <!-- Table -->
    <v-card variant="outlined">
      <div v-if="loading" class="d-flex justify-center py-10">
        <v-progress-circular color="primary" indeterminate />
      </div>

      <v-alert
        v-else-if="error"
        class="ma-4"
        density="compact"
        type="error"
        variant="tonal"
      >
        {{ error }}
      </v-alert>

      <v-alert
        v-else-if="configurations.length === 0"
        class="ma-4"
        density="compact"
        type="info"
        variant="tonal"
      >
        Aucune configuration trouvée.
      </v-alert>

      <v-table v-else density="comfortable">
        <thead>
          <tr>
            <th>Code</th>
            <th>Libellé</th>
            <th>Portée</th>
            <th>Aperçu</th>
            <th>Docs</th>
            <th>Statut</th>
            <th class="text-right">Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="config in configurations" :key="config.id">
            <td><code class="text-body-2">{{ config.type_code }}</code></td>
            <td class="text-body-2">{{ config.type_label }}</td>
            <td>
              <v-chip :color="scopeColor(config.scope_label)" size="x-small" variant="tonal">
                {{ config.scope_label || 'N/A' }}
              </v-chip>
            </td>
            <td>
              <code class="text-caption text-medium-emphasis">{{ config.structure_preview }}</code>
            </td>
            <td class="text-body-2 text-medium-emphasis">{{ config.documents_count ?? 0 }}</td>
            <td>
              <v-chip :color="config.is_active ? 'success' : 'default'" size="x-small" variant="tonal">
                {{ config.is_active ? 'Active' : 'Inactive' }}
              </v-chip>
            </td>
            <td class="text-right">
              <v-menu>
                <template #activator="{ props: menuProps }">
                  <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="menuProps" />
                </template>
                <v-list density="compact" min-width="180">
                  <v-list-item
                    v-if="config.can_edit && !legacyReadonly"
                    prepend-icon="mdi-pencil-outline"
                    title="Modifier"
                    @click="openWizard(config)"
                  />
                  <v-list-item
                    v-if="config.can_edit && !legacyReadonly"
                    :prepend-icon="config.is_active ? 'mdi-toggle-switch-off-outline' : 'mdi-toggle-switch-outline'"
                    :title="config.is_active ? 'Désactiver' : 'Activer'"
                    @click="toggleActive(config)"
                  />
                  <v-list-item
                    v-if="!legacyReadonly"
                    prepend-icon="mdi-share-variant-outline"
                    title="Partager"
                    @click="openShareModal(config)"
                  />
                  <v-list-item
                    v-if="!legacyReadonly"
                    prepend-icon="mdi-content-copy"
                    title="Dupliquer"
                    @click="openDuplicateDialog(config)"
                  />
                  <v-divider v-if="config.can_edit && !legacyReadonly" />
                  <v-list-item
                    v-if="config.can_edit && !legacyReadonly"
                    class="text-error"
                    prepend-icon="mdi-delete-outline"
                    title="Supprimer"
                    @click="openDeleteDialog(config)"
                  />
                </v-list>
              </v-menu>
            </td>
          </tr>
        </tbody>
      </v-table>
    </v-card>

    <!-- Wizard -->
    <ConfigurationWizard
      v-if="showWizard"
      :configuration="selectedConfig"
      @close="closeWizard"
      @saved="onSaved"
    />

    <!-- Share Modal -->
    <ShareConfigurationModal
      v-model="showShareModal"
      :can-share-with-enterprises="canShareWithEnterprises"
      :configuration="selectedConfigForShare"
      @shared="onShared"
    />

    <!-- Dialog Dupliquer -->
    <v-dialog v-model="duplicateDialog" max-width="440">
      <v-card>
        <v-card-title>Dupliquer la configuration</v-card-title>
        <v-card-text>
          <v-text-field
            v-model="duplicateForm.type_code"
            class="mb-2"
            density="comfortable"
            label="Code du nouveau type *"
            variant="outlined"
          />
          <v-text-field
            v-model="duplicateForm.type_label"
            density="comfortable"
            label="Libellé du nouveau type *"
            variant="outlined"
          />
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="duplicateDialog = false">Annuler</v-btn>
          <v-btn
            color="primary"
            :disabled="!duplicateForm.type_code || !duplicateForm.type_label"
            :loading="duplicating"
            @click="confirmDuplicate"
          >
            Dupliquer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Supprimer -->
    <v-dialog v-model="deleteDialog" max-width="400">
      <v-card>
        <v-card-title>Confirmer la suppression</v-card-title>
        <v-card-text>
          Supprimer la configuration <strong>{{ configToDelete?.type_label }}</strong> ?
          Cette action est irréversible.
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="deleteDialog = false">Annuler</v-btn>
          <v-btn color="error" :loading="deleting" @click="confirmDelete">Supprimer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Dialog Propagation -->
    <v-dialog v-model="showPropagationDialog" max-width="560">
      <v-card>
        <v-card-title>Appliquer aux autres sites</v-card-title>
        <v-card-text>
          <p class="text-body-2 mb-3">
            Configuration enregistrée pour ce site. Voulez-vous l’appliquer à d’autres sites de l’entreprise ?
          </p>
          <v-select
            v-model="propagationConflictStrategy"
            class="mb-3"
            density="compact"
            :items="[
              { title: 'Ignorer si existant (skip)', value: 'skip' },
              { title: 'Remplacer si existant (override)', value: 'override' },
            ]"
            label="Gestion des conflits"
            variant="outlined"
          />
          <v-list
            v-if="propagationSites.length > 0"
            class="rounded border"
            density="compact"
            max-height="260"
            style="overflow-y:auto;"
          >
            <v-list-item
              v-for="site in propagationSites"
              :key="site.id"
              :title="site.name"
              @click="togglePropagationSite(site.id)"
            >
              <template #prepend>
                <v-checkbox-btn
                  color="primary"
                  :model-value="selectedPropagationSiteIds.includes(site.id)"
                  @click.stop="togglePropagationSite(site.id)"
                />
              </template>
            </v-list-item>
          </v-list>
          <v-alert v-else density="compact" type="info" variant="tonal">
            Aucun autre site disponible.
          </v-alert>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="showPropagationDialog = false">Plus tard</v-btn>
          <v-btn
            color="primary"
            :disabled="selectedPropagationSiteIds.length === 0 || propagating"
            :loading="propagating"
            @click="confirmPropagation"
          >
            Appliquer
          </v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

  </v-container>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { type DocumentTypeConfiguration, documentTypeConfigurationApi, type VisibilityStats } from '@/api/documentTypeConfiguration'
  import ConfigurationWizard from '@/components/nomenclature/ConfigurationWizard.vue'
  import ShareConfigurationModal from '@/components/nomenclature/ShareConfigurationModal.vue'
  import { useAuthStore } from '@/stores/auth'

  const authStore = useAuthStore()

  const configurations = ref<any[]>([])
  const loading = ref(false)
  const error = ref('')
  const legacyReadonly = true
  const showWizard = ref(false)
  const showAdvancedFilters = ref(false)
  const showShareModal = ref(false)
  const selectedConfig = ref<DocumentTypeConfiguration | undefined>()
  const selectedConfigForShare = ref<any>(null)
  const visibilityStats = ref<VisibilityStats | null>(null)
  const filters = ref<Record<string, any>>({})

  // Duplicate
  const duplicateDialog = ref(false)
  const duplicating = ref(false)
  const configToDuplicate = ref<any>(null)
  const duplicateForm = ref({ type_code: '', type_label: '' })

  // Delete
  const deleteDialog = ref(false)
  const deleting = ref(false)
  const configToDelete = ref<any>(null)
  const showPropagationDialog = ref(false)
  const propagationSites = ref<any[]>([])
  const selectedPropagationSiteIds = ref<number[]>([])
  const propagationTargetConfig = ref<any>(null)
  const propagating = ref(false)
  const propagationConflictStrategy = ref<'skip' | 'override'>('skip')

  const canShareWithEnterprises = computed(() =>
    (authStore.user as any)?.roles?.includes('super-admin') ?? false,
  )

  function scopeColor (scope: string): string {
    return { Global: 'default', Entreprise: 'info', Site: 'warning' }[scope] ?? 'default'
  }

  async function loadConfigurations () {
    loading.value = true
    error.value = ''
    try {
      const res = await documentTypeConfigurationApi.list(filters.value)
      configurations.value = (res.data as any)?.data ?? res.data ?? []
    } catch (error_: any) {
      error.value = error_.response?.data?.message ?? 'Erreur lors du chargement'
    } finally {
      loading.value = false
    }
  }

  async function loadVisibilityStats () {
    try {
      const res = await documentTypeConfigurationApi.getVisibilityStats()
      visibilityStats.value = (res.data as any)?.data ?? res.data
    } catch { /* silencieux */ }
  }

  function openWizard (config?: DocumentTypeConfiguration) {
    selectedConfig.value = config
    showWizard.value = true
  }

  function closeWizard () {
    showWizard.value = false
    selectedConfig.value = undefined
  }

  async function onSaved (payload: { configuration: any, mode: 'create' | 'update' }) {
    closeWizard()
    await loadConfigurations()
    await loadVisibilityStats()

    const config = payload?.configuration
    if (!config?.id || config.scope !== 'site') {
      return
    }

    try {
      const res = await documentTypeConfigurationApi.getAvailableSitesForSharing(config.id)
      const sites = (res.data as any)?.data ?? res.data ?? []
      if (!Array.isArray(sites) || sites.length === 0) {
        return
      }
      propagationSites.value = sites
      selectedPropagationSiteIds.value = []
      propagationTargetConfig.value = config
      propagationConflictStrategy.value = 'skip'
      showPropagationDialog.value = true
    } catch {
      // silencieux: la sauvegarde est déjà faite
    }
  }

  function openShareModal (config: any) {
    selectedConfigForShare.value = config
    showShareModal.value = true
  }

  function onShared () {
    loadConfigurations()
    loadVisibilityStats()
  }

  function togglePropagationSite (id: number) {
    const idx = selectedPropagationSiteIds.value.indexOf(id)
    if (idx === -1) {
      selectedPropagationSiteIds.value.push(id)
      return
    }
    selectedPropagationSiteIds.value.splice(idx, 1)
  }

  async function confirmPropagation () {
    if (!propagationTargetConfig.value?.id || selectedPropagationSiteIds.value.length === 0) return
    propagating.value = true
    try {
      await documentTypeConfigurationApi.shareWithSites(
        propagationTargetConfig.value.id,
        selectedPropagationSiteIds.value,
        propagationConflictStrategy.value,
      )
      showPropagationDialog.value = false
      await loadConfigurations()
      await loadVisibilityStats()
    } finally {
      propagating.value = false
    }
  }

  async function toggleActive (config: any) {
    try {
      await documentTypeConfigurationApi.toggleActive(config.id)
      await loadConfigurations()
    } catch { /* erreur gérée par intercepteur */ }
  }

  function openDuplicateDialog (config: any) {
    configToDuplicate.value = config
    duplicateForm.value = {
      type_code: `${config.type_code}_COPY`,
      type_label: `${config.type_label} (copie)`,
    }
    duplicateDialog.value = true
  }

  async function confirmDuplicate () {
    if (!configToDuplicate.value) return
    duplicating.value = true
    try {
      await documentTypeConfigurationApi.duplicate(configToDuplicate.value.id, duplicateForm.value)
      duplicateDialog.value = false
      await loadConfigurations()
    } finally {
      duplicating.value = false
    }
  }

  function openDeleteDialog (config: any) {
    configToDelete.value = config
    deleteDialog.value = true
  }

  async function confirmDelete () {
    if (!configToDelete.value) return
    deleting.value = true
    try {
      await documentTypeConfigurationApi.delete(configToDelete.value.id)
      deleteDialog.value = false
      await loadConfigurations()
      await loadVisibilityStats()
    } finally {
      deleting.value = false
    }
  }

  watch(filters, () => loadConfigurations(), { deep: true })

  onMounted(() => {
    loadConfigurations()
    loadVisibilityStats()
  })
</script>
