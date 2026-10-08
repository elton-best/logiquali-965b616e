<template>
  <ClientALayout current-page="management-reviews">
    <v-container class="pa-6 review-form-theme" fluid>
      <PageHeader
        icon="mdi-clipboard-text-clock-outline"
        :title="review?.title || `Revue #${reviewId}`"
      >
        <template #subtitle>
          Revue de direction: entrées, décisions et suivi des actions.
        </template>
        <template #actions>
          <v-btn variant="text" @click="router.push('/company/management-reviews')">
            Retour
          </v-btn>
        </template>
      </PageHeader>

      <v-alert
        v-if="reviewStore.error"
        class="mt-4"
        closable
        type="error"
        variant="tonal"
      >
        {{ reviewStore.error }}
      </v-alert>

      <v-skeleton-loader
        v-if="reviewStore.loading && !review"
        class="mt-6"
        type="card, card, card"
      />

      <template v-else-if="review">
        <v-row dense>
          <v-col cols="12" md="3" sm="6">
            <v-card class="kpi-card" rounded="xl" variant="tonal">
              <v-card-text class="d-flex align-center justify-space-between">
                <div>
                  <div class="kpi-label">Statut</div>
                  <div class="kpi-value">{{ getStatusLabel(review.status) }}</div>
                </div>
                <v-icon color="primary" size="22">mdi-clipboard-text-clock-outline</v-icon>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3" sm="6">
            <v-card class="kpi-card" rounded="xl" variant="tonal">
              <v-card-text class="d-flex align-center justify-space-between">
                <div>
                  <div class="kpi-label">Date planifiée</div>
                  <div class="kpi-value">{{ formatDate(review.planned_date || review.scheduled_date) }}</div>
                </div>
                <v-icon color="info" size="22">mdi-calendar-month-outline</v-icon>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3" sm="6">
            <v-card class="kpi-card" rounded="xl" variant="tonal">
              <v-card-text class="d-flex align-center justify-space-between">
                <div>
                  <div class="kpi-label">Actions de suivi</div>
                  <div class="kpi-value">{{ fromLines(actionItemsText).length }}</div>
                </div>
                <v-icon color="warning" size="22">mdi-format-list-bulleted-square</v-icon>
              </v-card-text>
            </v-card>
          </v-col>
          <v-col cols="12" md="3" sm="6">
            <v-card class="kpi-card" rounded="xl" variant="tonal">
              <v-card-text class="d-flex align-center justify-space-between">
                <div>
                  <div class="kpi-label">Participants</div>
                  <div class="kpi-value">{{ form.participants.length }}</div>
                </div>
                <v-icon color="success" size="22">mdi-account-group-outline</v-icon>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>

        <v-card class="glass-card mt-4" rounded="xl">
          <v-tabs v-model="activeTab" class="review-tabs px-2 pt-2" color="primary">
            <v-tab value="pilotage">Pilotage</v-tab>
            <v-tab value="entrees">Entrées de revue</v-tab>
            <v-tab value="decisions">Décisions et actions</v-tab>
            <v-tab value="synthese">Synthèse SM</v-tab>
          </v-tabs>
          <v-divider />

          <v-window v-model="activeTab">
            <v-window-item value="pilotage">
              <v-card-text>
                <v-row dense>
                  <v-col cols="12" md="4">
                    <v-card class="inner-card h-100" rounded="lg" variant="outlined">
                      <v-card-title class="section-head">Informations générales</v-card-title>
                      <v-card-text class="text-body-2">
                        <div class="summary-grid">
                          <div class="summary-item">
                            <span>Référence</span>
                            <strong>{{ review.ref || '-' }}</strong>
                          </div>
                          <div class="summary-item">
                            <span>Date réalisée</span>
                            <strong>{{ formatDate(review.actual_date) }}</strong>
                          </div>
                          <div class="summary-item">
                            <span>Période</span>
                            <strong>{{ review.year || '-' }} / {{ review.quarter || '-' }}</strong>
                          </div>
                        </div>
                      </v-card-text>
                      <v-divider />
                      <v-card-text class="action-cluster">
                        <v-tooltip location="top" text="Enregistrer les modifications">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="primary"
                              :loading="reviewStore.loading"
                              prepend-icon="mdi-content-save"
                              rounded="lg"
                              @click="saveReview"
                            >
                              Enregistrer
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Générer les données d'entrée de revue">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="info"
                              :loading="reviewStore.loading"
                              prepend-icon="mdi-refresh"
                              rounded="lg"
                              variant="tonal"
                              @click="generateData"
                            >
                              Générer données
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Produire la synthèse ISO 9001 §9.3.2">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="indigo"
                              :loading="reviewStore.loading"
                              prepend-icon="mdi-file-document-refresh-outline"
                              rounded="lg"
                              variant="tonal"
                              @click="generateSmSynthesis"
                            >
                              Créer synthèse du SM
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Envoyer les invitations aux participants">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="success"
                              :loading="reviewStore.loading"
                              prepend-icon="mdi-email-send"
                              rounded="lg"
                              variant="tonal"
                              @click="sendInvitations"
                            >
                              Envoyer invitations
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Prévisualiser avant téléchargement du rapport (RT-01)">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="primary"
                              :loading="reviewStore.loading || exportingMr"
                              prepend-icon="mdi-file-eye-outline"
                              rounded="lg"
                              variant="tonal"
                              @click="lastExportedDocumentId ? handlePreviewDraft() : openMrConfig('preview')"
                            >
                              Prévisualiser & Exporter (PDF)
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Soumettre le document exporté en vérification">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="warning"
                              :disabled="submittingForVerification || mrVerificationSent"
                              :loading="submittingForVerification"
                              prepend-icon="mdi-shield-check"
                              rounded="lg"
                              variant="tonal"
                              @click="lastExportedDocumentId ? openVerifyDialog() : openMrConfig('verify')"
                            >
                              Vérifier document
                            </v-btn>
                          </template>
                        </v-tooltip>
                        <v-tooltip location="top" text="Clôturer la revue et verrouiller le rapport">
                          <template #activator="{ props }">
                            <v-btn
                              v-bind="props"
                              color="warning"
                              :loading="reviewStore.loading"
                              prepend-icon="mdi-check-decagram"
                              rounded="lg"
                              variant="tonal"
                              @click="closeReview"
                            >
                              Clôturer revue
                            </v-btn>
                          </template>
                        </v-tooltip>
                      </v-card-text>
                    </v-card>
                  </v-col>

                  <v-col cols="12" md="8">
                    <v-card class="inner-card" rounded="lg" variant="outlined">
                      <v-card-title class="section-head">Saisie de la revue</v-card-title>
                      <v-card-text>
                        <v-row dense>
                          <v-col cols="12" md="8">
                            <AppInput v-model="form.title" label="Titre" placeholder="Titre de la revue de direction" />
                          </v-col>
                          <v-col cols="12" md="4">
                            <AppSelect
                              v-model="form.status"
                              label="Statut"
                              :options="statusOptions.map(item => ({ label: item.label, value: item.value }))"
                            />
                          </v-col>
                          <v-col cols="12" md="4">
                            <AppDatePickerField v-model="form.planned_date" label="Date planifiée" mode="date" />
                          </v-col>
                          <v-col cols="12" md="4">
                            <AppDatePickerField v-model="form.actual_date" label="Date réalisée" mode="date" />
                          </v-col>
                          <v-col cols="12" md="4">
                            <AppSelect
                              v-model="form.quarter"
                              label="Trimestre"
                              :options="quarterOptions.map(item => ({ label: item, value: item }))"
                            />
                          </v-col>
                          <v-col cols="12" md="6">
                            <AppInput v-model="form.year" label="Année" type="number" />
                          </v-col>
                          <v-col cols="12" md="6">
                            <AppSelect
                              v-model="form.chairman_id"
                              label="Président de séance"
                              :options="users.map(user => ({ label: user.name, value: user.id }))"
                            />
                          </v-col>
                          <v-col cols="12">
                            <label class="field-label">Participants</label>
                            <v-select
                              v-model="form.participants"
                              chips
                              class="field-vuetify"
                              item-title="name"
                              item-value="id"
                              :items="users"
                              multiple
                              placeholder="Sélectionner les participants"
                              variant="outlined"
                            />
                          </v-col>
                        </v-row>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>

            <v-window-item value="entrees">
              <v-card-text>
                <v-row dense>
                  <v-col cols="12" md="6">
                    <AppTextarea v-model="form.previous_actions_status" label="Statut des actions précédentes" :rows="2" />
                    <AppTextarea v-model="form.context_changes" class="mt-2" label="Changements de contexte" :rows="2" />
                    <AppTextarea v-model="form.performance_indicators" class="mt-2" label="Performance et indicateurs" :rows="2" />
                    <AppTextarea v-model="form.customer_satisfaction" class="mt-2" label="Satisfaction client" :rows="2" />
                  </v-col>
                  <v-col cols="12" md="6">
                    <AppTextarea v-model="form.audit_results" label="Résultats d'audit" :rows="2" />
                    <AppTextarea v-model="form.nc_complaints_status" class="mt-2" label="NC / Réclamations" :rows="2" />
                    <AppTextarea v-model="form.resources_adequacy" class="mt-2" label="Adéquation des ressources" :rows="2" />
                    <AppTextarea v-model="form.improvement_opportunities" class="mt-2" label="Opportunités d'amélioration" :rows="2" />
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>

            <v-window-item value="decisions">
              <v-card-text>
                <v-row dense>
                  <v-col cols="12" md="6">
                    <AppTextarea
                      v-model="decisionsText"
                      hint="Une décision par ligne"
                      label="Décisions"
                      :rows="8"
                    />
                  </v-col>
                  <v-col cols="12" md="6">
                    <AppTextarea
                      v-model="actionItemsText"
                      hint="Une action par ligne"
                      label="Actions de suivi"
                      :rows="8"
                    />
                  </v-col>
                </v-row>

                <v-divider class="my-4" />

                <div class="text-subtitle-2 font-weight-bold mb-3 d-flex align-center text-primary">
                  <v-icon class="mr-2" color="primary" size="20">mdi-clipboard-plus-outline</v-icon>
                  Ajouter une décision / action formelle (RT-12 / RT-11)
                </div>
                <StandardActionBlock
                  :available-norms="['ISO 9001:2015', 'ISO 14001:2015', 'ISO 45001:2018']"
                  :collaborators="users.map(u => ({ id: u.id, name: u.name }))"
                  :is-multi-norm="true"
                  title="Formulaire Décision & Action de revue"
                  @action-created="onStandardActionCreated"
                />
              </v-card-text>
            </v-window-item>

            <v-window-item value="synthese">
              <v-card-text>
                <v-alert class="mb-4" density="comfortable" type="info" variant="tonal">
                  La synthèse du SM est disponible sur l’interface dédiée pour une analyse complète (ISO 9001 §9.3.2: b, c1, c2, c4, c7, e).
                </v-alert>
                <div class="d-flex justify-end mb-3">
                  <v-btn
                    color="primary"
                    prepend-icon="mdi-chart-bar"
                    rounded="lg"
                    @click="router.push('/company/performance/revue-direction/synthese')"
                  >
                    Ouvrir la synthèse dédiée
                  </v-btn>
                </div>

                <v-row class="mt-1" dense>
                  <v-col cols="12" md="6">
                    <v-card class="inner-card" rounded="lg" variant="outlined">
                      <v-card-title>Données générées: KPI / Objectifs / Actions</v-card-title>
                      <v-card-text>
                        <v-list density="compact">
                          <v-list-item>
                            <v-list-item-title>KPI: {{ countItems(review.kpi_data) }}</v-list-item-title>
                          </v-list-item>
                          <v-list-item>
                            <v-list-item-title>Objectifs: {{ countItems(review.objectives_data) }}</v-list-item-title>
                          </v-list-item>
                          <v-list-item>
                            <v-list-item-title>Actions: {{ countItems(review.actions_data) }}</v-list-item-title>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>
                  <v-col cols="12" md="6">
                    <v-card class="inner-card" rounded="lg" variant="outlined">
                      <v-card-title>Données générées: Risques / NC / Audits</v-card-title>
                      <v-card-text>
                        <v-list density="compact">
                          <v-list-item>
                            <v-list-item-title>Risques: {{ countItems(review.risks_data) }}</v-list-item-title>
                          </v-list-item>
                          <v-list-item>
                            <v-list-item-title>Non-conformités: {{ countItems(review.nc_data) }}</v-list-item-title>
                          </v-list-item>
                          <v-list-item>
                            <v-list-item-title>Audits: {{ countItems(review.audit_data) }}</v-list-item-title>
                          </v-list-item>
                        </v-list>
                      </v-card-text>
                    </v-card>
                  </v-col>
                </v-row>
              </v-card-text>
            </v-window-item>
          </v-window>
        </v-card>

        <v-row class="mt-2" dense>
          <v-col v-if="review.report_path && review.status === 'completed'" cols="12">
            <v-alert
              border="start"
              color="success"
              icon="mdi-check-circle-outline"
              variant="tonal"
            >
              Rapport généré automatiquement après clôture de la revue.
            </v-alert>
          </v-col>
        </v-row>
      </template>
    </v-container>
  </ClientALayout>

  <GeneratedDocumentConfigDialog v-model="mrGenDialog" :site-id="mrSiteId" title="Paramètres du rapport" @confirm="onMrGenConfirm" />

  <v-dialog v-model="mrVerifyDialog" max-width="560">
    <v-card rounded="xl">
      <v-card-title class="pa-4">Envoyer en vérification</v-card-title>
      <v-card-text><v-alert type="info" variant="tonal">Code : {{ exportedDocumentCode || '—' }}</v-alert></v-card-text>
      <v-card-actions class="pa-4">
        <v-spacer />
        <v-btn variant="text" @click="mrVerifyDialog = false">Annuler</v-btn>
        <v-btn color="warning" :loading="submittingForVerification" @click="submitMrForVerification">Confirmer</v-btn>
      </v-card-actions>
    </v-card>
  </v-dialog>

  <!-- Prévisualisation avant export (RT-01, Contract C9) -->
  <PreviewExportModal
    v-model="mrPreviewDialog"
    :blob-url="mrPreviewBlobUrl"
    file-type="pdf"
    :filename="mrPreviewFilename"
    :loading="exportingMr"
    :metadata="{
      date: formatDate(review?.planned_date || review?.scheduled_date),
      summaryItems: [
        { label: 'Titre', value: review?.title || `Revue #${reviewId}` },
        { label: 'Statut', value: getStatusLabel(review?.status) },
        { label: 'Période', value: `${form.quarter} ${form.year}` },
        { label: 'Code document', value: exportedDocumentCode || review?.code || '-' },
      ],
    }"
    title="Prévisualisation avant export — Revue de direction (PDF)"
    @close="closeMrPreview"
    @download="handleDownloadDraft"
  />
