<template>
  <ClientALayout current-page="my-actions">
    <v-container class="pa-6" fluid>
      <PageHeader
        icon="mdi-format-list-checks"
        subtitle="Consultez et suivez toutes vos actions assignées à travers l'ensemble des modules"
        title="Mes Actions"
      >
        <template #actions>
          <ColumnVisibilitySelector
            v-model="visibleColumnKeys"
            :columns="allColumns"
            storage-key="my_actions_columns"
          />
        </template>
      </PageHeader>

      <!-- KPI Summary Cards with clickable filters (RT-08) -->
      <v-row class="mb-4">
        <v-col cols="12" sm="6" md="2-4" lg="2-4">
          <v-card
            class="pa-4 cursor-pointer transition-all"
            :elevation="activeStatus === 'all' ? 4 : 1"
            rounded="lg"
            :variant="activeStatus === 'all' ? 'elevated' : 'outlined'"
            @click="activeStatus = 'all'"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-medium">Total de mes actions</div>
                <div class="text-h5 font-weight-bold mt-1 text-primary">{{ myActions.length }}</div>
              </div>
              <v-avatar color="primary-lighten-4" rounded="lg">
                <v-icon color="primary">mdi-format-list-checks</v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2-4" lg="2-4">
          <v-card
            class="pa-4 cursor-pointer transition-all"
            :elevation="activeStatus === 'assigned' ? 4 : 1"
            rounded="lg"
            :variant="activeStatus === 'assigned' ? 'elevated' : 'outlined'"
            @click="activeStatus = 'assigned'"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-medium">À faire</div>
                <div class="text-h5 font-weight-bold mt-1 text-info">{{ assignedCount }}</div>
              </div>
              <v-avatar color="info-lighten-4" rounded="lg">
                <v-icon color="info">mdi-clock-outline</v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2-4" lg="2-4">
          <v-card
            class="pa-4 cursor-pointer transition-all"
            :elevation="activeStatus === 'in_progress' ? 4 : 1"
            rounded="lg"
            :variant="activeStatus === 'in_progress' ? 'elevated' : 'outlined'"
            @click="activeStatus = 'in_progress'"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-medium">En cours</div>
                <div class="text-h5 font-weight-bold mt-1 text-warning">{{ inProgressCount }}</div>
              </div>
              <v-avatar color="warning-lighten-4" rounded="lg">
                <v-icon color="warning">mdi-progress-clock</v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2-4" lg="2-4">
          <v-card
            class="pa-4 cursor-pointer transition-all"
            :elevation="activeStatus === 'overdue' ? 4 : 1"
            rounded="lg"
            :variant="activeStatus === 'overdue' ? 'elevated' : 'outlined'"
            @click="activeStatus = 'overdue'"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-medium">En retard</div>
                <div class="text-h5 font-weight-bold mt-1 text-error">{{ overdueCount }}</div>
              </div>
              <v-avatar color="error-lighten-4" rounded="lg">
                <v-icon color="error">mdi-alert-circle-outline</v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>

        <v-col cols="12" sm="6" md="2-4" lg="2-4">
          <v-card
            class="pa-4 cursor-pointer transition-all"
            :elevation="activeStatus === 'completed' ? 4 : 1"
            rounded="lg"
            :variant="activeStatus === 'completed' ? 'elevated' : 'outlined'"
            @click="activeStatus = 'completed'"
          >
            <div class="d-flex align-center justify-space-between">
              <div>
                <div class="text-caption text-grey-darken-1 font-weight-medium">Réalisées</div>
                <div class="text-h5 font-weight-bold mt-1 text-success">{{ completedCount }}</div>
              </div>
              <v-avatar color="success-lighten-4" rounded="lg">
                <v-icon color="success">mdi-check-circle-outline</v-icon>
              </v-avatar>
            </div>
          </v-card>
        </v-col>
      </v-row>

      <!-- Search & Filters -->
      <v-card class="mb-6" elevation="0" rounded="lg">
        <v-card-text>
          <v-row align="center">
            <v-col cols="12" md="4">
              <v-text-field
                v-model="searchQuery"
                clearable
                density="comfortable"
                hide-details
                placeholder="Rechercher par titre, référence..."
                prepend-inner-icon="mdi-magnify"
                rounded="lg"
                variant="outlined"
              />
            </v-col>

            <v-col cols="12" md="8" class="d-flex flex-wrap align-center gap-2">
              <span class="text-caption font-weight-bold text-grey-darken-1 mr-2">Filtre statut (RT-08) :</span>
              <StatusFilterChip
                :active="activeStatus === 'all'"
                :count="myActions.length"
                label="Toutes"
                status="all"
                @click="activeStatus = 'all'"
              />
              <StatusFilterChip
                :active="activeStatus === 'assigned'"
                color="info"
                :count="assignedCount"
                label="À faire"
                status="assigned"
                @click="activeStatus = 'assigned'"
              />
              <StatusFilterChip
                :active="activeStatus === 'in_progress'"
                color="warning"
                :count="inProgressCount"
                label="En cours"
                status="in_progress"
                @click="activeStatus = 'in_progress'"
              />
              <StatusFilterChip
                :active="activeStatus === 'overdue'"
                color="error"
                :count="overdueCount"
                label="En retard"
                status="overdue"
                @click="activeStatus = 'overdue'"
              />
              <StatusFilterChip
                :active="activeStatus === 'completed'"
                color="success"
                :count="completedCount"
                label="Réalisées"
                status="completed"
                @click="activeStatus = 'completed'"
              />
            </v-col>
          </v-row>
        </v-card-text>
      </v-card>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <v-progress-circular color="primary" indeterminate size="48" />
        <p class="mt-4 text-grey">Chargement de vos actions...</p>
      </div>

      <!-- Empty State -->
      <v-card v-else-if="filteredSortedActions.length === 0" class="pa-8 text-center" rounded="lg" variant="outlined">
        <v-icon color="grey-lighten-1" size="56">mdi-checkbox-marked-circle-outline</v-icon>
        <h3 class="text-h6 mt-3 font-weight-bold">Aucune action correspondante</h3>
        <p class="text-body-2 text-grey">
          {{ searchQuery || activeStatus !== 'all' ? 'Aucune action ne correspond à vos critères de recherche.' : 'Vous n\'avez aucune action assignée pour le moment.' }}
        </p>
        <v-btn
          v-if="searchQuery || activeStatus !== 'all'"
          class="mt-3"
          color="primary"
          rounded="lg"
          variant="tonal"
          @click="resetFilters"
        >
          Réinitialiser les filtres
        </v-btn>
      </v-card>

      <!-- Actions Table with Double Scroll (RT-05) and Sticky Columns -->
      <DoubleScrollWrapper
        v-else
        :has-top-scroll="true"
        :sticky-first-col="true"
        :sticky-last-col="true"
      >
        <v-table class="my-actions-table elevation-1 rounded-lg">
          <thead>
            <tr>
              <th v-if="isColVisible('reference')" class="font-weight-bold">Référence</th>
              <th v-if="isColVisible('title')" class="font-weight-bold">Titre de l'action</th>
              <th v-if="isColVisible('type')" class="font-weight-bold">Type</th>
              <th v-if="isColVisible('priority')" class="font-weight-bold">Priorité</th>
              <th v-if="isColVisible('due_date')" class="font-weight-bold cursor-pointer" @click="toggleSortDue">
                Échéance
                <v-icon size="small">{{ sortAsc ? 'mdi-arrow-up' : 'mdi-arrow-down' }}</v-icon>
              </th>
              <th v-if="isColVisible('status')" class="font-weight-bold">Statut</th>
              <th v-if="isColVisible('progress')" class="font-weight-bold">Progression</th>
              <th v-if="isColVisible('actions')" class="font-weight-bold text-right">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="action in filteredSortedActions"
              :key="action.id"
              :class="{ 'row-overdue': isActionOverdue(action) }"
            >
              <td v-if="isColVisible('reference')">
                <span class="font-mono text-caption font-weight-bold text-primary">{{ action.reference || `#${action.id}` }}</span>
              </td>
              <td v-if="isColVisible('title')">
                <div class="font-weight-medium text-body-2">{{ action.title }}</div>
                <div v-if="action.description" class="text-caption text-grey text-truncate max-w-xs">
                  {{ action.description }}
                </div>
              </td>
              <td v-if="isColVisible('type')">
                <v-chip
                  density="compact"
                  size="small"
                  :color="action.type === 'corrective' ? 'warning' : 'info'"
                  variant="tonal"
                >
                  {{ action.type === 'corrective' ? 'Corrective' : 'Préventive' }}
                </v-chip>
              </td>
              <td v-if="isColVisible('priority')">
                <v-chip
                  density="compact"
                  size="small"
                  :color="getPriorityColor(action.priority)"
                  variant="outlined"
                >
                  {{ getPriorityLabel(action.priority) }}
                </v-chip>
              </td>
              <td v-if="isColVisible('due_date')">
                <div class="d-flex align-center">
                  <v-icon
                    v-if="isActionOverdue(action)"
                    class="mr-1"
                    color="error"
                    size="small"
                  >
                    mdi-alert-circle
                  </v-icon>
                  <span :class="{ 'text-error font-weight-bold': isActionOverdue(action) }">
                    {{ formatDate(action.due_date) }}
                  </span>
                </div>
              </td>
              <td v-if="isColVisible('status')">
                <v-chip
                  class="cursor-pointer"
                  density="compact"
                  size="small"
                  :color="getStatusColor(action.status, isActionOverdue(action))"
                  variant="flat"
                  @click="activeStatus = isActionOverdue(action) ? 'overdue' : String(action.status)"
                >
                  {{ getStatusLabel(action.status, isActionOverdue(action)) }}
                </v-chip>
              </td>
              <td v-if="isColVisible('progress')" style="min-width: 130px;">
                <div class="d-flex align-center">
                  <v-progress-linear
                    class="mr-2"
                    :color="Number(action.progress) >= 100 ? 'success' : 'primary'"
                    height="6"
                    :model-value="Number(action.progress || 0)"
                    rounded
                  />
                  <span class="text-caption font-weight-bold">{{ Number(action.progress || 0) }}%</span>
                </div>
              </td>
              <td v-if="isColVisible('actions')" class="text-right">
                <v-btn
                  color="primary"
                  density="comfortable"
                  icon="mdi-pencil-outline"
                  size="small"
                  title="Mettre à jour la progression"
                  variant="text"
                  @click="openProgressDialog(action)"
                />
              </td>
            </tr>
          </tbody>
        </v-table>
      </DoubleScrollWrapper>

      <!-- Progress Update Dialog -->
      <v-dialog v-model="progressDialog.open" max-width="500">
        <v-card v-if="progressDialog.action" rounded="lg">
          <v-card-title class="pa-4 bg-grey-lighten-4 font-weight-bold text-subtitle-1">
            Mettre à jour la progression
          </v-card-title>
          <v-card-text class="pa-4">
            <div class="text-caption font-weight-bold text-grey-darken-1 mb-1">
              {{ progressDialog.action.reference || `#${progressDialog.action.id}` }} - {{ progressDialog.action.title }}
            </div>
            <div class="my-4">
              <label class="text-caption font-weight-bold d-block mb-2">
                Progression : {{ progressDialog.progress }}%
              </label>
              <v-slider
                v-model="progressDialog.progress"
                color="primary"
                max="100"
                min="0"
                step="5"
                thumb-label
              />
            </div>
            <v-textarea
              v-model="progressDialog.note"
              auto-grow
              label="Commentaire / Avancement"
              placeholder="Décrivez les avancées ou difficultés..."
              rounded="lg"
              rows="3"
              variant="outlined"
            />
          </v-card-text>
          <v-card-actions class="pa-4 border-t">
            <v-spacer />
            <v-btn variant="text" @click="progressDialog.open = false">Annuler</v-btn>
            <v-btn
              color="primary"
              :loading="progressDialog.saving"
              rounded="lg"
              @click="submitProgressDialog"
            >
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
import PageHeader from '@/modules/clienta/components/PageHeader.vue'
import ColumnVisibilitySelector, { type ColumnDefinition } from '@/modules/shared/components/ColumnVisibilitySelector.vue'
import DoubleScrollWrapper from '@/modules/shared/components/DoubleScrollWrapper.vue'
import StatusFilterChip from '@/modules/shared/components/StatusFilterChip.vue'
import { useActions } from '@/modules/clienta/composables/useActions'
import { useAuthStore } from '@/stores/auth'

