<template>
  <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <AppWidget
      clickable
      :icon="Users"
      title="Total"
      :value="String(stats.total)"
      variant="primary"
    />
    <AppWidget
      clickable
      :icon="CheckCircle"
      title="Actifs"
      :value="String(stats.active)"
      variant="success"
    />
    <AppWidget
      clickable
      :icon="UserX"
      title="Inactifs"
      :value="String(stats.inactive)"
      variant="warning"
    />
    <AppWidget
      clickable
      :icon="MapPin"
      title="Sites"
      :value="String(sitesCount)"
      variant="info"
    />
  </div>
</template>

<script setup lang="ts">
  import { CheckCircle, MapPin, Users, UserX } from 'lucide-vue-next'
  import AppWidget from '@/components/common/AppWidget.vue'

  interface Props {
    stats: {
      total: number
      active: number
      inactive: number
    }
    sitesCount: number
  }

  defineProps<Props>()
</script>
