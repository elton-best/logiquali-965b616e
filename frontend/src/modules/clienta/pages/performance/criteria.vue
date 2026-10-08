<template>
  <ClientALayout current-page="performance-settings">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-tune-vertical" title="Critères d'évaluation">
        <template #subtitle>
          Définir et personnaliser les critères utilisés dans vos formulaires d'évaluation
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            color="secondary"
            prepend-icon="mdi-refresh"
            variant="outlined"
            @click="initializeDefaults"
          >
            Charger défauts
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouveau critère
          </v-btn>
        </template>
      </PageHeader>

      <!-- Carte héro avec statistiques -->
      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Configuration</div>
              <h2 class="text-h5 font-weight-bold mb-2">
                Personnalisez vos critères d'évaluation
              </h2>
              <p class="text-body-2 text-medium-emphasis mb-0">
                Configurez les critères par type de formulaire : satisfaction client, évaluation du personnel, auditeurs, fournisseurs...
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Critères actifs</span>
                <strong>{{ activeCriteriaCount }}</strong>
              </div>
              <div class="hero-badge">
                <span>Catégories</span>
                <strong>{{ categories.length }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- Filtres -->
      <v-card class="filters-card mb-4" rounded="xl" variant="tonal">
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.search"
                clearable
                hide-details
                label="Rechercher"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.formType"
                clearable
                hide-details
                item-title="label"
                item-value="value"
                :items="formTypeOptions"
                label="Type de formulaire"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.category"
                clearable
                hide-details
                :items="categories"
                label="Catégorie"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center gap-2" cols="12" md="3">
              <v-checkbox
                v-model="filters.showInactive"
                hide-details
                label="Inactifs"
              />
              <v-spacer />
              <v-btn
                icon="mdi-filter-off"
                size="small"
                variant="text"
                @click="resetFilters"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Liste des critères par catégorie -->
      <v-card class="criteria-surface" rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between pa-4 pb-2">
          <div>
            <div class="text-h6 font-weight-bold">Critères configurés</div>
            <div class="text-caption text-medium-emphasis">
              Réorganisez par glisser-déposer et gérez chaque critère rapidement.
            </div>
          </div>
          <v-chip color="primary" size="small" variant="tonal">
            {{ filteredCriteria.length }} critère(s)
          </v-chip>
        </v-card-title>

        <v-card-text v-if="loading" class="text-center py-8">
          <v-progress-circular color="primary" indeterminate />
        </v-card-text>

        <v-card-text v-else-if="filteredCriteria.length === 0" class="text-center py-8 text-medium-emphasis">
          <v-icon class="mb-2" color="grey" size="48">mdi-clipboard-text-off-outline</v-icon>
          <p>Aucun critère trouvé.</p>
          <v-btn color="primary" variant="text" @click="initializeDefaults">
            Charger les critères par défaut
          </v-btn>
        </v-card-text>

        <template v-else>
          <v-expansion-panels v-model="expandedCategories" multiple variant="accordion">
            <v-expansion-panel
              v-for="(group, categoryName) in groupedCriteria"
              :key="categoryName"
              :title="categoryName || 'Sans catégorie'"
            >
              <template #title>
                <div class="d-flex align-center gap-2">
                  <v-icon color="primary" size="20">mdi-folder-outline</v-icon>
                  <span class="font-weight-medium">{{ categoryName || 'Sans catégorie' }}</span>
                  <v-chip class="ml-2" size="x-small" variant="tonal">
                    {{ group.length }}
                  </v-chip>
                </div>
              </template>

              <v-expansion-panel-text class="pt-2">
                <draggable
                  class="criteria-list"
                  ghost-class="ghost"
                  handle=".drag-handle"
                  item-key="id"
                  :list="group"
                  @end="onDragEnd(categoryName, group)"
                >
                  <template #item="{ element: criterion }">
                    <v-card
                      class="criteria-item mb-2"
                      :class="{ 'opacity-50': !criterion.is_active }"
                      rounded="lg"
                      variant="outlined"
                    >
                      <v-card-text class="d-flex align-center pa-3">
                        <v-icon class="drag-handle cursor-grab mr-3" color="grey">mdi-drag</v-icon>

                        <div class="flex-grow-1">
                          <div class="d-flex align-center gap-2 mb-1">
                            <v-chip v-if="criterion.code" color="primary" label size="x-small">
                              {{ criterion.code }}
                            </v-chip>
                            <span class="font-weight-medium">{{ criterion.name }}</span>
                            <v-chip color="info" size="x-small" variant="tonal">
                              {{ getFormTypeLabel(criterion.form_type) }}
                            </v-chip>
                            <v-chip
                              v-if="!criterion.is_active"
                              color="warning"
                              size="x-small"
                              variant="tonal"
                            >
                              Inactif
                            </v-chip>
                            <v-chip
                              v-if="criterion.is_mandatory"
                              color="error"
                              size="x-small"
                              variant="tonal"
                            >
                              Obligatoire
                            </v-chip>
                          </div>
                          <div v-if="criterion.description" class="text-caption text-medium-emphasis mb-1">
                            {{ criterion.description }}
                          </div>
                          <div class="text-caption text-medium-emphasis">
                            Échelle {{ criterion.scale_min }} - {{ criterion.scale_max }}
                            • {{ getScaleTypeLabel(criterion.scale_type) }}
                            <span v-if="criterion.weight !== 1"> • Poids: ×{{ criterion.weight }}</span>
                          </div>
                        </div>

                        <div class="d-flex gap-1">
                          <v-tooltip location="top" text="Modifier">
                            <template #activator="{ props }">
                              <v-btn
                                v-bind="props"
                                icon="mdi-pencil"
                                size="small"
                                variant="text"
                                @click="openEditDialog(criterion)"
                              />
                            </template>
                          </v-tooltip>

                          <v-tooltip location="top" text="Dupliquer">
                            <template #activator="{ props }">
                              <v-btn
                                v-bind="props"
                                icon="mdi-content-copy"
                                size="small"
                                variant="text"
                                @click="duplicateCriterion(criterion.id)"
                              />
                            </template>
                          </v-tooltip>

                          <v-tooltip location="top" :text="criterion.is_active ? 'Désactiver' : 'Activer'">
                            <template #activator="{ props }">
                              <v-btn
                                v-bind="props"
                                :color="criterion.is_active ? 'warning' : 'success'"
                                :icon="criterion.is_active ? 'mdi-eye-off' : 'mdi-eye'"
                                size="small"
                                variant="text"
                                @click="toggleActive(criterion.id)"
                              />
                            </template>
                          </v-tooltip>

                          <v-tooltip location="top" text="Supprimer">
                            <template #activator="{ props }">
                              <v-btn
                                v-bind="props"
                                color="error"
                                icon="mdi-delete"
                                size="small"
                                variant="text"
                                @click="deleteCriterion(criterion.id)"
                              />
                            </template>
                          </v-tooltip>
                        </div>
                      </v-card-text>
                    </v-card>
                  </template>
                </draggable>
              </v-expansion-panel-text>
            </v-expansion-panel>
          </v-expansion-panels>
        </template>
      </v-card>

      <!-- Dialog création/édition -->
      <v-dialog v-model="showDialog" max-width="860" persistent>
        <v-card class="criteria-dialog-card" rounded="xl">
          <v-card-title class="criteria-dialog-header d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-2" color="primary">{{ editingId ? 'mdi-pencil' : 'mdi-plus-circle' }}</v-icon>
              <div>
                <div class="text-h6 font-weight-bold">{{ editingId ? 'Modifier le critère' : 'Nouveau critère' }}</div>
                <div class="text-caption text-medium-emphasis">
                  Configurez précisément l’échelle, le type de formulaire et les règles d’usage.
                </div>
              </div>
            </div>
            <v-btn icon="mdi-close" size="small" variant="text" @click="closeDialog" />
          </v-card-title>

          <v-divider />

          <v-card-text class="pa-4 pa-md-6">
            <v-row>
              <v-col cols="12" md="7">
                <v-card class="criteria-block h-100" rounded="lg" variant="tonal">
                  <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                    Informations du critère
                  </v-card-title>
                  <v-card-text class="pt-3">
                    <v-row>
                      <v-col cols="12" md="8">
                        <v-text-field
                          v-model="form.name"
                          label="Nom du critère *"
                          :rules="[v => !!v || 'Requis']"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="4">
                        <v-text-field
                          v-model="form.code"
                          hint="Ex: SC1, AU2"
                          label="Code"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12">
                        <v-textarea
                          v-model="form.description"
                          label="Description"
                          rows="2"
                          variant="outlined"
                        />
                      </v-col>

                      <v-col cols="12" md="6">
                        <v-select
                          v-model="form.form_type"
                          item-title="label"
                          item-value="value"
                          :items="formTypeOptions"
                          label="Type de formulaire *"
                          :rules="[v => !!v || 'Requis']"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12" md="6">
                        <v-combobox
                          v-model="form.category"
                          :items="categories"
                          label="Catégorie"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12" md="5">
                <v-card class="criteria-block mb-4" rounded="lg" variant="tonal">
                  <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                    Échelle de notation
                  </v-card-title>
                  <v-card-text class="pt-3">
                    <v-row>
                      <v-col cols="12">
                        <v-select
                          v-model="form.scale_type"
                          item-title="label"
                          item-value="value"
                          :items="scaleTypeOptions"
                          label="Type d'échelle"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model.number="form.scale_min"
                          label="Min"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="6">
                        <v-text-field
                          v-model.number="form.scale_max"
                          label="Max"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                      <v-col cols="12">
                        <v-text-field
                          v-model.number="form.weight"
                          hint="Coefficient de pondération"
                          label="Poids"
                          persistent-hint
                          step="0.1"
                          type="number"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>

                <v-card class="criteria-block" rounded="lg" variant="tonal">
                  <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                    Options
                  </v-card-title>
                  <v-card-text class="pt-3">
                    <v-checkbox v-model="form.is_mandatory" hide-details label="Critère obligatoire" />
                    <v-checkbox v-model="form.is_active" hide-details label="Critère actif" />
                  </v-card-text>
                </v-card>
              </v-col>

              <v-col cols="12">
                <v-card class="criteria-block" rounded="lg" variant="tonal">
                  <v-card-title class="text-subtitle-1 font-weight-bold pb-0">
                    Labels d’échelle (optionnel)
                  </v-card-title>
                  <v-card-text class="pt-3">
                    <v-row dense>
                      <v-col
                        v-for="i in (form.scale_max - form.scale_min + 1)"
                        :key="i"
                        cols="12"
                        md="4"
                      >
                        <v-text-field
                          v-model="form.scale_labels[form.scale_min + i - 1]"
                          density="compact"
                          :label="`${form.scale_min + i - 1}`"
                          :placeholder="`Label pour ${form.scale_min + i - 1}`"
                          variant="outlined"
                        />
                      </v-col>
                    </v-row>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>

          <v-divider />

          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="closeDialog">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="saveCriterion">
              {{ editingId ? 'Enregistrer' : 'Créer' }}
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Dialog initialisation défauts -->
      <v-dialog v-model="showDefaultsDialog" max-width="500">
        <v-card rounded="xl">
          <v-card-title class="pa-4">Charger les critères par défaut</v-card-title>
          <v-card-text>
            <p class="mb-4">Sélectionnez le type de formulaire pour lequel charger les critères par défaut :</p>
            <v-select
              v-model="defaultsFormType"
              item-title="label"
              item-value="value"
              :items="formTypeOptions.filter(o => o.value !== 'custom')"
              label="Type de formulaire"
              variant="outlined"
            />
          </v-card-text>
          <v-card-actions class="pa-4">
            <v-spacer />
            <v-btn variant="text" @click="showDefaultsDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="loadingDefaults" @click="confirmInitializeDefaults">
              Charger
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute } from 'vue-router'
  import draggable from 'vuedraggable'
  import { type EvaluationCriteria, evaluationCriteriaApi, type EvaluationFormType } from '@/api/services/evaluationCriteria'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { getErrorMessage } from '@/utils/errorMessage'

  const toast = useToast()
  const route = useRoute()

  const loading = ref(false)
  const saving = ref(false)
  const loadingDefaults = ref(false)
  const showDialog = ref(false)
  const showDefaultsDialog = ref(false)
  const editingId = ref<number | null>(null)
  const defaultsFormType = ref<EvaluationFormType>('satisfaction_client')
  const expandedCategories = ref<number[]>([0])

  const criteria = ref<EvaluationCriteria[]>([])
  const categories = ref<string[]>([])

  const formTypeOptions = [
    { value: 'satisfaction_client', label: 'Satisfaction client' },
    { value: 'satisfaction_personnel', label: 'Satisfaction personnel' },
    { value: 'performance_personnel', label: 'Performance personnel' },
    { value: 'evaluation_auditeur', label: 'Évaluation auditeur' },
    { value: 'satisfaction_fournisseur', label: 'Satisfaction prestataires' },
    { value: 'performance_fournisseur', label: 'Performance prestataires' },
    { value: 'audit_interne', label: 'Audit interne' },
    { value: 'custom', label: 'Personnalisé' },
  ]

  const scaleTypeOptions = [
    { value: 'numeric', label: 'Numérique' },
    { value: 'stars', label: 'Étoiles' },
    { value: 'percentage', label: 'Pourcentage' },
    { value: 'custom', label: 'Personnalisé' },
  ]

  function getFormTypeLabel (value: EvaluationFormType) {
    return formTypeOptions.find(option => option.value === value)?.label || value
  }

  function getScaleTypeLabel (value: string) {
    return scaleTypeOptions.find(option => option.value === value)?.label || value
  }

  const filters = ref({
    search: '',
    formType: null as EvaluationFormType | null,
    category: null as string | null,
    showInactive: false,
  })

  const form = reactive({
    name: '',
    code: '',
    description: '',
    category: '',
    form_type: 'custom' as EvaluationFormType,
    scale_type: 'numeric' as 'numeric' | 'stars' | 'percentage' | 'custom',
    scale_min: 0,
    scale_max: 4,
    scale_labels: {} as Record<string, string>,
    weight: 1,
    is_mandatory: true,
    is_active: true,
  })

  const filteredCriteria = computed(() => {
    return criteria.value.filter(c => {
      if (!filters.value.showInactive && !c.is_active) return false
      if (filters.value.formType && c.form_type !== filters.value.formType) return false
      if (filters.value.category && c.category !== filters.value.category) return false
      if (filters.value.search) {
        const search = filters.value.search.toLowerCase()
        const matches = [c.name, c.code, c.description, c.category]
          .filter(Boolean)
          .some(v => v!.toLowerCase().includes(search))
        if (!matches) return false
      }
      return true
    })
  })

  const groupedCriteria = computed(() => {
    const groups: Record<string, EvaluationCriteria[]> = {}
    for (const c of filteredCriteria.value) {
      const cat = c.category || ''
      if (!groups[cat]) groups[cat] = []
      groups[cat].push(c)
    }
    // Trier par display_order dans chaque groupe
    for (const cat of Object.keys(groups)) {
      groups[cat] = groups[cat].toSorted((a, b) => a.display_order - b.display_order)
    }
    return groups
  })

  const activeCriteriaCount = computed(() => criteria.value.filter(c => c.is_active).length)

  async function loadCriteria () {
    loading.value = true
    try {
      const response = await evaluationCriteriaApi.list({ per_page: 200 })
      criteria.value = response.data || []
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les critères'))
    } finally {
      loading.value = false
    }
  }

  async function loadCategories () {
    try {
      const response = await evaluationCriteriaApi.getCategories()
      categories.value = response.data || []
    } catch {
    // Ignorer silencieusement
    }
  }

  function resetFilters () {
    filters.value = {
      search: '',
      formType: null,
      category: null,
      showInactive: false,
    }
  }

  function resetForm () {
    form.name = ''
    form.code = ''
    form.description = ''
    form.category = ''
    form.form_type = (filters.value.formType || 'satisfaction_client') as EvaluationFormType
    form.scale_type = 'numeric'
    form.scale_min = 0
    form.scale_max = 4
    form.scale_labels = {}
    form.weight = 1
    form.is_mandatory = true
    form.is_active = true
  }

  function openCreateDialog () {
    editingId.value = null
    resetForm()
    showDialog.value = true
  }

  function openEditDialog (criterion: EvaluationCriteria) {
    editingId.value = criterion.id
    form.name = criterion.name
    form.code = criterion.code || ''
    form.description = criterion.description || ''
    form.category = criterion.category || ''
    form.form_type = criterion.form_type
    form.scale_type = criterion.scale_type
    form.scale_min = criterion.scale_min
    form.scale_max = criterion.scale_max
    form.scale_labels = { ...criterion.scale_labels }
    form.weight = criterion.weight
    form.is_mandatory = criterion.is_mandatory
    form.is_active = criterion.is_active
    showDialog.value = true
  }

  function closeDialog () {
    showDialog.value = false
    editingId.value = null
  }

  async function saveCriterion () {
    if (!form.name || !form.form_type) {
      toast.error('Le nom et le type de formulaire sont requis')
      return
    }

    saving.value = true
    try {
      const payload = {
        name: form.name,
        code: form.code || undefined,
        description: form.description || undefined,
        category: form.category || undefined,
        form_type: form.form_type,
        scale_type: form.scale_type,
        scale_min: form.scale_min,
        scale_max: form.scale_max,
        scale_labels: Object.keys(form.scale_labels).length > 0 ? form.scale_labels : undefined,
        weight: form.weight,
        is_mandatory: form.is_mandatory,
        is_active: form.is_active,
      }

      if (editingId.value) {
        await evaluationCriteriaApi.update(editingId.value, payload)
        toast.success('Critère mis à jour')
      } else {
        await evaluationCriteriaApi.create(payload)
        toast.success('Critère créé')
      }

      closeDialog()
      await loadCriteria()
      await loadCategories()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Erreur lors de l\'enregistrement'))
    } finally {
      saving.value = false
    }
  }

  async function deleteCriterion (id: number) {
    if (!confirm('Supprimer ce critère ?')) return

    try {
      await evaluationCriteriaApi.delete(id)
      toast.success('Critère supprimé')
      await loadCriteria()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de supprimer'))
    }
  }

  async function duplicateCriterion (id: number) {
    try {
      await evaluationCriteriaApi.duplicate(id)
      toast.success('Critère dupliqué')
      await loadCriteria()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de dupliquer'))
    }
  }

  async function toggleActive (id: number) {
    try {
      await evaluationCriteriaApi.toggleActive(id)
      await loadCriteria()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de modifier le statut'))
    }
  }

  function initializeDefaults () {
    showDefaultsDialog.value = true
  }

  async function confirmInitializeDefaults () {
    if (!defaultsFormType.value) return

    loadingDefaults.value = true
    try {
      const response = await evaluationCriteriaApi.initializeDefaults(defaultsFormType.value)
      toast.success(`${response.data?.length || 0} critères créés`)
      showDefaultsDialog.value = false
      await loadCriteria()
      await loadCategories()
    } catch (error: any) {
      if (error.response?.status === 409) {
        toast.warning('Des critères existent déjà pour ce type')
      } else {
        toast.error(getErrorMessage(error, 'Impossible de charger les défauts'))
      }
    } finally {
      loadingDefaults.value = false
    }
  }

  async function onDragEnd (categoryName: string, reorderedGroup: EvaluationCriteria[]) {
    const category = categoryName || ''
    const reorderedIds = reorderedGroup.map(criterion => criterion.id)

    if (reorderedIds.length > 0) {
      const reorderedIdSet = new Set(reorderedIds)
      const nextCriteria: EvaluationCriteria[] = []
      const reorderedCriteria = reorderedIds
        .map(id => criteria.value.find(criterion => criterion.id === id))
        .filter(Boolean)
        .map((criterion, index) => ({
          ...criterion,
          display_order: index,
        }))

      let reorderedIndex = 0
      for (const criterion of criteria.value) {
        if ((criterion.category || '') === category && reorderedIdSet.has(criterion.id)) {
          nextCriteria.push(reorderedCriteria[reorderedIndex++])
          continue
        }

        nextCriteria.push(criterion)
      }

      criteria.value = nextCriteria
    }

    // Recalculer l'ordre de tous les critères
    const reorderPayload: Array<{ id: number, display_order: number }> = []
    let order = 0

    for (const [, group] of Object.entries(groupedCriteria.value)) {
      for (const criterion of group) {
        reorderPayload.push({ id: criterion.id, display_order: order++ })
      }
    }

    try {
      await evaluationCriteriaApi.reorder({ criteria: reorderPayload })
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de réorganiser'))
      await loadCriteria() // Recharger en cas d'erreur
    }
  }

  onMounted(async () => {
    const formTypeFromQuery = String(route.query.form_type || '').trim() as EvaluationFormType
    if (formTypeOptions.some(option => option.value === formTypeFromQuery)) {
      filters.value.formType = formTypeFromQuery
      defaultsFormType.value = formTypeFromQuery
      form.form_type = formTypeFromQuery
    }

    await Promise.all([loadCriteria(), loadCategories()])
  })