const authStore = useAuthStore()
const { actions, loading, fetchActions, updateProgress } = useActions()

const currentUserId = computed(() => Number((authStore.user as any)?.id || 0))
const searchQuery = ref('')
const activeStatus = ref<'all' | 'assigned' | 'in_progress' | 'completed' | 'overdue'>('all')
const sortAsc = ref(true)

// Column configuration (RT-04)
const allColumns: ColumnDefinition[] = [
  { key: 'reference', title: 'Référence', mandatory: true },
  { key: 'title', title: "Titre de l'action", mandatory: true },
  { key: 'type', title: 'Type', defaultVisible: true },
  { key: 'priority', title: 'Priorité', defaultVisible: true },
  { key: 'due_date', title: 'Échéance', defaultVisible: true },
  { key: 'status', title: 'Statut', defaultVisible: true },
  { key: 'progress', title: 'Progression', defaultVisible: true },
  { key: 'actions', title: 'Actions', mandatory: true },
]
const visibleColumnKeys = ref<string[]>(allColumns.map(c => c.key))

function isColVisible(key: string): boolean {
  return visibleColumnKeys.value.includes(key)
}

// RT-13: Filter strictly to user's actions
const myActions = computed(() => {
  const uid = currentUserId.value
  return actions.value.filter(a => {
    if (!uid) return true
    return Number(a.responsible_id) === uid
  })
})

