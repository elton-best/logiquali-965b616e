<template>
  <ClientALayout current-page="my-tasks">
    <div class="my-tasks-page">
      <div class="header-section">
        <h1 class="page-title">Mes Tâches</h1>
        <div class="month-navigation">
          <button class="btn-month-nav" title="Mois précédent" @click="previousMonth">
            <v-icon>mdi-chevron-left</v-icon>
          </button>
          <span class="current-month">{{ formatMonthLabel(currentMonth) }}</span>
          <button class="btn-month-nav" title="Mois suivant" @click="nextMonth">
            <v-icon>mdi-chevron-right</v-icon>
          </button>
        </div>
      </div>

      <div class="filters-section">
        <v-alert
          v-if="processesError"
          class="mb-4"
          type="warning"
          variant="tonal"
        >
          {{ processesError }}
        </v-alert>
        <v-card class="filters-card">
          <v-card-text>
            <div class="filters-grid">
              <div class="filter-group">
                <label class="filter-label">Statut</label>
                <v-select
                  v-model="filters.status"
                  :items="statusOptions"
                  item-title="label"
                  item-value="value"
                  single-line
                  hide-details
                  @update:model-value="applyFilters"
                />
              </div>

              <div class="filter-group">
                <label class="filter-label">Type</label>
                <v-select
                  v-model="filters.types"
                  :items="typeOptions"
                  item-title="label"
                  item-value="value"
                  multiple
                  single-line
                  hide-details
                  @update:model-value="applyFilters"
                />
              </div>

              <div class="filter-group">
                <label class="filter-label">Processus</label>
                <v-select
                  v-model="filters.processId"
                  :items="processes"
                  item-title="name"
                  item-value="id"
                  single-line
                  hide-details
                  clearable
                  @update:model-value="applyFilters"
                />
              </div>

              <div class="filter-group filter-reset">
                <v-btn
                  variant="tonal"
                  size="small"
                  @click="resetFilters"
                >
                  <v-icon small>mdi-refresh</v-icon>
                  Réinitialiser
                </v-btn>
              </div>
            </div>
          </v-card-text>
        </v-card>
      </div>

      <div class="tasks-section">
        <v-alert
          v-if="tasksError && !loading"
          class="mb-4"
          type="warning"
          variant="tonal"
        >
          {{ tasksError }}
        </v-alert>

        <v-card v-if="loading" class="loading-card">
          <v-card-text class="text-center">
            <v-progress-circular indeterminate />
            <p class="mt-4">Chargement des tâches...</p>
          </v-card-text>
        </v-card>

        <v-card v-else-if="filteredTasks.length === 0" class="empty-card">
          <v-card-text class="text-center">
            <v-icon class="mb-2" size="48">mdi-inbox-multiple-outline</v-icon>
            <p class="mt-2">Aucune tâche pour cette période</p>
          </v-card-text>
        </v-card>

        <v-table v-else class="tasks-table">
          <thead>
            <tr>
              <th>Activité</th>
              <th>Type</th>
              <th>Début</th>
              <th>Fin</th>
              <th>Deadline</th>
              <th>Responsable</th>
              <th>Statut</th>
              <th>Taux</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="task in filteredTasks" :key="`${task.type}-${task.id}`">
              <tr class="task-row">
                <td class="task-title">
                  <div>
                    <strong>{{ task.title }}</strong>
                    <span v-if="task.process_id" class="process-badge">
                      Processus: {{ task.process_name || `#${task.process_id}` }}
                    </span>
                  </div>
                </td>
                <td>
                  <v-chip :color="getTypeColor(task.type)" :label="true" size="small">
                    {{ getTypeLabel(task.type) }}
                  </v-chip>
                </td>
                <td>{{ formatDate(task.start_date) }}</td>
                <td>{{ formatDate(task.end_date) }}</td>
                <td>
                  <strong>{{ formatDate(task.deadline) }}</strong>
                </td>
                <td>{{ task.responsible || '—' }}</td>
                <td>
                  <v-chip
                    :color="getStatusColor(task.status)"
                    :label="true"
                    size="small"
                  >
                    {{ getStatusLabel(task.status) }}
                  </v-chip>
                </td>
                <td>
                  <div class="progress-cell">
                    <v-progress-linear
                      class="progress-bar"
                      :color="getProgressColor(task.progress_rate)"
                      height="20"
                      :model-value="task.progress_rate"
                    >
                      <span class="progress-text">{{ task.progress_rate }}%</span>
                    </v-progress-linear>
                  </div>
                </td>
                <td class="actions-cell">
                  <div class="action-buttons">
                    <v-tooltip text="Mettre à jour le suivi">
                      <template #activator="{ props }">
                        <v-btn
                          v-bind="props"
                          :disabled="!task.tracking_available"
                          aria-label="Mettre à jour le suivi"
                          icon
                          size="small"
                          @click="openTrackingDialog(task)"
                        >
                          <v-icon>mdi-pencil</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>

                    <v-tooltip v-if="task.status === 'termine'" text="Télécharger le rapport">
                      <template #activator="{ props }">
                        <v-btn
                          v-bind="props"
                          aria-label="Télécharger le rapport"
                          icon
                          size="small"
                          @click="downloadReport(task)"
                        >
                          <v-icon>mdi-file-download-outline</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>

                    <v-tooltip v-if="task.has_tracking" text="Voir l'historique">
                      <template #activator="{ props }">
                        <v-btn
                          v-bind="props"
                          aria-label="Voir l'historique"
                          icon
                          size="small"
                          @click="toggleHistory(task)"
                        >
                          <v-icon>mdi-history</v-icon>
                        </v-btn>
                      </template>
                    </v-tooltip>
                  </div>
                </td>
              </tr>

              <tr
                v-show="expandedHistories[`${task.type}-${task.id}`]"
                class="history-row"
              >
                <td class="history-cell" colspan="9">
                  <HistoryTimeline
                    :task-id="task.id"
                    :task-type="task.type"
                  />
                </td>
              </tr>
            </template>
          </tbody>
        </v-table>
      </div>

      <v-dialog
        v-model="trackingDialog.open"
        max-width="500"
      >
        <v-card>
          <v-card-title>Mise à jour du suivi</v-card-title>
          <v-card-text>
            <div v-if="trackingDialog.task" class="dialog-content">
              <div class="task-info">
                <p><strong>{{ trackingDialog.task.title }}</strong></p>
                <p class="text-caption">{{ getTypeLabel(trackingDialog.task.type) }}</p>
              </div>

              <div class="mt-4">
                <label class="label-bold">Statut</label>
                <v-radio-group v-model="trackingDialog.form.status">
                  <v-radio label="Non démarré" value="non_demarre" />
                  <v-radio label="En cours" value="en_cours" />
                  <v-radio label="Terminé" value="termine" />
                </v-radio-group>
              </div>

              <div class="mt-4">
                <label class="label-bold">Taux de progression : {{ trackingDialog.form.progress_rate }}%</label>
                <v-slider
                  v-model="trackingDialog.form.progress_rate"
                  :disabled="isProgressDisabled(trackingDialog.form.status)"
                  :max="getProgressMax(trackingDialog.form.status)"
                  :min="getProgressMin(trackingDialog.form.status)"
                  :step="1"
                  thumb-label="always"
                />
              </div>

              <div class="mt-4">
                <label class="label-bold">Notes</label>
                <v-textarea
                  v-model="trackingDialog.form.notes"
                  placeholder="Ajouter des remarques (optionnel)"
                  rows="3"
                />
              </div>
            </div>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn
              variant="tonal"
              @click="trackingDialog.open = false"
            >
              Annuler
            </v-btn>
            <v-btn
              color="primary"
              :loading="trackingDialog.submitting"
              @click="submitTracking"
            >
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </ClientALayout>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/client'
import { useMyTasks } from '@/composables/useMyTasks'
import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
import { useNotification } from '@/plugins/notification'
import HistoryTimeline from './components/HistoryTimeline.vue'

