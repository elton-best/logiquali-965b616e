<template>
  <v-container class="pa-6" fluid>
    <!-- Header -->
    <v-row align="center" class="mb-6">
      <v-col cols="12" md="6">
        <h1 class="text-h3 font-weight-bold mb-2">
          <v-icon class="mr-2" icon="mdi-flash" size="36" />
          Actions QHSE
        </h1>
        <p class="text-subtitle-1 text-medium-emphasis">
          Actions correctives, préventives et d'amélioration
        </p>
      </v-col>
      <v-col class="text-right" cols="12" md="6">
        <v-btn-toggle v-model="viewMode" class="mr-3" color="primary" mandatory>
          <v-btn size="small" value="kanban">
            <v-icon start>mdi-view-column</v-icon>
            Kanban
          </v-btn>
          <v-btn size="small" value="list">
            <v-icon start>mdi-view-list</v-icon>
            Liste
          </v-btn>
          <v-btn size="small" value="gantt">
            <v-icon start>mdi-chart-gantt</v-icon>
            Gantt
          </v-btn>
        </v-btn-toggle>

        <v-btn class="mr-2" color="secondary" @click="exportToExcel">
          <v-icon start>mdi-file-excel</v-icon>
          Export
        </v-btn>

        <v-btn color="primary" elevation="2" size="large" @click="showCreateDialog = true">
          <v-icon start>mdi-plus-circle</v-icon>
          Nouvelle action
        </v-btn>
      </v-col>
    </v-row>

    <!-- Stats Cards -->
    <v-row v-if="statistics" class="mb-6">
      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-h4 font-weight-bold">{{ statistics.total }}</div>
                <div class="text-subtitle-2 text-medium-emphasis">Total actions</div>
              </div>
              <v-avatar color="primary" size="56">
                <v-icon size="32">mdi-flash</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-h4 font-weight-bold text-warning">{{ statistics.by_status?.in_progress || 0 }}</div>
                <div class="text-subtitle-2 text-medium-emphasis">En cours</div>
              </div>
              <v-avatar color="warning" size="56">
                <v-icon size="32">mdi-progress-clock</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-h4 font-weight-bold text-success">{{ statistics.by_status?.completed || 0 }}</div>
                <div class="text-subtitle-2 text-medium-emphasis">Terminées</div>
              </div>
              <v-avatar color="success" size="56">
                <v-icon size="32">mdi-check-circle</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="3" sm="6">
        <v-card elevation="2">
          <v-card-text>
            <div class="d-flex justify-space-between align-center">
              <div>
                <div class="text-h4 font-weight-bold text-error">{{ statistics.overdue_count || 0 }}</div>
                <div class="text-subtitle-2 text-medium-emphasis">En retard</div>
              </div>
              <v-avatar color="error" size="56">
                <v-icon size="32">mdi-alert-circle</v-icon>
              </v-avatar>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Filtres -->
    <v-card class="mb-6" elevation="1">
      <v-card-text>
        <v-row dense>
          <v-col cols="12" md="3">
            <v-text-field
              v-model="filters.search"
              clearable
              density="compact"
              hide-details
              label="Rechercher..."
              prepend-inner-icon="mdi-magnify"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.status"
              clearable
              density="compact"
              hide-details
              :items="statusOptions"
              label="Statut"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.priority"
              clearable
              density="compact"
              hide-details
              :items="priorityOptions"
              label="Priorité"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="2">
            <v-select
              v-model="filters.type"
              clearable
              density="compact"
              hide-details
              :items="typeOptions"
              label="Type"
              variant="outlined"
            />
          </v-col>
          <v-col cols="12" md="3">
            <v-autocomplete
              v-model="filters.responsible_id"
              clearable
              density="compact"
              hide-details
              item-title="name"
              item-value="id"
              :items="users"
              label="Responsable"
              variant="outlined"
            />
          </v-col>
        </v-row>
      </v-card-text>
    </v-card>

    <!-- Vue Kanban -->
    <ActionKanbanView
      v-if="viewMode === 'kanban'"
      :actions="actions"
      :loading="loading"
      @update-status="handleStatusUpdate"
      @view-action="viewAction"
    />

    <!-- Vue Liste -->
    <v-card v-else-if="viewMode === 'list'" elevation="2">
      <v-data-table
        :headers="headers"
        :items="actions"
        :items-per-page="filters.per_page || 15"
        :loading="loading"
      >
        <template #item.ref="{ item }">
          <v-chip color="primary" size="small" variant="outlined">
            {{ item.ref }}
          </v-chip>
        </template>

        <template #item.priority="{ item }">
          <v-chip :color="getPriorityColor(item.priority)" size="small" variant="flat">
            {{ getPriorityLabel(item.priority) }}
          </v-chip>
        </template>

        <template #item.status="{ item }">
          <v-chip :color="getStatusColor(item.status)" size="small" variant="flat">
            {{ getStatusLabel(item.status) }}
          </v-chip>
        </template>

        <template #item.progress="{ item }">
          <v-progress-linear
            :color="getProgressColor(item.progress)"
            height="20"
            :model-value="item.progress || 0"
            rounded
          >
            <template #default>
              <span class="text-caption">{{ item.progress || 0 }}%</span>
            </template>
          </v-progress-linear>
        </template>

        <template #item.deadline="{ item }">
          <div :class="isOverdue(item.deadline) ? 'text-error' : ''">
            {{ formatDate(item.deadline) }}
          </div>
        </template>

        <template #item.actions="{ item }">
          <v-menu>
            <template #activator="{ props }">
              <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
            </template>
            <v-list>
              <v-list-item @click="viewAction(item.id)">
                <template #prepend><v-icon>mdi-eye</v-icon></template>
                <v-list-item-title>Voir</v-list-item-title>
              </v-list-item>
              <v-list-item @click="deleteAction(item)">
                <template #prepend><v-icon>mdi-delete</v-icon></template>
                <v-list-item-title>Supprimer</v-list-item-title>
              </v-list-item>
            </v-list>
          </v-menu>
        </template>
      </v-data-table>
    </v-card>

    <!-- Vue Gantt -->
    <ActionGanttView
      v-else-if="viewMode === 'gantt'"
      :actions="actions"
      :loading="loading"
      @view-action="viewAction"
    />

    <!-- Dialog Création -->
    <ActionWizardDialog
      v-model="showCreateDialog"
      :users="users"
      @created="handleActionCreated"
    />
  </v-container>
