<template>
  <div class="nc-analysis">
    <h3 class="text-lg font-semibold mb-4">Analyse des Causes</h3>
    <CauseAnalysisTool :initial-data="analysisData" @save="saveAnalysis" />
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useNonConformityStore } from '@/stores/improvement/nonConformityStore'
  import CauseAnalysisTool from '../shared/CauseAnalysisTool.vue'

  interface Props { ncId: number }
  const props = defineProps<Props>()
  const emit = defineEmits(['updated'])

  const ncStore = useNonConformityStore()
  const analysisData = ref({})

  async function saveAnalysis (data: any) {
    await ncStore.analyze(props.ncId, data)
    emit('updated')
  }
</script>