const notification = useNotification()
const {
  fetchMyTasks,
  submitTracking: apiSubmitTracking,
  downloadReport: apiDownloadReport,
} = useMyTasks()
const route = useRoute()
const router = useRouter()

const loading = ref(false)
const currentMonth = ref(new Date().toISOString().slice(0, 7))
const tasks = ref([])
const expandedHistories = ref({})
const tasksError = ref('')
const processesError = ref('')

const filters = ref({
  status: 'all',
  types: [],
  processId: null,
})

const trackingDialog = ref({
  open: false,
  task: null,
  submitting: false,
  form: {
    status: 'non_demarre',
    progress_rate: 0,
    notes: '',
  },
})

const statusOptions = [
  { label: 'Tous les statuts', value: 'all' },
  { label: 'Non démarré', value: 'non_demarre' },
  { label: 'En cours', value: 'en_cours' },
  { label: 'Terminé', value: 'termine' },
]

const typeOptions = [
  { label: 'Action', value: 'action' },
  { label: 'Audit', value: 'audit' },
  { label: 'Formation', value: 'formation' },
  { label: 'Communication', value: 'communication' },
  { label: 'Non-conformité', value: 'non_conformity' },
  { label: 'Objectif', value: 'objective' },
  { label: 'Risque', value: 'risk' },
  { label: 'Plan d\'action', value: 'plan_action' },
  { label: 'Maintenance', value: 'maintenance_plan' },
  { label: 'Calibration', value: 'calibration_plan' },
  { label: 'Activité projet', value: 'operational_project_activity' },
  { label: 'Tâche projet', value: 'operational_project_task' },
]

