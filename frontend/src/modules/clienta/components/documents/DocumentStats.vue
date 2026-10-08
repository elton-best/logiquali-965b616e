<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <div
      v-for="stat in statCards"
      :key="stat.label"
      class="stat-card bg-white rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-300"
      :style="{ borderLeft: `4px solid ${stat.color}` }"
    >
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-600">{{ stat.label }}</p>
          <p class="text-3xl font-bold mt-2" :style="{ color: stat.color }">{{ stat.value }}</p>
        </div>
        <div class="p-3 rounded-full" :style="{ backgroundColor: stat.color + '20' }">
          <component :is="stat.icon" class="w-6 h-6" :style="{ color: stat.color }" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { DocumentStats } from '../../types/document.types'
  import { AlertCircle, CheckCircle, Clock, FileText } from 'lucide-vue-next'
  import { computed } from 'vue'

  const props = defineProps<{
    stats: DocumentStats | null
  }>()

  const statCards = computed(() => [
    {
      label: 'Total Documents',
      value: props.stats?.total || 0,
      icon: FileText,
      color: '#3B82F6',
    },
    {
      label: 'validé - version 1',
      value: props.stats?.par_statut?.find(s => s.statut === 'valide')?.count || 0,
      icon: CheckCircle,
      color: '#10B981',
    },
    {
      label: 'brouillon en cours de vérification',
      value: props.stats?.par_statut?.find(s => s.statut === 'en_revision')?.count || 0,
      icon: Clock,
      color: '#F59E0B',
    },
    {
      label: 'À réviser',
      value: props.stats?.a_reviser || 0,
      icon: AlertCircle,
      color: '#EF4444',
    },
  ])
</script>

<style scoped>
.stat-card {
  transform: translateY(0);
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
