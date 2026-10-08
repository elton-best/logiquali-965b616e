<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <AppWidget
      v-for="(stat, index) in statsData"
      :key="index"
      clickable
      :icon="stat.iconComponent"
      :title="stat.label"
      :value="stat.value"
      :variant="stat.variant"
    />
  </div>
</template>

<script setup lang="ts">
  import { Calendar, CheckCircle, ClipboardCheck, Clock } from 'lucide-vue-next'
  import { computed } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'

  interface Props {
    stats: {
      total: number
      planned: number
      in_progress: number
      completed: number
    }
  }

  const props = defineProps<Props>()

  const statsData = computed(() => [
    {
      label: 'Total',
      value: props.stats.total,
      iconComponent: ClipboardCheck,
      variant: 'primary' as const,
    },
    {
      label: 'Planifiés',
      value: props.stats.planned,
      iconComponent: Calendar,
      variant: 'info' as const,
    },
    {
      label: 'En cours',
      value: props.stats.in_progress,
      iconComponent: Clock,
      variant: 'warning' as const,
    },
    {
      label: 'Terminés',
      value: props.stats.completed,
      iconComponent: CheckCircle,
      variant: 'success' as const,
    },
  ])
</script>
