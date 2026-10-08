<template>
  <v-card
    v-if="canViewWidget"
    class="process-dashboard-widget"
    elevation="2"
    rounded="xl"
  >
    <v-card-title class="d-flex align-center justify-space-between pa-6">
      <div class="d-flex align-center gap-2">
        <v-icon color="primary" size="28">mdi-sitemap</v-icon>
        <span class="text-h6 font-weight-bold">Processus</span>
      </div>
      <v-btn
        v-if="canReadProcesses"
        color="primary"
        size="small"
        variant="text"
        @click="goToProcesses"
      >
        Voir tout
        <v-icon class="ml-1" size="16">mdi-arrow-right</v-icon>
      </v-btn>
    </v-card-title>
    <v-divider />

    <!-- Loading State -->
    <template v-if="loading">
      <v-card-text>
        <v-row>
          <v-col v-for="i in 3" :key="i" cols="12" md="4">
            <v-skeleton-loader type="card" />
          </v-col>
        </v-row>
      </v-card-text>
    </template>

    <!-- Content -->
    <template v-else>
      <template v-if="canReadProcesses">
        <!-- Quick Stats -->
        <v-card-text class="pb-0">
          <v-row>
            <!-- Total Processes -->
            <v-col cols="12" md="4">
              <div
                class="text-center pa-4 rounded-lg"
                style="background: rgba(var(--v-theme-primary), 0.05)"
              >
                <div class="text-h4 font-weight-bold text-primary mb-1">
                  {{ stats.total }}
                </div>
                <div class="text-body-2 text-medium-emphasis">
                  Total processus
                </div>
              </div>
            </v-col>

            <!-- Active Processes -->
            <v-col cols="12" md="4">
              <div
                class="text-center pa-4 rounded-lg"
                style="background: rgba(var(--v-theme-success), 0.05)"
              >
                <div class="text-h4 font-weight-bold text-success mb-1">
                  {{ stats.active }}
                </div>
                <div class="text-body-2 text-medium-emphasis">Actifs</div>
              </div>
            </v-col>

            <!-- Processes with Issues -->
            <v-col cols="12" md="4">
              <div
                class="text-center pa-4 rounded-lg"
                style="background: rgba(var(--v-theme-warning), 0.05)"
              >
                <div class="text-h4 font-weight-bold text-warning mb-1">
                  {{ stats.needsReview }}
                </div>
                <div class="text-body-2 text-medium-emphasis">À réviser</div>
              </div>
            </v-col>
          </v-row>
        </v-card-text>

        <!-- Process Type Distribution -->
        <v-card-text class="pt-4">
          <div
            class="text-subtitle-2 font-weight-bold mb-3 text-medium-emphasis"
          >
            Répartition par type
          </div>
          <v-row dense>
            <v-col v-for="type in processTypes" :key="type.value" cols="12">
              <div class="d-flex align-center justify-space-between mb-2">
                <div class="d-flex align-center gap-2">
                  <v-icon :color="type.color" size="20">{{ type.icon }}</v-icon>
                  <span class="text-body-2">{{ type.label }}</span>
                </div>
                <v-chip :color="type.color" size="small" variant="tonal">
                  {{ type.count }}
                </v-chip>
              </div>
              <v-progress-linear
                :color="type.color"
                height="6"
                :model-value="type.percentage"
                rounded
              />
            </v-col>
          </v-row>
        </v-card-text>

        <!-- Recent Processes -->
        <v-card-text class="pt-0">
          <div
            class="text-subtitle-2 font-weight-bold mb-3 text-medium-emphasis"
          >
            Processus récents
          </div>
          <v-list
            v-if="recentProcesses.length > 0"
            class="py-0"
            density="compact"
          >
            <v-list-item
              v-for="process in recentProcesses"
              :key="process.id"
              class="px-0"
              @click="goToProcess(process.id)"
            >
              <template #prepend>
                <v-avatar
                  :color="getTypeColor(process.category)"
                  size="32"
                  variant="tonal"
                >
                  <v-icon size="16">{{ getTypeIcon(process.category) }}</v-icon>
                </v-avatar>
              </template>

              <v-list-item-title class="text-body-2 font-weight-medium">
                {{ process.title }}
              </v-list-item-title>
              <v-list-item-subtitle class="text-caption">
                {{ process.code }} • Pilote: {{ process.pilot?.name }}
              </v-list-item-subtitle>

              <template #append>
                <v-chip
                  :color="getStatusColor(process.status)"
                  size="x-small"
                  variant="flat"
                >
                  {{ getStatusLabel(process.status) }}
                </v-chip>
              </template>
            </v-list-item>
          </v-list>

          <div v-else class="text-center py-6">
            <v-icon color="grey-lighten-1" size="48">mdi-inbox</v-icon>
            <p class="text-body-2 text-medium-emphasis mt-2">
              Aucun processus disponible
            </p>
          </div>
        </v-card-text>
      </template>

      <!-- Actions -->
      <v-divider v-if="canCreateProcesses" />
      <v-card-actions v-if="canCreateProcesses" class="pa-4">
        <v-btn
          block
          color="primary"
          prepend-icon="mdi-plus"
          rounded="lg"
          variant="tonal"
          @click="createProcess"
        >
          Créer un processus
        </v-btn>
      </v-card-actions>
    </template>
  </v-card>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'
  import { useRouter } from 'vue-router'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { useProcessStore } from '@/stores/processStore'
  import { expandPermissionAliases } from '@/utils/permissions'

  const router = useRouter()
  const processStore = useProcessStore()
  const authStore = useAuthStore()

  const loading = ref(false)

  const stats = computed(() => {
    const processes = processStore.processes || []
    return {
      total: processes.length,
      active: processes.filter(p => p.status === 'active').length,
      needsReview: processes.filter(p => p.status === 'in_review').length,
    }
  })

  const processTypes = computed(() => {
    const processes = processStore.processes || []
    const total = processes.length || 1

    const types = [
      {
        value: 'strategic',
        label: 'Management',
        icon: 'mdi-chart-line',
        color: 'purple',
        count: processes.filter(p => p.category === 'pilotage').length,
      },
      {
        value: 'core',
        label: 'Opérationnel',
        icon: 'mdi-cog',
        color: 'teal',
        count: processes.filter(p => p.category === 'operationnel').length,
      },
      {
        value: 'enabling',
        label: 'Support',
        icon: 'mdi-hand-heart',
        color: 'indigo',
        count: processes.filter(p => p.category === 'support').length,
      },
    ]

    return types.map(type => ({
      ...type,
      percentage: (type.count / total) * 100,
    }))
  })

  const recentProcesses = computed(() => {
    const processes = processStore.processes || []
    return [...processes]
      .toSorted(
        (a, b) =>
          new Date(b.updated_at || '').getTime()
          - new Date(a.updated_at || '').getTime(),
      )
      .slice(0, 5)
  })

  onMounted(async () => {
    loading.value = true
    try {
      await processStore.fetchProcesses()
    } finally {
      loading.value = false
    }
  })

  function getEffectivePermissionSet (): Set<string> {
    return getNavigationPermissionSet(authStore.user as any)
  }

  function hasRole (roleName: string): boolean {
    const roleNames = Array.isArray((authStore.user as any)?.role_names)
      ? (authStore.user as any).role_names
      : []
    if (roleNames.includes(roleName)) {
      return true
    }

    const roles = (authStore.user as any)?.roles
    if (!Array.isArray(roles)) {
      return false
    }
    return roles.some(
      (role: any) =>
        role?.name === roleName
        || role?.attributes?.name === roleName
        || role === roleName,
    )
  }

  function canAccess (requiredPermissions: string[]): boolean {
    if (
      authStore.user?.user_type === 'super_admin'
      || hasRole('admin_entreprise')
    ) {
      return true
    }

    const directPermissions = getEffectivePermissionSet()
    return requiredPermissions.some(permission =>
      expandPermissionAliases(permission).some(alias =>
        directPermissions.has(alias),
      ),
    )
  }

  function routeExists (path: string): boolean {
    const resolved = router.resolve(path)
    return resolved.matched.length > 0
  }

  function canNavigate (path: string, requiredPermissions: string[]): boolean {
    return routeExists(path) && canAccess(requiredPermissions)
  }

  const canReadProcesses = computed(() =>
    canNavigate('/company/context/management-system', ['evaluation.revue_processus.read']),
  )
  const canCreateProcesses = computed(() =>
    canNavigate('/company/context/management-system', ['processes.create']),
  )
  const canViewWidget = computed(
    () => canReadProcesses.value || canCreateProcesses.value,
  )

  function goToProcesses () {
    if (!canNavigate('/company/context/management-system', ['evaluation.revue_processus.read'])) {
      return
    }
    router.push('/company/context/management-system')
  }

  function goToProcess (id: number) {
    if (!canNavigate('/company/context/management-system', ['evaluation.revue_processus.read'])) {
      return
    }
    router.push(`/company/context/management-system/${id}`)
  }

  function createProcess () {
    if (
      !canNavigate('/company/context/management-system', ['processes.create'])
    ) {
      return
    }
    router.push({
      path: '/company/context/management-system',
      query: { openCreate: '1' },
    })
  }

  function getTypeIcon (type: string): string {
    const typeMap: Record<string, string> = {
      pilotage: 'mdi-chart-line',
      operationnel: 'mdi-cog',
      support: 'mdi-hand-heart',
      mesure_amelioration: 'mdi-chart-timeline-variant',
    }
    return typeMap[type] || 'mdi-sitemap'
  }

  function getTypeColor (type: string): string {
    const colorMap: Record<string, string> = {
      pilotage: 'purple',
      operationnel: 'teal',
      support: 'indigo',
      mesure_amelioration: 'orange',
    }
    return colorMap[type] || 'grey'
  }

  function getStatusColor (status: string): string {
    const colorMap: Record<string, string> = {
      draft: 'grey',
      in_review: 'warning',
      validated: 'info',
      active: 'success',
      obsolete: 'error',
    }
    return colorMap[status] || 'grey'
  }

  function getStatusLabel (status: string): string {
    const labelMap: Record<string, string> = {
      draft: 'Brouillon',
      in_review: 'En révision',
      validated: 'Validé',
      active: 'Actif',
      obsolete: 'Obsolète',
    }
    return labelMap[status] || status
  }
</script>

<style scoped>
.process-dashboard-widget :deep(.v-list-item) {
  cursor: pointer;
  transition: background-color 0.2s;
}

.process-dashboard-widget :deep(.v-list-item:hover) {
  background-color: rgba(var(--v-theme-primary), 0.05);
}
</style>
