<template>
  <v-row>
    <v-col
      v-for="obj in objectives"
      :key="obj.id"
      cols="12"
      lg="4"
      md="6"
    >
      <v-card
        class="objective-card"
        elevation="2"
        hover
        rounded="lg"
        @click="emit('open', obj)"
      >
        <div class="card-header" :style="{ background: getAxeGradient(obj.axeStrategique) }">
          <div class="d-flex align-center justify-space-between mb-2">
            <v-chip color="white" size="small" variant="flat">
              {{ obj.processus }}
            </v-chip>
            <v-chip v-if="obj.axesStrategiques.length > 0" :color="getAxeColor(obj.axeStrategique)" size="small" variant="flat">
              {{ axisDisplayLabel(obj.axeStrategique) }}
            </v-chip>
            <v-chip
              v-if="obj.axesStrategiques.length > 1"
              class="ml-1"
              color="primary"
              size="x-small"
              variant="outlined"
            >
              +{{ obj.axesStrategiques.length - 1 }}
            </v-chip>
          </div>
          <h3 class="text-h6 text-white font-weight-bold">{{ obj.titre }}</h3>
        </div>

        <v-card-text class="pt-4">
          <div class="mb-3">
            <div class="text-caption text-medium-emphasis mb-1">Indicateur</div>
            <div class="text-body-2 font-weight-medium">{{ obj.indicateur }}</div>
          </div>

          <div class="mb-3">
            <div class="d-flex align-center justify-space-between mb-2">
              <span class="text-caption text-medium-emphasis">Taux d'atteinte</span>
              <span class="text-h6 font-weight-bold" :style="{ color: getPerformanceColor(obj.tauxAtteinte) }">
                {{ obj.tauxAtteinte }}%
              </span>
            </div>
            <v-progress-linear
              :color="getPerformanceColor(obj.tauxAtteinte)"
              height="10"
              :model-value="obj.tauxAtteinte"
              rounded
            />
          </div>

          <div class="mb-3">
            <div class="text-caption text-medium-emphasis mb-2">Performance mensuelle</div>
            <div class="mini-chart">
              <div
                v-for="(val, idx) in obj.realisationMensuelle"
                :key="idx"
                class="mini-bar"
                :style="{
                  height: `${val}%`,
                  backgroundColor: getPerformanceColor(val)
                }"
                :title="`M${Number(idx) + 1}: ${val}%`"
              />
            </div>
          </div>

          <v-divider class="my-3" />

          <div class="d-flex align-center justify-space-between">
            <div class="d-flex align-center">
              <v-icon class="mr-1" size="small">mdi-playlist-check</v-icon>
              <span class="text-caption">{{ obj.actions.length }} action(s)</span>
            </div>
            <v-chip size="x-small" variant="outlined">{{ obj.frequence }}</v-chip>
          </div>
        </v-card-text>
      </v-card>
    </v-col>
  </v-row>
</template>

<script setup lang="ts">
  import type { PropType } from 'vue'

  defineProps({
    objectives: {
      type: Array as PropType<Array<Record<string, any>>>,
      required: true,
    },
    getAxeGradient: {
      type: Function as PropType<(axe: string) => string>,
      required: true,
    },
    getAxeColor: {
      type: Function as PropType<(axe: string) => string>,
      required: true,
    },
    axisDisplayLabel: {
      type: Function as PropType<(axe: string) => string>,
      required: true,
    },
    getPerformanceColor: {
      type: Function as PropType<(value: number) => string>,
      required: true,
    },
  })

  const emit = defineEmits<{
    (event: 'open', objective: any): void
  }>()
</script>

<style scoped>
.objective-card {
  cursor: pointer;
  transition: all 0.3s ease;
}

.objective-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15) !important;
}

.card-header {
  padding: 20px;
  border-radius: 12px 12px 0 0;
}

.mini-chart {
  display: flex;
  align-items: flex-end;
  height: 60px;
  gap: 2px;
}

.mini-bar {
  flex: 1;
  min-height: 4px;
  border-radius: 2px;
  transition: all 0.2s ease;
}

.mini-bar:hover {
  opacity: 0.8;
  transform: scaleY(1.1);
}
</style>
