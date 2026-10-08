<template>
  <div class="nc-form">
    <h3 class="text-lg font-semibold mb-4">{{ isEdit ? 'Modifier' : 'Nouvelle' }} Non-Conformité</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Description *</label>
        <textarea v-model="formData.description" class="form-input" required rows="3" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Gravité *</label>
          <select v-model.number="formData.severity" class="form-input" required>
            <option :value="1">Mineure</option>
            <option :value="2">Majeure</option>
            <option :value="3">Critique</option>
          </select>
        </div>
        <div>
          <label class="form-label">Source</label>
          <select v-model="formData.source" class="form-input">
            <option value="audit">Audit</option>
            <option value="inspection">Inspection</option>
            <option value="reclamation">Réclamation</option>
            <option value="autre">Autre</option>
          </select>
        </div>
      </div>

      <AxeSelector v-model="formData.axes" label="Axes QHSE" />

      <div class="flex justify-end gap-3">
        <button class="btn-secondary" type="button" @click="$emit('cancel')">Annuler</button>
        <button class="btn-primary" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import { reactive } from 'vue'
  import { useNonConformityStore } from '@/stores/improvement/nonConformityStore'
  import AxeSelector from '../shared/AxeSelector.vue'

  interface Props { nc?: any, isEdit?: boolean }
  const props = defineProps<Props>()
  const emit = defineEmits(['success', 'cancel'])

  const ncStore = useNonConformityStore()
  const formData = reactive({
    description: props.nc?.description || '',
    severity: props.nc?.severity || 2,
    source: props.nc?.source || 'audit',
    axes: props.nc?.axes || ['Q'],
  })

  async function handleSubmit () {
    await (props.isEdit && props.nc ? ncStore.update(props.nc.id, formData as any) : ncStore.create(formData as any))
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
