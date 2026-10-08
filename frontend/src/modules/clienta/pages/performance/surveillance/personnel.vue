<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6" fluid>
      <PageHeader icon="mdi-account-heart-outline" title="Satisfaction personnel">
        <template #subtitle>
          M13-D3 - Fiches de satisfaction des collaborateurs.
        </template>
        <template #actions>
          <v-btn
            class="mr-2"
            color="info"
            prepend-icon="mdi-link-variant-plus"
            variant="outlined"
            @click="openLinkDialog()"
          >
            Générer un lien
          </v-btn>
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

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Baromètre interne</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Satisfaction des collaborateurs
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Mesurez l’engagement, détectez les signaux faibles et pilotez les actions RH.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Fiches</span>
                <strong>{{ filteredItems.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>Score moyen</span>
                <strong>{{ averageScore }}/{{ maxTotalScore }}</strong>
              </div>
              <div class="hero-badge">
                <span>Dernière période</span>
                <strong>{{ latestPeriodLabel }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="filters-card" rounded="xl" variant="tonal">
        <v-card-text>
          <v-row dense>
            <v-col cols="12" md="6">
              <v-text-field
                v-model="filters.search"
                clearable
                label="Rechercher (collaborateur, email, référence)"
                prepend-inner-icon="mdi-magnify"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model.number="filters.year"
                clearable
                label="Année"
                type="number"
                variant="outlined"
              />
            </v-col>
            <v-col cols="12" md="3">
              <v-text-field
                v-model="filters.period"
                clearable
                label="Période"
                placeholder="Ex: T1"
                variant="outlined"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <v-card class="mt-4 data-card" rounded="xl">
        <v-card-text>
          <v-data-table :headers="headers" :items="filteredItems" items-per-page="10" :loading="loading">
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
              <v-tooltip text="Voir le détail">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-eye"
                    size="small"
                    variant="text"
                    @click="openDetails(item.id)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip text="Générer et copier le lien d'évaluation">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    icon="mdi-link-variant"
                    size="small"
                    variant="text"
                    @click="generateEvaluationLink(item)"
                  />
                </template>
              </v-tooltip>
              <v-tooltip text="Supprimer la fiche">
                <template #activator="{ props }">
                  <v-btn
                    v-bind="props"
                    color="error"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="removeSurvey(item.id)"
                  />
                </template>
              </v-tooltip>
            </template>
          </v-data-table>
        </v-card-text>
      </v-card>

      <v-dialog v-model="showDialog" max-width="900">
        <v-card class="dialog-card" rounded="xl">
          <v-card-title class="dialog-header d-flex align-center justify-space-between pa-6">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" size="40">
                <v-icon color="white">mdi-account-heart-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">Nouvelle fiche satisfaction personnel</div>
                <div class="text-caption text-medium-emphasis">Mesurer l’engagement et le climat interne</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="showDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="px-6 pt-6">
            <EmployeeSatisfactionForm v-model="form" />
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showDialog = false">Annuler</v-btn>
            <v-btn color="primary" :loading="saving" @click="createSurvey">Enregistrer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="showLinkDialog" max-width="640">
        <v-card class="dialog-card" rounded="xl">
          <v-card-title class="dialog-header d-flex align-center justify-space-between pa-6">
            <div class="d-flex align-center ga-3">
              <v-avatar color="info" size="40">
                <v-icon color="white">mdi-link-variant-plus</v-icon>
              </v-avatar>
              <div>
                <div class="text-subtitle-1 font-weight-bold">Générer un lien d'évaluation personnel</div>
                <div class="text-caption text-medium-emphasis">Le destinataire pourra remplir le formulaire via ce lien</div>
              </div>
            </div>
            <v-btn icon="mdi-close" variant="text" @click="showLinkDialog = false" />
          </v-card-title>
          <v-divider />
          <v-card-text class="px-6 pt-6">
            <v-row>
              <v-col cols="12">
                <v-select
                  v-model.number="linkForm.site_id"
                  item-title="title"
                  item-value="value"
                  :items="siteOptions"
                  label="Site concerné *"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="linkForm.recipient_name" label="Nom du destinataire" variant="outlined" />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field v-model="linkForm.recipient_email" label="Email du destinataire" type="email" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-text-field v-model="linkForm.title" label="Objet" variant="outlined" />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="linkForm.custom_message" label="Message" rows="3" variant="outlined" />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="px-6 pb-6">
            <v-spacer />
            <v-btn variant="text" @click="showLinkDialog = false">Annuler</v-btn>
            <v-btn color="info" :loading="generatingLink" @click="generateFormLink()">
              Générer et copier le lien
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import evaluationCriteriaApi from '@/api/services/evaluationCriteria'
  import evaluationRequestsApi from '@/api/services/evaluationRequests'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import EmployeeSatisfactionForm, {
    type EmployeeSatisfactionFormModel,
  } from '@/modules/clienta/components/performance/EmployeeSatisfactionForm.vue'
  import { useEvaluationCriteria } from '@/modules/clienta/composables/useEvaluationCriteria'
  import { defaultEmployeeSatisfactionCriteria } from '@/modules/clienta/constants/employeeSatisfactionCriteria'
  import { useToast } from '@/modules/shared/composables/useToast'
  import satisfactionSurveyService from '@/services/satisfactionSurveyService'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()
  const service = satisfactionSurveyService
  const { criteria, reloadCriteria } = useEvaluationCriteria('satisfaction_personnel', defaultEmployeeSatisfactionCriteria)

  const loading = ref(false)
  const saving = ref(false)
  const showDialog = ref(false)
  const showLinkDialog = ref(false)
  const generatingLink = ref(false)
  const items = ref<any[]>([])
  const filters = ref({
    search: '',
    year: null as number | null,
    period: '',
  })

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Collaborateur', key: 'respondent_name' },
    { title: 'Année', key: 'year' },
    { title: 'Période', key: 'period' },
    { title: 'Score total', key: 'total_score' },
    { title: 'Niveau', key: 'satisfaction_level' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const form = reactive<EmployeeSatisfactionFormModel>({
    respondent_name: '',
    respondent_email: '',
    year: new Date().getFullYear(),
    period: '',
    responses: {
      ambiance_travail: 3,
      communication_interne: 3,
      conditions_travail: 3,
      reconnaissance: 3,
      support_managerial: 3,
    } as Record<string, number>,
    recommendations: '',
  })
  const linkForm = reactive({
    site_id: null as number | null,
    recipient_name: '',
    recipient_email: '',
    title: 'Évaluation personnel',
    custom_message: 'Merci de compléter cette évaluation.',
    related_entity_id: null as number | null,
  })
  const siteOptions = computed(() =>
    (authStore.availableSites || []).map(site => ({
      title: String(site.name || `Site #${site.id}`),
      value: Number(site.id),
    })),
  )
  const maxTotalScore = computed(() => {
    return criteria.value.reduce((sum, criterion) => sum + Number(criterion.scaleMax || 5), 0)
  })

  function getCurrentSiteId (): number | null {
    const stored = Number(localStorage.getItem('current_site_id'))
    const resolved = authStore.currentSiteId ?? (Number.isFinite(stored) ? stored : null)
    return Number.isFinite(Number(resolved)) && Number(resolved) > 0 ? Number(resolved) : null
  }

  function formatScore (item: any): number {
    if (typeof item.total_score === 'number') return item.total_score
    const values = Object.values(item.responses || {}) as Array<number | string | null | undefined>
    return values.reduce<number>((sum, value) => sum + (Number(value) || 0), 0)
  }

  const filteredItems = computed(() => {
    const search = filters.value.search.trim().toLowerCase()
    return items.value.filter(item => {
      const haystack = [
        item.respondent_name,
        item.respondent_email,
        item.ref,
        item.period,
      ].join(' ').toLowerCase()

      if (search && !haystack.includes(search)) return false
      if (filters.value.year && Number(item.year) !== Number(filters.value.year)) return false
      if (filters.value.period && String(item.period || '').toLowerCase() !== filters.value.period.trim().toLowerCase()) {
        return false
      }
      return true
    })
  })

  const averageScore = computed(() => {
    if (filteredItems.value.length === 0) return 0
    const total = filteredItems.value.reduce((sum, item) => sum + formatScore(item), 0)
    return Math.round((total / filteredItems.value.length) * 10) / 10
  })

  const latestPeriodLabel = computed(() => {
    if (filteredItems.value.length === 0) return '—'
    const sorted = filteredItems.value
      .slice()
      .toSorted((a, b) => Number(b.year || 0) - Number(a.year || 0))
    const top = sorted[0]
    const year = top?.year ? String(top.year) : '—'
    const period = top?.period ? String(top.period) : ''
    return period ? `${period} ${year}` : year
  })

  function resetForm () {
    form.respondent_name = ''
    form.respondent_email = ''
    form.year = new Date().getFullYear()
    form.period = ''
    form.responses = {
      ambiance_travail: 3,
      communication_interne: 3,
      conditions_travail: 3,
      reconnaissance: 3,
      support_managerial: 3,
    }
    form.recommendations = ''
  }

  function openCreateDialog () {
    resetForm()
    showDialog.value = true
  }

  async function loadSurveys () {
    const siteId = getCurrentSiteId()
    if (!siteId) {
      items.value = []
      return
    }

    loading.value = true
    try {
      const { data } = await service.getSurveys({ site_id: siteId, type: 'employee', per_page: 200 })
      items.value = data.filter((row: any) => row.type === 'employee' && Number(row.site_id) === siteId)
    } catch (error) {
      console.error(error)
      items.value = []
    } finally {
      loading.value = false
    }
  }

  async function createSurvey () {
    const siteId = getCurrentSiteId()
    if (!siteId || !form.respondent_name) {
      toast.error('Le site et le nom du collaborateur sont requis.')
      return
    }

    saving.value = true
    try {
      await service.createSurvey({
        site_id: siteId,
        type: 'employee',
        year: Number(form.year) || new Date().getFullYear(),
        period: form.period || undefined,
        respondent_name: form.respondent_name,
        respondent_email: form.respondent_email || undefined,
        responses: { ...form.responses },
        recommendations: form.recommendations || undefined,
      })
      showDialog.value = false
      await loadSurveys()
      toast.success('Fiche satisfaction personnel créée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Erreur lors de la création.'))
    } finally {
      saving.value = false
    }
  }

  async function removeSurvey (id: number) {
    if (!confirm('Supprimer cette fiche ?')) return
    try {
      await service.deleteSurvey(Number(id))
      await loadSurveys()
      toast.success('Fiche supprimée.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Suppression impossible.'))
    }
  }

  function openDetails (id: number) {
    router.push(`/company/performance/surveillance/personnel/${id}`)
  }

  function goToCriteria () {
    router.push('/company/performance/criteria?form_type=satisfaction_personnel')
  }

  async function generateEvaluationLink (item: any) {
    openLinkDialog(item)
  }

  async function copyToClipboard (text: string): Promise<void> {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(text)
      return
    }

    const textArea = document.createElement('textarea')
    textArea.value = text
    textArea.style.position = 'fixed'
    textArea.style.opacity = '0'
    document.body.append(textArea)
    textArea.select()
    document.execCommand('copy')
    textArea.remove()
  }

  function openLinkDialog (item?: any): void {
    linkForm.site_id = getCurrentSiteId()
    linkForm.recipient_name = item?.respondent_name || ''
    linkForm.recipient_email = item?.respondent_email || ''
    linkForm.title = `Évaluation personnel${item?.ref ? ` - ${item.ref}` : ''}`
    linkForm.custom_message = 'Merci de compléter cette évaluation.'
    linkForm.related_entity_id = Number.isFinite(Number(item?.id)) ? Number(item.id) : null
    showLinkDialog.value = true
  }

  async function generateFormLink (): Promise<void> {
    if (!linkForm.site_id) {
      toast.error('Sélectionnez le site concerné.')
      return
    }

    if (!linkForm.recipient_email) {
      toast.error('Email du destinataire requis.')
      return
    }

    const criteriaIds = await resolveCriteriaIdsForLink()

    if (criteriaIds.length === 0) {
      toast.error('Aucun critère actif disponible. Configurez les critères dans "Définir critères".')
      return
    }

    generatingLink.value = true
    try {
      const response = await evaluationRequestsApi.create({
        form_type: 'satisfaction_personnel',
        site_id: Number(linkForm.site_id),
        title: linkForm.title || 'Évaluation personnel',
        recipient_email: String(linkForm.recipient_email),
        recipient_name: linkForm.recipient_name || undefined,
        related_entity_id: linkForm.related_entity_id ?? undefined,
        related_entity_type: linkForm.related_entity_id ? 'satisfaction_survey' : undefined,
        criteria_ids: criteriaIds,
        custom_message: linkForm.custom_message || undefined,
      })

      const absoluteUrl = buildFrontendEvaluationUrl(response.data?.token, response.data?.public_url)
      await copyToClipboard(absoluteUrl)
      showLinkDialog.value = false
      toast.success('Lien généré et copié dans le presse-papiers.')
    } catch (error: any) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Impossible de générer le lien du formulaire.'))
    } finally {
      generatingLink.value = false
    }
  }

  async function resolveCriteriaIdsForLink (): Promise<number[]> {
    let ids = criteria.value
      .map(criterion => Number(criterion.id))
      .filter(value => Number.isFinite(value) && value > 0)

    if (ids.length > 0) {
      return ids
    }

    try {
      await evaluationCriteriaApi.initializeDefaults('satisfaction_personnel')
    } catch (error: any) {
      if (error?.response?.status !== 409) {
        console.warn('[Personnel Satisfaction] initializeDefaults failed', error)
      }
    }

    await reloadCriteria()
    ids = criteria.value
      .map(criterion => Number(criterion.id))
      .filter(value => Number.isFinite(value) && value > 0)

    return ids
  }

  function buildFrontendEvaluationUrl (token?: string, fallbackUrl?: string): string {
    const tokenValue = String(token || '').trim()
    if (tokenValue) {
      return `${window.location.origin}/evaluation/${tokenValue}`
    }

    const fallback = String(fallbackUrl || '').trim()
    if (fallback) {
      const parsed = new URL(fallback, window.location.origin)
      const extractedToken = parsed.pathname.split('/').findLast(Boolean)
      if (extractedToken) {
        return `${window.location.origin}/evaluation/${extractedToken}`
      }
    }

    return `${window.location.origin}/evaluation`
  }

  onMounted(loadSurveys)
  watch(() => authStore.currentSiteId, loadSurveys)
</script>

<style scoped>
  .hero {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  }

  .hero-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    align-items: center;
    justify-content: space-between;
  }

  .hero-kicker {
    color: rgba(15, 23, 42, 0.6);
  }

  .hero-badges {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    min-width: min(420px, 100%);
  }

  .hero-badge {
    padding: 12px 16px;
    border-radius: 16px;
    border: 1px solid rgba(15, 23, 42, 0.1);
    background: rgba(255, 255, 255, 0.8);
    display: grid;
    gap: 4px;
  }

  .hero-badge span {
    font-size: 12px;
    color: rgba(15, 23, 42, 0.6);
  }

  .hero-badge strong {
    font-size: 18px;
    color: #0f172a;
  }

  .filters-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .data-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
  }

  .dialog-card {
    border: 1px solid rgba(15, 23, 42, 0.08);
    overflow: hidden;
  }

  .dialog-header {
    background: linear-gradient(180deg, rgba(248, 250, 252, 0.9), rgba(255, 255, 255, 0.98));
  }
</style>