function isActionOverdue(action: any): boolean {
  if (['completed', 'verified', 'closed', 'cancelled'].includes(String(action.status || ''))) {
    return false
  }
  if (!action.due_date) return false
  return new Date(action.due_date) < new Date()
}

// Counts
const assignedCount = computed(() => myActions.value.filter(a => a.status === 'assigned' || a.status === 'draft').length)
const inProgressCount = computed(() => myActions.value.filter(a => a.status === 'in_progress' && !isActionOverdue(a)).length)
const overdueCount = computed(() => myActions.value.filter(isActionOverdue).length)
const completedCount = computed(() => myActions.value.filter(a => ['completed', 'verified', 'closed'].includes(String(a.status || ''))).length)

// Filter & Sort
const filteredSortedActions = computed(() => {
  let list = [...myActions.value]

  // Status Filter
  if (activeStatus.value === 'assigned') {
    list = list.filter(a => a.status === 'assigned' || a.status === 'draft')
  } else if (activeStatus.value === 'in_progress') {
    list = list.filter(a => a.status === 'in_progress' && !isActionOverdue(a))
  } else if (activeStatus.value === 'overdue') {
    list = list.filter(isActionOverdue)
  } else if (activeStatus.value === 'completed') {
    list = list.filter(a => ['completed', 'verified', 'closed'].includes(String(a.status || '')))
  }

  // Search Filter
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase().trim()
    list = list.filter(a =>
      a.title?.toLowerCase().includes(q) ||
      a.reference?.toLowerCase().includes(q) ||
      a.description?.toLowerCase().includes(q),
    )
  }

  // Sort by due date (RT-13)
  list.sort((a, b) => {
    const da = a.due_date ? new Date(a.due_date).getTime() : Infinity
    const db = b.due_date ? new Date(b.due_date).getTime() : Infinity
    return sortAsc.value ? da - db : db - da
  })

  return list
})

