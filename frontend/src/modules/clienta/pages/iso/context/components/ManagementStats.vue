<template>
  <v-row class="mb-6">
    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="Info" title="Total" :value="stats.total" variant="primary" />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="CheckCircle"
        title="Management"
        :value="stats.management"
        variant="success"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="AlertTriangle"
        title="Réalisation"
        :value="stats.realization"
        variant="warning"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget icon="AlertCircle" title="Support" :value="stats.support" variant="info" />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import { AppWidget } from '@/components/common'

  defineProps<{
    stats: {
      total: number
      management: number
      realization: number
      support: number
    }
  }>()
</script>
