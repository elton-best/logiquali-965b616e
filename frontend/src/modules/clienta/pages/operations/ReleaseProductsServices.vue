<template>
  <ClientALayout current-page="production-service-provision">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-package-check"
        subtitle="Les projets planifies dans la maitrise operationnelle sont centralises ici pour la decision de liberation."
        title="Liberation des produits et services"
      />

      <v-card class="hero mb-6" elevation="0" rounded="xl">
        <v-card-text class="pa-6 pa-md-8">
          <div class="hero-grid">
            <div>
              <div class="text-overline hero-kicker mb-2">Point 8.6</div>
              <h2 class="text-h4 text-md-h3 font-weight-black mb-2">
                Piloter la liberation avec preuves
              </h2>
              <p class="text-body-1 text-medium-emphasis mb-0">
                Meme portefeuille de projets que la planification operationnelle, avec statut de liberation,
                details de realisation et preuves des produits/projets acheves.
              </p>
            </div>
            <div class="hero-badges">
              <div class="hero-badge">
                <span>Projets</span>
                <strong>{{ projects.length }}</strong>
              </div>
              <div class="hero-badge">
                <span>A liberer</span>
                <strong>{{ countByStatus('planned') }}</strong>
              </div>
              <div class="hero-badge">
                <span>Acheves</span>
                <strong>{{ countByStatus('completed') }}</strong>
              </div>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <v-card class="registry-shell pa-4 mb-6" rounded="xl">
        <v-row dense>
          <v-col cols="12" md="5">
            <v-text-field
              v-model="filters.search"
              clearable
              density="comfortable"
              hide-details
              label="Rechercher un projet"
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-select
              v-model="filters.status"
              clearable
              density="comfortable"
              hide-details
              :items="statusItems"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-btn
              block
              color="primary"
              prepend-icon="mdi-refresh"
              rounded="lg"
              variant="tonal"
              @click="fetchOverview"
            >
              Actualiser
            </v-btn>
          </v-col>
          <v-col cols="12" md="2">
            <v-btn-toggle
              v-model="viewMode"
              class="w-100"
              color="primary"
              density="comfortable"
              divided
              mandatory
              variant="outlined"
            >
              <v-btn value="table">
                <v-icon size="18">mdi-table-large</v-icon>
              </v-btn>
              <v-btn value="cards">
                <v-icon size="18">mdi-view-grid-outline</v-icon>
              </v-btn>
            </v-btn-toggle>
          </v-col>
        </v-row>
      </v-card>

      <v-card class="registry-shell" rounded="xl">
        <v-card-title class="d-flex align-center justify-space-between">
          <span>Projets recuperes de la planification operationnelle</span>
          <v-chip color="primary" size="small" variant="tonal">
            {{ filteredProjects.length }} projet(s)
          </v-chip>
        </v-card-title>

        <template v-if="viewMode === 'table'">
          <DataTable :headers="headers" :items="filteredProjects" :loading="loading">
            <template #item.title="{ item }">
              <div class="stacked-cell">
                <span class="stacked-label">{{ item.code || 'Sans code' }}</span>
                <span class="stacked-value strong">{{ item.title }}</span>
              </div>
            </template>

            <template #item.site="{ item }">
              {{ item.site?.name || 'Site non defini' }}
            </template>

            <template #item.project_manager="{ item }">
              {{ item.project_manager?.name || 'Non assigne' }}
            </template>

            <template #item.status="{ item }">
              <StatusChip :color="statusColor(item.status)" :label="formatStatusLabel(item.status)" size="small" />
            </template>

            <template #item.release_evidences="{ item }">
              <v-chip :color="(item.release_evidences || []).length > 0 ? 'success' : 'default'" size="small" variant="tonal">
                {{ (item.release_evidences || []).length }} preuve(s)
              </v-chip>
            </template>

            <template #item.actions="{ item }">
              <div class="table-actions">
                <v-menu location="bottom end">
                  <template #activator="{ props }">
                    <v-btn icon="mdi-progress-pencil" size="small" variant="text" v-bind="props" />
                  </template>
                  <v-list density="compact">
                    <v-list-item
                      v-for="status in projectStatuses"
                      :key="`${item.id}-${status}`"
                      :active="item.status === status"
                      @click="updateProjectStatus(item, status)"
                    >
                      <v-list-item-title>{{ formatStatusLabel(status) }}</v-list-item-title>
                    </v-list-item>
                  </v-list>
                </v-menu>
                <v-btn icon="mdi-file-upload-outline" size="small" variant="text" @click="openEvidenceDialog(item)" />
                <v-btn icon="mdi-eye-outline" size="small" variant="text" @click="openDetailsDialog(item)" />
              </div>
            </template>
          </DataTable>
        </template>

        <template v-else>
          <v-card-text>
            <v-row dense>
              <v-col
                v-for="project in filteredProjects"
                :key="project.id"
                cols="12"
                md="6"
                xl="4"
              >
                <v-card class="project-card h-100" elevation="0" rounded="xl">
                  <v-card-text class="pa-5">
                    <div class="d-flex align-start justify-space-between ga-2 mb-3">
                      <div>
                        <div class="text-caption text-medium-emphasis">{{ project.code || 'Sans code' }}</div>
                        <div class="text-subtitle-1 font-weight-bold">{{ project.title }}</div>
                      </div>
                      <StatusChip :color="statusColor(project.status)" :label="formatStatusLabel(project.status)" size="small" />
                    </div>
                    <div class="text-body-2 text-medium-emphasis mb-3">
                      {{ project.description || 'Aucune description fournie.' }}
                    </div>
                    <div class="project-meta">
                      <v-chip size="x-small" variant="outlined">Site: {{ project.site?.name || 'N/A' }}</v-chip>
                      <v-chip size="x-small" variant="outlined">Pilote: {{ project.project_manager?.name || 'N/A' }}</v-chip>
                      <v-chip color="success" size="x-small" variant="tonal">
                        {{ (project.release_evidences || []).length }} preuve(s)
                      </v-chip>
                    </div>
                    <div class="project-actions">
                      <v-btn
                        color="primary"
                        prepend-icon="mdi-eye-outline"
                        rounded="lg"
                        variant="tonal"
                        @click="openDetailsDialog(project)"
                      >
                        Details
                      </v-btn>
                      <v-btn prepend-icon="mdi-file-upload-outline" rounded="lg" variant="text" @click="openEvidenceDialog(project)">
                        Preuves
                      </v-btn>
                    </div>
                  </v-card-text>
                </v-card>
              </v-col>
            </v-row>
          </v-card-text>
        </template>
      </v-card>

      <v-dialog v-model="detailsDialog" max-width="1080">
        <v-card v-if="selectedProject" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="primary" size="44" variant="tonal">
                <v-icon size="22">mdi-package-variant-closed-check</v-icon>
              </v-avatar>
              <div>
                <div class="text-caption text-medium-emphasis">{{ selectedProject.code || 'Sans code' }}</div>
                <div class="text-h6 font-weight-bold">{{ selectedProject.title }}</div>
              </div>
            </div>
            <StatusChip :color="statusColor(selectedProject.status)" :label="formatStatusLabel(selectedProject.status)" size="small" />
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-6">
            <v-stepper v-model="detailsStep" class="modal-stepper" flat>
              <v-stepper-header>
                <v-stepper-item :complete="detailsStep > 1" title="Vue projet" :value="1" />
                <v-divider />
                <v-stepper-item :complete="detailsStep > 2" title="Activites et taches" :value="2" />
                <v-divider />
                <v-stepper-item title="Validation et notes" :value="3" />
              </v-stepper-header>

              <v-stepper-window>
                <v-stepper-window-item :value="1">
                  <v-row class="mt-2" dense>
                    <v-col cols="12">
                      <div class="section-title">
                        <v-icon color="primary" size="18">mdi-information-outline</v-icon>
                        <span>Contexte du projet</span>
                      </div>
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field label="Site" :model-value="selectedProject.site?.name || 'Non defini'" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field label="Chef de projet" :model-value="selectedProject.project_manager?.name || 'Non assigne'" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field label="Debut" :model-value="selectedProject.start_date || 'N/A'" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field label="Echeance" :model-value="selectedProject.due_date || 'N/A'" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12" md="4">
                      <v-text-field label="Taches" :model-value="String(totalTaskCount(selectedProject))" readonly variant="outlined" />
                    </v-col>
                    <v-col cols="12">
                      <div class="metric-grid">
                        <div class="metric-card">
                          <span class="metric-label">Activites</span>
                          <strong class="metric-value">{{ (selectedProject.activities || []).length }}</strong>
                        </div>
                        <div class="metric-card">
                          <span class="metric-label">Taches terminees</span>
                          <strong class="metric-value">{{ completedTaskCount(selectedProject) }}</strong>
                        </div>
                        <div class="metric-card">
                          <span class="metric-label">Preuves de liberation</span>
                          <strong class="metric-value">{{ (selectedProject.release_evidences || []).length }}</strong>
                        </div>
                      </div>
                    </v-col>
                    <v-col cols="12">
                      <v-alert density="comfortable" type="info" variant="tonal">
                        Cette vue centralise les informations de pilotage provenant de la planification et maitrise operationnelle.
                      </v-alert>
                    </v-col>
                  </v-row>
                </v-stepper-window-item>

                <v-stepper-window-item :value="2">
                  <div class="section-title mt-2 mb-2">
                    <v-icon color="primary" size="18">mdi-format-list-checks</v-icon>
                    <span>Execution par activites et taches</span>
                  </div>
                  <v-alert
                    v-if="!selectedProject.activities || selectedProject.activities.length === 0"
                    class="mt-2"
                    density="comfortable"
                    type="info"
                    variant="tonal"
                  >
                    Aucune activite planifiee pour ce projet.
                  </v-alert>

                  <v-expansion-panels v-else class="activity-panels mt-2" variant="accordion">
                    <v-expansion-panel
                      v-for="activity in selectedProject.activities"
                      :key="`activity-${activity.id}`"
                      rounded="lg"
                    >
                      <v-expansion-panel-title>
                        <div class="d-flex align-center justify-space-between w-100 ga-2">
                          <div>
                            <div class="font-weight-bold">{{ activity.title || `Activite ${activity.id}` }}</div>
                            <div class="text-caption text-medium-emphasis">{{ activity.description || 'Sans description' }}</div>
                          </div>
                          <div class="d-flex align-center ga-2">
                            <StatusChip :color="statusColor(activity.status)" :label="formatStatusLabel(activity.status)" size="small" />
                            <v-chip size="x-small" variant="tonal">{{ (activity.tasks || []).length }} tache(s)</v-chip>
                          </div>
                        </div>
                      </v-expansion-panel-title>
                      <v-expansion-panel-text>
                        <v-list
                          v-if="activity.tasks && activity.tasks.length > 0"
                          class="task-list"
                          density="compact"
                          lines="two"
                        >
                          <v-list-item v-for="task in activity.tasks" :key="`task-${task.id}`">
                            <template #prepend>
                              <v-icon color="primary">mdi-checkbox-marked-circle-outline</v-icon>
                            </template>
                            <v-list-item-title>{{ task.title || `Tache ${task.id}` }}</v-list-item-title>
                            <v-list-item-subtitle>
                              {{ task.description || 'Sans description' }}
                            </v-list-item-subtitle>
                            <template #append>
                              <StatusChip
                                :color="statusColor(mapTaskStatusToProjectStatus(task.status))"
                                :label="formatStatusLabel(mapTaskStatusToProjectStatus(task.status))"
                                size="small"
                              />
                            </template>
                          </v-list-item>
                        </v-list>
                        <v-alert v-else density="compact" type="warning" variant="tonal">
                          Cette activite ne contient pas encore de taches.
                        </v-alert>
                      </v-expansion-panel-text>
                    </v-expansion-panel>
                  </v-expansion-panels>
                </v-stepper-window-item>

                <v-stepper-window-item :value="3">
                  <div class="section-title mt-2 mb-2">
                    <v-icon color="primary" size="18">mdi-clipboard-check-outline</v-icon>
                    <span>Validation finale</span>
                  </div>
                  <v-row class="mt-2" dense>
                    <v-col cols="12" md="6">
                      <v-text-field
                        label="Preuves de liberation"
                        :model-value="String((selectedProject.release_evidences || []).length)"
                        readonly
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12" md="6">
                      <v-text-field
                        label="Taches terminees"
                        :model-value="String(completedTaskCount(selectedProject))"
                        readonly
                        variant="outlined"
                      />
                    </v-col>
                    <v-col cols="12">
                      <v-textarea v-model="releaseNotesDraft" label="Notes de liberation" rows="4" variant="outlined" />
                    </v-col>
                  </v-row>
                </v-stepper-window-item>
              </v-stepper-window>
            </v-stepper>
          </v-card-text>
          <v-card-actions class="px-5 pb-5">
            <v-btn :disabled="detailsStep <= 1" prepend-icon="mdi-arrow-left" variant="text" @click="detailsStep--">
              Precedent
            </v-btn>
            <v-spacer />
            <v-btn variant="text" @click="detailsDialog = false">Fermer</v-btn>
            <v-btn
              v-if="detailsStep < 3"
              append-icon="mdi-arrow-right"
              color="primary"
              variant="tonal"
              @click="detailsStep++"
            >
              Suivant
            </v-btn>
            <v-btn v-else color="primary" prepend-icon="mdi-content-save-outline" @click="saveReleaseNotes">
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>

      <v-dialog v-model="evidenceDialog" max-width="920">
        <v-card v-if="selectedProject" rounded="xl">
          <v-card-title class="modal-header d-flex align-center justify-space-between">
            <div class="d-flex align-center ga-3">
              <v-avatar color="success" size="44" variant="tonal">
                <v-icon size="22">mdi-file-check-outline</v-icon>
              </v-avatar>
              <div>
                <div class="text-caption text-medium-emphasis">Dossier de preuve</div>
                <div class="text-h6 font-weight-bold">Preuves de liberation - {{ selectedProject.title }}</div>
              </div>
            </div>
            <v-chip color="success" size="small" variant="tonal">
              {{ (selectedProject.release_evidences || []).length }} preuve(s)
            </v-chip>
          </v-card-title>
          <v-divider />
          <v-card-text class="pa-6">
            <v-row class="mb-2" dense>
              <v-col cols="12" md="8">
                <v-file-input
                  v-model="evidenceFile"
                  accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
                  density="comfortable"
                  hide-details
                  label="Ajouter une preuve de projet / produit acheve"
                  prepend-icon="mdi-paperclip"
                  show-size
                  variant="outlined"
                />
              </v-col>
              <v-col class="d-flex align-center" cols="12" md="4">
                <v-btn
                  block
                  color="primary"
                  :disabled="!evidenceFile || evidenceUploading"
                  prepend-icon="mdi-upload"
                  rounded="lg"
                  @click="uploadEvidence"
                >
                  Importer la preuve
                </v-btn>
              </v-col>
            </v-row>
            <div class="evidence-summary mb-4">
              <v-chip color="primary" size="small" variant="tonal">
                Projet: {{ selectedProject.code || selectedProject.title }}
              </v-chip>
              <v-chip color="success" size="small" variant="tonal">
                {{ (selectedProject.release_evidences || []).length }} preuve(s)
              </v-chip>
            </div>

            <v-alert v-if="(selectedProject.release_evidences || []).length === 0" density="compact" type="info" variant="tonal">
              Aucune preuve disponible pour ce projet.
            </v-alert>

            <v-list v-else class="evidence-list" lines="two">
              <v-list-item
                v-for="(evidence, index) in selectedProject.release_evidences"
                :key="`${selectedProject.id}-evidence-${index}`"
              >
                <template #prepend>
                  <v-icon color="success">mdi-file-check-outline</v-icon>
                </template>
                <v-list-item-title>{{ evidence.name || `Preuve ${index + 1}` }}</v-list-item-title>
                <v-list-item-subtitle>
                  {{ evidence.uploaded_by || 'Utilisateur' }} - {{ formatDateTime(evidence.uploaded_at) }}
                </v-list-item-subtitle>
                <template #append>
                  <v-btn
                    v-if="evidence.url"
                    icon="mdi-download"
                    size="small"
                    variant="text"
                    @click="downloadEvidence(evidence.url)"
                  />
                  <v-btn
                    color="error"
                    icon="mdi-delete-outline"
                    size="small"
                    variant="text"
                    @click="deleteEvidence(index)"
                  />
                </template>
              </v-list-item>
            </v-list>
          </v-card-text>
          <v-card-actions class="px-5 pb-5">
            <v-spacer />
            <v-btn variant="text" @click="evidenceDialog = false">Fermer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'
  import { getErrorMessage } from '@/utils/errorMessage'

  type ProjectEvidence = {
    name?: string
    url?: string
    path?: string
    uploaded_at?: string
    uploaded_by?: string
  }

  type OperationalProject = {
    id: number
    code?: string
    title: string
    description?: string
    status: string
    priority?: string
    start_date?: string
    due_date?: string
    project_manager_id?: number
    project_manager?: { id: number, name: string }
    projectManager?: { id: number, name: string }
    site?: { id: number, name: string }
    activities?: Array<{
      id: number
      title?: string
      description?: string
      status?: string
      tasks?: Array<{
        id: number
        title?: string
        description?: string
        status?: string
      }>
    }>
    release_notes?: string
    release_evidences?: ProjectEvidence[]
  }

  const authStore = useAuthStore()
  const toast = useToast()
  const loading = ref(false)
  const projects = ref<OperationalProject[]>([])

  const filters = ref({
    search: '',
    status: '',
  })

  const viewMode = ref<'table' | 'cards'>('table')
  const detailsDialog = ref(false)
  const detailsStep = ref(1)
  const evidenceDialog = ref(false)
  const selectedProject = ref<OperationalProject | null>(null)
  const releaseNotesDraft = ref('')
  const evidenceFile = ref<File | null>(null)
  const evidenceUploading = ref(false)

  const projectStatuses = ['planned', 'in_progress', 'on_hold', 'completed', 'cancelled']
  const statusItems = projectStatuses.map(status => ({
    title: formatStatusLabel(status),
    value: status,
  }))

  const headers = [
    { key: 'title', title: 'Projet', sortable: false },
    { key: 'site', title: 'Site', sortable: false },
    { key: 'project_manager', title: 'Pilote', sortable: false },
    { key: 'status', title: 'Statut', sortable: false },
    { key: 'release_evidences', title: 'Preuves', sortable: false },
    { key: 'actions', title: 'Actions', sortable: false },
  ]

  const filteredProjects = computed(() => {
    const query = filters.value.search.trim().toLowerCase()

    return projects.value.filter(project => {
      const statusOk = !filters.value.status || project.status === filters.value.status
      if (!statusOk) return false
      if (!query) return true

      const managerName = project.project_manager?.name || project.projectManager?.name || ''
      const haystack = [
        project.code || '',
        project.title || '',
        project.description || '',
        project.site?.name || '',
        managerName,
      ]
        .join(' ')
        .toLowerCase()

      return haystack.includes(query)
    })
  })

  function getCurrentSiteId (): number | null {
    if (authStore.currentSiteId) return Number(authStore.currentSiteId)
    const raw = localStorage.getItem('current_site_id')
    if (!raw) return null
    const parsed = Number(raw)
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null
  }

  function normalizeProject (raw: any): OperationalProject {
    return {
      ...raw,
      project_manager: raw?.project_manager || raw?.projectManager,
      release_evidences: Array.isArray(raw?.release_evidences) ? raw.release_evidences : [],
      activities: Array.isArray(raw?.activities) ? raw.activities : [],
    }
  }

  async function fetchOverview (): Promise<void> {
    loading.value = true
    try {
      const siteId = getCurrentSiteId()
      const { data } = await api.get('/operational-planning/overview', {
        params: { site_id: siteId || undefined },
      })

      const rows = Array.isArray(data?.data?.projects) ? data.data.projects : []
      projects.value = rows.map((row: any) => normalizeProject(row))

      if (selectedProject.value?.id) {
        const refreshed = projects.value.find(project => project.id === selectedProject.value?.id) || null
        selectedProject.value = refreshed
        if (refreshed) {
          releaseNotesDraft.value = refreshed.release_notes || ''
        }
      }
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de charger les projets de liberation.'))
    } finally {
      loading.value = false
    }
  }

  function countByStatus (status: string): number {
    return projects.value.filter(project => project.status === status).length
  }

  function statusColor (status: string): string {
    switch (String(status || '').toLowerCase()) {
      case 'completed': {
        return 'success'
      }
      case 'in_progress': {
        return 'info'
      }
      case 'on_hold': {
        return 'warning'
      }
      case 'cancelled': {
        return 'error'
      }
      default: {
        return 'default'
      }
    }
  }

  function formatStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      planned: 'Planifie',
      in_progress: 'En cours',
      on_hold: 'En attente',
      completed: 'Acheve',
      cancelled: 'Annule',
    }
    return labels[String(status || '').toLowerCase()] || status || 'Non defini'
  }

  function formatDateTime (value?: string): string {
    if (!value) return 'Date inconnue'
    const date = new Date(value)
    if (Number.isNaN(date.getTime())) return value
    return date.toLocaleString('fr-FR')
  }

  async function updateProjectStatus (project: OperationalProject, status: string): Promise<void> {
    if (!project.id || project.status === status) return
    try {
      await api.put(`/operational-projects/${project.id}`, { status })
      toast.success(`Statut mis a jour: ${formatStatusLabel(status)}.`)
      await fetchOverview()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de modifier le statut du projet.'))
    }
  }

  function openDetailsDialog (project: OperationalProject): void {
    selectedProject.value = normalizeProject(project)
    releaseNotesDraft.value = selectedProject.value.release_notes || ''
    detailsStep.value = 1
    detailsDialog.value = true
  }

  function openEvidenceDialog (project: OperationalProject): void {
    selectedProject.value = normalizeProject(project)
    evidenceFile.value = null
    evidenceDialog.value = true
  }

  async function saveReleaseNotes (): Promise<void> {
    if (!selectedProject.value?.id) return
    try {
      await api.put(`/operational-projects/${selectedProject.value.id}`, {
        release_notes: releaseNotesDraft.value || null,
      })
      toast.success('Notes de liberation enregistrees.')
      await fetchOverview()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible d’enregistrer les notes de liberation.'))
    }
  }

  async function uploadEvidence (): Promise<void> {
    if (!selectedProject.value?.id || !evidenceFile.value) return
    evidenceUploading.value = true
    try {
      const formData = new FormData()
      formData.append('file', evidenceFile.value)
      await api.post(`/operational-projects/${selectedProject.value.id}/evidences`, formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      evidenceFile.value = null
      toast.success('Preuve importee avec succes.')
      await fetchOverview()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible d’importer la preuve.'))
    } finally {
      evidenceUploading.value = false
    }
  }

  async function deleteEvidence (index: number): Promise<void> {
    if (!selectedProject.value?.id) return
    try {
      await api.delete(`/operational-projects/${selectedProject.value.id}/evidences/${index}`)
      toast.success('Preuve supprimee.')
      await fetchOverview()
    } catch (error) {
      toast.error(getErrorMessage(error, 'Impossible de supprimer la preuve.'))
    }
  }

  function resolveEvidenceUrl (rawUrl: string): string {
    if (!rawUrl) return ''
    if (/^https?:\/\//i.test(rawUrl)) return rawUrl

    const baseUrl = String((api as any)?.defaults?.baseURL || '').trim()
    if (!baseUrl) return rawUrl

    try {
      const apiOrigin = new URL(baseUrl, window.location.origin).origin
      return new URL(rawUrl, apiOrigin).toString()
    } catch {
      return rawUrl
    }
  }

  function downloadEvidence (url: string): void {
    const resolvedUrl = resolveEvidenceUrl(url)
    if (!resolvedUrl) {
      toast.error('Lien de preuve invalide.')
      return
    }
    window.open(resolvedUrl, '_blank', 'noopener')
  }

  function mapTaskStatusToProjectStatus (taskStatus?: string): string {
    switch (String(taskStatus || '').toLowerCase()) {
      case 'done': {
        return 'completed'
      }
      case 'blocked': {
        return 'on_hold'
      }
      case 'in_progress': {
        return 'in_progress'
      }
      default: {
        return 'planned'
      }
    }
  }

  function totalTaskCount (project: OperationalProject): number {
    return (project.activities || []).reduce((total, activity) => total + ((activity.tasks || []).length), 0)
  }

  function completedTaskCount (project: OperationalProject): number {
    return (project.activities || []).reduce((total, activity) => {
      const completed = (activity.tasks || []).filter(task => String(task.status || '').toLowerCase() === 'done').length
      return total + completed
    }, 0)
  }

  onMounted(fetchOverview)
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

.registry-shell {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.table-actions {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  white-space: nowrap;
}

.stacked-cell {
  display: grid;
  gap: 4px;
}

.stacked-label {
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  text-transform: uppercase;
  color: #94a3b8;
}

.stacked-value {
  color: #334155;
  font-size: 0.92rem;
}

.stacked-value.strong {
  color: #0f172a;
  font-weight: 700;
}

.project-card {
  border: 1px solid rgba(15, 23, 42, 0.08);
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.95));
}

.project-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.project-actions {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
  margin-top: 16px;
  flex-wrap: wrap;
}

.modal-header {
  border-bottom: 1px solid rgba(68, 113, 196, 0.18);
  background:
    radial-gradient(760px 240px at 0% -10%, rgba(68, 113, 196, 0.22), transparent 60%),
    linear-gradient(180deg, rgba(235, 241, 255, 0.9), rgba(255, 255, 255, 0.96));
}

.modal-stepper :deep(.v-stepper-header) {
  border: 1px solid rgba(68, 113, 196, 0.16);
  border-radius: 12px;
  padding: 6px 10px;
  margin-bottom: 14px;
  background: linear-gradient(180deg, rgba(68, 113, 196, 0.08), rgba(255, 255, 255, 0.92));
}

.modal-stepper :deep(.v-stepper-item--selected .v-stepper-item__avatar) {
  background: #4471c4;
  color: #fff;
}

.section-title {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(68, 113, 196, 0.1);
  color: #233d6f;
  font-weight: 700;
  font-size: 0.85rem;
  letter-spacing: 0.01em;
}

.metric-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
}

.metric-card {
  border: 1px solid rgba(68, 113, 196, 0.2);
  border-radius: 12px;
  padding: 12px;
  background: linear-gradient(180deg, rgba(68, 113, 196, 0.08), rgba(255, 255, 255, 0.98));
  display: grid;
  gap: 4px;
}

.metric-label {
  font-size: 0.74rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: #5d6f93;
  font-weight: 700;
}

.metric-value {
  font-size: 1.15rem;
  color: #1f335a;
  line-height: 1.15;
}

.activity-panels :deep(.v-expansion-panel) {
  border: 1px solid rgba(68, 113, 196, 0.15);
  border-radius: 12px;
  background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(239, 244, 255, 0.7));
}

.task-list {
  border: 1px solid rgba(68, 113, 196, 0.14);
  border-radius: 10px;
}

.evidence-summary {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.evidence-list {
  border: 1px solid rgba(68, 113, 196, 0.16);
  border-radius: 12px;
}

@media (min-width: 960px) {
  .hero-grid {
    grid-template-columns: 2fr 1fr;
    align-items: center;
  }
}

@media (max-width: 960px) {
  .metric-grid {
    grid-template-columns: 1fr;
  }
}
</style>