function toggleSortDue() {
  sortAsc.value = !sortAsc.value
}

function resetFilters() {
  searchQuery.value = ''
  activeStatus.value = 'all'
}

function formatDate(dateStr?: string | null): string {
  if (!dateStr) return '—'
  try {
    return new Date(dateStr).toLocaleDateString('fr-FR')
  } catch {
    return dateStr
  }
}

function getPriorityColor(priority?: string): string {
  switch (priority) {
    case 'urgent': return 'error'
    case 'high': return 'error'
    case 'medium': return 'warning'
    default: return 'info'
  }
}

function getPriorityLabel(priority?: string): string {
  switch (priority) {
    case 'urgent': return 'Urgente'
    case 'high': return 'Haute'
    case 'medium': return 'Moyenne'
    default: return 'Basse'
  }
}

function getStatusColor(status?: string, overdue?: boolean): string {
  if (overdue) return 'error'
  switch (status) {
    case 'completed':
    case 'verified':
    case 'closed': return 'success'
    case 'in_progress': return 'warning'
    case 'assigned': return 'info'
    default: return 'grey'
  }
}

function getStatusLabel(status?: string, overdue?: boolean): string {
  if (overdue) return 'En retard'
  switch (status) {
    case 'completed': return 'Terminé'
    case 'verified': return 'Vérifiée'
    case 'closed': return 'Fermée'
    case 'in_progress': return 'En cours'
    case 'assigned': return 'Assignée'
    default: return 'Brouillon'
  }
}

// Progress Dialog
const progressDialog = ref({
  open: false,
  action: null as any,
  progress: 0,
  note: '',
  saving: false,
})

function openProgressDialog(action: any) {
  progressDialog.value.action = action
  progressDialog.value.progress = Number(action.progress || 0)
  progressDialog.value.note = ''
  progressDialog.value.open = true
}

async function submitProgressDialog() {
  const target = progressDialog.value.action
  if (!target) return
  progressDialog.value.saving = true
  try {
    await updateProgress(Number(target.id), {
      progress: Number(progressDialog.value.progress || 0),
      comments: progressDialog.value.note?.trim() || undefined,
    })
    progressDialog.value.open = false
    await fetchActions()
  } catch (err) {
    console.error('Failed to update progress:', err)
  } finally {
    progressDialog.value.saving = false
  }
}

onMounted(() => {
  fetchActions()
})
</script>

<style scoped>
.my-actions-table {
  width: 100%;
}
.row-overdue {
  background-color: rgba(var(--v-theme-error), 0.04);
}
.cursor-pointer {
  cursor: pointer;
}
</style>

