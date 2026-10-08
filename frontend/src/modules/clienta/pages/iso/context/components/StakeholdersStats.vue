<template>
  <v-row class="mb-6">
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="Info"
        title="Parties prenantes"
        :value="stats.total"
        variant="primary"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="CheckCircle"
        title="Faible influence"
        :value="stats.low"
        variant="success"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="AlertTriangle"
        title="Influence moyenne"
        :value="stats.medium"
        variant="warning"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="AlertCircle"
        title="Forte influence"
        :value="stats.high"
        variant="error"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import AppWidget from '@/components/common/AppWidget.vue'

  defineProps({
    stats: {
      type: Object as PropType<{ total: number, low: number, medium: number, high: number }>,
      required: true,
    },
  })
</script>
