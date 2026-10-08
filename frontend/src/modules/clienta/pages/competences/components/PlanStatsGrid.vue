<template>
  <v-row class="mb-4">
    <v-col cols="12" md="3">
      <v-card elevation="1">
        <v-card-text>
          <div class="text-caption text-grey">Formations du plan</div>
          <div class="text-h5">{{ stats?.totalFormations || 0 }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="1">
        <v-card-text>
          <div class="text-caption text-grey">Taux de réalisation</div>
          <div class="text-h5">{{ stats?.tauxRealisation || 0 }}%</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="1">
        <v-card-text>
          <div class="text-caption text-grey">Budget engagé</div>
          <div class="text-h5">{{ formatCurrency(stats?.budgetEngaged || 0) }}</div>
        </v-card-text>
      </v-card>
    </v-col>
    <v-col cols="12" md="3">
      <v-card elevation="1">
        <v-card-text>
          <div class="text-caption text-grey">Budget restant</div>
          <div class="text-h5">{{ formatCurrency(stats?.budgetRemaining || 0) }}</div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'
  import type { TrainingPlan } from '../../../types/formation.types'

  type PlanStats = TrainingPlan['stats'] & {
    budgetEngaged?: number
    budgetRemaining?: number
  }

  defineProps({
    stats: {
      type: Object as PropType<PlanStats | null | undefined>,
      default: null,
    },
    formatCurrency: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
  })
</script>
