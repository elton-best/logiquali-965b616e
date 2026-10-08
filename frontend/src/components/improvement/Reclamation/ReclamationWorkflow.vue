<template>
  <div class="reclamation-workflow">
    <h3 class="text-lg font-semibold mb-4">Traitement de la Réclamation</h3>

    <WorkflowStepper
      :current-state="currentStatus"
      :show-actions="true"
      :states="workflowStates"
      @transition="handleTransition"
    />

    <div class="mt-6 space-y-4">
      <div>
        <label class="form-label">Réponse</label>
        <textarea v-model="response" class="form-input" placeholder="Votre réponse au client..." rows="4" />
      </div>

      <button class="btn-primary" @click="submitResponse">Envoyer la réponse</button>
    </div>
  </div>
</template>

<script setup lang="ts">
  import type { Reclamation, WorkflowState } from '@/types/improvement'
  import { ref } from 'vue'
  import { useReclamationStore } from '@/stores/improvement/reclamationStore'
  import WorkflowStepper from '../shared/WorkflowStepper.vue'

  interface Props { reclamation: Reclamation }
  const props = defineProps<Props>()
  const emit = defineEmits(['updated'])

  const reclamationStore = useReclamationStore()
  const response = ref('')
  const currentStatus = ref<NonNullable<Reclamation['status']>>(props.reclamation.status ?? 'pending')

  const workflowStates: WorkflowState[] = [
    { id: 1, entity_type: 'reclamation', name: 'pending', slug: 'pending', color: 'warning', icon: 'mdi-clock-outline', order: 1, next_states: ['in_progress'], is_initial: true, is_final: false },
    { id: 2, entity_type: 'reclamation', name: 'in_progress', slug: 'in_progress', color: 'info', icon: 'mdi-progress-clock', order: 2, next_states: ['closed', 'cancelled'], is_initial: false, is_final: false },
    { id: 3, entity_type: 'reclamation', name: 'closed', slug: 'closed', color: 'success', icon: 'mdi-check-circle', order: 3, next_states: [], is_initial: false, is_final: true },
  ]

  async function handleTransition (stateName: string) {
    const nextStatus = stateName as NonNullable<Reclamation['status']>
    await reclamationStore.updateReclamation(props.reclamation.id, { status: nextStatus })
    currentStatus.value = nextStatus
    emit('updated')
  }

  async function submitResponse () {
    await reclamationStore.respondReclamation(props.reclamation.id, response.value)
    emit('updated')
  }
</script>

<style scoped>
.form-label { @apply block text-sm font-medium text-gray-700 mb-1; }
.form-input { @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500; }
.btn-primary { @apply px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700; }
</style>
