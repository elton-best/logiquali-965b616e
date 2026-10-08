/**
 * ReviewWorkflow Component
 * Document review and validation workflow
 */

<script setup lang="ts">
  import type { Document } from '@/types/document'
  import { AlertCircle, CheckCircle, Send, XCircle } from 'lucide-vue-next'
  import { ref } from 'vue'

  interface Props {
    document: Document
    loading?: boolean
  }

  const _props = withDefaults(defineProps<Props>(), {
    loading: false,
  })

  const emit = defineEmits<{
    'approve': []
    'reject': [reason: string]
    'request-changes': [comments: string]
  }>()

  const showRejectModal = ref(false)
  const showRequestChangesModal = ref(false)
  const rejectReason = ref('')
  const changeComments = ref('')

  function handleApprove () {
    if (confirm('Êtes-vous sûr de vouloir valider ce document ?')) {
      emit('approve')
    }
  }

  function handleReject () {
    if (!rejectReason.value.trim()) {
      alert('Veuillez fournir une raison pour le rejet')
      return
    }
    emit('reject', rejectReason.value)
    showRejectModal.value = false
    rejectReason.value = ''
  }

  function handleRequestChanges () {
    if (!changeComments.value.trim()) {
      alert('Veuillez fournir des commentaires')
      return
    }
    emit('request-changes', changeComments.value)
    showRequestChangesModal.value = false
    changeComments.value = ''
  }
</script>

<template>
  <div class="card p-6">
    <h3 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
      Actions de révision
    </h3>

    <div class="mb-6 p-4 bg-neutral-50 dark:bg-neutral-900 rounded-lg">
      <p class="text-sm text-neutral-600 dark:text-neutral-400 mb-1">
        Statut actuel
      </p>
      <p class="font-medium text-neutral-900 dark:text-neutral-50">
        {{ document.status === 'draft' ? 'brouillon en attente de vérification' :
          document.status === 'under_review' ? 'brouillon en cours de vérification' :
          document.status === 'approved' ? 'validé - version 1' :
          document.status }}
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
      <button
        class="btn-success inline-flex items-center justify-center gap-2"
        :disabled="loading"
        @click="handleApprove"
      >
        <CheckCircle class="w-5 h-5" />
        Valider
      </button>

      <button
        class="btn-secondary inline-flex items-center justify-center gap-2"
        :disabled="loading"
        @click="showRequestChangesModal = true"
      >
        <AlertCircle class="w-5 h-5" />
        Demander des modifications
      </button>

      <button
        class="btn-danger inline-flex items-center justify-center gap-2"
        :disabled="loading"
        @click="showRejectModal = true"
      >
        <XCircle class="w-5 h-5" />
        Rejeter
      </button>
    </div>

    <div
      v-if="showRejectModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showRejectModal = false"
    >
      <div class="card p-6 max-w-lg w-full">
        <h4 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
          Rejeter le document
        </h4>
        <div class="mb-6">
          <label class="label">Raison du rejet *</label>
          <textarea
            v-model="rejectReason"
            class="input w-full"
            placeholder="Expliquez pourquoi ce document est rejeté..."
            rows="4"
          />
        </div>
        <div class="flex justify-end gap-3">
          <button class="btn-secondary" @click="showRejectModal = false">
            Annuler
          </button>
          <button class="btn-danger" @click="handleReject">
            Confirmer le rejet
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="showRequestChangesModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
      @click.self="showRequestChangesModal = false"
    >
      <div class="card p-6 max-w-lg w-full">
        <h4 class="text-lg font-semibold text-neutral-900 dark:text-neutral-50 mb-4">
          Demander des modifications
        </h4>
        <div class="mb-6">
          <label class="label">Commentaires *</label>
          <textarea
            v-model="changeComments"
            class="input w-full"
            placeholder="Décrivez les modifications nécessaires..."
            rows="4"
          />
        </div>
        <div class="flex justify-end gap-3">
          <button class="btn-secondary" @click="showRequestChangesModal = false">
            Annuler
          </button>
          <button class="btn-primary inline-flex items-center gap-2" @click="handleRequestChanges">
            <Send class="w-4 h-4" />
            Envoyer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.btn-success {
  @apply bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}

.btn-danger {
  @apply bg-red-600 hover:bg-red-700 text-white font-medium px-4 py-2 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed;
}
</style>
