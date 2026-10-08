<template>
  <div class="document-workflow-panel">
    <!-- Workflow Actions -->
    <div v-if="canPerformActions" class="workflow-actions bg-white rounded-lg shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4">Actions de workflow</h3>

      <div class="actions-grid grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Verify Action -->
        <button
          v-if="canVerify"
          class="btn btn-primary"
          :disabled="loading"
          @click="showVerifyModal = true"
        >
          <i class="fas fa-check-circle mr-2" />
          Vérifier le document
        </button>

        <!-- Validate Action -->
        <button
          v-if="canApprove"
          class="btn btn-success"
          :disabled="loading"
          @click="showApproveModal = true"
        >
          <i class="fas fa-check-double mr-2" />
          Valider le document
        </button>

        <!-- Reject Action -->
        <button
          v-if="canReject"
          class="btn btn-danger"
          :disabled="loading"
          @click="showRejectModal = true"
        >
          <i class="fas fa-times-circle mr-2" />
          Rejeter le document
        </button>

        <!-- Delegate Action -->
        <button
          v-if="canDelegate"
          class="btn btn-secondary"
          :disabled="loading"
          @click="showDelegateModal = true"
        >
          <i class="fas fa-user-plus mr-2" />
          Déléguer
        </button>

        <!-- Send Reminder -->
        <button
          v-if="canSendReminder"
          class="btn btn-warning"
          :disabled="loading"
          @click="handleSendReminder"
        >
          <i class="fas fa-bell mr-2" />
          Envoyer un rappel
        </button>
      </div>
    </div>

    <!-- Workflow Status -->
    <div class="workflow-status bg-white rounded-lg shadow-md p-6 mb-6">
      <h3 class="text-lg font-semibold mb-4">Statut du workflow</h3>

      <div class="status-info">
        <div class="flex items-center mb-3">
          <span class="text-gray-600 mr-3">Statut actuel:</span>
          <span :class="getStatusBadgeClass(document.workflow_status)">
            {{ getStatusLabel(document.workflow_status) }}
          </span>
        </div>

        <div v-if="document.verifier_id" class="flex items-center mb-3">
          <span class="text-gray-600 mr-3">Vérificateur:</span>
          <span class="font-medium">{{ document.verifier?.name || 'Non assigné' }}</span>
        </div>

        <div v-if="document.approver_id" class="flex items-center mb-3">
          <span class="text-gray-600 mr-3">Validateur:</span>
          <span class="font-medium">{{ document.approver?.name || 'Non assigné' }}</span>
        </div>

        <div v-if="document.approved_at" class="flex items-center mb-3">
          <span class="text-gray-600 mr-3">Date de validation:</span>
          <span class="font-medium">{{ formatDate(document.approved_at) }}</span>
        </div>
      </div>
    </div>

    <!-- Workflow History -->
    <div class="workflow-history bg-white rounded-lg shadow-md p-6">
      <div class="flex justify-between items-center mb-4">
        <h3 class="text-lg font-semibold">Historique du workflow</h3>
        <button class="btn btn-sm btn-secondary" :disabled="loadingHistory" @click="loadHistory">
          <i class="fas fa-sync-alt mr-2" :class="{ 'fa-spin': loadingHistory }" />
          Actualiser
        </button>
      </div>

      <div v-if="loadingHistory && history.length === 0" class="text-center py-8">
        <div class="spinner mx-auto mb-3" />
        <p class="text-gray-600">Chargement de l'historique...</p>
      </div>

      <div v-else-if="history.length === 0" class="text-center py-8">
        <i class="fas fa-history text-4xl text-gray-400 mb-3" />
        <p class="text-gray-600">Aucun historique disponible</p>
      </div>

      <div v-else class="timeline">
        <div
          v-for="entry in history"
          :key="entry.id"
          class="timeline-entry"
        >
          <div class="timeline-marker" :class="getActionColor(entry.action)" />
          <div class="timeline-content">
            <div class="flex justify-between items-start mb-2">
              <div>
                <span class="font-semibold">{{ entry.action_label }}</span>
                <span class="text-sm text-gray-600 ml-2">par {{ entry.user.name }}</span>
              </div>
              <span class="text-sm text-gray-500">{{ formatDate(entry.action_at) }}</span>
            </div>

            <div v-if="entry.from_status || entry.to_status" class="text-sm text-gray-600 mb-2">
              <span v-if="entry.from_status">{{ getStatusLabel(entry.from_status) }}</span>
              <i v-if="entry.from_status && entry.to_status" class="fas fa-arrow-right mx-2" />
              <span v-if="entry.to_status">{{ getStatusLabel(entry.to_status) }}</span>
            </div>

            <div v-if="entry.comment" class="comment bg-gray-50 p-3 rounded mt-2">
              <p class="text-sm">{{ entry.comment }}</p>
            </div>

            <div v-if="entry.delegated_to_user" class="delegated-info mt-2">
              <i class="fas fa-user-plus text-blue-600 mr-2" />
              <span class="text-sm">Délégué à {{ entry.delegated_to_user.name }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Verify Modal -->
    <DocumentApprovalModal
      v-if="showVerifyModal"
      action="verify"
      :document="document"
      title="Vérifier le document"
      @cancel="showVerifyModal = false"
      @confirm="handleVerify"
    />

    <!-- Validate Modal -->
    <DocumentApprovalModal
      v-if="showApproveModal"
      action="approve"
      :document="document"
      title="Valider le document"
      @cancel="showApproveModal = false"
      @confirm="handleApprove"
    />

    <!-- Reject Modal -->
    <DocumentApprovalModal
      v-if="showRejectModal"
      action="reject"
      :document="document"
      :require-comment="true"
      title="Rejeter le document"
      @cancel="showRejectModal = false"
      @confirm="handleReject"
    />

    <!-- Delegate Modal -->
    <DocumentApprovalModal
      v-if="showDelegateModal"
      action="delegate"
      :document="document"
      :require-comment="true"
      :show-user-select="true"
      title="Déléguer le document"
      @cancel="showDelegateModal = false"
      @confirm="handleDelegate"
    />
  </div>
</template>

<script setup lang="ts">
  import type { WorkflowHistoryEntry } from '../../composables/useDocumentWorkflow'
  import { computed, onMounted, ref } from 'vue'
  import { getNavigationPermissionSet } from '@/modules/clienta/utils/userPermissions'
  import { useAuthStore } from '@/stores/auth'
  import { expandPermissionAliases } from '@/utils/permissions'
  import { useDocumentWorkflow } from '../../composables/useDocumentWorkflow'
  import DocumentApprovalModal from './DocumentApprovalModal.vue'

  const authStore = useAuthStore()

  const props = defineProps<{
    document: any
    currentUser: any
  }>()

  const emit = defineEmits<{
    (e: 'updated'): void
  }>()

  const {
    loading,
    verifyDocument,
    approveDocument,
    rejectDocument,
    getWorkflowHistory,
    delegateDocument,
    sendReminder,
    getStatusLabel,
    getStatusColor,
  } = useDocumentWorkflow()

  const showVerifyModal = ref(false)
  const showApproveModal = ref(false)
  const showRejectModal = ref(false)
  const showDelegateModal = ref(false)
  const loadingHistory = ref(false)
  const history = ref<WorkflowHistoryEntry[]>([])

  const normalizedPermissionSet = computed(() => getNavigationPermissionSet(authStore.user))
  const rawPermissionSet = computed(() => {
    const sourceUser = props.currentUser || authStore.user || {}
    const candidates = [
      ...(Array.isArray(sourceUser?.active_scoped_permissions) ? sourceUser.active_scoped_permissions : []),
      ...(Array.isArray(sourceUser?.effective_permissions) ? sourceUser.effective_permissions : []),
      ...(Array.isArray(sourceUser?.all_permissions) ? sourceUser.all_permissions : []),
      ...(Array.isArray(sourceUser?.permissions) ? sourceUser.permissions : []),
    ]

    return new Set(
      candidates
        .map((entry: any) => {
          if (typeof entry === 'string') return entry
          if (typeof entry?.name === 'string') return entry.name
          if (typeof entry?.attributes?.name === 'string') return entry.attributes.name
          return null
        })
        .filter((value): value is string => typeof value === 'string')
        .map(value => value.trim().toLowerCase()),
    )
  })

  const hasPermission = (permission: string): boolean => {
    const normalizedMatches = expandPermissionAliases(permission)
      .some(alias => normalizedPermissionSet.value.has(alias))
    if (normalizedMatches) {
      return true
    }

    return rawPermissionSet.value.has(permission.trim().toLowerCase())
  }

  const canVerify = computed(() => {
    return (
      props.document.workflow_status === 'pending_verification'
      && hasPermission('verify_documents')
    )
  })

  const canApprove = computed(() => {
    return (
      props.document.workflow_status === 'pending_approval'
      && hasPermission('approve_documents')
    )
  })

  const canReject = computed(() => {
    return (
      (props.document.workflow_status === 'pending_verification'
        || props.document.workflow_status === 'pending_approval')
      && (hasPermission('verify_documents')
        || hasPermission('approve_documents'))
    )
  })

  const canDelegate = computed(() => {
    return (
      (props.document.workflow_status === 'pending_verification'
        || props.document.workflow_status === 'pending_approval')
      && (hasPermission('verify_documents')
        || hasPermission('approve_documents'))
    )
  })

  const canSendReminder = computed(() => {
    return (
      props.document.workflow_status === 'pending_verification'
      || props.document.workflow_status === 'pending_approval'
    )
  })

  const canPerformActions = computed(() => {
    return canVerify.value || canApprove.value || canReject.value || canDelegate.value || canSendReminder.value
  })

  async function loadHistory () {
    loadingHistory.value = true
    try {
      history.value = await getWorkflowHistory(props.document.id)
    } catch (error) {
      console.error('Erreur chargement historique:', error)
    } finally {
      loadingHistory.value = false
    }
  }

  async function handleVerify (data: { comment?: string }) {
    try {
      await verifyDocument(props.document.id, data.comment)
      showVerifyModal.value = false
      emit('updated')
      loadHistory()
    } catch (error) {
      console.error('Erreur vérification:', error)
    }
  }

  async function handleApprove (data: { comment?: string }) {
    try {
      await approveDocument(props.document.id, data.comment)
      showApproveModal.value = false
      emit('updated')
      loadHistory()
    } catch (error) {
      console.error('Erreur validation:', error)
    }
  }

  async function handleReject (data: { comment: string }) {
    try {
      await rejectDocument(props.document.id, data.comment)
      showRejectModal.value = false
      emit('updated')
      loadHistory()
    } catch (error) {
      console.error('Erreur rejet:', error)
    }
  }

  async function handleDelegate (data: { userId: number, comment: string }) {
    try {
      await delegateDocument(props.document.id, data.userId, data.comment)
      showDelegateModal.value = false
      emit('updated')
      loadHistory()
    } catch (error) {
      console.error('Erreur délégation:', error)
    }
  }

  async function handleSendReminder () {
    if (!confirm('Êtes-vous sûr de vouloir envoyer un rappel ?')) {
      return
    }

    try {
      await sendReminder(props.document.id)
      alert('Rappel envoyé avec succès')
      loadHistory()
    } catch (error) {
      console.error('Erreur envoi rappel:', error)
      alert('Erreur lors de l\'envoi du rappel')
    }
  }

  function formatDate (date: string): string {
    return new Date(date).toLocaleString('fr-FR', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    })
  }

  function getStatusBadgeClass (status: string): string {
    const color = getStatusColor(status)
    const classes: Record<string, string> = {
      gray: 'badge badge-secondary',
      blue: 'badge badge-info',
      orange: 'badge badge-warning',
      yellow: 'badge badge-warning',
      red: 'badge badge-danger',
      green: 'badge badge-success',
    }
    return classes[color] || 'badge badge-secondary'
  }

  function getActionColor (action: string): string {
    const colors: Record<string, string> = {
      submitted: 'blue',
      verified: 'green',
      approved: 'green',
      rejected: 'red',
      rejection_confirmed: 'red',
      rejection_cancelled: 'yellow',
      delegated: 'purple',
      reminded: 'orange',
    }
    return colors[action] || 'gray'
  }

  onMounted(() => {
    loadHistory()
  })
