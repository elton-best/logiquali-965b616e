<template>
  <div class="risk-form">
    <h3 class="text-lg font-semibold mb-4">{{ isEdit ? 'Modifier' : 'Nouveau' }} Risque</h3>

    <form class="space-y-4" @submit.prevent="handleSubmit">
      <div>
        <label class="form-label">Description *</label>
        <textarea v-model="formData.description" class="form-input" required rows="2" />
      </div>

      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="form-label">Type</label>
          <select v-model="formData.type" class="form-input">
            <option value="risk">Risque</option>
            <option value="opportunity">Opportunité</option>
          </select>
        </div>
        <div>
          <label class="form-label">Probabilité *</label>
          <select v-model.number="formData.probability" class="form-input" required>
            <option :value="1">1 - Rare</option>
            <option :value="2">2 - Peu probable</option>
            <option :value="3">3 - Possible</option>
            <option :value="4">4 - Probable</option>
            <option :value="5">5 - Très probable</option>
          </select>
        </div>
        <div>
          <label class="form-label">Gravité *</label>
          <select v-model.number="formData.gravity" class="form-input" required>
            <option :value="1">1 - Négligeable</option>
            <option :value="2">2 - Mineur</option>
            <option :value="3">3 - Modéré</option>
            <option :value="4">4 - Majeur</option>
            <option :value="5">5 - Catastrophique</option>
          </select>
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
  import { useRiskStore } from '@/stores/improvement/riskStore'
  import AxeSelector from '../shared/AxeSelector.vue'

  interface Props { risk?: any, isEdit?: boolean }
  const props = defineProps<Props>()
  const emit = defineEmits(['success', 'cancel'])

  const riskStore = useRiskStore()
  const formData = reactive({
    description: props.risk?.description || '',
    type: props.risk?.type || 'risk',
    probability: props.risk?.probability || 3,
    gravity: props.risk?.gravity || 3,
    axes: props.risk?.axes || [],
  })

  async function handleSubmit () {
    await (props.isEdit && props.risk ? riskStore.updateRisk(props.risk.id, formData as any) : riskStore.createRisk(formData as any))
    emit('success')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
.btn-secondary { @apply px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300; }
</style>
