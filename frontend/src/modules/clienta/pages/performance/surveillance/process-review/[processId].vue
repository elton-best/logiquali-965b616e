<template>
  <ClientALayout current-page="performance-surveillance">
    <v-container class="pa-6 process-review-page" fluid>
      <PageHeader
        icon="mdi-clipboard-text-clock-outline"
        :subtitle="`Processus ${processData?.code || ''} - pilotage par sections`"
        :title="`Revue de : ${processData?.title || '...'}`"
      >
        <template #actions>
          <v-btn prepend-icon="mdi-arrow-left" variant="outlined" @click="router.push('/company/performance/surveillance/process-review')">
            Retour
          </v-btn>
        </template>
      </PageHeader>

      <v-row v-if="!isBootstrapping" class="mt-2" dense>
        <v-col cols="12">
          <v-alert
            v-if="!canUpdateReview"
            border="start"
            color="info"
            icon="mdi-eye-outline"
            variant="tonal"
          >
            Cette revue est ouverte en lecture seule pour votre profil.
          </v-alert>
          <v-alert
            v-else-if="!canEditIdentification"
            border="start"
            color="warning"
            icon="mdi-lock-outline"
            variant="tonal"
          >
            La section Identification est verrouillée pour votre profil.
          </v-alert>
        </v-col>
      </v-row>

      <v-row v-if="isBootstrapping" class="mt-2" dense>
        <v-col cols="12" lg="8">
          <v-skeleton-loader type="article" />
        </v-col>
        <v-col cols="12" lg="4">
          <v-skeleton-loader type="article" />
        </v-col>
      </v-row>

      <v-row v-else class="mt-2" dense>
        <v-col cols="12" lg="8">
          <ReviewIdentificationSection
            v-model="draft.identification"
            :readonly="!canEditIdentification"
            :user-options="userOptions"
          />
        </v-col>
        <v-col cols="12" lg="4">
          <v-card class="h-100" rounded="xl" variant="outlined">
            <v-card-title>État de la revue</v-card-title>
            <v-card-text>
              <v-chip :color="isClosed ? 'success' : 'warning'" size="small" variant="tonal">
                {{ isClosed ? 'Clôturée' : 'En cours' }}
              </v-chip>
              <div class="mt-3 text-body-2">
                Pilote: <strong>{{ processData?.pilot?.name || 'N/A' }}</strong>
              </div>
              <div class="text-body-2">
                Copilote: <strong>{{ processData?.copilot?.name || 'N/A' }}</strong>
              </div>
              <div class="text-body-2 mt-2">
                Dernière sauvegarde: <strong>{{ formatDateTime(draft.updated_at) }}</strong>
              </div>
              <div class="text-body-2">
                Sauvegarde: <strong>{{ isSaving ? 'En cours...' : 'À jour' }}</strong>
              </div>
              <div class="text-body-2 mt-1">
                Mode: <strong>{{ canUpdateReview ? 'Édition' : 'Lecture seule' }}</strong>
              </div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-row v-if="!isBootstrapping" class="mt-2" dense>
        <v-col cols="12">
          <ReviewSectionsPanel
            v-model="draft.sections"
            :linked-data="linkedData"
            :linked-data-error="linkedDataError"
            :linked-data-loading="linkedDataLoading"
            :metrics="metrics"
            :quick-links="reviewQuickLinks"
            :readonly="!canUpdateReview"
            :show-management-section="isManagementFamily"
            @create-linked-action="openLinkedActionDialog"
          />
        </v-col>
      </v-row>

      <!-- Chantier 7: Process Metrics Dashboard -->
      <v-row v-if="!isBootstrapping && processData" class="mt-4" dense>
        <v-col cols="12">
          <ProcessMetricsDashboard
            :process-id="processId"
            :process-name="processData.title"
          />
        </v-col>
      </v-row>

      <v-row class="mt-2" dense>
        <v-col cols="12">
          <v-card rounded="xl" variant="outlined">
            <v-card-text class="d-flex flex-wrap justify-end ga-2">
              <v-btn prepend-icon="mdi-eye-outline" variant="outlined" @click="showReportDialog = true">
                Voir rapport
              </v-btn>
              <v-btn
                color="info"
                prepend-icon="mdi-file-pdf-box"
                variant="outlined"
                @click="exportReport('pdf')"
              >
                Export PDF
              </v-btn>
              <v-btn
                color="info"
                prepend-icon="mdi-file-word-box"
                variant="outlined"
                @click="exportReport('docx')"
              >
                Export DOCX
              </v-btn>
              <v-btn
                color="warning"
                :disabled="submittingForVerification"
                :loading="submittingForVerification"
                prepend-icon="mdi-shield-check"
                variant="outlined"
                @click="submitExportForVerification"
              >
                Vérifier document
              </v-btn>
              <v-btn
                v-if="canUpdateReview && !isClosed"
                color="primary"
                prepend-icon="mdi-content-save-outline"
                variant="tonal"
                @click="persistDraft"
              >
                Enregistrer
              </v-btn>
              <v-btn
                color="success"
                :disabled="isClosed || !canUpdateReview"
                prepend-icon="mdi-check-decagram-outline"
                @click="closeReview"
              >
                Clôturer la revue
              </v-btn>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-dialog v-model="showReportDialog" max-width="980" scrollable>
        <v-card rounded="xl">
          <v-card-title class="d-flex justify-space-between align-center">
            <span>Rapport - {{ processData?.title }}</span>
            <v-btn icon="mdi-close" size="small" variant="text" @click="showReportDialog = false" />
          </v-card-title>
          <v-card-text>
            <v-list density="comfortable">
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Identification</v-list-item-title>
                <v-list-item-subtitle>
                  RQ: {{ draft.identification.rq_name || 'N/A' }} • Période:
                  {{ draft.identification.coverage_start || 'N/A' }} → {{ draft.identification.coverage_end || 'N/A' }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-2" />
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Synthèse PIP</v-list-item-title>
                <v-list-item-subtitle>{{ draft.sections.pip_summary || 'Aucune synthèse' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Risques / Opportunités</v-list-item-title>
                <v-list-item-subtitle>{{ draft.sections.risk_opportunity_summary || 'Aucune synthèse' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Objectifs / Activités / Projets</v-list-item-title>
                <v-list-item-subtitle>{{ draft.sections.objectives_projects_summary || 'Aucune synthèse' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Conformité / NC / Satisfaction</v-list-item-title>
                <v-list-item-subtitle>{{ draft.sections.compliance_nc_satisfaction_summary || 'Aucune synthèse' }}</v-list-item-subtitle>
              </v-list-item>
              <v-list-item v-if="isManagementFamily">
                <v-list-item-title class="font-weight-bold">Leadership / DUERP</v-list-item-title>
                <v-list-item-subtitle>{{ draft.sections.management_duerp_display || 'Aucune synthèse' }}</v-list-item-subtitle>
              </v-list-item>
              <v-divider class="my-2" />
              <v-list-item>
                <v-list-item-title class="font-weight-bold">Traçabilité incidents</v-list-item-title>
                <v-list-item-subtitle>
                  Incidents ouverts: {{ linkedData.summary.incidents_open_count }} •
                  Incidents en retard: {{ linkedData.summary.incidents_overdue_count }}
                </v-list-item-subtitle>
              </v-list-item>
              <v-list-item>
                <v-list-item-subtitle>
                  Actions incidents liées (DUERP/AES inclus en parallèle): {{
                    linkedData.incidents_top.reduce((sum, item) => sum + Number(item.linked_actions_count || 0), 0)
                  }}
                </v-list-item-subtitle>
              </v-list-item>
            </v-list>
          </v-card-text>
        </v-card>
      </v-dialog>

      <v-dialog v-model="showLinkedActionDialog" max-width="700">
        <v-card rounded="xl">
          <v-card-title>Créer une action liée (DUERP/AES/Incident)</v-card-title>
          <v-card-text>
            <v-alert
              class="mb-3"
              data-testid="linked-action-source"
              density="comfortable"
              type="info"
              variant="tonal"
            >
              Source: <strong>{{ linkedActionForm.source_label || 'N/A' }}</strong>
            </v-alert>
            <v-text-field
              v-model="linkedActionForm.title"
              data-testid="linked-action-title"
              label="Titre"
              required
              variant="outlined"
            />
            <v-textarea
              v-model="linkedActionForm.description"
              data-testid="linked-action-description"
              label="Description"
              rows="3"
              variant="outlined"
            />
            <v-row dense>
              <v-col cols="12" md="6">
                <v-select
                  v-model="linkedActionForm.type"
                  data-testid="linked-action-type"
                  :items="actionTypeItems"
                  label="Type d'action"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="linkedActionForm.priority"
                  data-testid="linked-action-priority"
                  :items="actionPriorityItems"
                  label="Priorité"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-select
                  v-model="linkedActionForm.responsible_id"
                  clearable
                  data-testid="linked-action-responsible"
                  :items="userOptions"
                  label="Responsable"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" md="6">
                <v-text-field
                  v-model="linkedActionForm.deadline"
                  data-testid="linked-action-deadline"
                  label="Échéance"
                  type="date"
                  variant="outlined"
                />
              </v-col>
            </v-row>
          </v-card-text>
          <v-card-actions class="justify-end">
            <v-btn variant="text" @click="showLinkedActionDialog = false">Annuler</v-btn>
            <v-btn
              color="primary"
              data-testid="linked-action-submit"
              :disabled="!canSubmitLinkedAction || isCreatingLinkedAction"
              :loading="isCreatingLinkedAction"
              @click="createLinkedAction"
            >
              Créer l'action
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <PreviewExportModal
        v-model="previewExportOpen"
        :filename="previewExportFilename"
        :filesize="previewExportFilesize"
        :mime-type="previewExportMime"
        :preview-url="previewExportUrl"
        title="Prévisualisation du rapport de revue de processus"
        @confirm-download="handleConfirmDownload"
      />
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ProcessMetricsDashboard from '@/modules/clienta/components/ProcessMetricsDashboard.vue'
  import ReviewIdentificationSection from '@/modules/clienta/pages/performance/surveillance/process-review/components/ReviewIdentificationSection.vue'
  import ReviewSectionsPanel from '@/modules/clienta/pages/performance/surveillance/process-review/components/ReviewSectionsPanel.vue'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import { buildManagementDuerpSummary } from '@/modules/clienta/pages/performance/surveillance/process-review/utils/managementSummary'
  import { useToast } from '@/modules/shared/composables/useToast'
  import processService, { type ProcessReviewLinkedData } from '@/services/processService'
  import userService from '@/services/userService'
  import { useAuthStore } from '@/stores/auth'

  const route = useRoute()
  const router = useRouter()
  const authStore = useAuthStore()
  const toast = useToast()

  const showReportDialog = ref(false)
  const processData = ref<any>(null)
  const userOptions = ref<Array<{ title: string, value: number }>>([])
  const isBootstrapping = ref(true)
  const isSaving = ref(false)
  const currentReviewId = ref<number | null>(null)
  const serverCanUpdateReview = ref(false)
  const serverCanEditIdentification = ref(false)
  const managementSummaryAutoApplied = ref(false)
  const linkedDataLoading = ref(false)
  const linkedDataError = ref(false)
  const showLinkedActionDialog = ref(false)
  const isCreatingLinkedAction = ref(false)
  const lastExportedDocumentId = ref<number | null>(null)
  const exportedDocumentCode = ref('')
  const submittingForVerification = ref(false)

  // Preview Export Modal State (RT-01, REQ-9.2-12)
  const previewExportOpen = ref(false)
  const previewExportUrl = ref<string | null>(null)
  const previewExportFilename = ref('')
  const previewExportMime = ref('application/pdf')
  const previewExportFilesize = ref<number | undefined>(undefined)
  const pendingBlob = ref<Blob | null>(null)

  function handleConfirmDownload () {
    if (!pendingBlob.value) return
    downloadBlob(pendingBlob.value, previewExportFilename.value)
    previewExportOpen.value = false
    toast.success('Téléchargement effectué.')
  }
  const linkedData = reactive<ProcessReviewLinkedData>({
    duerp_top: [],
    aes_top: [],
    incidents_top: [],
    summary: {
      duerp_overdue_actions: 0,
      aes_missing_actions: 0,
      incidents_open_count: 0,
      incidents_overdue_count: 0,
      linked_incident_actions_overdue_count: 0,
      alerts: [],
    },
  })
  const linkedActionForm = reactive({
    source_type: 'duerp_danger' as 'duerp_danger' | 'aes_aspect' | 'reclamation',
    source_id: 0,
    source_label: '',
    source_hint: '',
    title: '',
    description: '',
    responsible_id: null as number | null,
    deadline: '',
    type: 'corrective' as 'corrective' | 'preventive' | 'improvement' | 'emergency' | 'curative',
    priority: 'medium' as 'low' | 'medium' | 'high' | 'critical',
  })
  let saveTimer: ReturnType<typeof setTimeout> | null = null

  function defaultDraft () {
    return {
      status: 'planned',
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      closed_at: '',
      identification: {
        rq_name: '',
        include_pilot: true,
        include_copilot: false,
        present_user_ids: [] as number[],
        present_others: '',
        coverage_start: '',
        coverage_end: '',
        started_at: new Date().toLocaleString('fr-FR'),
        ended_at: '',
      },
      sections: {
        pip_summary: '',
        risk_opportunity_summary: '',
        objectives_projects_summary: '',
        compliance_nc_satisfaction_summary: '',
        management_duerp_display: '',
      },
    }
  }

  const draft = reactive(defaultDraft())
  const metrics = reactive({
    pip_total: 0,
    pip_completed: 0,
    risks_count: 0,
    opportunities_count: 0,
    objectives_count: 0,
    objective_rate: 0,
    non_conformities_count: 0,
    satisfaction_rate: 0,
    duerp_versions_count: 0,
    duerp_dangers_count: 0,
    duerp_unacceptable_count: 0,
    aes_total_count: 0,
    aes_significant_count: 0,
  })

  const processId = computed(() => {
    const raw = (route.params as Record<string, string | string[] | undefined>)?.processId
    const value = Array.isArray(raw) ? raw[0] : raw
    return Number(value)
  })
  const isManagementFamily = computed(() => {
    const title = String(processData.value?.title || '').trim().toLowerCase()
    const type = String(processData.value?.type || '').trim().toLowerCase()
    const code = String(processData.value?.code || '').trim().toLowerCase()

    // Règle stricte métier: section Leadership/DUERP réservée au processus "Management".
    if (title === 'management') {
      return true
    }

    if (type === 'management') {
      return true
    }

    return code === 'management'
  })
  const isClosed = computed(() => draft.status === 'completed')
  const canUpdateReview = computed(() => serverCanUpdateReview.value)
  const canEditIdentification = computed(() => serverCanEditIdentification.value)
  const canSubmitLinkedAction = computed(() => {
    return linkedActionForm.source_id > 0
      && linkedActionForm.title.trim().length > 2
      && linkedActionForm.deadline.trim().length > 0
  })
  const actionTypeItems = [
    { title: 'Corrective', value: 'corrective' },
    { title: 'Préventive', value: 'preventive' },
    { title: 'Amélioration', value: 'improvement' },
    { title: 'Urgence', value: 'emergency' },
    { title: 'Curative', value: 'curative' },
  ]
  const actionPriorityItems = [
    { title: 'Faible', value: 'low' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Haute', value: 'high' },
    { title: 'Critique', value: 'critical' },
  ]
  const reviewQuickLinks = computed(() => {
    const siteId = authStore.currentSiteId || Number(localStorage.getItem('current_site_id') || 0)
    const query: Record<string, string> = {
      process_id: String(processId.value),
      source: 'process-review',
    }
    if (siteId > 0) {
      query.site_id = String(siteId)
    }

    return [
      { label: 'PIP', icon: 'mdi-clipboard-check-outline', to: { path: '/company/performance/evaluation-requests', query } },
      { label: 'DUERP', icon: 'mdi-shield-alert-outline', to: { path: '/company/planning/duerp', query } },
      { label: 'AES', icon: 'mdi-leaf', to: { path: '/company/planning/aspects-environmentaux', query } },
      { label: 'NC', icon: 'mdi-alert-circle-outline', to: { path: '/company/nonconformities', query } },
      { label: 'Objectifs', icon: 'mdi-target', to: { path: '/company/planning/objectives', query } },
    ]
  })

  function applyManagementSummaryAssist () {
    if (managementSummaryAutoApplied.value) {
      return
    }

    if (!isManagementFamily.value) {
      return
    }

    const existing = String(draft.sections.management_duerp_display || '').trim()
    if (existing.length > 0) {
      managementSummaryAutoApplied.value = true
      return
    }

    draft.sections.management_duerp_display = buildManagementDuerpSummary(metrics)
    managementSummaryAutoApplied.value = true
  }

  function nextDefaultDeadline () {
    const date = new Date()
    date.setDate(date.getDate() + 30)
    return date.toISOString().slice(0, 10)
  }

  function openLinkedActionDialog (payload: {
    source_type: 'duerp_danger' | 'aes_aspect' | 'reclamation'
    source_id: number
    source_label: string
    source_hint: string
  }) {
    if (!canUpdateReview.value) {
      toast.error('Vous avez un accès en lecture seule à cette revue.')
      return
    }

    linkedActionForm.source_type = payload.source_type
    linkedActionForm.source_id = Number(payload.source_id)
    linkedActionForm.source_label = payload.source_label
    linkedActionForm.source_hint = payload.source_hint
    linkedActionForm.title = payload.source_type === 'duerp_danger'
      ? `Action DUERP - ${payload.source_label}`
      : (payload.source_type === 'aes_aspect'
        ? `Action AES - ${payload.source_label}`
        : `Action Incident - ${payload.source_label}`)
    linkedActionForm.description = payload.source_hint || ''
    linkedActionForm.responsible_id = null
    linkedActionForm.deadline = nextDefaultDeadline()
    linkedActionForm.type = 'corrective'
    linkedActionForm.priority = 'medium'
    showLinkedActionDialog.value = true
  }

  async function createLinkedAction () {
    if (!canSubmitLinkedAction.value) {
      toast.error('Complète les champs requis pour créer l’action.')
      return
    }

    isCreatingLinkedAction.value = true
    try {
      await processService.createCurrentReviewLinkedAction(processId.value, {
        source_type: linkedActionForm.source_type,
        source_id: linkedActionForm.source_id,
        title: linkedActionForm.title.trim(),
        description: linkedActionForm.description.trim() || undefined,
        responsible_id: linkedActionForm.responsible_id,
        deadline: linkedActionForm.deadline,
        type: linkedActionForm.type,
        priority: linkedActionForm.priority,
      })
      toast.success('Action liée créée avec succès.')
      showLinkedActionDialog.value = false
      await fetchLinkedData()
    } catch {
      toast.error('Impossible de créer l’action liée.')
    } finally {
      isCreatingLinkedAction.value = false
    }
  }

  async function persistDraft () {
    if (!Number.isFinite(processId.value) || processId.value <= 0 || isClosed.value || !canUpdateReview.value) return

    isSaving.value = true
    try {
      const payload = {
        sections: { ...draft.sections },
        metrics_snapshot: { ...metrics },
        status: (draft.status === 'planned' ? 'planned' : 'in_progress') as 'planned' | 'in_progress',
      } as {
        sections: Record<string, any>
        metrics_snapshot: Record<string, any>
        status: 'planned' | 'in_progress'
        identification?: Record<string, any>
      }

      if (canEditIdentification.value) {
        payload.identification = { ...draft.identification }
      }

      const response = await processService.saveCurrentReview(processId.value, payload)
      const serverReview = response?.data
      if (serverReview) {
        currentReviewId.value = Number(serverReview.id)
        draft.status = String(serverReview.status || draft.status) as any
        draft.updated_at = String(serverReview.updated_at || new Date().toISOString())
      }
    } catch {
      toast.error('Échec de sauvegarde de la revue processus.')
    } finally {
      isSaving.value = false
    }
  }

  function scheduleSave () {
    if (saveTimer) {
      clearTimeout(saveTimer)
      saveTimer = null
    }

    if (isClosed.value || !canUpdateReview.value) return

    saveTimer = setTimeout(() => {
      persistDraft()
    }, 600)
  }

  function formatDateTime (value?: string) {
    if (!value) return 'N/A'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return 'N/A'
    return date.toLocaleString('fr-FR')
  }

  async function fetchProcess () {
    const response = await processService.getProcess(processId.value)
    processData.value = response?.data || null
    if (!draft.identification.rq_name) {
      draft.identification.rq_name = processData.value?.pilot?.name || authStore.userName
    }
    if (!draft.identification.coverage_start) {
      draft.identification.coverage_start = new Date().toISOString().slice(0, 10)
      draft.identification.coverage_end = new Date().toISOString().slice(0, 10)
    }
  }

  async function fetchCurrentReview () {
    const response = await processService.getCurrentReview(processId.value)
    const review = response?.data
    if (!review) return false

    currentReviewId.value = Number(review.id)
    draft.status = String(review.status || 'planned') as any
    draft.updated_at = String(review.updated_at || new Date().toISOString())

    if (review.identification && typeof review.identification === 'object') {
      draft.identification = { ...draft.identification, ...review.identification }
    }
    if (review.sections && typeof review.sections === 'object') {
      draft.sections = { ...draft.sections, ...review.sections }
      if (String(draft.sections.management_duerp_display || '').trim().length > 0) {
        managementSummaryAutoApplied.value = true
      }
    }
    if (review.metrics_snapshot && typeof review.metrics_snapshot === 'object') {
      Object.assign(metrics, review.metrics_snapshot)
    }

    if (review.computed_metrics && typeof review.computed_metrics === 'object') {
      Object.assign(metrics, review.computed_metrics)
    }

    if (review.capabilities && typeof review.capabilities === 'object') {
      serverCanUpdateReview.value = Boolean(review.capabilities.can_update_review)
      serverCanEditIdentification.value = Boolean(review.capabilities.can_edit_identification)
    } else {
      serverCanUpdateReview.value = false
      serverCanEditIdentification.value = false
    }

    if (review.computed_metrics && typeof review.computed_metrics === 'object') {
      return true
    }

    return false
  }

  async function fetchUsers () {
    const siteId = authStore.currentSiteId || Number(localStorage.getItem('current_site_id') || 0)
    const users = await userService.getBySite(siteId)
    userOptions.value = (users || []).map((user: any) => ({
      title: String(user.name || `${user.first_name || ''} ${user.last_name || ''}`).trim() || `Utilisateur #${user.id}`,
      value: Number(user.id),
    }))
  }

  async function fetchMetrics () {
    try {
      const [riskRes, ncRes, evalRes] = await Promise.all([
        api.get('/risks-opportunities', { params: { process_id: processId.value } }),
        api.get('/non-conformities', { params: { process_id: processId.value } }).catch(() => ({ data: { data: [] } })),
        api.get('/evaluation-responses', { params: { process_id: processId.value } }).catch(() => ({ data: { data: [] } })),
      ])
      const aesStatsRes = await api.get('/aspects-environnementaux/stats', { params: { process_id: processId.value } })
        .catch(() => ({ data: { data: {} } }))

      const risks = Array.isArray(riskRes?.data?.data) ? riskRes.data.data : []
      const objectives = Array.isArray(processData.value?.objectives) ? processData.value.objectives : []
      const ncs = Array.isArray(ncRes?.data?.data) ? ncRes.data.data : []
      const evaluations = Array.isArray(evalRes?.data?.data) ? evalRes.data.data : []

      metrics.risks_count = risks.filter((item: any) => String(item.type) === 'risque').length
      metrics.opportunities_count = risks.filter((item: any) => String(item.type) === 'opportunite').length
      metrics.objectives_count = objectives.length
      metrics.objective_rate = objectives.length > 0
        ? Math.round(objectives.reduce((sum: number, item: any) => sum + Number(item.achievement_percentage || 0), 0) / objectives.length)
        : 0
      metrics.non_conformities_count = ncs.length
      metrics.pip_total = evaluations.length
      metrics.pip_completed = evaluations.filter((item: any) => !!item.submitted_at).length
      metrics.satisfaction_rate = evaluations.length > 0
        ? Math.round(
          evaluations.reduce((sum: number, item: any) => sum + Number(item.overall_score || 0), 0)
            / evaluations.length
          * 20,
        )
        : 0

      const aesStats = aesStatsRes?.data?.data || {}
      metrics.aes_total_count = Number(aesStats.total || aesStats.count || 0)
      metrics.aes_significant_count = Number(aesStats.significatifs || aesStats.significant || aesStats.significant_count || 0)
    } catch {
      // métriques best-effort
    }
  }

  async function fetchLinkedData () {
    linkedDataLoading.value = true
    linkedDataError.value = false
    try {
      const payload = await processService.getCurrentReviewLinkedData(processId.value)
      linkedData.duerp_top = payload.duerp_top
      linkedData.aes_top = payload.aes_top
      linkedData.incidents_top = payload.incidents_top
      linkedData.summary.duerp_overdue_actions = payload.summary.duerp_overdue_actions
      linkedData.summary.aes_missing_actions = payload.summary.aes_missing_actions
      linkedData.summary.incidents_open_count = payload.summary.incidents_open_count
      linkedData.summary.incidents_overdue_count = payload.summary.incidents_overdue_count
      linkedData.summary.linked_incident_actions_overdue_count = payload.summary.linked_incident_actions_overdue_count
      linkedData.summary.alerts = payload.summary.alerts
    } catch {
      linkedDataError.value = true
      linkedData.duerp_top = []
      linkedData.aes_top = []
      linkedData.incidents_top = []
      linkedData.summary.duerp_overdue_actions = 0
      linkedData.summary.aes_missing_actions = 0
      linkedData.summary.incidents_open_count = 0
      linkedData.summary.incidents_overdue_count = 0
      linkedData.summary.linked_incident_actions_overdue_count = 0
      linkedData.summary.alerts = []
    } finally {
      linkedDataLoading.value = false
    }
  }

  async function closeReview () {
    if (!canUpdateReview.value) {
      toast.error('Vous avez un accès en lecture seule à cette revue.')
      return
    }
    const confirmed = window.confirm('Clôturer définitivement cette revue processus ? Cette action est irréversible.')
    if (!confirmed) {
      return
    }

    try {
      const response = await processService.closeCurrentReview(processId.value)
      const review = response?.data
      draft.status = String(review?.status || 'completed') as any
      draft.closed_at = String(review?.ended_at || new Date().toISOString())
      draft.identification.ended_at = new Date().toLocaleString('fr-FR')
      toast.success('Revue processus clôturée.')
    } catch {
      toast.error('Impossible de clôturer la revue.')
    }
  }

  function getFilenameFromDisposition (disposition: string | undefined, fallback: string) {
    const value = String(disposition || '')
    const match = value.match(/filename="?([^"]+)"?/i)
    return match?.[1] || fallback
  }

  function downloadBlob (blob: Blob, fileName: string) {
    const link = document.createElement('a')
    const url = window.URL.createObjectURL(blob)
    link.href = url
    link.download = fileName
    document.body.append(link)
    link.click()
    link.remove()
    window.URL.revokeObjectURL(url)
  }

  async function exportReport (format: 'pdf' | 'docx', download = true): Promise<boolean> {
    try {
      const response = format === 'pdf'
        ? await processService.exportCurrentReviewPdf(processId.value)
        : await processService.exportCurrentReviewDocx(processId.value)

      const fallbackName = `Revue_Processus_${processData.value?.code || processId.value}.${format}`
      const filename = getFilenameFromDisposition(response.headers?.['content-disposition'], fallbackName)
      const generatedDocumentId = Number(response.headers?.['x-generated-document-id'] || 0)
      if (generatedDocumentId > 0) {
        lastExportedDocumentId.value = generatedDocumentId
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        exportedDocumentCode.value = String(docResponse.data?.data?.code || docResponse.data?.code || '')
      }
      if (download) {
        const mime = format === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        const blob = new Blob([response.data], { type: mime })
        pendingBlob.value = blob
        if (previewExportUrl.value) {
          window.URL.revokeObjectURL(previewExportUrl.value)
        }
        previewExportUrl.value = window.URL.createObjectURL(blob)
        previewExportFilename.value = filename
        previewExportMime.value = mime
        previewExportFilesize.value = blob.size
        previewExportOpen.value = true
      }
      return generatedDocumentId > 0
    } catch {
      toast.error(`Export ${format.toUpperCase()} impossible.`)
      return false
    }
  }

  async function submitExportForVerification () {
    if (!lastExportedDocumentId.value || !exportedDocumentCode.value) {
      const generated = await exportReport('pdf', false)
      if (!generated || !lastExportedDocumentId.value || !exportedDocumentCode.value) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${lastExportedDocumentId.value}/confirm-code`, {
        needs_verification: true,
        confirmed_code: exportedDocumentCode.value,
      })
      toast.success('Document envoyé pour vérification.')
      lastExportedDocumentId.value = null
      exportedDocumentCode.value = ''
    } catch {
      toast.error('Échec de la soumission pour vérification.')
    } finally {
      submittingForVerification.value = false
    }
  }

  watch(
    () => draft,
    () => {
      scheduleSave()
    },
    { deep: true },
  )

  onMounted(async () => {
    if (!Number.isFinite(processId.value) || processId.value <= 0) {
      router.push('/company/performance/surveillance/process-review')
      return
    }

    try {
      const hasServerMetrics = (await Promise.all([fetchProcess(), fetchUsers(), fetchCurrentReview()]))[2]
      if (!hasServerMetrics) {
        await fetchMetrics()
      }
      await fetchLinkedData()
      applyManagementSummaryAssist()
      if (canUpdateReview.value) {
        scheduleSave()
      }
    } catch {
      toast.error('Impossible de charger la revue processus.')
    } finally {
      isBootstrapping.value = false
    }
  })

  onBeforeUnmount(() => {
    if (saveTimer) {
      clearTimeout(saveTimer)
      saveTimer = null
    }
  })
</script>

<style scoped>
.process-review-page {
  background:
    radial-gradient(circle at 92% 6%, rgba(14, 165, 233, 0.08), transparent 38%),
    radial-gradient(circle at 10% 24%, rgba(124, 58, 237, 0.08), transparent 38%);
}
</style>
