<template>
  <ClientALayout>
    <div class="sm-shell px-1 pb-6">
      <!-- Header -->
      <div class="mb-6">
        <h1 class="text-3xl font-bold mb-2">📅 Plan du SM</h1>
        <p class="text-gray-600">Vue d'ensemble de toutes les tâches et activités du site</p>
      </div>

      <!-- View Toggle & Navigation -->
      <div class="flex items-center gap-4 mb-6">
        <div class="flex gap-2">
          <v-btn
            :color="view === 'day' ? 'primary' : 'default'"
            size="small"
            variant="tonal"
            @click="view = 'day'"
          >
            Jour
          </v-btn>
          <v-btn
            :color="view === 'week' ? 'primary' : 'default'"
            size="small"
            variant="tonal"
            @click="view = 'week'"
          >
            Semaine
          </v-btn>
          <v-btn
            :color="view === 'month' ? 'primary' : 'default'"
            size="small"
            variant="tonal"
            @click="view = 'month'"
          >
            Mois
          </v-btn>
        </div>

        <v-spacer />

        <div class="flex gap-2 items-center">
          <v-btn
            icon="mdi-chevron-left"
            size="small"
            variant="tonal"
            @click="navigatePrevious"
          />
          <span class="text-sm font-semibold min-w-40 text-center">
            {{ dateRangeLabel }}
          </span>
          <v-btn
            icon="mdi-chevron-right"
            size="small"
            variant="tonal"
            @click="navigateNext"
          />
        </div>

        <v-badge
          class="ml-4"
          color="primary"
          :content="totalTasks"
        >
          <v-btn
            icon="mdi-information-outline"
            size="small"
            variant="tonal"
          />
        </v-badge>
      </div>

      <!-- Filters Section -->
      <v-card class="mb-6" rounded="xl">
        <v-card-title>Filtres</v-card-title>
        <v-card-text>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Task Types Filter -->
            <div>
              <label class="text-sm font-semibold mb-2 block">Types de tâches</label>
              <v-select
                v-model="selectedTypes"
                chips
                clearable
                density="compact"
                item-title="label"
                item-value="id"
                :items="availableTypes"
                multiple
                placeholder="Tous les types"
                variant="outlined"
                @update:model-value="onFiltersChange"
              />
            </div>

            <!-- Users Filter -->
            <div>
              <label class="text-sm font-semibold mb-2 block">Responsables</label>
              <v-select
                v-model="selectedUserIds"
                chips
                clearable
                density="compact"
                item-title="name"
                item-value="id"
                :items="users"
                multiple
                placeholder="Tous les responsables"
                variant="outlined"
                @update:model-value="onFiltersChange"
              />
            </div>

            <!-- Process Filter -->
            <div>
              <label class="text-sm font-semibold mb-2 block">Processus</label>
              <v-select
                v-model="selectedProcessId"
                clearable
                density="compact"
                item-title="name"
                item-value="id"
                :items="processes"
                placeholder="Tous les processus"
                variant="outlined"
                @update:model-value="onFiltersChange"
              />
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- Legend -->
      <div class="mb-6 flex flex-wrap gap-4">
        <div
          v-for="taskType in availableTypes"
          :key="taskType.id"
          class="flex items-center gap-2"
        >
          <div
            class="w-3 h-3 rounded"
            :style="{ backgroundColor: taskType.color }"
          />
          <span class="text-xs font-medium">{{ taskType.label }}</span>
        </div>
      </div>

      <!-- Calendar View -->
      <v-card class="mb-6" rounded="xl">
        <v-card-title>Calendrier</v-card-title>
        <v-card-text>
          <div v-if="loading" class="flex justify-center items-center py-12">
            <v-progress-circular indeterminate />
          </div>

          <div v-else>
            <!-- Calendar Grid (Day/Week/Month) -->
            <div v-if="view === 'month'" class="calendar-grid">
              <div
                v-for="(task, idx) in tasks"
                :key="idx"
                class="calendar-item cursor-pointer hover:shadow-md transition-all"
                :style="{ borderLeftColor: task.color }"
                @click="openTaskDetail(task)"
              >
                <div class="font-semibold text-sm truncate">{{ task.title }}</div>
                <div class="text-xs text-gray-600">{{ task.start_date }}</div>
                <div class="text-xs font-medium mt-1">
                  {{ taskType(task.type) }}
                </div>
                <div v-if="task.responsible_name" class="text-xs text-gray-700 mt-1">
                  👤 {{ task.responsible_name }}
                </div>
              </div>
            </div>

            <!-- Timeline View (Day/Week) -->
            <div v-if="view === 'week' || view === 'day'" class="space-y-2">
              <div
                v-for="(task, idx) in tasks"
                :key="idx"
                class="timeline-item p-3 border-l-4 rounded cursor-pointer hover:shadow-md transition-all"
                :style="{ borderLeftColor: task.color }"
                @click="openTaskDetail(task)"
              >
                <div class="flex justify-between items-start">
                  <div>
                    <div class="font-semibold">{{ task.title }}</div>
                    <div class="text-sm text-gray-600 mt-1">
                      {{ taskType(task.type) }}
                      <span v-if="task.responsible_name" class="ml-2">
                        • {{ task.responsible_name }}
                      </span>
                    </div>
                  </div>
                  <div class="text-xs font-medium text-gray-700">
                    {{ formatDateRange(task.start_date, task.deadline) }}
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="tasks.length === 0" class="text-center py-12">
              <div class="text-gray-400 mb-2">📭 Aucune tâche</div>
              <p class="text-sm text-gray-600">
                Aucune tâche trouvée pour la période sélectionnée
              </p>
            </div>
          </div>
        </v-card-text>
      </v-card>

      <!-- Task Detail Dialog -->
      <v-dialog v-model="detailDialog.open" max-width="800">
        <v-card v-if="detailDialog.task">
          <v-card-title class="d-flex align-center gap-2">
            <div
              class="w-4 h-4 rounded"
              :style="{ backgroundColor: detailDialog.task.color }"
            />
            {{ detailDialog.task.title }}
          </v-card-title>

          <v-card-text>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Type</div>
                <div class="text-sm">{{ taskType(detailDialog.task.type) }}</div>
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Statut</div>
                <div class="text-sm">{{ formatStatus(detailDialog.task.status) }}</div>
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Date début</div>
                <div class="text-sm">{{ detailDialog.task.start_date }}</div>
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Date fin</div>
                <div class="text-sm">{{ detailDialog.task.deadline }}</div>
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Responsable</div>
                <div class="text-sm">{{ detailDialog.task.responsible_name || '—' }}</div>
              </div>
              <div>
                <div class="text-xs font-semibold text-gray-600 mb-1">Fréquence</div>
                <div class="text-sm">{{ detailDialog.task.frequency }}</div>
              </div>
            </div>
            <div v-if="detailDialog.task.involved_people.length > 0" class="mt-4">
              <div class="text-xs font-semibold text-gray-600 mb-1">Personnes impliquées</div>
              <div class="text-sm">{{ detailDialog.task.involved_people.join(', ') }}</div>
            </div>
          </v-card-text>

          <v-card-actions>
            <v-spacer />
            <v-btn variant="text" @click="detailDialog.open = false">Fermer</v-btn>
          </v-card-actions>
        </v-card>
      </v-dialog>
    </div>
  </ClientALayout>