</script>

<style scoped>
.badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
}

.badge-secondary {
  background: #e5e7eb;
  color: #374151;
}

.badge-info {
  background: #dbeafe;
  color: #1e40af;
}

.badge-warning {
  background: #fef3c7;
  color: #92400e;
}

.badge-danger {
  background: #fee2e2;
  color: #991b1b;
}

.badge-success {
  background: #d1fae5;
  color: #065f46;
}

.timeline {
  position: relative;
  padding-left: 2rem;
}

.timeline::before {
  content: '';
  position: absolute;
  left: 0.5rem;
  top: 0;
  bottom: 0;
  width: 2px;
  background: #e5e7eb;
}

.timeline-entry {
  position: relative;
  margin-bottom: 2rem;
}

.timeline-marker {
  position: absolute;
  left: -1.5rem;
  width: 1rem;
  height: 1rem;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 0 0 2px currentColor;
}

.timeline-marker.blue {
  color: #3b82f6;
}

.timeline-marker.green {
  color: #10b981;
}

.timeline-marker.red {
  color: #ef4444;
}

.timeline-marker.yellow {
  color: #f59e0b;
}

.timeline-marker.purple {
  color: #8b5cf6;
}

.timeline-marker.orange {
  color: #f97316;
}

.timeline-marker.gray {
  color: #6b7280;
}

.timeline-content {
  background: #f9fafb;
  padding: 1rem;
  border-radius: 0.5rem;
  border-left: 3px solid #e5e7eb;
}

.comment {
  border-left: 3px solid #3b82f6;
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e5e7eb;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>
