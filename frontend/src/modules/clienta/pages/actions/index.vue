<template>
  <ClientALayout current-page="actions">
    <v-container class="pa-6" fluid>
      <PageHeader
        subtitle="Gérez les actions QHSE"
        title="Actions Correctives/Préventives"
      >
        <template #actions>
          <v-btn
            v-if="canCreateAction"
            color="primary"
            prepend-icon="mdi-plus"
            @click="createAction"
          >
            Nouvelle Action
          </v-btn>
        </template>
      </PageHeader>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 sm:grid-cols-5 gap-4 mb-6">
        <AppWidget
          clickable
          :icon="CheckCircle2"
          title="Total"
          :value="stats.total"
          variant="primary"
          @click="resetFilters"
        />
        <AppWidget
          clickable
          :icon="Clock"
          title="En attente"
          :value="stats.draft || 0"
          variant="info"
          @click="filterModel.status = 'draft'"
        />
        <AppWidget
          clickable
          :icon="PlayCircle"
          title="En cours"
          :value="stats.inProgress"
          variant="warning"
          @click="filterModel.status = 'in_progress'"
        />
        <AppWidget
          clickable
          :icon="CheckCircle"
          title="Terminé"
          :value="stats.completed"
          variant="success"
          @click="filterModel.status = 'completed'"
        />
        <AppWidget
          clickable
          :icon="AlertCircle"
          title="En retard"
          :value="stats.overdue"
          variant="error"
          @click="filterModel.status = 'in_progress'"
        />
      </div>

      <!-- Filters and View Toggle -->
      <div class="card p-4 mb-6">
        <div class="flex flex-col gap-4">
          <div class="flex items-center justify-between">
            <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">Filtres</h3>
            <div class="flex gap-2">
              <button
                :class="[
                  'p-2 rounded-lg transition-colors',
                  viewMode === 'kanban'
                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400'
                ]"
                @click="viewMode = 'kanban'"
              >
                <LayoutGrid class="w-5 h-5" />
              </button>
              <button
                :class="[
                  'p-2 rounded-lg transition-colors',
                  viewMode === 'list'
                    ? 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400'
                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400'
                ]"
                @click="viewMode = 'list'"
              >
                <ListIcon class="w-5 h-5" />
              </button>
            </div>
          </div>

          <FilterCard
            v-model="filterModel"
            :filters="filterFields"
            @reset="resetFilters"
          />

          <div
            v-if="activeFilterChips.length > 0"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-neutral-200 bg-white px-3 py-2 dark:border-neutral-700 dark:bg-neutral-900"
          >
            <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">
              Filtres actifs:
            </span>

            <span class="text-xs font-semibold text-primary-700 dark:text-primary-300">
              {{ filteredActionsCount }} action{{ filteredActionsCount > 1 ? 's' : '' }} affichée{{ filteredActionsCount > 1 ? 's' : '' }}
            </span>

            <button
              v-for="chip in activeFilterChips"
              :key="chip.key"
              class="inline-flex items-center gap-1 rounded-full border border-primary-200 bg-primary-50 px-2 py-1 text-xs font-medium text-primary-800 transition-colors hover:bg-primary-100 dark:border-primary-800/50 dark:bg-primary-900/20 dark:text-primary-200 dark:hover:bg-primary-900/40"
              @click="removeFilterChip(chip.key)"
            >
              <span>{{ chip.label }}</span>
              <span class="text-[10px]">x</span>
            </button>

            <button
              class="ml-auto rounded-md border border-neutral-200 px-2 py-1 text-xs font-medium text-neutral-600 transition-colors hover:bg-neutral-100 dark:border-neutral-700 dark:text-neutral-300 dark:hover:bg-neutral-800"
              @click="resetFilters"
            >
              Tout effacer
            </button>
          </div>

          <div
            v-if="hasActiveProcessFilter"
            class="flex items-center justify-between rounded-lg border border-primary-200 bg-primary-50 px-3 py-2 text-sm text-primary-800 dark:border-primary-800/40 dark:bg-primary-900/20 dark:text-primary-200"
          >
            <button
              v-if="canOpenActiveProcessDetails"
              class="font-medium underline underline-offset-2 transition-opacity hover:opacity-80"
              @click="openActiveProcessDetails"
            >
              Filtre actif: {{ activeProcessLabel }}
            </button>
            <span v-else>
              Filtre actif: {{ activeProcessLabel }}
            </span>
            <button
              class="rounded-md bg-white px-2 py-1 text-xs font-medium text-primary-700 transition-colors hover:bg-primary-100 dark:bg-neutral-900 dark:text-primary-300 dark:hover:bg-primary-900/40"
              @click="clearProcessFilter"
            >
              Effacer
            </button>
          </div>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600" />
        <p class="mt-4 text-neutral-600 dark:text-neutral-400">Chargement...</p>
      </div>

      <!-- Kanban View - Keep existing complex component -->
      <div v-else-if="viewMode === 'kanban'" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Draft Column -->
        <div class="card p-4">
          <div class="flex items-center gap-2 mb-4">
            <Edit class="w-5 h-5 text-gray-600" />
            <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">Brouillon</h3>
            <span class="ml-auto bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded-full text-sm">
              {{ kanbanColumns.draft.length }}
            </span>
          </div>
          <div class="space-y-3">
            <div
              v-for="action in kanbanColumns.draft"
              :key="action.id"
              class="p-3 bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:shadow-md transition-shadow cursor-pointer"
              @click="viewAction(action.id)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-neutral-500">{{ action.reference }}</span>
                <StatusChip :color="getPriorityConfig(action.priority).color" :label="getPriorityConfig(action.priority).label" size="x-small" />
              </div>
              <h4 class="font-medium text-sm text-neutral-900 dark:text-neutral-50 mb-2">
                {{ action.title }}
              </h4>
              <div class="flex items-center gap-2 text-xs text-neutral-500">
                <StatusChip :color="getTypeConfig(action.type).color" :label="getTypeConfig(action.type).label" size="x-small" />
                <button
                  v-if="action.process_id"
                  class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                  @click.stop="setProcessFilter(action.process_id)"
                >
                  {{ getProcessDisplayLabel(action) }}
                </button>
                <span v-if="action.due_date" :class="{ 'text-red-600': isOverdue(action) }">
                  {{ formatDate(action.due_date) }}
                </span>
              </div>
              <div class="mt-2 flex justify-end">
                <button
                  v-if="canUpdateActionProgress(action)"
                  class="rounded-md bg-primary-50 px-2 py-1 text-[11px] font-medium text-primary-700 transition-colors hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/40"
                  @click.stop="openProgressDialog(action)"
                >
                  Mettre à jour suivi
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Assigned Column -->
        <div class="card p-4">
          <div class="flex items-center gap-2 mb-4">
            <ListTodo class="w-5 h-5 text-blue-600" />
            <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">Assignée</h3>
            <span class="ml-auto bg-blue-100 dark:bg-blue-900/30 px-2 py-1 rounded-full text-sm">
              {{ kanbanColumns.assigned.length }}
            </span>
          </div>
          <div class="space-y-3">
            <div
              v-for="action in kanbanColumns.assigned"
              :key="action.id"
              class="p-3 bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:shadow-md transition-shadow cursor-pointer"
              @click="viewAction(action.id)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-neutral-500">{{ action.reference }}</span>
                <StatusChip :color="getPriorityConfig(action.priority).color" :label="getPriorityConfig(action.priority).label" size="x-small" />
              </div>
              <h4 class="font-medium text-sm text-neutral-900 dark:text-neutral-50 mb-2">
                {{ action.title }}
              </h4>
              <div class="flex items-center gap-2 text-xs text-neutral-500">
                <StatusChip :color="getTypeConfig(action.type).color" :label="getTypeConfig(action.type).label" size="x-small" />
                <button
                  v-if="action.process_id"
                  class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                  @click.stop="setProcessFilter(action.process_id)"
                >
                  {{ getProcessDisplayLabel(action) }}
                </button>
                <span v-if="action.due_date" :class="{ 'text-red-600': isOverdue(action) }">
                  {{ formatDate(action.due_date) }}
                </span>
              </div>
              <div class="mt-2 flex justify-end">
                <button
                  v-if="canUpdateActionProgress(action)"
                  class="rounded-md bg-primary-50 px-2 py-1 text-[11px] font-medium text-primary-700 transition-colors hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/40"
                  @click.stop="openProgressDialog(action)"
                >
                  Mettre à jour suivi
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- In Progress Column -->
        <div class="card p-4">
          <div class="flex items-center gap-2 mb-4">
            <Play class="w-5 h-5 text-yellow-600" />
            <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">En cours</h3>
            <span class="ml-auto bg-yellow-100 dark:bg-yellow-900/30 px-2 py-1 rounded-full text-sm">
              {{ kanbanColumns.in_progress.length }}
            </span>
          </div>
          <div class="space-y-3">
            <div
              v-for="action in kanbanColumns.in_progress"
              :key="action.id"
              class="p-3 bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:shadow-md transition-shadow cursor-pointer"
              @click="viewAction(action.id)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-neutral-500">{{ action.reference }}</span>
                <StatusChip :color="getPriorityConfig(action.priority).color" :label="getPriorityConfig(action.priority).label" size="x-small" />
              </div>
              <h4 class="font-medium text-sm text-neutral-900 dark:text-neutral-50 mb-2">
                {{ action.title }}
              </h4>
              <div class="mb-2">
                <div class="w-full bg-neutral-200 dark:bg-neutral-700 rounded-full h-1.5">
                  <div
                    class="bg-yellow-600 h-1.5 rounded-full transition-all"
                    :style="{ width: `${action.progress}%` }"
                  />
                </div>
                <span class="text-xs text-neutral-500">{{ action.progress }}%</span>
              </div>
              <div class="flex items-center gap-2 text-xs text-neutral-500">
                <StatusChip :color="getTypeConfig(action.type).color" :label="getTypeConfig(action.type).label" size="x-small" />
                <button
                  v-if="action.process_id"
                  class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                  @click.stop="setProcessFilter(action.process_id)"
                >
                  {{ getProcessDisplayLabel(action) }}
                </button>
                <span v-if="action.due_date" :class="{ 'text-red-600': isOverdue(action) }">
                  {{ formatDate(action.due_date) }}
                </span>
              </div>
              <div class="mt-2 flex justify-end">
                <button
                  v-if="canUpdateActionProgress(action)"
                  class="rounded-md bg-primary-50 px-2 py-1 text-[11px] font-medium text-primary-700 transition-colors hover:bg-primary-100 dark:bg-primary-900/20 dark:text-primary-300 dark:hover:bg-primary-900/40"
                  @click.stop="openProgressDialog(action)"
                >
                  Mettre à jour suivi
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Completed Column -->
        <div class="card p-4">
          <div class="flex items-center gap-2 mb-4">
            <CheckCircle class="w-5 h-5 text-green-600" />
            <h3 class="font-semibold text-neutral-900 dark:text-neutral-50">Terminé</h3>
            <span class="ml-auto bg-green-100 dark:bg-green-900/30 px-2 py-1 rounded-full text-sm">
              {{ kanbanColumns.completed.length }}
            </span>
          </div>
          <div class="space-y-3">
            <div
              v-for="action in kanbanColumns.completed"
              :key="action.id"
              class="p-3 bg-white dark:bg-neutral-900 rounded-lg border border-neutral-200 dark:border-neutral-700 hover:shadow-md transition-shadow cursor-pointer"
              @click="viewAction(action.id)"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="text-xs font-medium text-neutral-500">{{ action.reference }}</span>
                <StatusChip :color="getPriorityConfig(action.priority).color" :label="getPriorityConfig(action.priority).label" size="x-small" />
              </div>
              <h4 class="font-medium text-sm text-neutral-900 dark:text-neutral-50 mb-2">
                {{ action.title }}
              </h4>
              <div class="flex items-center gap-2 text-xs text-neutral-500">
                <StatusChip :color="getTypeConfig(action.type).color" :label="getTypeConfig(action.type).label" size="x-small" />
                <button
                  v-if="action.process_id"
                  class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
                  @click.stop="setProcessFilter(action.process_id)"
                >
                  {{ getProcessDisplayLabel(action) }}
                </button>
                <StatusChip :color="getStatusConfig(action.status).color" :label="getStatusConfig(action.status).label" size="x-small" />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- List View -->
      <DataTable
        v-else
        empty-message="Aucune action trouvée"
        :headers="listHeaders"
        :items="filteredActions"
        :loading="loading"
      >
        <template #item.reference="{ item }">
          <span class="text-sm font-medium text-neutral-900 dark:text-neutral-50">{{ item.reference }}</span>
        </template>

        <template #item.title="{ item }">
          <div class="font-medium">{{ item.title }}</div>
          <div class="mt-1 flex items-center gap-2 text-xs text-neutral-500">
            <span class="truncate max-w-xs">{{ item.description }}</span>
            <button
              v-if="item.process_id"
              class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] text-neutral-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:bg-primary-900/40 dark:hover:text-primary-300"
              @click.stop="setProcessFilter(item.process_id)"
            >
              {{ getProcessDisplayLabel(item) }}
            </button>
          </div>
        </template>

        <template #item.type="{ item }">
          <StatusChip :color="getTypeConfig(item.type).color" :label="getTypeConfig(item.type).label" />
        </template>

        <template #item.priority="{ item }">
          <StatusChip :color="getPriorityConfig(item.priority).color" :label="getPriorityConfig(item.priority).label" />
        </template>

        <template #item.status="{ item }">
          <StatusChip :color="getStatusConfig(item.status).color" :label="getStatusConfig(item.status).label" />
        </template>

        <template #item.responsible="{ item }">
          {{ item.responsible?.name || '-' }}
        </template>

        <template #item.due_date="{ item }">
          <span v-if="item.due_date" :class="{ 'text-red-600 font-medium': isOverdue(item) }">
            {{ formatDate(item.due_date) }}
          </span>
          <span v-else class="text-neutral-500">-</span>
        </template>

        <template #item.progress="{ item }">
          <div class="flex flex-col gap-1">
            <div class="flex items-center gap-2">
              <div class="w-16 bg-neutral-200 dark:bg-neutral-700 rounded-full h-2">
                <div
                  class="bg-primary-600 h-2 rounded-full transition-all"
                  :style="{ width: `${item.progress}%` }"
                />
              </div>
              <span class="text-xs text-neutral-500">{{ item.progress }}%</span>
            </div>
            <span
              v-if="latestProgressNote(item)"
              class="text-[11px] text-neutral-500 dark:text-neutral-400 truncate max-w-[220px]"
            >
              {{ latestProgressNote(item) }}
            </span>
          </div>
        </template>

        <template #item.actions="{ item }">
          <div class="flex items-center justify-end gap-2">
            <button
              class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
              @click="viewAction(item.id)"
            >
              <Eye class="w-4 h-4" />
            </button>
            <button
              v-if="canUpdateActionProgress(item)"
              class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300"
              title="Mettre à jour le suivi"
              @click="openProgressDialog(item)"
            >
              <TrendingUp class="w-4 h-4" />
            </button>
            <button
              v-if="canDeleteAction"
              class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300"
              @click="handleDelete(item.id)"
            >
              <Trash2 class="w-4 h-4" />
            </button>
          </div>
        </template>
      </DataTable>

      <v-dialog v-model="progressDialog.open" max-width="560" persistent>
        <v-card rounded="lg">
          <v-card-title class="text-h6">Mettre à jour le suivi</v-card-title>
          <v-card-text>
            <div class="text-body-2 mb-3 font-medium">{{ progressDialog.action?.title }}</div>
            <v-slider
              v-model="progressDialog.progress"
              class="mb-3"
              color="primary"
              :max="100"
              :min="0"
              step="5"
              thumb-label
            />
            <v-textarea
              v-model="progressDialog.note"
              label="Observation"
              maxlength="1000"
              rows="3"
              variant="outlined"
            />
          </v-card-text>
          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="closeProgressDialog">Annuler</v-btn>
            <v-btn color="primary" :loading="progressDialog.saving" @click="submitProgressDialog">
              Enregistrer
            </v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </v-container>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { ActionPriority, ActionStatus } from '@/api/services/actions.service'
  import {
    AlertCircle,
    CheckCircle,
    CheckCircle2,
    Clock,
    Edit,
    Eye,
    LayoutGrid,
    ListIcon,
    ListTodo,
    Play,
    PlayCircle,
    Trash2,
  } from 'lucide-vue-next'
  import { computed, onMounted, ref, watch } from 'vue'
  import { useRoute, useRouter } from 'vue-router'
  import AppWidget from '@/components/common/AppWidget.vue'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import DataTable from '@/modules/clienta/components/DataTable.vue'
  import FilterCard from '@/modules/clienta/components/FilterCard.vue'
  import PageHeader from '@/modules/clienta/components/PageHeader.vue'
  import StatusChip from '@/modules/clienta/components/StatusChip.vue'
  import { useActions } from '@/modules/clienta/composables/useActions'
  import { useSites } from '@/modules/clienta/composables/useSites'
  import { useUsers } from '@/modules/clienta/composables/useUsers'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { isEnterpriseAdminUser } from '@/utils/accessControl'
  import { expandPermissionAliases } from '@/utils/permissions'

  const router = useRouter()
  const route = useRoute()
  const authStore = useAuthStore()
  const {
    actions,
    loading,
    stats,
    fetchActions,
    deleteAction,
    updateProgress,
  } = useActions()

  const { fetchSites } = useSites()
  const { users, fetchUsers } = useUsers()

  const filterModel = ref<Record<string, any>>({
    search: '',
    type: 'all',
    priority: 'all',
    status: 'all',
    responsible_id: 'all',
    process_id: 'all',
  })
  const viewMode = ref<'kanban' | 'list'>('kanban')

  function canAccess (requiredPermissions: string[]): boolean {
    const currentUser = authStore.user as any
    if (currentUser?.user_type === 'super_admin' || isEnterpriseAdminUser(currentUser)) {
      return true
    }
    const permissions = getNavigationPermissionSet(currentUser)
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias => permissions.has(alias)),
    )
  }

  const canCreateAction = computed(() => canAccess(['amelioration.non_conformites.create']))
  const canDeleteAction = computed(() => canAccess(['amelioration.non_conformites.delete']))
  const canManageActions = computed(() => canAccess(['amelioration.non_conformites.manage_actions', 'amelioration.non_conformites.update']))
  const currentUserId = computed(() => Number((authStore.user as any)?.id || 0))

  const progressDialog = ref<{
    open: boolean
    action: any | null
    progress: number
    note: string
    saving: boolean
  }>({
    open: false,
    action: null,
    progress: 0,
    note: '',
    saving: false,
  })

  const actionTypes = [
    { value: 'corrective', label: 'Corrective' },
    { value: 'preventive', label: 'Préventive' },
  ]

  const priorities = [
    { value: 'low', label: 'Basse' },
    { value: 'medium', label: 'Moyenne' },
    { value: 'high', label: 'Haute' },
    { value: 'urgent', label: 'Urgente' },
  ]

  const statuses = [
    { value: 'draft', label: 'Brouillon' },
    { value: 'assigned', label: 'Assignée' },
    { value: 'in_progress', label: 'En cours' },
    { value: 'completed', label: 'Terminé' },
    { value: 'verified', label: 'Vérifiée' },
    { value: 'closed', label: 'Fermée' },
    { value: 'cancelled', label: 'Annulée' },
  ]

  const filterFields = computed<any[]>(() => [
    {
      type: 'select',
      key: 'type',
      label: 'Type',
      items: [{ value: 'all', label: 'Tous les types' }, ...actionTypes],
    },
    {
      type: 'select',
      key: 'priority',
      label: 'Priorité',
      items: [{ value: 'all', label: 'Toutes priorités' }, ...priorities],
    },
    {
      type: 'select',
      key: 'status',
      label: 'Statut',
      items: [{ value: 'all', label: 'Tous les statuts' }, ...statuses],
    },
    {
      type: 'select',
      key: 'responsible_id',
      label: 'Responsable',
      items: [{ value: 'all', label: 'Tous responsables' }, ...users.value.map(u => ({ value: u.id, label: u.name }))],
    },
  ])

  const listHeaders = [
    { key: 'reference', label: 'Référence', sortable: false },
    { key: 'title', label: 'Titre', sortable: false },
    { key: 'type', label: 'Type', sortable: false },
    { key: 'priority', label: 'Priorité', sortable: false },
    { key: 'status', label: 'Statut', sortable: false },
    { key: 'responsible', label: 'Responsable', sortable: false },
    { key: 'due_date', label: 'Échéance', sortable: false },
    { key: 'progress', label: 'Progression', sortable: false },
    { key: 'actions', label: 'Actions', sortable: false },
  ]

  const filteredActions = computed(() => {
    let filtered = actions.value

    const search = String(filterModel.value.search || '')
    if (search) {
      const query = search.toLowerCase()
      filtered = filtered.filter(
        action =>
          action.title.toLowerCase().includes(query)
          || action.reference?.toLowerCase().includes(query)
          || action.description?.toLowerCase().includes(query),
      )
    }

    if (filterModel.value.type && filterModel.value.type !== 'all') {
      filtered = filtered.filter(action => action.type === filterModel.value.type)
    }

    if (filterModel.value.priority && filterModel.value.priority !== 'all') {
      filtered = filtered.filter(action => action.priority === filterModel.value.priority)
    }

    if (filterModel.value.status && filterModel.value.status !== 'all') {
      filtered = filtered.filter(action => action.status === filterModel.value.status)
    }

    if (filterModel.value.responsible_id && filterModel.value.responsible_id !== 'all') {
      filtered = filtered.filter(action => action.responsible_id === Number(filterModel.value.responsible_id))
    }

    if (filterModel.value.process_id && filterModel.value.process_id !== 'all') {
      filtered = filtered.filter(action => action.process_id === Number(filterModel.value.process_id))
    }

    return filtered
  })

  const filteredActionsCount = computed(() => filteredActions.value.length)

  const kanbanColumns = computed(() => ({
    draft: filteredActions.value.filter(a => a.status === 'draft'),
    assigned: filteredActions.value.filter(a => a.status === 'assigned'),
    in_progress: filteredActions.value.filter(a => a.status === 'in_progress'),
    completed: filteredActions.value.filter(a => a.status === 'completed' || a.status === 'verified' || a.status === 'closed'),
  }))

  const activeProcessFilterId = computed<number | null>(() => {
    const raw = filterModel.value.process_id
    if (!raw || raw === 'all') {
      return null
    }
    const parsed = Number(raw)
    return Number.isFinite(parsed) ? parsed : null
  })

  const hasActiveProcessFilter = computed(() => activeProcessFilterId.value !== null)
  const canOpenActiveProcessDetails = computed(() => activeProcessFilterId.value !== null)

  const activeFilterChips = computed<Array<{ key: 'process_id' | 'status' | 'responsible_id', label: string }>>(() => {
    const chips: Array<{ key: 'process_id' | 'status' | 'responsible_id', label: string }> = []

    const processId = activeProcessFilterId.value
    if (processId !== null) {
      chips.push({
        key: 'process_id',
        label: `Processus: ${activeProcessLabel.value.replace(/^processus\s+/i, '')}`,
      })
    }

    const status = String(filterModel.value.status || 'all')
    if (status !== 'all') {
      const statusLabel = statuses.find(item => item.value === status)?.label || status
      chips.push({
        key: 'status',
        label: `Statut: ${statusLabel}`,
      })
    }

    const responsibleId = Number(filterModel.value.responsible_id || 0)
    if (Number.isFinite(responsibleId) && responsibleId > 0) {
      const responsibleName = users.value.find(user => user.id === responsibleId)?.name || `#${responsibleId}`
      chips.push({
        key: 'responsible_id',
        label: `Responsable: ${responsibleName}`,
      })
    }

    return chips
  })

  const activeProcessLabel = computed(() => {
    const processId = activeProcessFilterId.value
    if (!processId) {
      return 'Tous les processus'
    }

    const match = actions.value.find(action => action.process_id === processId)
    const processTitle = match?.process?.title?.trim()
    const processCode = match?.process?.code?.trim()

    if (processTitle) {
      return `processus ${processTitle}`
    }
    if (processCode) {
      return `processus ${processCode}`
    }
    return `processus #${processId}`
  })

  function getPriorityConfig (priority?: ActionPriority | string): { color: string, label: string } {
    const fallback = { color: 'info', label: 'Basse' }
    const configs: Record<string, { color: string, label: string }> = {
      low: fallback,
      medium: { color: 'warning', label: 'Moyenne' },
      high: { color: 'error', label: 'Haute' },
      urgent: { color: 'error', label: 'Urgente' },
    }
    return configs[priority || 'low'] ?? fallback
  }

  function getStatusConfig (status?: ActionStatus | string): { color: string, label: string } {
    const fallback = { color: 'grey', label: 'Brouillon' }
    const configs: Record<string, { color: string, label: string }> = {
      draft: fallback,
      assigned: { color: 'info', label: 'Assignée' },
      in_progress: { color: 'warning', label: 'En cours' },
      completed: { color: 'success', label: 'Terminé' },
      verified: { color: 'success', label: 'Vérifiée' },
      closed: { color: 'success', label: 'Fermée' },
      cancelled: { color: 'error', label: 'Annulée' },
    }
    return configs[status || 'draft'] ?? fallback
  }

  function getTypeConfig (type?: string): { color: string, label: string } {
    const fallback = { color: 'warning', label: 'Corrective' }
    const configs: Record<string, { color: string, label: string }> = {
      corrective: fallback,
      preventive: { color: 'info', label: 'Préventive' },
    }
    return configs[type || 'corrective'] ?? fallback
  }

  function resetFilters () {
    filterModel.value = {
      search: '',
      type: 'all',
      priority: 'all',
      status: 'all',
      responsible_id: 'all',
      process_id: 'all',
    }

    if (route.query.process_id) {
      void clearProcessFilter()
    }
  }

  function canUpdateActionProgress (action: any): boolean {
    if (!action) return false
    if (['closed', 'cancelled'].includes(String(action.status || ''))) return false

    if (canManageActions.value) {
      return true
    }

    return Number(action.responsible_id || 0) === currentUserId.value
  }

  function latestProgressNote (action: any): string | null {
    const notes = Array.isArray(action?.progress_notes) ? action.progress_notes : []
    if (notes.length === 0) return null
    const latest = notes.at(-1)
    if (!latest?.note) return null
    const author = latest.user_name ? `${latest.user_name} · ` : ''
    return `${author}${latest.note}`
  }

  function openProgressDialog (action: any): void {
    if (!canUpdateActionProgress(action)) return
    progressDialog.value.open = true
    progressDialog.value.action = action
    progressDialog.value.progress = Number(action.progress || 0)
    progressDialog.value.note = ''
  }

  function closeProgressDialog (): void {
    progressDialog.value.open = false
    progressDialog.value.action = null
    progressDialog.value.progress = 0
    progressDialog.value.note = ''
    progressDialog.value.saving = false
  }

  async function submitProgressDialog (): Promise<void> {
    const target = progressDialog.value.action
    if (!target) return

    progressDialog.value.saving = true
    try {
      await updateProgress(Number(target.id), {
        progress: Number(progressDialog.value.progress || 0),
        comments: progressDialog.value.note?.trim() || undefined,
      })
      closeProgressDialog()
    } catch (error_) {
      console.error('Failed to update action progress:', error_)
      progressDialog.value.saving = false
    }
  }

  function removeFilterChip (key: 'process_id' | 'status' | 'responsible_id') {
    if (key === 'process_id') {
      void clearProcessFilter()
      return
    }

    if (key === 'status') {
      filterModel.value.status = 'all'
      return
    }

    if (key === 'responsible_id') {
      filterModel.value.responsible_id = 'all'
    }
  }

  function isOverdue (action: any) {
    if (action.status === 'completed' || action.status === 'verified'
      || action.status === 'closed' || action.status === 'cancelled' || !action.due_date) {
      return false
    }
    return new Date(action.due_date) < new Date()
  }

  function parseProcessIdFromRoute (): number | null {
    const queryProcessId = route.query.process_id
    const processId = typeof queryProcessId === 'string' && queryProcessId.trim() !== ''
      ? Number(queryProcessId)
      : null

    return processId && Number.isFinite(processId) ? processId : null
  }

  async function syncActionsFromRouteFilter () {
    const processId = parseProcessIdFromRoute()
    if (processId !== null) {
      filterModel.value.process_id = processId
      await fetchActions({
        per_page: 100,
        process_id: processId,
      })
      return
    }

    filterModel.value.process_id = 'all'
    await fetchActions({ per_page: 100 })
  }

  async function loadData () {
    await Promise.all([
      syncActionsFromRouteFilter(),
      fetchSites({ per_page: 100 }),
      fetchUsers({ per_page: 100 }),
    ])
  }

  async function clearProcessFilter () {
    const query = { ...route.query }
    delete query.process_id
    await router.replace({
      path: route.path,
      query,
    })
  }

  async function setProcessFilter (processId: number) {
    const query = {
      ...route.query,
      process_id: String(processId),
    }
    await router.replace({
      path: route.path,
      query,
    })
  }

  function getProcessDisplayLabel (action: { process_id?: number, process?: { title?: string, code?: string } }): string {
    const title = action.process?.title?.trim()
    const code = action.process?.code?.trim()
    if (title) {
      return title
    }
    if (code) {
      return code
    }
    return `Processus #${action.process_id || ''}`.trim()
  }

  function openActiveProcessDetails () {
    const processId = activeProcessFilterId.value
    if (!processId) {
      return
    }

    router.push(`/company/context/management-system/${processId}`)
  }

  function createAction () {
    if (!canCreateAction.value) return
    router.push('/company/actions/create')
  }

  function viewAction (id: number) {
    router.push(`/company/actions/${id}`)
  }

  async function handleDelete (id: number) {
    if (!canDeleteAction.value) return
    if (confirm('Êtes-vous sûr de vouloir supprimer cette action ? Cette action est irréversible.')) {
      try {
        await deleteAction(id)
      } catch (error) {
        console.error('Failed to delete action:', error)
      }
    }
  }

  function formatDate (date: string) {
    return new Date(date).toLocaleDateString('fr-FR', {
      day: '2-digit',
      month: '2-digit',
      year: 'numeric',
    })
  }

  onMounted(() => {
    loadData()
  })

  watch(
    () => route.query.process_id,
    () => {
      void syncActionsFromRouteFilter()
    },
  )
</script>
