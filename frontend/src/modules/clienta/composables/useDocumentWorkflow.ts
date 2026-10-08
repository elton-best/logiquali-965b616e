import { ref, computed } from 'vue'
import api from '@/api/client'

export interface DocumentWorkflowAction {
  documentId: number
  action: 'verification' | 'approval' | 'rejection'
  workflowType: string
  notificationId?: number
}

export interface WorkflowHistoryEntry {
  id: number
  document_id: number
  user_id: number
  action: string
  action_label: string
  from_status: string | null
  to_status: string | null
  comment: string | null
  metadata: Record<string, any> | null
  delegated_to: number | null
  action_at: string
  user: {
    id: number
    name: string
    email: string
  }
  delegated_to_user?: {
    id: number
    name: string
    email: string
  }
}

export interface PendingDocuments {
  pending_verification: any[]
  pending_approval: any[]
  total: number
}

export interface WorkflowStats {
  verified: number
  approved: number
  rejected: number
  period_days: number
}

export function useDocumentWorkflow () {
  const loading = ref(false)
  const error = ref<string | null>(null)
  const requiredActions = ref<DocumentWorkflowAction[]>([])

  const verifyDocument = async (documentId: number, comment?: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/documents/${documentId}/verify`, {
        comment,
      })

      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de la vérification'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const approveDocument = async (documentId: number, comment?: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/documents/${documentId}/approve`, {
        comment,
      })

      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de l\'approbation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const rejectDocument = async (documentId: number, rejectionReason: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/documents/${documentId}/reject`, {
        rejection_reason: rejectionReason,
      })

      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors du rejet'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getWorkflowHistory = async (documentId: number): Promise<WorkflowHistoryEntry[]> => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get(`/documents/${documentId}/workflow-history`)

      if (response.data.success) {
        return response.data.data
      } else {
        throw new Error(response.data.message || 'Erreur lors de la récupération de l\'historique')
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de la récupération de l\'historique'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const delegateDocument = async (documentId: number, delegatedTo: number, comment: string) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/documents/${documentId}/delegate`, {
        delegated_to: delegatedTo,
        comment,
      })

      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de la délégation'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const sendReminder = async (documentId: number) => {
    loading.value = true
    error.value = null

    try {
      const response = await api.post(`/documents/${documentId}/send-reminder`)

      return response.data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de l\'envoi du rappel'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getPendingDocuments = async (): Promise<PendingDocuments> => {
    loading.value = true
    error.value = null

    try {
      const [verificationResponse, approvalResponse] = await Promise.all([
        api.get('/document-code-workflow/pending-verification'),
        api.get('/document-code-workflow/pending-approval'),
      ])

      const pendingVerification = Array.isArray(verificationResponse.data?.data)
        ? verificationResponse.data.data
        : (Array.isArray(verificationResponse.data) ? verificationResponse.data : [])
      const pendingApproval = Array.isArray(approvalResponse.data?.data)
        ? approvalResponse.data.data
        : (Array.isArray(approvalResponse.data) ? approvalResponse.data : [])

      return {
        pending_verification: pendingVerification,
        pending_approval: pendingApproval,
        total: pendingVerification.length + pendingApproval.length,
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de la récupération des documents en attente'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getWorkflowStats = async (days = 30): Promise<WorkflowStats> => {
    loading.value = true
    error.value = null

    try {
      const response = await api.get('/document-code-workflow/stats', {
        params: { days },
      })

      const payload = response.data?.data || response.data || {}
      return {
        verified: Number(payload.verified ?? payload.verified_count ?? 0),
        approved: Number(payload.approved ?? payload.approved_count ?? 0),
        rejected: Number(payload.rejected ?? payload.rejected_count ?? 0),
        period_days: Number(payload.period_days ?? days),
      }
    } catch (error_: any) {
      error.value = error_.response?.data?.message || error_.message || 'Erreur lors de la récupération des statistiques'
      throw error_
    } finally {
      loading.value = false
    }
  }

  const getStatusLabel = (status: string): string => {
    const labels: Record<string, string> = {
      draft: 'brouillon en attente de vérification',
      pending_verification: 'brouillon en cours de vérification',
      pending_approval: 'brouillon - en attente de validation',
      awaiting_submitter_confirmation: 'rejeté - décision soumissionnaire requise',
      rejected: 'Rejeté',
      approved: 'validé - version 1',
    }
    return labels[status] || status
  }

  const getStatusColor = (status: string): string => {
    const colors: Record<string, string> = {
      draft: 'gray',
      pending_verification: 'blue',
      pending_approval: 'orange',
      awaiting_submitter_confirmation: 'yellow',
      rejected: 'red',
      approved: 'green',
    }
    return colors[status] || 'gray'
  }

  const getActionLabel = (action: string): string => {
    const labels: Record<string, string> = {
      submitted: 'Soumis pour vérification',
      verified: 'Vérifié',
      approved: 'Validé',
      rejected: 'Rejeté',
      rejection_confirmed: 'Rejet confirmé',
      rejection_cancelled: 'Rejet annulé',
      delegated: 'Délégué',
      reminded: 'Rappel envoyé',
    }
    return labels[action] || action
  }

  const getDocumentAction = (documentId: number): DocumentWorkflowAction | undefined => {
    return requiredActions.value.find(a => a.documentId === documentId)
  }

  const setDocumentAction = (action: DocumentWorkflowAction) => {
    const existing = requiredActions.value.findIndex(a => a.documentId === action.documentId)
    if (existing > -1) {
      requiredActions.value[existing] = action
    } else {
      requiredActions.value.push(action)
    }
  }

  const clearDocumentAction = (documentId: number) => {
    requiredActions.value = requiredActions.value.filter(a => a.documentId !== documentId)
  }

  const updateActionsFromNotifications = (notifications: any[]) => {
    requiredActions.value = []

    for (const notif of notifications) {
      if (!notif.data?.workflow_type || !notif.data?.entity_id) continue

      const docId = notif.data.entity_id
      const action = {
        verification_request: 'verification',
        approval_request: 'approval',
        rejection_decision_required: 'rejection',
      }[notif.data.workflow_type]

      if (action) {
        setDocumentAction({
          documentId: docId,
          action: action as any,
          workflowType: notif.data.workflow_type,
          notificationId: notif.id,
        })
      }
    }
  }

  return {
    loading,
    error,
    requiredActions,
    verifyDocument,
    approveDocument,
    rejectDocument,
    getWorkflowHistory,
    delegateDocument,
    sendReminder,
    getPendingDocuments,
    getWorkflowStats,
    getStatusLabel,
    getStatusColor,
    getActionLabel,
    getDocumentAction,
    setDocumentAction,
    clearDocumentAction,
    updateActionsFromNotifications,
  }
}
