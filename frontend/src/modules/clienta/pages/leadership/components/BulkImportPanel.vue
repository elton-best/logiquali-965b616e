<template>
  <div v-if="loading" class="panel-loader-overlay">
    <UnifiedLoader
      description="Traitement du fichier et création des comptes"
      :show-skeleton="true"
      title="Import en cours..."
      variant="local"
    />
  </div>

  <h2 class="section-title">
    <span class="section-number">01</span>
    Import en masse
  </h2>

  <ExcelUpload
    @download-template="emit('download-template')"
    @file-selected="emit('file-selected', $event)"
  />

  <div v-if="importResult" class="import-result">
    <v-alert :type="importResult.type" variant="tonal">
      <div class="result-title">{{ importResult.title }}</div>
      <div class="result-message">{{ importResult.message }}</div>
    </v-alert>
  </div>
</template>

<script setup lang="ts">
  import ExcelUpload from '@/components/leadership/ExcelUpload.vue'
  import UnifiedLoader from '@/modules/shared/components/ui/UnifiedLoader.vue'

  type ImportResult = {
    type: 'success' | 'error' | 'warning' | 'info'
    title: string
    message: string
  } | null

  defineProps<{
    loading: boolean
    importResult: ImportResult
  }>()

  const emit = defineEmits<{
    (event: 'download-template'): void
    (event: 'file-selected', file: File): void
  }>()
</script>

<style scoped>
.panel-loader-overlay {
  position: sticky;
  top: 0;
  z-index: 9;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 10px 0 14px;
  margin-bottom: 10px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.82);
  backdrop-filter: blur(2px);
}

.section-title {
  display: flex;
  align-items: center;
  gap: 12px;
  font-size: 1.25rem;
  font-weight: 700;
  color: #1e293b;
  margin-bottom: 16px;
}

.section-number {
  display: grid;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 12px;
  background: linear-gradient(135deg, #5b8dd9 0%, #4a71b0 100%);
  color: white;
  font-size: 0.95rem;
  font-weight: 700;
}

.import-result {
  margin-top: 16px;
}

.result-title {
  font-weight: 600;
  margin-bottom: 4px;
}

.result-message {
  font-size: 0.8125rem;
}
</style>