</template>

<script setup lang="ts">
  import { computed, onMounted, reactive, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import { useToast } from 'vue-toastification'
  import api from '@/api/client'
  import AppDatePickerField from '@/components/common/AppDatePickerField.vue'
  import AppInput from '@/components/common/AppInput.vue'
  import AppSelect from '@/components/common/AppSelect.vue'
  import AppTextarea from '@/components/common/AppTextarea.vue'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import { useDocumentFlow } from '@/modules/clienta/composables/useDocumentFlow'
  import PreviewExportModal from '@/modules/shared/components/PreviewExportModal.vue'
  import StandardActionBlock from '@/modules/shared/components/StandardActionBlock.vue'
  import { useAuthStore } from '@/stores/auth'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'
  import GeneratedDocumentConfigDialog from '@/modules/clienta/components/documents/GeneratedDocumentConfigDialog.vue'
  import { getErrorMessage } from '@/utils/errorMessage'
  import ClientALayout from '../../components/ClientALayout.vue'
  import PageHeader from '../../components/PageHeader.vue'

  const route = useRoute()
  const router = useRouter()
  const toast = useToast()
  const reviewStore = useManagementReviewStore()
  const { users, fetchUsers } = useUsers()
  const activeTab = ref('pilotage')

  const authStore = useAuthStore()
  const mrSiteId = computed(() => authStore.currentSite?.id ?? null)
  const mrGenDialog = ref(false)
  const mrGenCatalogId = ref<number | null>(null)
  const mrGenProcessId = ref<number | null>(null)
  const mrPendingAction = ref<'download' | 'preview' | 'verify'>('download')

  function openMrConfig (action: 'download' | 'preview' | 'verify') {
    mrPendingAction.value = action
    mrGenDialog.value = true
  }

  function onMrGenConfirm (ctx: { document_type_catalog_id: number, process_id?: number | null }) {
    mrGenCatalogId.value = ctx.document_type_catalog_id
    mrGenProcessId.value = ctx.process_id ?? null
    if (mrPendingAction.value === 'preview') handlePreviewDraft()
    else if (mrPendingAction.value === 'verify') openVerifyDialog()
    else handleDownloadDraft()
  }

  const {
    exporting: exportingMr,
    submittingForVerification,
    verificationSent: mrVerificationSent,
    lastExportedDocumentId,
    exportedDocumentCode,
    previewDialog: mrPreviewDialog,
    previewBlobUrl: mrPreviewBlobUrl,
    previewFilename: mrPreviewFilename,
    verifyDialog: mrVerifyDialog,
    handlePreviewDraft,
    closePreview: closeMrPreview,
    handleDownloadDraft,
    openVerifyDialog,
    submitForVerification: submitMrForVerification,
  } = useDocumentFlow(
    async () => {
      if (!reviewId.value) return null
      if (!mrGenCatalogId.value) { openMrConfig('download'); return null }
      const response = await api.post(`/management-reviews/${reviewId.value}/generate-draft`, {
        document_type_catalog_id: mrGenCatalogId.value,
        process_id: mrGenProcessId.value,
      })
      const data = response.data?.data ?? response.data
      const attrs = data?.attributes ?? data
      const id = Number(data?.id || attrs?.id || 0) || null
      const code = String(attrs?.code || '')
      return id ? { id, code } : null
    },
    () => `Revue_Direction_${reviewId.value}_${new Date().toISOString().split('T')[0]}.pdf`,
  )

  const reviewId = computed(() => Number((route.params as Record<string, unknown>).id))
  const review = computed(() => reviewStore.currentReview)

  const quarterOptions = ['Q1', 'Q2', 'Q3', 'Q4']
  const statusOptions = [
    { value: 'planned', label: 'Planifiée' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'completed', label: 'Terminée' },
    { value: 'reported', label: 'Reportée' },
  ]

  const form = reactive({
    title: '',
    planned_date: '',
    actual_date: '',
    year: new Date().getFullYear(),
    quarter: 'Q1',
    status: 'planned' as 'planned' | 'in_progress' | 'completed' | 'reported',
    chairman_id: null as number | null,
    participants: [] as number[],
    previous_actions_status: '',
    context_changes: '',
    performance_indicators: '',
    customer_satisfaction: '',
    audit_results: '',
    nc_complaints_status: '',
    resources_adequacy: '',
    improvement_opportunities: '',
  })

  const decisionsText = ref('')
  const actionItemsText = ref('')

  function onStandardActionCreated (actionData: any) {
    if (actionData?.decision) {
      decisionsText.value = decisionsText.value
        ? `${decisionsText.value}\n${actionData.decision}`
        : actionData.decision
    }
    if (actionData?.action_title) {
      const respName = users.value.find((u: any) => u.id === actionData.responsable_id)?.name || 'Responsable désigné'
      const normPart = Array.isArray(actionData.normes) && actionData.normes.length > 0 ? ` [${actionData.normes.join(', ')}]` : ''
      const actionLine = `${actionData.action_title}${normPart} (Resp: ${respName}, Échéance: ${actionData.delai || 'N/A'})`
      actionItemsText.value = actionItemsText.value
        ? `${actionItemsText.value}\n${actionLine}`
        : actionLine
    }
    toast.success('Décision et action ajoutées aux conclusions de la revue.')
  }

  function formatDate (date?: string) {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function formatDateTime (date?: string) {
    if (!date) return '-'
    return new Date(date).toLocaleString('fr-FR')
  }

  function normalizeDateForInput (date?: string) {
    if (!date) return ''
    return new Date(date).toISOString().slice(0, 10)
  }

  function getStatusLabel (status: string) {
    return statusOptions.find(s => s.value === status)?.label || status
  }

  function countItems (value: unknown) {
    return Array.isArray(value) ? value.length : 0
  }

  function toLines (value: unknown) {
    if (!Array.isArray(value)) return ''
    return value.map(item => {
      if (typeof item === 'string') return item
      if (item && typeof item === 'object' && 'title' in item && typeof item.title === 'string') {
        return item.title
      }
      return JSON.stringify(item)
    }).join('\n')
  }

  function fromLines (value: string) {
    return value
      .split('\n')
      .map(line => line.trim())
      .filter(Boolean)
  }

  function syncFormFromReview () {
    if (!review.value) return

    form.title = review.value.title || ''
    form.planned_date = normalizeDateForInput(review.value.planned_date || review.value.scheduled_date)
    form.actual_date = normalizeDateForInput(review.value.actual_date)
    form.year = review.value.year || new Date().getFullYear()
    form.quarter = review.value.quarter || 'Q1'
    form.status = (review.value.status || 'planned') as typeof form.status
    form.chairman_id = review.value.chairman_id || null
    form.participants = (review.value.participants || [])
      .map(Number)
      .filter(Boolean)

    form.previous_actions_status = review.value.previous_actions_status || ''
    form.context_changes = review.value.context_changes || ''
    form.performance_indicators = review.value.performance_indicators || ''
    form.customer_satisfaction = review.value.customer_satisfaction || ''
    form.audit_results = review.value.audit_results || ''
    form.nc_complaints_status = review.value.nc_complaints_status || ''
    form.resources_adequacy = review.value.resources_adequacy || ''
    form.improvement_opportunities = review.value.improvement_opportunities || ''

    decisionsText.value = toLines(review.value.decisions)
    actionItemsText.value = toLines(review.value.action_items)
  }

  async function loadReview () {
    if (!reviewId.value) return
    await reviewStore.fetchReview(reviewId.value)
  }

  async function saveReview () {
    if (!reviewId.value) return

    try {
      await reviewStore.updateReview(reviewId.value, {
        title: form.title || undefined,
        planned_date: form.planned_date || undefined,
        actual_date: form.actual_date || undefined,
        year: form.year,
        quarter: form.quarter,
        status: form.status,
        chairman_id: form.chairman_id || undefined,
        participants: form.participants,
        previous_actions_status: form.previous_actions_status || undefined,
        context_changes: form.context_changes || undefined,
        performance_indicators: form.performance_indicators || undefined,
        customer_satisfaction: form.customer_satisfaction || undefined,
        audit_results: form.audit_results || undefined,
        nc_complaints_status: form.nc_complaints_status || undefined,
        resources_adequacy: form.resources_adequacy || undefined,
        improvement_opportunities: form.improvement_opportunities || undefined,
        decisions: fromLines(decisionsText.value),
        action_items: fromLines(actionItemsText.value),
      })

      await loadReview()
      toast.success('Revue enregistrée.')
    } catch (error) {
      console.error('Erreur enregistrement:', error)
      toast.error(getErrorMessage(error))
    }
  }

  async function generateData () {
    if (!reviewId.value) return

    try {
      await reviewStore.generateInputData(reviewId.value)
      await loadReview()
      toast.success('Données générées.')
    } catch (error) {
      console.error('Erreur génération:', error)
      toast.error(getErrorMessage(error))
    }
  }

  async function generateSmSynthesis () {
    if (!reviewId.value) return

    try {
      await reviewStore.generateSmSynthesis(reviewId.value)
      await loadReview()
      toast.success('Synthèse du SM générée.')
    } catch (error) {
      console.error('Erreur synthèse SM:', error)
      toast.error(getErrorMessage(error))
    }
  }

  async function sendInvitations () {
    if (!reviewId.value) return

    try {
      await reviewStore.sendInvitations(reviewId.value, form.participants)
      toast.success('Invitations envoyées.')
    } catch (error) {
      console.error('Erreur invitations:', error)
      toast.error(getErrorMessage(error))
    }
  }

  async function closeReview () {
    if (!reviewId.value) return
    if (!confirm('Clôturer cette revue et générer automatiquement le rapport ?')) return

    try {
      await reviewStore.closeReview(reviewId.value)
      await loadReview()
      toast.success('Revue clôturée. Rapport généré automatiquement.')
    } catch (error) {
      console.error('Erreur clôture revue:', error)
      toast.error(getErrorMessage(error))
    }
  }

  watch(review, syncFormFromReview)

  onMounted(async () => {
    await fetchUsers({ per_page: 200 })
    await loadReview()
  })
</script>

<style scoped>
.glass-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    radial-gradient(1200px 460px at 0% -10%, rgba(10, 132, 255, 0.07), transparent 62%),
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  box-shadow: 0 12px 30px rgba(15, 23, 42, 0.07);
}

.inner-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.99), rgba(248, 250, 252, 0.95));
}

