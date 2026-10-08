<template>
  <v-row class="mb-6">
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="Info"
        title="Total affiché"
        :value="total"
        variant="primary"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="AlertTriangle"
        title="Priorité élevée"
        :value="highPriorityCount"
        variant="warning"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="Clock"
        title="En cours / surveillé"
        :value="inProgressCount"
        variant="info"
      />
    </v-col>
    <v-col cols="12" md="3" sm="6">
      <AppWidget
        icon="CheckCircle"
        title="Traité / clôturé"
        :value="closedCount"
        variant="success"
      />
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import AppWidget from '@/components/common/AppWidget.vue'

  defineProps({
    total: {
      type: Number,
      required: true,
    },
    highPriorityCount: {
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
</script>