</template>

<script setup lang="ts">
  import type { Action, ActionFilters, ActionStatistics } from '@/types/action'
  import { onMounted, ref, watch } from 'vue'
  import { useRouter } from 'vue-router'
  import ActionGanttView from '@/components/actions/ActionGanttView.vue'
  import ActionKanbanView from '@/components/actions/ActionKanbanView.vue'
  import ActionWizardDialog from '@/components/actions/ActionWizardDialog.vue'
  import { useSnackbar } from '@/composables/useSnackbar'
  import { actionService } from '@/services/actionService'
  import { userService } from '@/services/userService'

  const router = useRouter()
  const { showSuccess, showError } = useSnackbar()

  const viewMode = ref<'kanban' | 'list' | 'gantt'>('kanban')
  const loading = ref(false)
  const actions = ref<Action[]>([])
  const statistics = ref<ActionStatistics | null>(null)
  type ResponsibleOption = {
    id: number
    name: string
  }

  const users = ref<ResponsibleOption[]>([])
  const showCreateDialog = ref(false)

  const filters = ref<ActionFilters>({
    page: 1,
    per_page: 15,
    sort_by: 'deadline',
    sort_direction: 'asc',
  })

  const headers = [
    { title: 'Réf', key: 'ref' },
    { title: 'Titre', key: 'title' },
    { title: 'Priorité', key: 'priority' },
    { title: 'Statut', key: 'status' },
    { title: 'Progression', key: 'progress' },
    { title: 'Échéance', key: 'deadline' },
    { title: 'Responsable', key: 'responsible.name' },
    { title: 'Actions', key: 'actions', sortable: false },
  ]

  const statusOptions = [
    { title: 'Planifiée', value: 'planned' },
    { title: 'En cours', value: 'in_progress' },
    { title: 'Terminée', value: 'completed' },
    { title: 'En retard', value: 'overdue' },
  ]

  const priorityOptions = [
    { title: 'Critique', value: 'critical' },
    { title: 'Élevée', value: 'high' },
    { title: 'Moyenne', value: 'medium' },
    { title: 'Basse', value: 'low' },
  ]

  const typeOptions = [
    { title: 'Corrective', value: 'corrective' },
    { title: 'Préventive', value: 'preventive' },
    { title: 'Amélioration', value: 'improvement' },
  ]

  async function loadActions () {
    loading.value = true
    try {
      const response = await actionService.getAll(filters.value)
      actions.value = response.data
    } catch {
      showError('Erreur chargement actions')
    } finally {
      loading.value = false
    }
  }

  async function loadStatistics () {
    try {
      statistics.value = await actionService.getStatistics()
    } catch (error) {
      console.error(error)
    }
  }

  async function loadUsers () {
    try {
      const response = await userService.getAll()
      const list = Array.isArray(response)
        ? response
        : (Array.isArray((response as any)?.data)
          ? (response as any).data
          : [])

      users.value = list
        .map((user: any) => {
          const id = Number(user?.id)
          const firstName = String(user?.first_name || '').trim()
          const lastName = String(user?.last_name || '').trim()
          const fullName = `${firstName} ${lastName}`.trim()
          const fallbackName = String(user?.name || user?.username || user?.email || '').trim()
          return {
            id,
            name: fullName || fallbackName || `Utilisateur #${id}`,
          }
        })
        .filter(
          (user: ResponsibleOption) => Number.isFinite(user.id) && user.id > 0 && Boolean(user.name),
        )
    } catch (error) {
      console.error(error)
    }
  }

  function viewAction (id: number) {
    router.push(`/improvement/actions/${id}`)
  }

  async function handleStatusUpdate (actionId: number, newStatus: string) {
    try {
      await actionService.update(actionId, { status: newStatus as any })
      showSuccess('Statut mis à jour')
      await loadActions()
    } catch {
      showError('Erreur mise à jour')
    }
  }

  async function handleActionCreated () {
    showSuccess('Action créée')
    showCreateDialog.value = false
    await loadActions()
    await loadStatistics()
  }

  async function deleteAction (action: Action) {
    if (!confirm(`Supprimer l'action ${action.ref} ?`)) return

    try {
      await actionService.delete(action.id)
      showSuccess('Action supprimée')
      await loadActions()
    } catch {
      showError('Erreur suppression')
    }
  }

  async function exportToExcel () {
    try {
      const blob = await actionService.exportExcel(filters.value)
      const url = window.URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url
      a.download = `actions-${new Date().toISOString().split('T')[0]}.xlsx`
      a.click()
      showSuccess('Export réussi')
    } catch {
      showError('Erreur export')
    }
  }

  function getPriorityColor (priority: string): string {
    const colors: Record<string, string> = {
      critical: 'error',
      high: 'warning',
      medium: 'info',
      low: 'grey',
    }
    return colors[priority] || 'grey'
  }

  function getPriorityLabel (priority: string): string {
    const labels: Record<string, string> = {
      critical: 'Critique',
      high: 'Élevée',
      medium: 'Moyenne',
      low: 'Basse',
    }
    return labels[priority] || priority
  }

  function getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      planned: 'info',
      in_progress: 'warning',
      completed: 'success',
      overdue: 'error',
    }
    return colors[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      planned: 'Planifiée',
      in_progress: 'En cours',
      completed: 'Terminée',
      overdue: 'En retard',
    }
    return labels[status] || status
  }

  function getProgressColor (progress: number): string {
    if (progress >= 75) return 'success'
    if (progress >= 50) return 'info'
    if (progress >= 25) return 'warning'
    return 'error'
  }

  function formatDate (date: string | undefined): string {
    if (!date) return '-'
    return new Date(date).toLocaleDateString('fr-FR')
  }

  function isOverdue (deadline: string | undefined): boolean {
    if (!deadline) return false
    return new Date(deadline) < new Date()
  }

  watch(filters, () => {
    loadActions()
  }, { deep: true })

  onMounted(async () => {
    await Promise.all([loadActions(), loadStatistics(), loadUsers()])
  })
</script>