.kpi-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: rgba(255, 255, 255, 0.9);
}

.kpi-label {
  color: #64748b;
  font-size: 0.8rem;
}

.kpi-value {
  color: #0f172a;
  font-weight: 800;
  font-size: 1rem;
}

.review-tabs {
  background: rgba(248, 250, 252, 0.86);
  border-radius: 14px;
}

.section-head {
  color: #0f172a;
  font-weight: 800;
}

.summary-grid {
  display: grid;
  gap: 10px;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 10px 12px;
  border: 1px solid rgba(148, 163, 184, 0.25);
  border-radius: 10px;
  background: rgba(255, 255, 255, 0.75);
}

.summary-item span {
  color: #64748b;
  font-size: 0.82rem;
}

.summary-item strong {
  color: #0f172a;
  font-size: 0.9rem;
}

.action-cluster {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.field-label {
  display: block;
  margin-bottom: 6px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
}

.field-vuetify :deep(.v-field.v-field--variant-outlined) {
  border-radius: 12px;
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.06);
}

.field-vuetify :deep(.v-field__outline) {
  color: rgba(148, 163, 184, 0.55);
}

.field-vuetify :deep(.v-field--focused .v-field__outline) {
  color: rgba(37, 99, 235, 0.75);
}

.field-vuetify :deep(.v-field__input) {
  min-height: 44px;
}

