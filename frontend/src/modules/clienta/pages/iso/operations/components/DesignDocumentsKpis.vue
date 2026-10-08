<template>
  <v-row class="mb-4">
    <v-col cols="12" md="3">
      <v-card elevation="0" rounded="lg">
        <v-card-text>
          <div class="text-caption text-medium-emphasis">Total documents</div>
          <div class="text-h5 font-weight-bold">{{ kpis.total }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="0" rounded="lg">
        <v-card-text>
          <div class="text-caption text-medium-emphasis">Validés</div>
          <div class="text-h5 font-weight-bold text-success">{{ kpis.valides }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="0" rounded="lg">
        <v-card-text>
          <div class="text-caption text-medium-emphasis">En révision</div>
          <div class="text-h5 font-weight-bold text-warning">{{ kpis.enRevision }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="0" rounded="lg">
        <v-card-text>
          <div class="text-caption text-medium-emphasis">Révision à planifier</div>
          <div class="text-h5 font-weight-bold text-error">{{ kpis.aReviser }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  defineProps<{
    kpis: {
      total: number
      valides: number
      enRevision: number
      aReviser: number
    }
  }>()
</script>