const processes = ref([])

const filteredTasks = computed(() => {
  let filtered = tasks.value

  if (filters.value.status !== 'all') {
    filtered = filtered.filter((task) => task.status === filters.value.status)
  }

  if (filters.value.types.length > 0) {
    filtered = filtered.filter((task) => filters.value.types.includes(task.type))
  }

  if (filters.value.processId) {
    filtered = filtered.filter((task) => task.process_id === filters.value.processId)
  }

  return filtered.toSorted((a, b) => new Date(a.deadline) - new Date(b.deadline))
})

function formatMonthLabel (monthStr) {
  const date = new Date(`${monthStr}-01`)
  return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' })
}

function formatDate (dateStr) {
  if (!dateStr) return '—'
  const date = new Date(`${dateStr}T00:00:00`)
  return date.toLocaleDateString('fr-FR')
}

function getTypeLabel (type) {
  const map = {
    action: 'Action',
    audit: 'Audit',
    formation: 'Formation',
    communication: 'Communication',
    non_conformity: 'Non-conformité',
    objective: 'Objectif',
    risk: 'Risque',
    plan_action: 'Plan d\'action',
    maintenance_plan: 'Maintenance',
    calibration_plan: 'Calibration',
    compliance_obligation_action: 'Obligation Conformité',
    stakeholder_requirement_action: 'Req. Parties Prenantes',
    process_risk_opportunity: 'Risque/Opportunité',
    operational_project_activity: 'Activité Projet',
    operational_project_task: 'Tâche Projet',
  }
  return map[type] || type
}

function getTypeColor (type) {
  const colors = {
    action: 'red',
    audit: 'orange',
    formation: 'blue',
    communication: 'green',
    non_conformity: 'purple',
    objective: 'indigo',
    risk: 'error',
    plan_action: 'warning',
    maintenance_plan: 'teal',
    calibration_plan: 'cyan',
  }
  return colors[type] || 'grey'
}

function getStatusLabel (status) {
  const map = {
    non_demarre: 'Non démarré',
    en_cours: 'En cours',
    termine: 'Terminé',
  }
  return map[status] || status
}

function getStatusColor (status) {
  const colors = {
    non_demarre: 'grey',
    en_cours: 'blue',
    termine: 'green',
  }
  return colors[status] || 'grey'
}

function getProgressColor (rate) {
  if (rate < 33) return 'error'
  if (rate < 66) return 'warning'
  return 'success'
}

function getProgressMin (status) {
  if (status === 'non_demarre') return 0
  if (status === 'termine') return 100
  return 1
}

function getProgressMax (status) {
  if (status === 'non_demarre') return 0
  if (status === 'termine') return 100
  return 99
}

function isProgressDisabled (status) {
  return status === 'non_demarre' || status === 'termine'
}

function previousMonth () {
  const date = new Date(`${currentMonth.value}-01`)
  date.setMonth(date.getMonth() - 1)
  currentMonth.value = date.toISOString().slice(0, 7)
  loadTasks()
}

function nextMonth () {
  const date = new Date(`${currentMonth.value}-01`)
  date.setMonth(date.getMonth() + 1)
  currentMonth.value = date.toISOString().slice(0, 7)
  loadTasks()
}

function applyFilters () {
  // Filters are applied by filteredTasks computed state.
}

function resetFilters () {
  filters.value = {
    status: 'all',
    types: [],
    processId: null,
  }
}

function openTrackingDialog (task) {
  trackingDialog.value.task = task
  trackingDialog.value.form = {
    status: task.status || 'non_demarre',
    progress_rate: task.progress_rate || 0,
    notes: '',
  }
  trackingDialog.value.open = true
}

function toggleHistory (task) {
  const key = `${task.type}-${task.id}`
  expandedHistories.value[key] = !expandedHistories.value[key]
}

async function submitTracking () {
  trackingDialog.value.submitting = true
  try {
    const task = trackingDialog.value.task
    await apiSubmitTracking(task.type, task.id, trackingDialog.value.form)
    notification.success('Suivi mis à jour avec succès')
    trackingDialog.value.open = false
    await loadTasks()
  } catch (error) {
    notification.error(error?.message || 'Erreur lors de la mise à jour')
  } finally {
    trackingDialog.value.submitting = false
  }
}

