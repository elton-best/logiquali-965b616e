<template>
  <ClientALayout current-page="iso-operations">
    <PageHeader
      icon="mdi-calendar-check"
      subtitle="Consolidation des actions et planification opérationnelle multi-niveaux"
      title="Planification et maîtrise opérationnelle"
    >
      <template #actions>
        <v-btn
          color="primary"
          prepend-icon="mdi-file-document-plus-outline"
          variant="tonal"
          @click="procedureDialog = true"
        >
          Ajouter une procédure
        </v-btn>
      </template>
    </PageHeader>

    <OperationalHero
      :consolidated-count="consolidatedActions.length"
      :near-due-count="nearDueActionsCount"
      :project-count="projects.length"
    />

    <!-- <ProceduresPanel ref="proceduresPanel" /> -->

    <OperationalTabs v-model="activeTab" />

    <v-window v-model="activeTab">
      <v-window-item value="actions">
        <ConsolidatedActionsSection
          :action-headers="actionHeaders"
          :actions-view-mode="actionsViewMode"
          :empty-state-message="emptyActionStateMessage"
          :filtered-actions="filteredConsolidatedActions"
          :format-priority-label="formatPriorityLabel"
          :format-source-label="formatSourceLabel"
          :format-status-label="formatStatusLabel"
          :has-active-filters="hasActiveActionFilters"
          :loading="loading"
          :priority-color="priorityColor"
          :search="search"
          :source-filter="sourceFilter"
          :source-filter-items="sourceFilterItems"
          :status-filter="statusFilter"
          :status-filter-items="statusFilterItems"
          @reset="resetActionFilters"
          @update:actions-view-mode="actionsViewMode = $event"
          @update:search="search = $event"
          @update:source-filter="sourceFilter = $event"
          @update:status-filter="statusFilter = $event"
        />
      </v-window-item>

      <v-window-item value="projects">
        <ProjectsSection
          :format-priority-label="formatPriorityLabel"
          :format-status-label="formatStatusLabel"
          :priority-color="priorityColor"
          :project-count-by-status="projectCountByStatus"
          :projects="projects"
          :projects-view-mode="projectsViewMode"
          @create-activity="openCreateActivityDialog"
          @create-project="openCreateProjectDialog"
          @create-task="openCreateTaskDialog"
          @delete-activity="deleteActivity"
          @delete-project="deleteProject"
          @delete-task="deleteTask"
          @edit-activity="openEditActivityDialog"
          @edit-project="openEditProjectDialog"
          @edit-task="openEditTaskDialog"
          @track-activity="openTrackingForOperationalActivity"
          @track-task="openTrackingForOperationalTask"
          @update:projects-view-mode="projectsViewMode = $event"
        />
      </v-window-item>
    </v-window>

    <v-dialog v-model="projectDialog" max-width="760">
      <v-card>
        <v-card-title>{{ editingProjectId ? 'Modifier projet' : 'Nouveau projet' }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12" md="6"><v-text-field v-model="projectForm.title" label="Titre" required /></v-col>
            <v-col cols="12" md="6"><v-text-field v-model="projectForm.code" label="Code" /></v-col>
            <v-col cols="12"><v-textarea v-model="projectForm.description" label="Description" rows="2" /></v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="projectForm.status"
                item-title="title"
                item-value="value"
                :items="projectStatuses"
                label="Statut"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="projectForm.priority"
                item-title="title"
                item-value="value"
                :items="priorities"
                label="Priorité"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="projectForm.project_manager_id"
                item-title="title"
                item-value="value"
                :items="userOptions"
                label="Responsable projet"
              />
            </v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="projectForm.start_date" label="Début" mode="date" /></v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="projectForm.due_date" label="Échéance" mode="date" /></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="projectDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveProject">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="activityDialog" max-width="760">
      <v-card>
        <v-card-title>{{ editingActivityId ? 'Modifier activité' : 'Nouvelle activité' }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12"><v-text-field v-model="activityForm.title" label="Titre" required /></v-col>
            <v-col cols="12"><v-textarea v-model="activityForm.description" label="Description" rows="2" /></v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="activityForm.status"
                item-title="title"
                item-value="value"
                :items="projectStatuses"
                label="Statut"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="activityForm.priority"
                item-title="title"
                item-value="value"
                :items="priorities"
                label="Priorité"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="activityForm.responsible_user_id"
                item-title="title"
                item-value="value"
                :items="userOptions"
                label="Responsable activité"
              />
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="activityForm.assigned_user_ids"
                chips
                item-title="title"
                item-value="value"
                :items="userOptions"
                label="Responsables impliqués"
                multiple
              />
            </v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="activityForm.start_date" label="Début" mode="date" /></v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="activityForm.due_date" label="Échéance" mode="date" /></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="activityDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveActivity">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-dialog v-model="taskDialog" max-width="760">
      <v-card>
        <v-card-title>{{ editingTaskId ? 'Modifier tâche' : 'Nouvelle tâche' }}</v-card-title>
        <v-card-text>
          <v-row>
            <v-col cols="12"><v-text-field v-model="taskForm.title" label="Titre" required /></v-col>
            <v-col cols="12"><v-textarea v-model="taskForm.description" label="Description" rows="2" /></v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="taskForm.status"
                item-title="title"
                item-value="value"
                :items="taskStatuses"
                label="Statut"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="taskForm.priority"
                item-title="title"
                item-value="value"
                :items="priorities"
                label="Priorité"
              />
            </v-col>
            <v-col cols="12" md="4">
              <v-select
                v-model="taskForm.responsible_user_id"
                item-title="title"
                item-value="value"
                :items="userOptions"
                label="Responsable tâche"
              />
            </v-col>
            <v-col cols="12">
              <v-select
                v-model="taskForm.assigned_user_ids"
                chips
                item-title="title"
                item-value="value"
                :items="userOptions"
                label="Responsables impliqués"
                multiple
              />
            </v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="taskForm.start_date" label="Début" mode="date" /></v-col>
            <v-col cols="12" md="6"><AppDatePickerField v-model="taskForm.due_date" label="Échéance" mode="date" /></v-col>
          </v-row>
        </v-card-text>
        <v-card-actions>
          <v-spacer />
          <v-btn variant="text" @click="taskDialog = false">Annuler</v-btn>
          <v-btn color="primary" @click="saveTask">Enregistrer</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <ProcedureUploadDialog v-model="procedureDialog" @created="proceduresPanel?.refresh()" />
  </ClientALayout>
</template>

<script setup lang="ts">
  import type ProceduresPanel from '@/modules/clienta/components/documents/ProceduresPanel.vue'
  import { computed, onMounted, ref } from 'vue'
  import { useI18n } from 'vue-i18n'
  import { useRouter } from 'vue-router'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import ProcedureUploadDialog from '@/modules/clienta/components/documents/ProcedureUploadDialog.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import ConsolidatedActionsSection from '@/modules/clienta/pages/iso/operations/components/ConsolidatedActionsSection.vue'
  import OperationalHero from '@/modules/clienta/pages/iso/operations/components/OperationalHero.vue'
  import OperationalTabs from '@/modules/clienta/pages/iso/operations/components/OperationalTabs.vue'
  import ProjectsSection from '@/modules/clienta/pages/iso/operations/components/ProjectsSection.vue'
  import { useAuthStore } from '@/stores/auth'

  const { t } = useI18n()
  type ConsolidatedAction = {
    id: string
    source_module: string
    source_type: string
    source_title: string
    process_name: string | null
    title: string
    description: string | null
    status: string | null
    priority: string | null
    responsible_name: string | null
    start_date: string | null
    due_date: string | null
    progress: number | null
  }

  type OperationalTask = {
    id: number
    title: string
    description?: string
    status: string
    priority: string
    start_date?: string
    due_date?: string
    responsible_user_id?: number
    assigned_user_ids?: number[]
    responsible_user?: { id: number, name: string }
  }

  type OperationalActivity = {
    id: number
    title: string
    description?: string
    status: string
    priority: string
    start_date?: string
    due_date?: string
    responsible_user_id?: number
    assigned_user_ids?: number[]
    responsible_user?: { id: number, name: string }
    tasks: OperationalTask[]
  }

  type OperationalProject = {
    id: number
    code?: string
    title: string
    description?: string
    status: string
    priority: string
    start_date?: string
    due_date?: string
    project_manager_id?: number
    project_manager?: { id: number, name: string }
    activities: OperationalActivity[]
  }

  const authStore = useAuthStore()
  const router = useRouter()
  const loading = ref(false)
  const proceduresPanel = ref<InstanceType<typeof ProceduresPanel> | null>(null)
  const search = ref('')
  const activeTab = ref<'actions' | 'projects'>('actions')
  const actionsViewMode = ref<'table' | 'grid'>('table')
  const projectsViewMode = ref<'list' | 'grid'>('list')
  const sourceFilter = ref<string | null>(null)
  const statusFilter = ref<string | null>(null)

  const consolidatedActions = ref<ConsolidatedAction[]>([])
  const projects = ref<OperationalProject[]>([])
  const users = ref<Array<{ id: number, name: string }>>([])

  const projectDialog = ref(false)
  const activityDialog = ref(false)
  const taskDialog = ref(false)
  const procedureDialog = ref(false)

  const editingProjectId = ref<number | null>(null)
  const editingActivityId = ref<number | null>(null)
  const editingTaskId = ref<number | null>(null)
  const selectedProjectId = ref<number | null>(null)
  const selectedActivityId = ref<number | null>(null)

  const projectStatuses = computed(() => [
    { title: t('operations.status.planned'), value: 'planned' },
    { title: t('operations.status.in_progress'), value: 'in_progress' },
    { title: t('operations.status.on_hold'), value: 'on_hold' },
    { title: t('operations.status.completed'), value: 'completed' },
    { title: t('operations.status.cancelled'), value: 'cancelled' },
  ])

  const taskStatuses = computed(() => [
    { title: t('operations.status.todo'), value: 'todo' },
    { title: t('operations.status.in_progress'), value: 'in_progress' },
    { title: t('operations.status.done'), value: 'done' },
    { title: t('operations.status.blocked'), value: 'blocked' },
  ])

  const priorities = computed(() => [
    { title: t('operations.priority.low'), value: 'low' },
    { title: t('operations.priority.medium'), value: 'medium' },
    { title: t('operations.priority.high'), value: 'high' },
    { title: t('operations.priority.critical'), value: 'critical' },
  ])

  const projectForm = ref({
    code: '',
    title: '',
    description: '',
    status: 'planned',
    priority: 'medium',
    start_date: '',
    due_date: '',
    project_manager_id: null as number | null,
  })

  const activityForm = ref({
    title: '',
    description: '',
    status: 'planned',
    priority: 'medium',
    start_date: '',
    due_date: '',
    responsible_user_id: null as number | null,
    assigned_user_ids: [] as number[],
  })

  const taskForm = ref({
    title: '',
    description: '',
    status: 'todo',
    priority: 'medium',
    start_date: '',
    due_date: '',
    responsible_user_id: null as number | null,
    assigned_user_ids: [] as number[],
  })

  const actionHeaders = [
    { title: 'Module', key: 'source_module' },
    { title: 'Source', key: 'source_title' },
    { title: 'Action', key: 'title' },
    { title: 'Description', key: 'description' },
    { title: 'Processus', key: 'process_name' },
    { title: 'Responsable', key: 'responsible_name' },
    { title: 'Statut', key: 'status' },
    { title: 'Début', key: 'start_date' },
    { title: 'Avancement', key: 'progress' },
    { title: 'Priorité', key: 'priority' },
    { title: 'Échéance', key: 'due_date' },
  ]

  const nearDueActionsCount = computed(() => {
    const now = new Date()
    const sevenDaysMs = 7 * 24 * 60 * 60 * 1000
    return consolidatedActions.value.filter(action => {
      if (!action.due_date) return false
      const due = new Date(action.due_date)
      if (Number.isNaN(due.getTime())) return false
      const delta = due.getTime() - now.getTime()
      return delta >= 0 && delta <= sevenDaysMs
    }).length
  })

  const sourceFilterItems = computed(() =>
    [...new Set(consolidatedActions.value.map(item => item.source_module).filter(Boolean))]
      .toSorted((a, b) => a.localeCompare(b))
      .map(value => ({
        title: formatSourceLabel(value),
        value,
      })),
  )

  const statusFilterItems = computed(() =>
    [...new Set(consolidatedActions.value.map(item => item.status || '').filter(Boolean))]
      .toSorted((a, b) => a.localeCompare(b))
      .map(value => ({
        title: formatStatusLabel(value),
        value,
      })),
  )

  const filteredConsolidatedActions = computed(() => {
    const query = search.value.trim().toLowerCase()
    return consolidatedActions.value.filter(item => {
      const sourceOk = !sourceFilter.value || item.source_module === sourceFilter.value
      const statusOk = !statusFilter.value || item.status === statusFilter.value
      if (!sourceOk || !statusOk) return false
      if (query) {
        const haystack = [
          item.source_module,
          item.source_title,
          item.title,
          item.description,
          item.process_name,
          item.responsible_name,
          item.status,
          item.priority,
          item.start_date,
          item.due_date,
          item.progress == null ? null : String(item.progress),
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase()

        return haystack.includes(query)
      }

      return true
    })
  })

  const hasActiveActionFilters = computed(() => {
    return Boolean(search.value.trim() || sourceFilter.value || statusFilter.value)
  })

  const emptyActionStateMessage = computed(() => {
    if (consolidatedActions.value.length === 0) {
      return 'Aucune action consolidée disponible pour ce site.'
    }

    return 'Aucune action consolidée ne correspond aux filtres sélectionnés.'
  })

  const userOptions = computed(() => users.value.map(user => ({
    title: user.name,
    value: user.id,
  })))

  function getCurrentSiteId (): number | null {
    if (authStore.currentSiteId) {
      return Number(authStore.currentSiteId)
    }
    const raw = localStorage.getItem('current_site_id')
    if (!raw) return null
    const parsed = Number(raw)
    return Number.isFinite(parsed) && parsed > 0 ? parsed : null
  }

  function priorityColor (priority: string | null | undefined): string {
    switch (priority) {
      case 'critical':
      case 'critique': {
        return 'error'
      }
      case 'high': { return 'warning'
      }
      case 'eleve': {
        return 'warning'
      }
      case 'medium': { return 'info'
      }
      case 'moyen': {
        return 'info'
      }
      default: { return 'grey'
      }
    }
  }

  function formatSourceLabel (source: string | null | undefined): string {
    const value = String(source || '').trim().toLowerCase()
    const labels: Record<string, string> = {
      risks_opportunities: 'Risques & opportunités',
      objectives: 'Objectifs',
      compliance_obligations: 'Obligations de conformité',
      actions: 'Actions',
    }
    return labels[value] || (source || 'Non défini')
  }

  function formatPriorityLabel (priority: string | null | undefined): string {
    const value = String(priority || '').trim().toLowerCase()
    const labels: Record<string, string> = {
      critical: t('operations.priority.critical'),
      critique: t('operations.priority.critical'),
      high: t('operations.priority.high'),
      eleve: t('operations.priority.high'),
      medium: t('operations.priority.medium'),
      moyen: t('operations.priority.medium'),
      low: t('operations.priority.low'),
      faible: t('operations.priority.low'),
    }
    return labels[value] || 'Non défini'
  }

  function formatStatusLabel (status: string | null | undefined): string {
    const value = String(status || '').trim().toLowerCase()
    const labels: Record<string, string> = {
      a_faire: 'À faire',
      todo: t('operations.status.todo'),
      planned: t('operations.status.planned'),
      in_progress: t('operations.status.in_progress'),
      en_cours: 'En cours',
      done: t('operations.status.done'),
      terminee: 'Terminée',
      completed: t('operations.status.completed'),
      cloture: 'Clôturé',
      closed: 'Clôturé',
      on_hold: t('operations.status.on_hold'),
      blocked: t('operations.status.blocked'),
      cancelled: t('operations.status.cancelled'),
      identifie: 'Identifié',
      traite: 'Traité',
      surveille: 'Surveillé',
      achieved: 'Atteint',
      failed: 'Échoué',
      not_started: 'Non démarré',
      regulatory_action: 'Action réglementaire',
    }
    return labels[value] || (status || 'Non défini')
  }

  function projectCountByStatus (status: string): number {
    return projects.value.filter(project => project.status === status).length
  }

  function resetActionFilters (): void {
    search.value = ''
    sourceFilter.value = null
    statusFilter.value = null
  }

  async function fetchUsers (): Promise<void> {
    const siteId = getCurrentSiteId()
    const { data } = await api.get('/users/job-description-collaborators', {
      params: {
        site_id: siteId || undefined,
      },
    })
    const rows = Array.isArray(data?.data) ? data.data : []
    users.value = rows.map((u: any) => ({
      id: Number(u?.id),
      name: String(u?.full_name || `${u?.last_name || ''} ${u?.first_name || ''}`.trim() || u?.name || u?.email || 'Utilisateur'),
    }))
  }

  async function fetchOverview (): Promise<void> {
    loading.value = true
    try {
      const siteId = getCurrentSiteId()
      const { data } = await api.get('/operational-planning/overview', {
        params: { site_id: siteId || undefined },
      })
      consolidatedActions.value = Array.isArray(data?.data?.consolidated_actions) ? data.data.consolidated_actions : []
      projects.value = Array.isArray(data?.data?.projects) ? data.data.projects : []
    } finally {
      loading.value = false
    }
  }

  function resetProjectForm (): void {
    projectForm.value = {
      code: '',
      title: '',
      description: '',
      status: 'planned',
      priority: 'medium',
      start_date: '',
      due_date: '',
      project_manager_id: null,
    }
  }

  function resetActivityForm (): void {
    activityForm.value = {
      title: '',
      description: '',
      status: 'planned',
      priority: 'medium',
      start_date: '',
      due_date: '',
      responsible_user_id: null,
      assigned_user_ids: [],
    }
  }

  function resetTaskForm (): void {
    taskForm.value = {
      title: '',
      description: '',
      status: 'todo',
      priority: 'medium',
      start_date: '',
      due_date: '',
      responsible_user_id: null,
      assigned_user_ids: [],
    }
  }

  function openCreateProjectDialog (): void {
    editingProjectId.value = null
    resetProjectForm()
    projectDialog.value = true
  }

  function openEditProjectDialog (project: OperationalProject): void {
    editingProjectId.value = project.id
    projectForm.value = {
      code: project.code || '',
      title: project.title,
      description: project.description || '',
      status: project.status || 'planned',
      priority: project.priority || 'medium',
      start_date: project.start_date || '',
      due_date: project.due_date || '',
      project_manager_id: project.project_manager_id || null,
    }
    projectDialog.value = true
  }

  async function saveProject (): Promise<void> {
    const siteId = getCurrentSiteId()
    const payload = {
      ...projectForm.value,
      site_id: siteId || undefined,
    }

    await (editingProjectId.value ? api.put(`/operational-projects/${editingProjectId.value}`, payload) : api.post('/operational-projects', payload))

    projectDialog.value = false
    await fetchOverview()
  }

  async function deleteProject (id: number): Promise<void> {
    await api.delete(`/operational-projects/${id}`)
    await fetchOverview()
  }

  function openCreateActivityDialog (projectId: number): void {
    selectedProjectId.value = projectId
    editingActivityId.value = null
    resetActivityForm()
    activityDialog.value = true
  }

  function openEditActivityDialog (activity: OperationalActivity): void {
    editingActivityId.value = activity.id
    activityForm.value = {
      title: activity.title,
      description: activity.description || '',
      status: activity.status || 'planned',
      priority: activity.priority || 'medium',
      start_date: activity.start_date || '',
      due_date: activity.due_date || '',
      responsible_user_id: activity.responsible_user_id || null,
      assigned_user_ids: Array.isArray(activity.assigned_user_ids) ? activity.assigned_user_ids : [],
    }
    activityDialog.value = true
  }

  async function saveActivity (): Promise<void> {
    if (editingActivityId.value) {
      await api.put(`/operational-project-activities/${editingActivityId.value}`, activityForm.value)
    } else if (selectedProjectId.value) {
      await api.post(`/operational-projects/${selectedProjectId.value}/activities`, activityForm.value)
    }

    activityDialog.value = false
    await fetchOverview()
  }

  async function deleteActivity (id: number): Promise<void> {
    await api.delete(`/operational-project-activities/${id}`)
    await fetchOverview()
  }

  function openCreateTaskDialog (activityId: number): void {
    selectedActivityId.value = activityId
    editingTaskId.value = null
    resetTaskForm()
    taskDialog.value = true
  }

  function openEditTaskDialog (task: OperationalTask): void {
    editingTaskId.value = task.id
    taskForm.value = {
      title: task.title,
      description: task.description || '',
      status: task.status || 'todo',
      priority: task.priority || 'medium',
      start_date: task.start_date || '',
      due_date: task.due_date || '',
      responsible_user_id: task.responsible_user_id || null,
      assigned_user_ids: Array.isArray(task.assigned_user_ids) ? task.assigned_user_ids : [],
    }
    taskDialog.value = true
  }

  async function saveTask (): Promise<void> {
    if (editingTaskId.value) {
      await api.put(`/operational-project-tasks/${editingTaskId.value}`, taskForm.value)
    } else if (selectedActivityId.value) {
      await api.post(`/operational-project-activities/${selectedActivityId.value}/tasks`, taskForm.value)
    }

    taskDialog.value = false
    await fetchOverview()
  }

  async function deleteTask (id: number): Promise<void> {
    await api.delete(`/operational-project-tasks/${id}`)
    await fetchOverview()
  }

  function openTrackingForOperationalActivity (activity: OperationalActivity): void {
    router.push({
      path: '/company/my-tasks',
      query: {
        preselect_type: 'operational_project_activity',
        preselect_id: String(activity.id),
      },
    })
  }

  function openTrackingForOperationalTask (task: OperationalTask): void {
    router.push({
      path: '/company/my-tasks',
      query: {
        preselect_type: 'operational_project_task',
        preselect_id: String(task.id),
      },
    })
  }

  onMounted(async () => {
    await Promise.all([fetchUsers(), fetchOverview()])
  })
</script>

<style scoped>
</style>
