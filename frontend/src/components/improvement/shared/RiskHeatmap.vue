<template>
  <div class="risk-heatmap">
    <div class="heatmap-header">
      <h3 v-if="title" class="heatmap-title">{{ title }}</h3>
      <div class="heatmap-legend">
        <div class="legend-item">
          <span class="legend-color bg-green-500" />
          <span>Faible</span>
        </div>
        <div class="legend-item">
          <span class="legend-color bg-yellow-500" />
          <span>Moyen</span>
        </div>
        <div class="legend-item">
          <span class="legend-color bg-orange-500" />
          <span>Élevé</span>
        </div>
        <div class="legend-item">
          <span class="legend-color bg-red-500" />
          <span>Critique</span>
        </div>
      </div>
    </div>

    <div class="heatmap-container">
      <!-- Y-axis label (Gravité) -->
      <div class="y-axis-label">
        <span>Gravité / Impact</span>
      </div>

      <!-- Grid -->
      <div class="heatmap-grid">
        <!-- Y-axis labels -->
        <div class="y-axis">
          <div v-for="level in gravityLevels" :key="level.value" class="axis-label">
            {{ level.label }}
          </div>
        </div>

        <!-- Matrix cells -->
        <div class="matrix-grid">
          <div
            v-for="g in gravityLevels"
            :key="g.value"
            class="matrix-row"
          >
            <div
              v-for="p in probabilityLevels"
              :key="p.value"
              class="matrix-cell"
              :class="getCellClass(p.value, g.value)"
              @click="handleCellClick(p.value, g.value)"
            >
              <div class="cell-risks">
                <template v-for="risk in getRisksInCell(p.value, g.value)" :key="risk.id">
                  <div class="risk-badge" :title="risk.title">
                    {{ risk.ref || `#${risk.id}` }}
                  </div>
                </template>
              </div>
              <div class="cell-count">
                {{ getRisksInCell(p.value, g.value).length }}
              </div>
            </div>
          </div>
        </div>

        <!-- X-axis labels -->
        <div class="x-axis">
          <div class="axis-spacer" />
          <div v-for="level in probabilityLevels" :key="level.value" class="axis-label">
            {{ level.label }}
          </div>
        </div>
      </div>

      <!-- X-axis label (Probabilité) -->
      <div class="x-axis-label">
        <span>Probabilité / Occurrence</span>
      </div>
    </div>

    <!-- Selected risks details -->
    <div v-if="selectedRisks.length > 0" class="selected-risks">
      <h4 class="text-sm font-semibold text-gray-900 mb-2">
        Risques sélectionnés ({{ selectedRisks.length }})
      </h4>
      <div class="risks-list">
        <div
          v-for="risk in selectedRisks"
          :key="risk.id"
          class="risk-item"
          @click="$emit('risk-click', risk)"
        >
          <div class="risk-ref">{{ risk.ref || `#${risk.id}` }}</div>
          <div class="risk-title">{{ risk.title }}</div>
          <div class="risk-score">Score: {{ risk.criticality_score }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, ref } from 'vue'

  interface Risk {
    id: number
    ref?: string
    title: string
    probability: number
    gravity: number
    criticality_score: number
  }

  interface Props {
    risks: Risk[]
    title?: string
  }

  const props = withDefaults(defineProps<Props>(), {
    title: 'Matrice des Risques',
  })

  const emit = defineEmits<{
    'cell-click': [probability: number, gravity: number]
    'risk-click': [risk: Risk]
  }>()

  const selectedCell = ref<{ probability: number, gravity: number } | null>(null)

  const probabilityLevels = [
    { value: 1, label: 'Rare' },
    { value: 2, label: 'Peu fréquent' },
    { value: 3, label: 'Fréquent' },
    { value: 4, label: 'Très fréquent' },
  ]

  const gravityLevels = [
    { value: 4, label: 'Très élevée' },
    { value: 3, label: 'Élevée' },
    { value: 2, label: 'Moyenne' },
    { value: 1, label: 'Faible' },
  ]

  function getRisksInCell (probability: number, gravity: number) {
    return props.risks.filter(r => r.probability === probability && r.gravity === gravity)
  }

  function getCellClass (probability: number, gravity: number) {
    const score = probability * gravity

    if (score >= 12) return 'cell-critical'
    if (score >= 8) return 'cell-high'
    if (score >= 4) return 'cell-medium'
    return 'cell-low'
  }

  function handleCellClick (probability: number, gravity: number) {
    selectedCell.value = { probability, gravity }
    emit('cell-click', probability, gravity)
  }

  const selectedRisks = computed(() => {
    if (!selectedCell.value) return []
    return getRisksInCell(selectedCell.value.probability, selectedCell.value.gravity)
  })
</script>

<style scoped>
.risk-heatmap {
  @apply bg-white rounded-lg border border-gray-200 p-6;
}

.heatmap-header {
  @apply flex items-center justify-between mb-6;
}

.heatmap-title {
  @apply text-lg font-semibold text-gray-900;
}

.heatmap-legend {
  @apply flex gap-4;
}

.legend-item {
  @apply flex items-center gap-2 text-sm;
}

.legend-color {
  @apply w-4 h-4 rounded;
}

.heatmap-container {
  @apply relative;
}

.y-axis-label {
  @apply absolute left-0 top-1/2 -translate-y-1/2 -rotate-90 origin-center text-sm font-medium text-gray-700;
  writing-mode: vertical-rl;
}

.x-axis-label {
  @apply text-center mt-4 text-sm font-medium text-gray-700;
}

.heatmap-grid {
  @apply ml-12;
}

.y-axis {
  @apply flex flex-col-reverse gap-1 absolute left-0;
}

.axis-label {
  @apply text-xs text-gray-600 h-20 flex items-center justify-end pr-2;
}

.matrix-grid {
  @apply space-y-1;
}

.matrix-row {
  @apply flex gap-1;
}

.matrix-cell {
  @apply relative w-20 h-20 border border-gray-300 cursor-pointer transition-all hover:scale-105 hover:z-10 hover:shadow-lg flex flex-col items-center justify-center;
}

.cell-low {
  @apply bg-green-100 hover:bg-green-200;
}

.cell-medium {
  @apply bg-yellow-100 hover:bg-yellow-200;
}

.cell-high {
  @apply bg-orange-100 hover:bg-orange-200;
}

.cell-critical {
  @apply bg-red-100 hover:bg-red-200;
}

.cell-risks {
  @apply flex flex-wrap gap-1 justify-center mb-1;
}

.risk-badge {
  @apply text-xs px-1.5 py-0.5 bg-white bg-opacity-80 rounded border border-gray-400 font-medium;
}

.cell-count {
  @apply text-xs font-semibold text-gray-700;
}

.x-axis {
  @apply flex gap-1 mt-1;
}

.axis-spacer {
  @apply w-0;
}

.x-axis .axis-label {
  @apply w-20 h-auto text-center;
}

.selected-risks {
  @apply mt-6 pt-6 border-t border-gray-200;
}

.risks-list {
  @apply space-y-2;
}

.risk-item {
  @apply flex items-center gap-3 p-3 bg-gray-50 rounded-lg cursor-pointer hover:bg-gray-100 transition-colors;
}

.risk-ref {
  @apply text-sm font-semibold text-blue-600;
}

.risk-title {
  @apply flex-1 text-sm text-gray-900;
}

.risk-score {
  @apply text-xs text-gray-600 px-2 py-1 bg-white rounded;
}
</style>
