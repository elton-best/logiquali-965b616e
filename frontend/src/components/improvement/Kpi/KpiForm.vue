<template>
  <div class="kpi-form">
    <h3 class="text-lg font-semibold mb-4">{{ isEdit ? 'Modifier' : 'Nouvel' }} Indicateur</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Nom *</label>
        <input v-model="formData.name" class="form-input" required type="text">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="form-label">Unité</label>
          <input v-model="formData.unit" class="form-input" placeholder="%, €, jours..." type="text">
        </div>
        <div>
          <label class="form-label">Seuil cible</label>
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
  import { useIndicateurStore } from '@/stores/improvement/indicateurStore'
  import AxeSelector from '../shared/AxeSelector.vue'

  interface Props { kpi?: any, isEdit?: boolean }
  const props = defineProps<Props>()
  const emit = defineEmits(['success', 'cancel'])

  const indicateurStore = useIndicateurStore()
  const formData = reactive({ name: props.kpi?.name || '', unit: props.kpi?.unit || '', target: props.kpi?.target || 0, axes: props.kpi?.axes || [] })

  async function handleSubmit () {
    await (props.isEdit && props.kpi ? indicateurStore.update(props.kpi.id, formData as any) : indicateurStore.create(formData as any))
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
