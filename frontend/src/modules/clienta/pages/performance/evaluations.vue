<template>
  <ClientALayout current-page="iso-performance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-chart-line" title="Évaluations de performance">
        <template #subtitle>
          Suivi des évaluations du personnel par site.
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

      <!-- Cartes statistiques KPI synthétiques (REQ-9.1-01) -->
      <v-row class="mt-4 mb-2" dense>
        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1" color="primary" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between pa-4">
              <div>
                <div class="text-caption text-medium-emphasis font-weight-bold">Total Évaluations</div>
                <div class="text-h4 font-weight-bold text-primary">{{ evaluations.length }}</div>
              </div>
              <v-avatar color="primary" size="48" variant="flat">
                <v-icon color="white">mdi-clipboard-list-outline</v-icon>
              </v-avatar>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1" color="success" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between pa-4">
              <div>
                <div class="text-caption text-medium-emphasis font-weight-bold">Score Moyen Global</div>
                <div class="text-h4 font-weight-bold text-success">{{ averageScoreDisplay }}</div>
              </div>
              <v-avatar color="success" size="48" variant="flat">
                <v-icon color="white">mdi-star-check-outline</v-icon>
              </v-avatar>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1" color="info" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between pa-4">
              <div>
                <div class="text-caption text-medium-emphasis font-weight-bold">Année en Cours</div>
                <div class="text-h4 font-weight-bold text-info">{{ currentYearEvaluationsCount }}</div>
              </div>
              <v-avatar color="info" size="48" variant="flat">
                <v-icon color="white">mdi-calendar-check</v-icon>
              </v-avatar>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" sm="6" md="3">
          <v-card class="elevation-1" color="purple" rounded="xl" variant="tonal">
            <v-card-text class="d-flex align-center justify-space-between pa-4">
              <div>
                <div class="text-caption text-medium-emphasis font-weight-bold">Collaborateurs Évalués</div>
                <div class="text-h4 font-weight-bold text-purple">{{ evaluatedUsersCount }}</div>
              </div>
              <v-avatar color="purple" size="48" variant="flat">
                <v-icon color="white">mdi-account-group-outline</v-icon>
              </v-avatar>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="mt-4 data-card" rounded="xl">
        <v-card-text class="pb-0">
          <v-row dense>
            <v-col cols="12" md="4">
              <v-text-field
                v-model="filters.search"
                clearable
                hide-details
                label="Rechercher (réf, collaborateur, évaluateur)"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.year"
                clearable
                hide-details
                :items="yearFilterItems"
                label="Année"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-select
                v-model="filters.period"
                clearable
                hide-details
                :items="periodFilterItems"
                label="Période"
                variant="outlined"
              />
            </v-col>
            <v-col class="d-flex align-center justify-end" cols="12" md="2">
              <v-btn
                :disabled="!hasActiveFilters"
                prepend-icon="mdi-filter-off"
                variant="outlined"
                @click="resetFilters"
              >
                Réinitialiser
              </v-btn>
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-text>
          <v-data-table
            :headers="headers"
            :items="filteredEvaluations"
            items-per-page="10"
            :loading="loading"
          >
            <template #[`item.user`]="{ item }">
              {{ item.user?.name || '-' }}
            </template>
            <template #[`item.evaluator`]="{ item }">
              {{ item.evaluator?.name || '-' }}
            </template>
            <template #[`item.total_score`]="{ item }">
              <v-chip color="primary" size="small" variant="tonal">
                {{ item.total_score ?? computeTotal(item.scores) }}
              </v-chip>
            </template>
            <template #[`item.actions`]="{ item }">
              <v-tooltip location="top" text="Modifier">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-pencil"
                    size="small"
                    variant="text"
                    @click="openEditDialog(item)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Exporter PDF">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-file-pdf-box"
                    size="small"
                    variant="text"
                    @click="exportPdf(item)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip location="top" text="Vérifier document">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="warning"
                    :disabled="submittingForVerification"
                    icon="mdi-shield-check"
                    size="small"
                    variant="text"
                    @click="submitExportForVerification(item.id)"
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
                    @click="removeEvaluation(item)"
                  />
                </template>
              </v-tooltip>
            </template>
            <template #no-data>
              <div class="text-center py-8 text-medium-emphasis">{{ emptyStateMessage }}</div>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showDialog" max-width="920" persistent>
        <v-card class="modern-modal" rounded="xl">
          <v-card-title class="modal-title">
            <div class="modal-title-inner d-flex align-center justify-space-between">
              <div>
                <div class="text-overline mb-1">Performance personnel</div>
                <div class="text-h6 font-weight-bold">{{ editingId ? 'Modifier' : 'Créer' }} une évaluation</div>
              </div>
              <v-chip color="primary" size="small" variant="flat">
                Critères dédiés
              </v-chip>
            </div>
          </v-card-title>
          <v-card-text class="px-6">
            <v-row>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.user_id"
                  item-title="name"
                  item-value="id"
                  :items="users"
                  label="Collaborateur évalué"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="form.evaluator_id"
                  item-title="name"
                  item-value="id"
                  :items="users"
                  label="Évaluateur"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="form.period" label="Période" placeholder="Ex: S1" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model.number="form.year" label="Année" type="number" variant="outlined" />
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
                  v-model.number="form.scores[criterion.key]"
                  :hint="getCriterionMeaning(criterion.key, form.scores[criterion.key])"
                  item-title="title"
                  item-value="value"
                  :items="criterion.options"
                  :label="criterion.label"
                  :loading="loadingCriteria"
                  persistent-hint
                  variant="outlined"
                />
              </v-col>

              <v-col cols="12">
                <v-textarea v-model="form.comments" label="Commentaires" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="saveEvaluation">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <!-- Prévisualisation avant export (RT-01) -->
      <PreviewExportModal
        v-model="previewExportDialog"
        :blob="previewBlob"
        file-type="pdf"
        :filename="previewFilename"
        :loading="loading"
        :metadata="previewMetadata"
        title="Prévisualisation — Évaluation de Performance"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import { type DynamicCriterion, useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()

  const defaultPersonnelCriteria: DynamicCriterion[] = [
    {
      key: 'competence',
      label: 'Compétence',
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
      key: 'qualite_travail',
      label: 'Qualité du travail',
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
      key: 'ponctualite',
      label: 'Ponctualité',
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
      key: 'esprit_equipe',
      label: 'Esprit d\'équipe',
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
    'performance_personnel',
    defaultPersonnelCriteria,
  )

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const editingId = ref<number | null>(null)

  const evaluations = ref<any[]>([])
  const users = ref<any[]>([])
  const exportedDocsByEvaluationId = ref<Record<number, { id: number, code: string }>>({})
  const submittingForVerification = ref(false)

  const previewExportDialog = ref(false)
  const previewBlob = ref<Blob | null>(null)
  const previewFilename = ref('Evaluation_Performance.pdf')
  const previewMetadata = ref<any>(null)

  const averageScoreDisplay = computed(() => {
    if (evaluations.value.length === 0) return '0.0'
    const total = evaluations.value.reduce((sum, item) => sum + (Number(item.total_score) || computeTotal(item.scores)), 0)
    return (total / evaluations.value.length).toFixed(1)
  })

  const currentYearEvaluationsCount = computed(() => {
    const curYear = new Date().getFullYear()
    return evaluations.value.filter(item => Number(item.year) === curYear).length
  })

  const evaluatedUsersCount = computed(() => {
    const set = new Set(evaluations.value.map(item => item.user_id || item.user?.id).filter(Boolean))
    return set.size
  })

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Collaborateur', key: 'user' },
    { title: 'Évaluateur', key: 'evaluator' },
    { title: 'Période', key: 'period' },
    { title: 'Année', key: 'year' },
    { title: 'Score total', key: 'total_score' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const form = reactive({
    user_id: null as number | null,
    evaluator_id: null as number | null,
    period: '',
    year: new Date().getFullYear(),
    scores: {} as Record<string, number>,
    comments: '',
  })

  function getDefaultScore (criterion: DynamicCriterion): number {
    return criterion.options[Math.floor(criterion.options.length / 2)]?.value ?? criterion.scaleMin
  }

  function normalizeScores (): void {
    const nextScores: Record<string, number> = { ...form.scores }
    let hasChanged = false

    for (const criterion of criteria.value) {
      const current = Number(nextScores[criterion.key])
      if (!Number.isFinite(current) || current < criterion.scaleMin || current > criterion.scaleMax) {
        nextScores[criterion.key] = getDefaultScore(criterion)
        hasChanged = true
      }
    }

    if (hasChanged || Object.keys(form.scores || {}).length === 0) {
      form.scores = nextScores
    }
  }

  const filters = ref({
    search: '',
    year: null as number | null,
    period: null as string | null,
  })

  const yearFilterItems = computed(() => {
    const years = new Set(
      evaluations.value
        .map((item: any) => Number(item.year || 0))
        .filter((value: number) => Number.isFinite(value) && value > 0),
    )
    return Array.from(years).toSorted((a, b) => b - a)
  })

  const periodFilterItems = computed(() => {
    const periods = new Set(
      evaluations.value
        .map((item: any) => String(item.period || '').trim())
        .filter(Boolean),
    )
    return Array.from(periods).toSorted((a, b) => a.localeCompare(b))
  })

  const filteredEvaluations = computed(() => {
    const query = filters.value.search.trim().toLowerCase()

    return evaluations.value.filter((item: any) => {
      const matchesSearch
        = !query
          || [
            item.ref,
            item.user?.name,
            item.evaluator?.name,
            item.period,
          ]
            .map(value => String(value || '').toLowerCase())
            .some(value => value.includes(query))

      const matchesYear = !filters.value.year || Number(item.year || 0) === filters.value.year
      const matchesPeriod = !filters.value.period || String(item.period || '').trim() === filters.value.period

      return matchesSearch && matchesYear && matchesPeriod
    })
  })

  const hasActiveFilters = computed(() => {
    return Boolean(filters.value.search.trim() || filters.value.year || filters.value.period)
  })

  const emptyStateMessage = computed(() => {
    if (evaluations.value.length === 0) {
      return 'Aucune évaluation de performance disponible pour ce site.'
    }

    return 'Aucune évaluation ne correspond aux filtres sélectionnés.'
  })

  function getCurrentSiteId (): number | null {
    const siteId = Number(authStore.currentSiteId)
    return Number.isFinite(siteId) && siteId > 0 ? siteId : null
  }

  function computeTotal (scores: Record<string, number> = {}) {
    return Object.values(scores).reduce((sum, v) => sum + (Number(v) || 0), 0)
  }

  function sanitizeThreeScaleScore (value: number): number {
    return Math.max(1, Math.min(3, Math.round(Number(value) || 1)))
  }

  function getCriterionMeaning (criterion: string, score: number): string {
    const criterionConfig = criteria.value.find(item => item.key === criterion)
    if (!criterionConfig) return ''
    const option = criterionConfig.options.find(item => item.value === Number(score))
    return option ? `Interprétation: ${option.title}` : ''
  }

  function transformJsonApiList (rows: any[]) {
    return rows.map((item: any) => ({
      id: Number(item.id),
      ...item.attributes,
      site: item.relationships?.site ? { id: Number(item.relationships.site.id), ...item.relationships.site.attributes } : null,
      user: item.relationships?.user ? { id: Number(item.relationships.user.id), ...item.relationships.user.attributes } : null,
      evaluator: item.relationships?.evaluator ? { id: Number(item.relationships.evaluator.id), ...item.relationships.evaluator.attributes } : null,
    }))
  }

  async function loadUsers () {
    const siteId = getCurrentSiteId()
    if (!siteId) return

    const { data } = await api.get('/users', { params: { site_id: siteId, per_page: 200 } })
    const rows = data?.data || []
    users.value = rows.map((item: any) => ({ id: Number(item.id), ...item.attributes }))
  }

  async function loadEvaluations () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      evaluations.value = []
      return
    }

    loading.value = true
    try {
      const { data } = await api.get('/employee-evaluations', {
        params: { site_id: siteId },
      })
      const allRows = transformJsonApiList(data?.data || [])
      evaluations.value = allRows.filter((row: any) => Number(row.site?.id || row.site_id) === siteId)
    } finally {
      loading.value = false
    }
  }

  function resetForm () {
    form.user_id = null
    form.evaluator_id = null
    form.period = ''
    form.year = new Date().getFullYear()
    form.scores = {}
    normalizeScores()
    form.comments = ''
  }

  function resetFilters () {
    filters.value = {
      search: '',
      year: null,
      period: null,
    }
  }

  function openCreateDialog () {
    editingId.value = null
    resetForm()
    showDialog.value = true
  }

  function openEditDialog (item: any) {
    editingId.value = Number(item.id)
    form.user_id = item.user?.id || null
    form.evaluator_id = item.evaluator?.id || null
    form.period = item.period || ''
    form.year = item.year || new Date().getFullYear()
    const nextScores: Record<string, number> = {}
    for (const criterion of criteria.value) {
      nextScores[criterion.key] = sanitizeThreeScaleScore(item.scores?.[criterion.key])
    }
    form.scores = nextScores
    normalizeScores()
    form.comments = item.comments || ''
    showDialog.value = true
  }

  async function saveEvaluation () {
    const siteId = getCurrentSiteId()
    if (!siteId || !form.user_id || !form.evaluator_id || !form.period) {
      toast.error('Site, collaborateur, évaluateur et période sont requis.')
      return
    }

    saving.value = true
    try {
      const scores: Record<string, number> = {}
      for (const criterion of criteria.value) {
        scores[criterion.key] = sanitizeThreeScaleScore(form.scores[criterion.key])
      }
      const payload = {
        site_id: siteId,
        user_id: form.user_id,
        evaluator_id: form.evaluator_id,
        period: form.period,
        year: form.year,
        scores,
        total_score: computeTotal(scores),
        comments: form.comments || null,
      }

      await (editingId.value ? api.put(`/employee-evaluations/${editingId.value}`, payload) : api.post('/employee-evaluations', payload))

      showDialog.value = false
      await loadEvaluations()
      toast.success('Évaluation enregistrée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Erreur lors de l\'enregistrement.'))
    } finally {
      saving.value = false
    }
  }

  async function removeEvaluation (item: any) {
    if (!confirm('Supprimer cette évaluation ?')) return

    const siteId = getCurrentSiteId()
    if (!siteId || Number(item?.site?.id || item?.site_id) !== siteId) {
      toast.error('Action non autorisée pour ce site.')
      return
    }

    try {
      const id = Number(item.id)
      await api.delete(`/employee-evaluations/${id}`)
      await loadEvaluations()
      toast.success('Évaluation supprimée.')
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Suppression impossible.'))
    }
  }

  async function exportPdf (item: any, preview = true): Promise<boolean> {
    const siteId = getCurrentSiteId()
    if (!siteId || Number(item?.site?.id || item?.site_id) !== siteId) {
      toast.error('Action non autorisée pour ce site.')
      return false
    }

    try {
      const id = Number(item.id)
      const response = await api.get(`/employee-evaluations/${id}/export-pdf`, { responseType: 'blob' })
      const generatedDocumentId = Number(response.headers?.['x-generated-document-id'] || 0)
      if (generatedDocumentId > 0) {
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        const code = String(docResponse.data?.data?.code || docResponse.data?.code || '')
        exportedDocsByEvaluationId.value[id] = { id: generatedDocumentId, code }
      }

      const filename = `Evaluation_${item.user?.name || id}_${item.year || new Date().getFullYear()}.pdf`
      const blob = new Blob([response.data], { type: 'application/pdf' })

      if (preview) {
        previewBlob.value = blob
        previewFilename.value = filename
        previewMetadata.value = {
          version: '1.0',
          summaryItems: [
            { label: 'Collaborateur', value: item.user?.name || '-' },
            { label: 'Évaluateur', value: item.evaluator?.name || '-' },
            { label: 'Période', value: `${item.period || '-'} ${item.year || ''}` },
            { label: 'Score total', value: item.total_score ?? computeTotal(item.scores) },
          ],
        }
        previewExportDialog.value = true
      } else {
        const url = window.URL.createObjectURL(blob)
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', filename)
        document.body.append(link)
        link.click()
        link.remove()
        window.URL.revokeObjectURL(url)
      }
      return generatedDocumentId > 0
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible de générer le PDF.'))
      return false
    }
  }

  async function submitExportForVerification (evaluationId: number) {
    const exported = exportedDocsByEvaluationId.value[Number(evaluationId)]
    if (!exported?.id || !exported?.code) {
      const evaluation = evaluations.value.find(item => Number(item.id) === Number(evaluationId))
      const generated = await exportPdf(evaluation, false)
      if (!generated) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    const documentToSubmit = exportedDocsByEvaluationId.value[Number(evaluationId)]
    if (!documentToSubmit?.id || !documentToSubmit?.code) return
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${documentToSubmit.id}/confirm-code`, {
        needs_verification: true,
        confirmed_code: documentToSubmit.code,
      })
      toast.success('Document envoyé pour vérification.')
      delete exportedDocsByEvaluationId.value[Number(evaluationId)]
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Échec de la soumission pour vérification.'))
    } finally {
      submittingForVerification.value = false
    }
  }

  onMounted(async () => {
    await Promise.all([loadUsers(), loadEvaluations()])
  })

  function goToCriteria () {
    router.push('/company/performance/criteria?form_type=performance_personnel')
  }

  watch(() => authStore.currentSiteId, async () => {
    await Promise.all([loadUsers(), loadEvaluations()])
  })

  watch(criteria, normalizeScores, { immediate: true })
</script>

<style scoped>
  .data-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .modern-modal {
    border: 1px solid rgba(15, 23, 42, 0.1);
    backdrop-filter: blur(10px);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  }

  .modal-title {
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    padding: 0;
    background:
      radial-gradient(120% 180% at 0% -10%, rgba(16, 185, 129, 0.16), transparent 60%),
      radial-gradient(120% 160% at 100% 0%, rgba(59, 130, 246, 0.14), transparent 55%),
      linear-gradient(135deg, rgba(255, 255, 255, 0.96), rgba(248, 250, 252, 0.96));
  }

  .modal-title-inner {
    width: 100%;
    padding: 20px 24px;
  }
</style>