/* Force un fond clair sur tous les champs de cette page */
.glass-card :deep(.input-container),
.glass-card :deep(.select-container),
.glass-card :deep(.textarea-container) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
  border-color: rgba(148, 163, 184, 0.55) !important;
}

.glass-card :deep(.input-field),
.glass-card :deep(.select-field),
.glass-card :deep(.textarea-field) {
  color: #0f172a !important;
}

.glass-card :deep(.input-field::placeholder),
.glass-card :deep(.select-field::placeholder),
.glass-card :deep(.textarea-field::placeholder) {
  color: #94a3b8 !important;
}

.glass-card :deep(.v-field.v-field--variant-outlined) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
}

.glass-card :deep(.v-field__input) {
  color: #0f172a !important;
}

.review-form-theme :deep(.input-label),
.review-form-theme :deep(.select-label),
.review-form-theme :deep(.textarea-label),
.review-form-theme :deep(.date-label),
.review-form-theme :deep(.v-label),
.review-form-theme :deep(.field-label) {
  color: #334155 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.v-field-label),
.review-form-theme :deep(.v-field-label--floating) {
  color: #475569 !important;
  opacity: 1 !important;
}

.review-form-theme :deep(.input-field::placeholder),
.review-form-theme :deep(.select-field::placeholder),
.review-form-theme :deep(.textarea-field::placeholder),
.review-form-theme :deep(.dp__input::placeholder),
.review-form-theme :deep(.v-field__input::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
}
</style>