async function downloadReport (task) {
  try {
    await apiDownloadReport(task.type, task.id)
    notification.success('Rapport téléchargé')
  } catch (error) {
    notification.error(error?.message || 'Erreur lors du téléchargement')
  }
}

async function loadProcesses () {
  try {
    processesError.value = ''
    const response = await api.get('/processes')
    const payload = response.data
    processes.value = Array.isArray(payload?.data)
      ? payload.data
      : (Array.isArray(payload) ? payload : [])
  } catch {
    processes.value = []
    processesError.value = 'Le chargement des processus est indisponible pour le moment. Vous pouvez continuer sans ce filtre.'
  }
}

async function loadTasks () {
  loading.value = true
  try {
    tasksError.value = ''
    const data = await fetchMyTasks({
      month: currentMonth.value,
      status: filters.value.status === 'all' ? undefined : filters.value.status,
      type: filters.value.types.length > 0 ? filters.value.types : undefined,
      process_id: filters.value.processId,
    })
    tasks.value = data
    await maybeOpenTrackingFromQuery()
  } catch (error) {
    tasks.value = []
    tasksError.value = error?.message || 'Impossible de charger les tâches pour cette période.'
    notification.error(error?.message || 'Erreur lors du chargement')
  } finally {
    loading.value = false
  }
}

async function maybeOpenTrackingFromQuery () {
  const preselectType = String(route.query.preselect_type || '').trim()
  const preselectId = Number(route.query.preselect_id || 0)
  if (!preselectType || !Number.isFinite(preselectId) || preselectId <= 0) {
    return
  }

  const match = tasks.value.find(task => task.type === preselectType && Number(task.id) === preselectId)
  if (match && match.tracking_available) {
    openTrackingDialog(match)
  }

  const nextQuery = { ...route.query }
  delete nextQuery.preselect_type
  delete nextQuery.preselect_id
  router.replace({ query: nextQuery })
}

onMounted(() => {
  loadProcesses()
  loadTasks()
})
</script>

<style scoped lang="scss">
.my-tasks-page {
  padding: 20px;
  max-width: 1400px;
  margin: 0 auto;

  .header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;

    .page-title {
      font-size: 28px;
      font-weight: 600;
      margin: 0;
    }

    .month-navigation {
      display: flex;
      align-items: center;
      gap: 15px;

      .current-month {
        min-width: 150px;
        text-align: center;
        font-weight: 500;
        font-size: 16px;
      }

      .btn-month-nav {
        padding: 8px;
        min-width: auto;
      }
    }
  }

  .filters-section {
    margin-bottom: 24px;

    .filters-card {
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .filters-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      align-items: flex-end;

      .filter-group {
        &.filter-reset {
          align-self: flex-end;
        }

        .filter-label {
          display: block;
          font-size: 12px;
          font-weight: 500;
          margin-bottom: 8px;
          color: rgba(0, 0, 0, 0.7);
        }
      }
    }
  }

  .tasks-section {
    .loading-card,
    .empty-card {
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    }

    .tasks-table {
      .task-row {
        &:hover {
          background-color: rgba(0, 0, 0, 0.02);
        }

        .task-title {
          font-weight: 500;

          .process-badge {
            display: inline-block;
            margin-left: 8px;
            padding: 2px 6px;
            background: rgba(0, 0, 0, 0.05);
            border-radius: 3px;
            font-size: 11px;
            color: rgba(0, 0, 0, 0.6);
          }
        }

        .progress-cell {
          min-width: 120px;

          .progress-bar {
            position: relative;
            overflow: visible;

            .progress-text {
              position: absolute;
              width: 100%;
              height: 100%;
              display: flex;
              align-items: center;
              justify-content: center;
              font-size: 12px;
              font-weight: 500;
              color: rgba(0, 0, 0, 0.8);
            }
          }
        }

        .actions-cell {
          .action-buttons {
            display: flex;
            gap: 4px;
          }
        }
      }

      .history-row {
        .history-cell {
          padding: 16px !important;
          background-color: rgba(0, 0, 0, 0.02);
        }
      }
    }
  }

  .dialog-content {
    .task-info {
      padding: 12px;
      background: rgba(0, 0, 0, 0.02);
      border-radius: 4px;
      margin-bottom: 16px;

      p {
        margin: 0;

        &.text-caption {
          font-size: 12px;
          color: rgba(0, 0, 0, 0.6);
        }
      }
    }

    .label-bold {
      font-weight: 500;
      display: block;
      margin-bottom: 8px;
    }
  }
}
</style>
