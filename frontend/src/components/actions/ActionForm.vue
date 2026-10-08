<template>
  <div class="action-form">
    <form @submit.prevent="handleSubmit">
      <!-- Type & Priorité -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium mb-2">Type d'action</label>
          <select
            v-model="formData.type"
            class="w-full px-3 py-2 border rounded-lg"
            required
          >
            <option value="">Sélectionner...</option>
            <option value="corrective">Corrective</option>
            <option value="preventive">Préventive</option>
            <option value="improvement">Amélioration</option>
          </select>
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Priorité</label>
          <select
            v-model="formData.priority"
            class="w-full px-3 py-2 border rounded-lg"
            required
          >
            <option value="">Sélectionner...</option>
            <option value="low">Basse</option>
            <option value="medium">Moyenne</option>
            <option value="high">Haute</option>
            <option value="critical">Critique</option>
          </select>
        </div>
      </div>

      <!-- Titre -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Titre *</label>
        <input
          v-model="formData.title"
          class="w-full px-3 py-2 border rounded-lg"
          placeholder="Titre de l'action"
          required
          type="text"
        >
      </div>

      <!-- Description -->
      <div class="mb-4">
        <label class="block text-sm font-medium mb-2">Description *</label>
        <textarea
          v-model="formData.description"
          class="w-full px-3 py-2 border rounded-lg"
          placeholder="Description détaillée de l'action"
          required
          rows="4"
        />
      </div>

      <!-- Responsable & Deadline -->
      <div class="grid grid-cols-2 gap-4 mb-4">
        <div>
          <label class="block text-sm font-medium mb-2">Responsable *</label>
          <input
            v-model.number="formData.responsible_id"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="ID utilisateur"
            required
            type="number"
          >
        </div>

        <div>
          <label class="block text-sm font-medium mb-2">Deadline</label>
          <input
            v-model="formData.deadline"
            class="w-full px-3 py-2 border rounded-lg"
            type="date"
          >
        </div>
      </div>

      <!-- Actions -->
      <div class="flex justify-end gap-2 mt-6">
        <button
          class="px-4 py-2 border rounded-lg hover:bg-gray-50"
          type="button"
          @click="emit('cancel')"
        >
          Annuler
        </button>
        <button
          class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700"
          :disabled="loading"
          type="submit"
        >
          {{ loading ? 'Enregistrement...' : action ? 'Modifier' : 'Créer' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
  import type { Action } from '@/types/action'
  import { reactive, watch } from 'vue'

  interface Props {
    action?: Action | null
    loading?: boolean
  }

  interface Emits {
    (e: 'submit', data: Partial<Action>): void
    (e: 'cancel'): void
  }

  const props = withDefaults(defineProps<Props>(), {
    action: null,
    loading: false,
  })

  const emit = defineEmits<Emits>()

  const formData = reactive({
    title: '',
    description: '',
    type: '' as 'corrective' | 'preventive' | 'improvement' | 'routine' | '',
    priority: '' as 'low' | 'medium' | 'high' | 'critical' | '',
    responsible_id: null as number | null,
    deadline: '',
  })

  // Populate form with action data if editing
  watch(() => props.action, action => {
    if (action) {
      formData.title = action.title || ''
      formData.description = action.description || ''
      formData.type = action.type || ''
      formData.priority = action.priority || ''
      formData.responsible_id = action.responsible?.id || null
      formData.deadline = action.deadline || ''
    }
  }, { immediate: true })

  function handleSubmit () {
    emit('submit', { ...formData } as Partial<Action>)
  }
</script>

<style scoped>
/* Optional: Add custom styles if needed */
</style>
