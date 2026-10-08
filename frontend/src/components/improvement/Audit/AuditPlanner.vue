<template>
  <div class="audit-planner">
    <h3 class="text-lg font-semibold mb-4">Planifier un Audit</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Titre *</label>
        <input v-model="formData.title" class="form-input" required type="text">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Date prévue *</label>
          <input v-model="formData.planned_date" class="form-input" required type="date">
        </div>
        <div>
          <label class="form-label">Type d'audit *</label>
          <select v-model="formData.type" class="form-input" required>
            <option value="internal">Interne</option>
            <option value="external">Externe</option>
            <option value="surveillance">Surveillance</option>
          </select>
        </div>
      </div>

      <AxeSelector v-model="formData.axes" label="Axes" />

      <div class="flex justify-end gap-3">
        <button class="btn-secondary" type="button" @click="$emit('cancel')">Annuler</button>
        <button class="btn-primary" type="submit">Planifier</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import { reactive } from 'vue'
  import { useAuditStore } from '@/stores/improvement/auditStore'
  import AxeSelector from '../shared/AxeSelector.vue'

  const emit = defineEmits(['success', 'cancel'])
  const auditStore = useAuditStore()

  const formData = reactive({
    title: '',
    planned_date: '',
    type: 'internal',
    axes: ['Q'],
  })

  async function handleSubmit () {
    await auditStore.createAudit(formData)
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