</script>

<style scoped>
.hero {
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.18), transparent 62%),
    radial-gradient(900px 360px at 100% 0%, rgba(5, 150, 105, 0.17), transparent 60%),
    linear-gradient(135deg, rgba(17, 24, 39, 0.02), rgba(255, 255, 255, 0.8));
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.hero-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr);
  gap: 24px;
}

.hero-badges {
  display: grid;
  gap: 12px;
}

.hero-badge {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.84);
  border-radius: 12px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.filters-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.criteria-surface {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(1200px 240px at 10% -20%, rgba(10, 132, 255, 0.12), transparent 65%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(247, 250, 252, 0.98));
}

.criteria-list {
  min-height: 50px;
}

.criteria-item {
  transition: all 0.2s ease;
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(249, 250, 251, 0.96));
}

.criteria-item:hover {
  border-color: rgb(var(--v-theme-primary));
  transform: translateY(-1px);
  box-shadow: 0 10px 22px rgba(15, 23, 42, 0.08);
}

.ghost {
  opacity: 0.5;
  background: rgb(var(--v-theme-primary-lighten-4));
}

.cursor-grab {
  cursor: grab;
}

.cursor-grab:active {
  cursor: grabbing;
}

.criteria-dialog-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(900px 280px at 0% 0%, rgba(10, 132, 255, 0.12), transparent 65%),
    radial-gradient(800px 260px at 100% 0%, rgba(5, 150, 105, 0.1), transparent 60%),
    rgba(255, 255, 255, 0.98);
}

.criteria-dialog-header {
  padding: 16px 20px;
}

.criteria-block {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.92), rgba(248, 250, 252, 0.9));
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }
}
</style>
