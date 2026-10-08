<template>
  <div class="audit-report">
    <h3 class="text-lg font-semibold mb-4">Rapport d'Audit</h3>

    <div class="space-y-4">
      <div class="report-section">
        <h4 class="font-semibold mb-2">Informations générales</h4>
        <p><strong>Titre:</strong> {{ audit.title }}</p>
        <p><strong>Date:</strong> {{ formatDate(audit.planned_date) }}</p>
        <p><strong>Type:</strong> {{ audit.type }}</p>
      </div>

      <div class="report-section">
        <h4 class="font-semibold mb-2">Résumé</h4>
        <textarea v-model="summary" class="form-input" placeholder="Résumé de l'audit..." rows="4" />
      </div>

      <div class="flex gap-3">
        <button class="btn-primary" @click="saveReport">Enregistrer</button>
        <button class="btn-secondary" @click="exportPdf">Exporter PDF</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { ref } from 'vue'
  import { useAuditStore } from '@/stores/improvement/auditStore'

  interface Props { audit: any }
  const props = defineProps<Props>()
  const emit = defineEmits(['updated'])

  const auditStore = useAuditStore()
  const summary = ref(props.audit.summary || '')

  const formatDate = (date: string) => new Date(date).toLocaleDateString('fr-FR')
  async function saveReport () {
    await auditStore.updateAudit(props.audit.id, { summary: summary.value })
    emit('updated')
  }
  function exportPdf () {
    console.log('Export PDF - to implement')
  }
</script>

<style scoped>
.report-section { @apply p-4 bg-gray-50 rounded-lg; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
