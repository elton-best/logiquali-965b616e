<template>
  <div class="modal-overlay" @click="handleCancel">
    <div class="modal-content" @click.stop>
      <div class="modal-header">
        <h3 class="text-xl font-semibold">{{ title }}</h3>
        <button class="btn-close" @click="handleCancel">
          <i class="fas fa-times" />
        </button>
      </div>

      <div class="modal-body">
        <!-- Document Info -->
        <div class="document-info bg-gray-50 p-4 rounded mb-4">
          <div class="flex items-center mb-2">
            <i class="fas fa-file-alt text-blue-600 mr-3 text-2xl" />
            <div>
              <p class="font-semibold">{{ document.code }}</p>
              <p class="text-sm text-gray-600">{{ document.title }}</p>
            </div>
          </div>
        </div>

        <!-- Action Description -->
        <div class="action-description mb-4">
          <p v-if="action === 'verify'" class="text-gray-700">
            Vous êtes sur le point de vérifier ce document. Cette action le fera passer à l'étape de validation.
          </p>
          <p v-else-if="action === 'approve'" class="text-gray-700">
            Vous êtes sur le point de valider ce document. Cette action le rendra officiel et disponible.
          </p>
          <p v-else-if="action === 'reject'" class="text-red-600">
            Vous êtes sur le point de rejeter ce document. Le soumetteur recevra le motif et devra choisir s'il libère le code ou le garde pour correction.
          </p>
          <p v-else-if="action === 'delegate'" class="text-gray-700">
            Vous êtes sur le point de déléguer la vérification/validation de ce document à un autre utilisateur.
          </p>
        </div>

        <!-- User Selection (for delegation) -->
        <div v-if="showUserSelect" class="form-group mb-4">
          <label class="form-label">
            Déléguer à <span class="text-red-600">*</span>
          </label>
          <select v-model="selectedUserId" class="form-select" required>
            <option value="">Sélectionner un utilisateur</option>
            <option v-for="user in availableUsers" :key="user.id" :value="user.id">
              {{ user.name }} ({{ user.email }})
            </option>
          </select>
        </div>

        <!-- Comment Field -->
        <div class="form-group mb-4">
          <label class="form-label">
            Commentaire
            <span v-if="requireComment" class="text-red-600">*</span>
            <span v-else class="text-gray-500">(optionnel)</span>
          </label>
          <textarea
            v-model="comment"
            class="form-textarea"
            :placeholder="getCommentPlaceholder()"
            :required="requireComment"
            rows="4"
          />
          <p v-if="requireComment && !comment" class="text-sm text-red-600 mt-1">
            Le commentaire est obligatoire pour cette action
          </p>
        </div>

        <!-- Warning for rejection -->
        <div v-if="action === 'reject'" class="alert alert-warning">
          <i class="fas fa-exclamation-triangle mr-2" />
          Le soumetteur sera notifié du rejet et devra confirmer : libérer le code ou garder le code.
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" :disabled="loading" @click="handleCancel">
          Annuler
        </button>
        <button
          :class="getConfirmButtonClass()"
          :disabled="loading || !canConfirm"
          @click="handleConfirm"
        >
          <span v-if="loading" class="spinner-sm mr-2" />
          <i v-else class="mr-2" :class="getConfirmIconClass()" />
          {{ getConfirmButtonText() }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
  import { computed, onMounted, ref } from 'vue'

  const props = defineProps<{
    title: string
    action: 'verify' | 'approve' | 'reject' | 'delegate'
    document: any
    requireComment?: boolean
    showUserSelect?: boolean
  }>()

  const emit = defineEmits<{
    (e: 'confirm', data: any): void
    (e: 'cancel'): void
  }>()

  const loading = ref(false)
  const comment = ref('')
  const selectedUserId = ref('')
  const availableUsers = ref<any[]>([])

  const canConfirm = computed(() => {
    if (props.requireComment && !comment.value.trim()) {
      return false
    }
    if (props.showUserSelect && !selectedUserId.value) {
      return false
    }
    return true
  })

  async function loadAvailableUsers () {
    // TODO: Load users with appropriate permissions
    // For now, mock data
    availableUsers.value = [
      { id: 1, name: 'Jean Dupont', email: 'jean.dupont@example.com' },
      { id: 2, name: 'Marie Martin', email: 'marie.martin@example.com' },
      { id: 3, name: 'Pierre Durand', email: 'pierre.durand@example.com' },
    ]
  }

  function handleConfirm () {
    if (!canConfirm.value) return

    loading.value = true

    const data: any = {}

    if (comment.value.trim()) {
      data.comment = comment.value.trim()
    }

    if (props.showUserSelect && selectedUserId.value) {
      data.userId = Number.parseInt(selectedUserId.value)
    }

    emit('confirm', data)
  }

  function handleCancel () {
    emit('cancel')
  }

  function getCommentPlaceholder (): string {
    switch (props.action) {
      case 'verify': {
        return 'Ajoutez un commentaire sur la vérification (optionnel)...'
      }
      case 'approve': {
        return 'Ajoutez un commentaire sur la validation (optionnel)...'
      }
      case 'reject': {
        return 'Expliquez les raisons du rejet (obligatoire)...'
      }
      case 'delegate': {
        return 'Expliquez la raison de la délégation (obligatoire)...'
      }
      default: {
        return 'Ajoutez un commentaire...'
      }
    }
  }

  function getConfirmButtonText (): string {
    switch (props.action) {
      case 'verify': {
        return 'Vérifier'
      }
      case 'approve': {
        return 'Valider'
      }
      case 'reject': {
        return 'Rejeter'
      }
      case 'delegate': {
        return 'Déléguer'
      }
      default: {
        return 'Confirmer'
      }
    }
  }

  function getConfirmButtonClass (): string {
    switch (props.action) {
      case 'verify': {
        return 'btn btn-primary'
      }
      case 'approve': {
        return 'btn btn-success'
      }
      case 'reject': {
        return 'btn btn-danger'
      }
      case 'delegate': {
        return 'btn btn-secondary'
      }
      default: {
        return 'btn btn-primary'
      }
    }
  }

  function getConfirmIconClass (): string {
    switch (props.action) {
      case 'verify': {
        return 'fas fa-check-circle'
      }
      case 'approve': {
        return 'fas fa-check-double'
      }
      case 'reject': {
        return 'fas fa-times-circle'
      }
      case 'delegate': {
        return 'fas fa-user-plus'
      }
      default: {
        return 'fas fa-check'
      }
    }
  }

  onMounted(() => {
    if (props.showUserSelect) {
      loadAvailableUsers()
    }
  })
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 8px;
  max-width: 600px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 1.5rem;
  border-top: 1px solid #e5e7eb;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6b7280;
  cursor: pointer;
}

.btn-close:hover {
  color: #374151;
}

.form-label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.5rem;
  color: #374151;
}

.form-select,
.form-textarea {
  width: 100%;
  padding: 0.5rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
}

.form-select:focus,
.form-textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.alert {
  padding: 0.75rem 1rem;
  border-radius: 0.375rem;
  display: flex;
  align-items: center;
}

.alert-warning {
  background: #fef3c7;
  color: #92400e;
  border: 1px solid #fde68a;
}

.spinner-sm {
  display: inline-block;
  width: 1rem;
  height: 1rem;
  border: 2px solid currentColor;
  border-top-color: transparent;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
