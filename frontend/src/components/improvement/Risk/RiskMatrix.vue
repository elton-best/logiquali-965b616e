<template>
  <div class="risk-matrix-wrapper">
    <h3 class="text-lg font-semibold mb-4">Matrice des Risques</h3>
    <RiskHeatmap :risks="risks" @cell-click="handleCellClick" @risk-click="$emit('risk-click', $event)" />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useRiskStore } from '@/stores/improvement/riskStore'
  import RiskHeatmap from '../shared/RiskHeatmap.vue'

  defineEmits(['risk-click'])
  const riskStore = useRiskStore()
  const risks = ref<any[]>([])

  riskStore.fetchRisks().then(() => {
    risks.value = riskStore.risks
  })

  function handleCellClick (probability: number, gravity: number) {
    console.log('Cell clicked:', probability, gravity)
  }
</script>