</template>

<script setup lang="ts">
  import type { PlanSMTask } from '@/modules/clienta/composables/usePlanSM'
  import { computed, onMounted, ref, watch } from 'vue'
  import api from '@/api/client'
  import ClientALayout from '@/modules/clienta/components/ClientALayout.vue'
  import { usePlanSM } from '@/modules/clienta/composables/usePlanSM'
  import { useToast } from '@/modules/shared/composables/useToast'
  import { useAuthStore } from '@/stores/auth'

  const toast = useToast()
  const { success: showSuccess, error: showError } = toast
  const {
    tasks,
    loading,
    view,
    selectedUserIds,
    selectedProcessId,
    selectedTypes,
    availableTypes,
    typeLabels,
    fetchTasks,
  } = usePlanSM()

  // State
  const authStore = useAuthStore()
  const currentDate = ref(new Date())
  const users = ref<{ id: number, name: string }[]>([])
  const processes = ref<{ id: number, name: string }[]>([])
  const detailDialog = ref({
    open: false,
    task: null as PlanSMTask | null,
  })

  // Computed
  const dateRangeLabel = computed(() => {
    const date = currentDate.value
    if (view.value === 'day') {
      return date.toLocaleDateString('fr-FR', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      })
    }
    if (view.value === 'week') {
      const startOfWeek = new Date(date)
      startOfWeek.setDate(date.getDate() - date.getDay())
      const endOfWeek = new Date(startOfWeek)
      endOfWeek.setDate(startOfWeek.getDate() + 6)
      return `${startOfWeek.toLocaleDateString('fr-FR', {
        month: 'short',
        day: 'numeric',
      })} - ${endOfWeek.toLocaleDateString('fr-FR', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })}`
    }
    return date.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
  })

  const totalTasks = computed(() => tasks.value.length)
  const hasViewAllTasksPermission = computed(() => {
    const permissions = authStore.user?.permissions ?? []
    return permissions.some(permission =>
      permission?.name === 'view_all_tasks'
      || permission?.slug === 'view_all_tasks',
    )
  })

  // Methods
  function taskType (type: string): string {
    return typeLabels[type] || type
  }

  function formatStatus (status: string): string {
    const statuses: Record<string, string> = {
      non_demarre: 'Non démarré',
      en_cours: 'En cours',
      termine: 'Terminé',
      draft: 'Brouillon',
      validated: 'Validé',
      in_progress: 'En cours',
      completed: 'Complété',
      planifiee: 'Planifiée',
      replanifiee: 'Replanifiée',
    }
    return statuses[status] || status
  }

  function formatDateRange (startDate: string, endDate: string): string {
    if (startDate === endDate) {
      return startDate
    }
    return `${startDate} → ${endDate}`
  }

  function navigatePrevious (): void {
    const newDate = new Date(currentDate.value)
    if (view.value === 'day') {
      newDate.setDate(newDate.getDate() - 1)
    } else if (view.value === 'week') {
      newDate.setDate(newDate.getDate() - 7)
    } else {
      newDate.setMonth(newDate.getMonth() - 1)
    }
    currentDate.value = newDate
    fetchTasks()
  }

  function navigateNext (): void {
    const newDate = new Date(currentDate.value)
    if (view.value === 'day') {
      newDate.setDate(newDate.getDate() + 1)
    } else if (view.value === 'week') {
      newDate.setDate(newDate.getDate() + 7)
    } else {
      newDate.setMonth(newDate.getMonth() + 1)
    }
    currentDate.value = newDate
    fetchTasks()
  }

  function onFiltersChange (): void {
    fetchTasks()
  }

  function openTaskDetail (task: PlanSMTask): void {
    detailDialog.value.task = task
    detailDialog.value.open = true
  }

  async function loadUsers (): Promise<void> {
    try {
      const response = await api.get<{ data: { id: number, name: string }[] }>(
        '/users/collaborators',
      )
      users.value = response.data.data || []
    } catch (error) {
      console.error('Error loading users:', error)
    }
  }

  async function loadProcesses (): Promise<void> {
    try {
      const response = await api.get<{ data: { id: number, name: string }[] }>(
        '/processes/active',
      )
      processes.value = response.data.data || []
    } catch (error) {
      console.error('Error loading processes:', error)
    }
  }

  // Watch view changes
  watch(view, () => {
    currentDate.value = new Date()
    fetchTasks()
  })

  // Lifecycle
  onMounted(async () => {
    if (!hasViewAllTasksPermission.value) {
      showError('Vous n\'avez pas les permissions nécessaires pour accéder au Plan du SM')
      return
    }
    await Promise.all([loadUsers(), loadProcesses(), fetchTasks()])
  })
</script>

<style scoped lang="postcss">
.calendar-grid {
  @apply grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4;
}

.calendar-item {
  @apply p-3 border-l-4 rounded-lg bg-white border border-gray-200 transition-all hover:shadow-lg;
}

.timeline-item {
  @apply bg-white rounded-lg;
}

/* Responsive calendar */
@media (max-width: 768px) {
  .calendar-grid {
    @apply grid-cols-1;
  }
}
</style>
