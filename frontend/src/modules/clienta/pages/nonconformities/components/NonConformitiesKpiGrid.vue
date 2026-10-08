<template>
  <div class="kpi-grid mb-4">
    <AppWidget
      icon="FileText"
      title="Fiches"
      :value="String(total)"
      variant="primary"
      @click="emit('all')"
    />
    <AppWidget
      icon="AlertOctagon"
      title="NC majeures"
      :value="String(majorCount)"
      variant="error"
      @click="emit('major')"
    />
    <AppWidget
      icon="Clock"
      title="En traitement"
      :value="String(inProgressCount)"
      variant="warning"
      @click="emit('in-progress')"
    />
    <AppWidget
      icon="CheckCircle"
      title="Clôturées"
      :value="String(closedCount)"
      variant="success"
      @click="emit('closed')"
    />
  </div>
</template>

<script setup lang="ts">
  import AppWidget from '@/components/common/AppWidget.vue'

  defineProps({
    total: {
      type: Number,
      required: true,
    },
    majorCount: {
      type: Number,
      required: true,
    },
    inProgressCount: {
      type: Number,
      required: true,
    },
    closedCount: {
      type: Number,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'all'): void
    (event: 'major'): void
    (event: 'in-progress'): void
    (event: 'closed'): void
  }>()
</script>

<style scoped>
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 16px;
}

@media (min-width: 600px) {
  .kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 960px) {
  .kpi-grid {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.kpi-grid :deep(.app-widget) {
  height: 100%;
}
</style>
