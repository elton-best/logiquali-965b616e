<template>
  <div class="objective-form">
    <h3 class="text-lg font-semibold mb-4">{{ isEdit ? 'Modifier' : 'Nouvel' }} Objectif</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Description *</label>
        <textarea v-model="formData.description" class="form-input" required rows="2" />
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Date d'échéance *</label>
          <input v-model="formData.due_date" class="form-input" required type="date">
        </div>
        <div>
          <label class="form-label">Cible</label>
          <input v-model.number="formData.target" class="form-input" type="number">
        </div>
      </div>

      <AxeSelector v-model="formData.axes" label="Axes" />

      <div class="flex justify-end gap-3">
        <button class="btn-secondary" type="button" @click="$emit('cancel')">Annuler</button>
        <button class="btn-primary" type="submit">Enregistrer</button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import { reactive } from 'vue'
  import { useObjectiveStore } from '@/stores/improvement/objectiveStore'
  import AxeSelector from '../shared/AxeSelector.vue'

  interface Props { objective?: any, isEdit?: boolean }
  const props = defineProps<Props>()
  const emit = defineEmits(['success', 'cancel'])

  const objectiveStore = useObjectiveStore()
  const formData = reactive({ description: props.objective?.description || '', due_date: props.objective?.due_date || '', target: props.objective?.target || 100, axes: props.objective?.axes || [] })

  async function handleSubmit () {
    await (props.isEdit && props.objective ? objectiveStore.updateObjective(props.objective.id, formData) : objectiveStore.createObjective(formData))
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
