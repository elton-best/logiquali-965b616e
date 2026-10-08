<template>
  <ClientALayout current-page="performance-revue">
    <v-container class="pa-6 review-form-theme" fluid>
      <PageHeader icon="mdi-account-group" title="Revue de Direction">
        <template #subtitle>
          Rapports et invitations pour les revues de direction (M12-D2/D3)
        </template>
        <template #actions>
          <v-btn color="primary" prepend-icon="mdi-plus" @click="openCreateDialog">
            Nouvelle revue
          </v-btn>
        </template>
      </PageHeader>

      <v-row class="mt-6">
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="primary" size="40">mdi-calendar-month</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.scheduled }}</div>
              <div class="text-caption text-grey">Planifiées</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="success" size="40">mdi-check-circle</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.completed }}</div>
              <div class="text-caption text-grey">Réalisées</div>
            </v-card-text>
          </v-card>
        </v-col>
        <v-col cols="12" md="4">
          <v-card rounded="xl">
            <v-card-text class="text-center">
              <v-icon color="warning" size="40">mdi-clipboard-text</v-icon>
              <div class="text-h4 mt-2 font-weight-bold">{{ stats.actions }}</div>
              <div class="text-caption text-grey">Actions décidées</div>
            </v-card-text>
          </v-card>
        </v-col>
      </v-row>

      <v-card class="mt-6 hero" rounded="xl">
        <v-card-text class="d-flex flex-wrap align-center justify-space-between gap-4">
          <div>
            <div class="text-overline hero-kicker">Pilotage</div>
            <div class="text-h5 font-weight-bold">Revues de direction</div>
            <div class="text-body-2 text-medium-emphasis">
              Centralisez les rapports, invitations et décisions.
            </div>
          </div>
          <v-chip class="font-weight-medium" color="primary" variant="tonal">
            {{ stats.completed }} revue(s) réalisées
          </v-chip>
        </v-card-text>
      </v-card>

      <v-card class="mt-6 registry-shell" rounded="xl">
        <v-card-text>
          <div class="filters-inline">
            <AppInput
              v-model="filters.search"
              label="Rechercher une revue"
              placeholder="Titre, référence, lieu..."
            />
            <AppSelect
              v-model="filters.status"
              label="Statut"
              :options="[
                { label: 'Tous les statuts', value: null },
                ...statusOptions.map(item => ({ label: item.title, value: item.value })),
              ]"
            />
            <AppSelect
              v-model="filters.year"
              label="Année"
              :options="[
                { label: 'Toutes les années', value: null },
                ...yearOptions.map(item => ({ label: String(item), value: item })),
              ]"
            />
          </div>
        </v-card-text>
      </v-card>

      <div class="list-toolbar mt-6 mb-2">
        <div class="list-toolbar-copy">
          <div class="list-toolbar-title">Pilotage multi-vues</div>
          <div class="list-toolbar-subtitle">Chaque liste peut basculer entre un tableau de suivi et des cartes de lecture rapide.</div>
        </div>
      </div>

      <v-tabs v-model="activeTab" class="mt-6" color="primary">
        <v-tab value="reports">Rapports</v-tab>
        <v-tab value="invitations">Invitations</v-tab>
        <v-tab value="decisions">Décisions</v-tab>
      </v-tabs>

      <v-window v-model="activeTab" class="mt-6">
        <!-- Rapports -->
        <v-window-item value="reports">
          <v-card class="registry-shell" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
              <div>
                <div class="panel-title">Rapports de revue</div>
                <div class="panel-subtitle">Préparation, tenue et clôture des revues de direction</div>
              </div>
              <div class="d-flex align-center ga-3">
                <v-chip color="primary" variant="tonal">{{ filteredReports.length }} élément(s)</v-chip>
                <v-btn-toggle
                  v-model="reportViewMode"
                  color="primary"
                  density="comfortable"
                  divided
                  mandatory
                  variant="outlined"
                >
                  <v-btn icon="mdi-table-large" value="table" />
                  <v-btn icon="mdi-view-grid-outline" value="cards" />
                </v-btn-toggle>
              </div>
            </v-card-title>
            <v-card-text>
              <v-data-table
                v-if="reportViewMode === 'table'"
                :headers="reportHeaders"
                :items="filteredReports"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                    {{ getStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.participants`]="{ item }">
                  <v-chip size="small" variant="outlined">
                    {{ item.participants }} participants
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewReport(item)" />
                  <v-btn icon="mdi-pencil" size="small" variant="text" @click="editReport(item)" />
                  <v-btn icon="mdi-file-pdf-box" size="small" variant="text" @click="exportReport(item.id)" />
                  <v-btn
                    color="warning"
                    :disabled="submittingForVerification"
                    icon="mdi-shield-check"
                    size="small"
                    variant="text"
                    @click="submitExportForVerification(item.id)"
                  />
                </template>
              </v-data-table>
              <div v-else class="card-grid">
                <v-card
                  v-for="item in filteredReports"
                  :key="`report-${item.id}`"
                  class="entity-card"
                  elevation="0"
                  rounded="xl"
                >
                  <v-card-text class="pa-5">
                    <div class="entity-card-top">
                      <div>
                        <div class="entity-card-ref">{{ item.reference || 'Sans référence' }}</div>
                        <div class="entity-card-title">{{ item.title }}</div>
                      </div>
                      <v-chip :color="getStatusColor(item.status)" size="small" variant="tonal">
                        {{ getStatusLabel(item.status) }}
                      </v-chip>
                    </div>
                    <div class="entity-card-meta">
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-calendar</v-icon><span>{{ item.date }}</span></div>
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-account-group-outline</v-icon><span>{{ item.participants }} participants</span></div>
                    </div>
                    <div class="entity-card-actions">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-eye"
                        rounded="lg"
                        variant="tonal"
                        @click="viewReport(item)"
                      >Voir</v-btn>
                      <v-btn prepend-icon="mdi-pencil" rounded="lg" variant="text" @click="editReport(item)">Modifier</v-btn>
                      <v-btn
                        color="warning"
                        :disabled="submittingForVerification"
                        prepend-icon="mdi-shield-check"
                        rounded="lg"
                        variant="outlined"
                        @click="submitExportForVerification(item.id)"
                      >Vérifier document</v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Invitations -->
        <v-window-item value="invitations">
          <v-card class="registry-shell" rounded="xl">
            <v-card-title class="pa-6 d-flex align-center flex-wrap ga-3">
              <div>
                <div class="panel-title">Gestion des invitations</div>
                <div class="panel-subtitle">Participants convoqués, relances et statut de présence</div>
              </div>
              <v-spacer />
              <v-btn-toggle
                v-model="invitationViewMode"
                color="primary"
                density="comfortable"
                divided
                mandatory
                variant="outlined"
              >
                <v-btn icon="mdi-table-large" value="table" />
                <v-btn icon="mdi-view-grid-outline" value="cards" />
              </v-btn-toggle>
              <v-btn color="primary" prepend-icon="mdi-email-send" size="small" @click="openInvitationDialog">
                Envoyer des invitations
              </v-btn>
            </v-card-title>
            <v-card-text>
              <v-data-table
                v-if="invitationViewMode === 'table'"
                :headers="invitationHeaders"
                :items="filteredInvitations"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getInvitationStatusColor(item.status)" size="small" variant="tonal">
                    {{ getInvitationStatusLabel(item.status) }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-email-sync" size="small" variant="text" @click="resendInvitation(item)" />
                  <v-btn
                    color="error"
                    icon="mdi-delete"
                    size="small"
                    variant="text"
                    @click="deleteInvitation(item)"
                  />
                </template>
              </v-data-table>
              <div v-else class="card-grid">
                <v-card
                  v-for="item in filteredInvitations"
                  :key="`inv-${item.id}`"
                  class="entity-card"
                  elevation="0"
                  rounded="xl"
                >
                  <v-card-text class="pa-5">
                    <div class="entity-card-top">
                      <div>
                        <div class="entity-card-ref">{{ item.review_title || 'Invitation' }}</div>
                        <div class="entity-card-title">{{ item.participant_name }}</div>
                      </div>
                      <v-chip :color="getInvitationStatusColor(item.status)" size="small" variant="tonal">
                        {{ getInvitationStatusLabel(item.status) }}
                      </v-chip>
                    </div>
                    <div class="entity-card-meta">
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-email-outline</v-icon><span>{{ item.email }}</span></div>
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-calendar</v-icon><span>{{ item.sent_at }}</span></div>
                    </div>
                    <div class="entity-card-actions">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-email-sync"
                        rounded="lg"
                        variant="tonal"
                        @click="resendInvitation(item)"
                      >Relancer</v-btn>
                      <v-btn
                        color="error"
                        prepend-icon="mdi-delete"
                        rounded="lg"
                        variant="text"
                        @click="deleteInvitation(item)"
                      >Supprimer</v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>

        <!-- Décisions -->
        <v-window-item value="decisions">
          <v-card class="registry-shell" rounded="xl">
            <v-card-title class="d-flex align-center justify-space-between flex-wrap ga-3">
              <div>
                <div class="panel-title">Décisions et suites</div>
                <div class="panel-subtitle">Responsables, échéances et priorités issues des revues</div>
              </div>
              <v-btn-toggle
                v-model="decisionViewMode"
                color="primary"
                density="comfortable"
                divided
                mandatory
                variant="outlined"
              >
                <v-btn icon="mdi-table-large" value="table" />
                <v-btn icon="mdi-view-grid-outline" value="cards" />
              </v-btn-toggle>
            </v-card-title>
            <v-card-text>
              <v-data-table
                v-if="decisionViewMode === 'table'"
                :headers="decisionHeaders"
                :items="filteredDecisions"
                items-per-page="10"
                :loading="loading"
              >
                <template #[`item.priority`]="{ item }">
                  <v-chip :color="getPriorityColor(item.priority)" size="small" variant="tonal">
                    {{ item.priority }}
                  </v-chip>
                </template>
                <template #[`item.status`]="{ item }">
                  <v-chip :color="getDecisionStatusColor(item.status)" size="small" variant="tonal">
                    {{ item.status }}
                  </v-chip>
                </template>
                <template #[`item.actions`]="{ item }">
                  <v-btn icon="mdi-eye" size="small" variant="text" @click="viewDecision(item)" />
                </template>
              </v-data-table>
              <div v-else class="card-grid">
                <v-card
                  v-for="item in filteredDecisions"
                  :key="`decision-${item.id}`"
                  class="entity-card"
                  elevation="0"
                  rounded="xl"
                >
                  <v-card-text class="pa-5">
                    <div class="entity-card-top">
                      <div>
                        <div class="entity-card-ref">{{ item.review_title || 'Décision' }}</div>
                        <div class="entity-card-title">{{ item.decision }}</div>
                      </div>
                      <v-chip :color="getDecisionStatusColor(item.status)" size="small" variant="tonal">
                        {{ item.status }}
                      </v-chip>
                    </div>
                    <div class="entity-card-meta">
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-account-tie-outline</v-icon><span>{{ item.responsible }}</span></div>
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-calendar-clock-outline</v-icon><span>{{ item.deadline }}</span></div>
                      <div class="entity-card-meta-item"><v-icon size="16">mdi-flag-outline</v-icon><span>{{ item.priority }}</span></div>
                    </div>
                    <div class="entity-card-actions">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-eye"
                        rounded="lg"
                        variant="tonal"
                        @click="viewDecision(item)"
                      >Voir</v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </div>
            </v-card-text>
          </v-card>
        </v-window-item>
      </v-window>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import AppInput from '@/components/common/AppInput.vue'
  import AppSelect from '@/components/common/AppSelect.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { useManagementReviewStore } from '@/stores/managementReviewStore'
  import { getErrorMessage } from '@/utils/errorMessage'

  const router = useRouter()
  const toast = useToast()
  const authStore = useAuthStore()
  const reviewStore = useManagementReviewStore()
  const activeTab = ref('reports')
  const reportViewMode = ref<'table' | 'cards'>('table')
  const invitationViewMode = ref<'table' | 'cards'>('table')
  const decisionViewMode = ref<'table' | 'cards'>('table')
  const exportedDocsByReviewId = ref<Record<number, { id: number, code: string }>>({})
  const submittingForVerification = ref(false)

  const loading = computed(() => reviewStore.loading)
  const filters = ref({
    search: '',
    status: null as string | null,
    year: null as number | null,
  })

  const statusOptions = [
    { title: 'Planifiée', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Clôturée', value: 'completed' },
    { title: 'Reportée', value: 'reported' },
  ]

  const reports = computed(() => reviewStore.reviews.map(review => {
    const participants = Array.isArray(review.participants) ? review.participants.length : 0
    return {
      id: Number(review.id),
      reference: review.ref || `RD-${review.id}`,
      title: review.title || review.ref || `Revue #${review.id}`,
      date: formatDate(review.actual_date || review.planned_date || review.scheduled_date),
      rawDate: review.actual_date || review.planned_date || review.scheduled_date || '',
      participants,
      status: review.status || 'planned',
      year: review.year || (review.planned_date ? new Date(review.planned_date).getFullYear() : null),
      location: review.site?.name || '',
      source: review,
    }
  }))

  const invitations = computed(() => reviewStore.reviews.flatMap(review => {
    const participants = Array.isArray(review.participants) ? review.participants : []
    return participants.map((participant, index) => ({
      id: `${review.id}-${index}`,
      review_id: review.id,
      review_title: review.title || review.ref || `Revue #${review.id}`,
      participant_name: `Participant ${participant}`,
      email: '',
      sent_at: formatDate(review.updated_at || review.created_at),
      status: review.status === 'completed' ? 'accepted' : 'pending',
    }))
  }))

  const decisions = computed(() => reviewStore.reviews.flatMap(review => {
    const rows = [
      ...(Array.isArray(review.decisions) ? review.decisions : []),
      ...(Array.isArray(review.action_items) ? review.action_items : []),
    ]

    return rows.map((row: any, index: number) => ({
      id: `${review.id}-${index}`,
      review_id: review.id,
      review_title: review.title || review.ref || `Revue #${review.id}`,
      decision: typeof row === 'string' ? row : (row.title || row.decision || row.description || 'Décision'),
      responsible: row.responsible || row.responsible_name || '—',
      deadline: row.deadline || row.due_date || '—',
      priority: row.priority || 'medium',
      status: row.status || 'pending',
    }))
  }))

  const yearOptions = computed(() => {
    const years = new Set<number>()
    for (const item of reports.value) {
      if (item.year) years.add(Number(item.year))
    }
    return Array.from(years).toSorted((a, b) => b - a)
  })

  const filteredReports = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return reports.value.filter(item => {
      if (filters.value.status && item.status !== filters.value.status) return false
      if (filters.value.year && Number(item.year) !== filters.value.year) return false
      if (!query) return true
      const haystack = `${item.title || ''} ${item.reference || ''} ${item.location || ''}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  const filteredInvitations = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return invitations.value.filter(item => {
      if (!query) return true
      const haystack = `${item.email || ''} ${item.review_title || ''} ${item.participant_name || ''}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  const filteredDecisions = computed(() => {
    const query = filters.value.search.trim().toLowerCase()
    return decisions.value.filter(item => {
      if (!query) return true
      const haystack = `${item.decision || ''} ${item.responsible || ''} ${item.status || ''}`.toLowerCase()
      return haystack.includes(query)
    })
  })

  const stats = computed(() => ({
    scheduled: reviewStore.reviews.filter(item => ['planned', 'in_progress'].includes(item.status)).length,
    completed: reviewStore.reviews.filter(item => ['completed', 'reported'].includes(item.status)).length,
    actions: decisions.value.length,
  }))

  const reportHeaders = [
    { title: 'Référence', key: 'reference' },
    { title: 'Titre', key: 'title' },
    { title: 'Date', key: 'date' },
    { title: 'Participants', key: 'participants' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const invitationHeaders = [
    { title: 'Revue', key: 'review_title' },
    { title: 'Participant', key: 'participant_name' },
    { title: 'Email', key: 'email' },
    { title: 'Envoyé le', key: 'sent_at' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  const decisionHeaders = [
    { title: 'Revue', key: 'review_title' },
    { title: 'Décision', key: 'decision' },
    { title: 'Responsable', key: 'responsible' },
    { title: 'Échéance', key: 'deadline' },
    { title: 'Priorité', key: 'priority' },
    { title: 'Statut', key: 'status' },
    { title: 'Actions', key: 'actions', sortable: false, align: 'end' as const },
  ]

  function getStatusColor (status: string) {
    const colors: Record<string, string> = {
      draft: 'grey',
      scheduled: 'primary',
      planned: 'primary',
      in_progress: 'warning',
      completed: 'success',
      reported: 'info',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string) {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      scheduled: 'Planifiée',
      planned: 'Planifiée',
      in_progress: 'En cours',
      completed: 'Réalisée',
      reported: 'Reportée',
    }
    return labels[status] || status
  }

  function getInvitationStatusColor (status: string) {
    const colors: Record<string, string> = {
      sent: 'info',
      accepted: 'success',
      declined: 'error',
      pending: 'warning',
    }
    return colors[status] || 'grey'
  }

  function getInvitationStatusLabel (status: string) {
    const labels: Record<string, string> = {
      sent: 'Envoyée',
      accepted: 'Acceptée',
      declined: 'Refusée',
      pending: 'En attente',
    }
    return labels[status] || status
  }

  function getPriorityColor (priority: string) {
    const colors: Record<string, string> = {
      high: 'error',
      medium: 'warning',
      low: 'info',
    }
    return colors[priority] || 'grey'
  }

  function getDecisionStatusColor (status: string) {
    const colors: Record<string, string> = {
      pending: 'warning',
      in_progress: 'info',
      completed: 'success',
    }
    return colors[status] || 'grey'
  }

  function openCreateDialog () {
    router.push('/company/management-reviews?openCreate=1')
  }

  function openInvitationDialog () {
    router.push('/company/management-reviews')
  }

  function editReport (item: any) {
    if (item?.id) router.push(`/company/management-reviews/${item.id}`)
  }

  function viewReport (item: any) {
    if (item?.id) router.push(`/company/management-reviews/${item.id}`)
  }

  function viewDecision (item: any) {
    if (item?.review_id) router.push(`/company/management-reviews/${item.review_id}`)
  }

  async function exportReport (id: number, download = true): Promise<boolean> {
    try {
      const exportResult = await reviewStore.exportReportDocx(Number(id), download)
      const generatedDocumentId = Number(exportResult?.generatedDocumentId || 0)
      if (generatedDocumentId > 0) {
        const docResponse = await api.get(`/documents/${generatedDocumentId}`)
        const code = String(docResponse.data?.data?.code || docResponse.data?.code || '')
        exportedDocsByReviewId.value[Number(id)] = { id: generatedDocumentId, code }
      }
      return generatedDocumentId > 0
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Export du rapport impossible.'))
      return false
    }
  }

  async function submitExportForVerification (reviewId: number) {
    const exported = exportedDocsByReviewId.value[Number(reviewId)]
    if (!exported?.id || !exported?.code) {
      const generated = await exportReport(Number(reviewId), false)
      if (!generated) {
        toast.error('Impossible de préparer le brouillon documentaire avant soumission.')
        return
      }
    }
    const documentToSubmit = exportedDocsByReviewId.value[Number(reviewId)]
    if (!documentToSubmit?.id || !documentToSubmit?.code) return
    try {
      submittingForVerification.value = true
      await api.post(`/documents/${documentToSubmit.id}/confirm-code`, {
        needs_verification: true,
        confirmed_code: documentToSubmit.code,
      })
      toast.success('Document envoyé pour vérification.')
      delete exportedDocsByReviewId.value[Number(reviewId)]
    } catch (error) {
      console.error(error)
      toast.error(getErrorMessage(error, 'Échec de la soumission pour vérification.'))
    } finally {
      submittingForVerification.value = false
    }
  }

  async function resendInvitation (item: any) {
    if (item?.review_id) {
      router.push(`/company/management-reviews/${item.review_id}`)
    }
  }

  async function deleteInvitation (item: any) {
    if (item?.review_id) {
      router.push(`/company/management-reviews/${item.review_id}`)
    }
  }

  function formatDate (date?: string) {
    if (!date) return '—'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  async function loadReviews () {
    const siteId = authStore.currentSiteId || Number(localStorage.getItem('current_site_id')) || null
    await reviewStore.fetchReviews(1, siteId ? { site_id: siteId } : {})
  }

  onMounted(async () => {
    await loadReviews()
  })

  watch(() => authStore.currentSiteId, async () => {
    await loadReviews()
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

.hero-kicker {
  color: rgba(15, 23, 42, 0.62);
}

.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.filters-inline {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 12px;
}

.list-toolbar-title,
.panel-title {
  font-weight: 800;
  color: #0f172a;
}

.list-toolbar-subtitle,
.panel-subtitle {
  color: #64748b;
  font-size: 0.92rem;
}

.card-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 16px;
}

.entity-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.06);
}

.entity-card-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.entity-card-ref {
  color: #64748b;
  font-size: 0.82rem;
  margin-bottom: 4px;
}

.entity-card-title {
  color: #0f172a;
  font-size: 1rem;
  font-weight: 800;
}

.entity-card-meta {
  display: grid;
  gap: 10px;
  margin-top: 14px;
}

.entity-card-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: #475569;
}

.entity-card-actions {
  display: flex;
  gap: 8px;
  justify-content: flex-end;
  margin-top: 18px;
  flex-wrap: wrap;
}

.review-form-theme :deep(.input-container),
.review-form-theme :deep(.select-container),
.review-form-theme :deep(.textarea-container),
.review-form-theme :deep(.v-field.v-field--variant-outlined) {
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
  border-color: rgba(148, 163, 184, 0.55) !important;
}

.review-form-theme :deep(.input-field),
.review-form-theme :deep(.select-field),
.review-form-theme :deep(.textarea-field),
.review-form-theme :deep(.v-field__input) {
  color: #0f172a !important;
}

.review-form-theme :deep(.input-field::placeholder),
.review-form-theme :deep(.select-field::placeholder),
.review-form-theme :deep(.textarea-field::placeholder),
.review-form-theme :deep(.v-field__input::placeholder) {
  color: #94a3b8 !important;
  opacity: 1 !important;
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

@media (min-width: 960px) {
  .filters-inline {
    grid-template-columns: minmax(0, 2fr) minmax(180px, 0.9fr) minmax(180px, 0.9fr);
    align-items: center;
  }

  .card-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
