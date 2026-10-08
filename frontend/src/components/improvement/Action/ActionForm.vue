<template>
  <div class="action-form">
    <h3 class="text-lg font-semibold mb-4">{{ isEdit ? 'Modifier' : 'Nouvelle' }} Action</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Description *</label>
        <textarea v-model="formData.description" class="form-input" required rows="2" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Type *</label>
          <select v-model="formData.type" class="form-input" required>
            <option value="corrective">Corrective</option>
            <option value="preventive">Préventive</option>
            <option value="improvement">Amélioration</option>
          </select>
        </div>
        <div>
          <label class="form-label">Échéance *</label>
          <input v-model="formData.due_date" class="form-input" required type="date">
        </div>
      </div>

      <div>
        <label class="form-label">Responsable</label>
        <select v-model.number="formData.responsible_id" class="form-input">
          <option :value="null">Non assigné</option>
          <!-- Users loaded dynamically -->
        </select>
      </div>

      <div class="flex justify-end gap-3">
        <button class="btn-secondary" type="button" @click="$emit('cancel')">Annuler</button>
        <button class="btn-primary" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import { reactive } from 'vue'
  import { useActionStore } from '@/stores/improvement/actionStore'

  interface Props { action?: any, isEdit?: boolean }
  const props = defineProps<Props>()
  const emit = defineEmits(['success', 'cancel'])

  const actionStore = useActionStore()
  const formData = reactive({
    description: props.action?.description || '',
    type: props.action?.type || 'corrective',
    due_date: props.action?.due_date || '',
    responsible_id: props.action?.responsible_id || null,
  })

  async function handleSubmit () {
    await (props.isEdit && props.action ? actionStore.updateAction(props.action.id, formData) : actionStore.createAction(formData))
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
