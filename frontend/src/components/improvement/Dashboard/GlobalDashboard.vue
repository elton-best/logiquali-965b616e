<script setup lang="ts">
  import { onMounted } from 'vue'
  import { useAxeStore } from '@/stores/improvement/axeStore'
  import { useDashboardStore } from '@/stores/improvement/dashboardStore'
  // import StatCard from '../shared/ImprovementStatCard.vue'

  const dashboardStore = useDashboardStore()
  const axeStore = useAxeStore()

  onMounted(async () => {
    try {
      await axeStore.fetchAxes(true)
      await dashboardStore.fetchGlobal()
    } catch (error) {
      console.error('Error loading dashboard:', error)
    }
  })
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Amélioration Continue</h1>
          <p class="text-gray-600 mt-1">Dashboard QHSE - {{ axeStore.systemMode }}</p>
        </div>

        <div class="flex gap-2">
          <span
            v-for="axe in axeStore.activeAxes"
            :key="axe.id"
            class="px-4 py-2 rounded-full font-medium shadow-sm"
            :style="{
              backgroundColor: `${axe.color}20`,
              color: axe.color,
              border: `2px solid ${axe.color}`
            }"
          >
            {{ axe.name }}
          </span>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="dashboardStore.loading" class="flex items-center justify-center py-20">
      <div class="text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto" />
        <p class="mt-4 text-gray-600">Chargement du dashboard...</p>
      </div>
    </div>

    <!-- Error State -->
    <div v-else-if="dashboardStore.error" class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
      <div class="flex">
        <i class="mdi mdi-alert-circle text-red-500 text-xl mr-3" />
        <div>
          <h3 class="text-red-800 font-medium">Erreur de chargement</h3>
          <p class="text-red-700 text-sm mt-1">{{ dashboardStore.error }}</p>
        </div>
      </div>
    </div>

    <!-- Dashboard Content -->
    <div v-else>
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <ImprovementStatCard
          color="#ef4444"
          icon="alert-triangle"
          title="Non-Conformités"
          :value="dashboardStore.stats?.non_conformities?.total || 0"
        />
        <ImprovementStatCard
          color="#3b82f6"
          icon="clipboard-check"
          title="Audits Réalisés"
          :value="dashboardStore.stats?.audits?.completed || 0"
        />
        <ImprovementStatCard
          color="#f59e0b"
          icon="alert-circle"
          title="Risques Critiques"
          :value="dashboardStore.stats?.risks?.high_criticality || 0"
        />
        <ImprovementStatCard
          color="#10b981"
          icon="target"
          title="Objectifs Atteints"
          :value="`${dashboardStore.stats?.objectives?.average_progress || 0}%`"
        />
      </div>

      <!-- Detailed Stats Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Non-Conformities Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="mdi mdi-alert-triangle text-red-500 mr-2" />
            Non-Conformités par Gravité
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Critiques</span>
              <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.non_conformities?.by_severity?.critical || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Majeures</span>
              <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.non_conformities?.by_severity?.major || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Mineures</span>
              <span class="px-3 py-1 bg-yellow-100 text-yellow-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.non_conformities?.by_severity?.minor || 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Audits Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="mdi mdi-clipboard-check text-blue-500 mr-2" />
            Audits
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Planifiés</span>
              <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.audits?.upcoming || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Complétés</span>
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.audits?.completed || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Constatations</span>
              <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.audits?.findings_count || 0 }}
              </span>
            </div>
          </div>
        </div>

        <!-- Indicators Details -->
        <div class="bg-white rounded-lg shadow-md p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
            <i class="mdi mdi-chart-line text-purple-500 mr-2" />
            Indicateurs
          </h3>
          <div class="space-y-3">
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Avec Alertes</span>
              <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.indicators?.with_alerts || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">Au-dessus Cible</span>
              <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.indicators?.above_target || 0 }}
              </span>
            </div>
            <div class="flex justify-between items-center">
              <span class="text-sm text-gray-600">En-dessous Cible</span>
              <span class="px-3 py-1 bg-orange-100 text-orange-700 rounded-full text-sm font-medium">
                {{ dashboardStore.stats?.indicators?.below_target || 0 }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Critical Alerts -->
      <div v-if="dashboardStore.criticalAlerts.length > 0" class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg">
        <h3 class="text-lg font-semibold text-red-900 mb-4 flex items-center">
          <i class="mdi mdi-alert-octagon mr-2" />
          Alertes Critiques ({{ dashboardStore.criticalAlerts.length }})
        </h3>
        <div class="space-y-2">
          <div
            v-for="alert in dashboardStore.criticalAlerts.slice(0, 5)"
            :key="alert.id"
            class="bg-white p-3 rounded border-l-2 border-red-500"
          >
            <p class="text-sm text-gray-900">{{ alert.message }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ alert.created_at }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
