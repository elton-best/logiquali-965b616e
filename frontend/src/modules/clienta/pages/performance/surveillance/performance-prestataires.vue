<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-chart-line" title="Performance prestataires">
        <template #subtitle>
          Évaluation continue des performances fournisseurs/prestataires.
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            color="secondary"
            prepend-icon="mdi-tune-vertical"
            variant="outlined"
            @click="goToCriteria"
          >
            Définir critères
          </v-btn>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouvelle évaluation
          </v-btn>
        </template>
      </PageHeader>

      <v-card class="mt-6" rounded="xl">
        <v-card-text>
          <v-data-table :headers="headers" :items="items" items-per-page="10" :loading="loading">
            <template #[`item.total_score`]="{ item }">
              <v-chip color="primary" size="small" variant="tonal">
                {{ formatScore(item) }}/{{ maxTotalScore }}
              </v-chip>
            </template>
            <template #[`item.satisfaction_level`]="{ item }">
              <v-chip :color="service.getSatisfactionColor(item.satisfaction_level)" size="small" variant="tonal">
                {{ service.getSatisfactionLabel(item.satisfaction_level) }}
              </v-chip>
            </template>
            <template #[`item.actions`]="{ item }">
              <v-btn
                color="error"
                icon="mdi-delete"
                size="small"
                variant="text"
                @click="removeItem(item)"
              />
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showDialog" max-width="860">
        <v-card rounded="xl">
          <v-card-title class="pa-6">Créer une évaluation prestataire</v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.respondent_name" label="Prestataire" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.respondent_email" label="Email" type="email" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.year" label="Année" type="number" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.period" label="Période" placeholder="Ex: T1" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-alert
                  v-if="usesFallbackCriteria"
                  border="start"
                  color="warning"
                  density="comfortable"
                  icon="mdi-alert-outline"
                  variant="tonal"
                >
                  Les critères paramétrés n'ont pas pu être chargés. Les critères par défaut sont utilisés.
                </v-alert>
              </v-col>
              <v-col v-for="criterion in criteria" :key="criterion.key" cols="12" md="6">
                <v-select
                  v-model.number="form.responses[criterion.key]"
                  item-title="title"
                  item-value="value"
                  :items="criterion.options"
                  :label="buildCriterionLabel(criterion)"
                  :loading="loadingCriteria"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.recommendations" label="Commentaires" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="saveItem">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { type DynamicCriterion, useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { useToast } from '@/modules/shared/composables/useToast'
  import satisfactionSurveyService from '@/services/satisfactionSurveyService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()
  const service = satisfactionSurveyService

  const defaultSupplierCriteria: DynamicCriterion[] = [
    {
      key: 'qualite',
      label: 'Qualité',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'delai',
      label: 'Délai',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
    {
      key: 'communication',
      label: 'Communication',
      description: null,
      scaleMin: 1,
      scaleMax: 3,
      weight: 1,
      required: false,
      options: [
        { value: 1, title: '1 - Insatisfait' },
        { value: 2, title: '2 - Moyennement satisfait' },
        { value: 3, title: '3 - Satisfait' },
      ],
    },
  ]

  const { criteria, loadingCriteria, usesFallbackCriteria } = useEvaluationCriteria(
    'performance_fournisseur',
    defaultSupplierCriteria,
  )

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const items = ref<any[]>([])

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Prestataire', key: 'respondent_name' },
    { title: 'Année', key: 'year' },
    { title: 'Période', key: 'period' },
    { title: 'Score total', key: 'total_score' },
    { title: 'Niveau', key: 'satisfaction_level' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const form = reactive({
    respondent_name: '',
    respondent_email: '',
    year: new Date().getFullYear(),
    period: '',
    responses: {} as Record<string, number>,
    recommendations: '',
  })

  const maxTotalScore = computed(() => {
    return criteria.value.reduce((sum, criterion) => sum + Number(criterion.scaleMax || 3), 0)
  })

  function getCurrentSiteId (): number | null {
    const siteId = Number(authStore.currentSiteId)
    return Number.isFinite(siteId) && siteId > 0 ? siteId : null
  }

  function getDefaultScore (criterion: DynamicCriterion): number {
    return criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin
  }

  function normalizeResponses (): void {
    const nextResponses: Record<string, number> = { ...form.responses }
    let hasChanged = false

    for (const criterion of criteria.value) {
      const current = Number(nextResponses[criterion.key])
      if (!Number.isFinite(current) || current < criterion.scaleMin || current > criterion.scaleMax) {
        nextResponses[criterion.key] = getDefaultScore(criterion)
        hasChanged = true
      }
    }

    if (hasChanged || Object.keys(form.responses || {}).length === 0) {
      form.responses = nextResponses
    }
  }

  function buildCriterionLabel (criterion: DynamicCriterion): string {
    return `${criterion.label} (${criterion.scaleMin}-${criterion.scaleMax})`
  }

  function formatScore (item: any): number {
    if (typeof item.total_score === 'number') return item.total_score
    const values = Object.values(item.responses || {}) as Array<number | string | null | undefined>
    return values.reduce<number>((sum, value) => sum + (Number(value) || 0), 0)
  }

  function resetForm () {
    form.respondent_name = ''
    form.respondent_email = ''
    form.year = new Date().getFullYear()
    form.period = ''
    form.responses = {}
    normalizeResponses()
    form.recommendations = ''
  }

  function openCreateDialog () {
    resetForm()
    showDialog.value = true
  }

  async function loadItems () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      items.value = []
      return
    }

    loading.value = true
    try {
      const { data } = await service.getSurveys({ site_id: siteId, type: 'supplier', per_page: 200 })
      items.value = data.filter((row: any) => row.type === 'supplier' && Number(row.site_id) === siteId)
    } catch (error) {
      console.error(error)
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function saveItem () {
    const siteId = getCurrentSiteId()
    if (!siteId || !form.respondent_name) {
      toast.error('Le site et le nom du prestataire sont requis.')
      return
    }

    saving.value = true
    try {
      await service.createSurvey({
        site_id: siteId,
        type: 'supplier',
        year: Number(form.year) || new Date().getFullYear(),
        period: form.period || undefined,
        respondent_name: form.respondent_name,
        respondent_email: form.respondent_email || undefined,
        responses: { ...form.responses },
        recommendations: form.recommendations || undefined,
      })

      showDialog.value = false
      await loadItems()
      toast.success('Évaluation prestataire créée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Erreur lors de la création.'))
    } finally {
      saving.value = false
    }
  }

  async function removeItem (item: any) {
    if (!confirm('Supprimer cette évaluation ?')) return

    const siteId = getCurrentSiteId()
    if (!siteId || Number(item?.site_id) !== siteId) {
      toast.error('Action non autorisée pour ce site.')
      return
    }

    try {
      const id = Number(item.id)
      await service.deleteSurvey(Number(id))
      await loadItems()
      toast.success('Évaluation supprimée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Suppression impossible.'))
    }
  }

  function goToCriteria () {
    router.push('/company/performance/criteria?form_type=performance_fournisseur')
  }

  watch(criteria, normalizeResponses, { immediate: true })
  onMounted(loadItems)
  watch(() => authStore.currentSiteId, loadItems)
</script>
