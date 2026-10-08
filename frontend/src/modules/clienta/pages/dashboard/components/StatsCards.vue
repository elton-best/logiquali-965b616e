<template>
  <v-row class="mb-6">
    <v-col
      v-for="stat in stats"
      :key="stat.id"
      cols="12"
      lg="3"
      md="6"
    >
      <AppWidget
        :icon="stat.icon"
        :title="stat.title"
        :trend="stat.trend"
        :value="stat.value"
        :variant="stat.variant"
        @click="emit('stat-click', stat.id)"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import { AppWidget } from '@/components/common'

  type StatItem = {
    id: number
    title: string
    value: string
    variant: string
    icon: string
    trend: number
  }

  defineProps<{
    stats: StatItem[]
  }>()

  const emit = defineEmits<{
    (event: 'stat-click', id: number): void
  }>()
</script>
