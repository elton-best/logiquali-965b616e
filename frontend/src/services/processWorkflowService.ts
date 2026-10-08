import api from '@/api/client'
import { expandPermissionAliases } from '@/utils/permissions'

export interface ProcessWorkflowResponse {
  data: any
  message?: string
}

export interface WorkflowComment {
  comment?: string
}

export interface RejectReason {
  reason: string
}

/**
 * Service pour gérer le workflow de validation des processus
 */
class ProcessWorkflowService {
  /**
   * Vérifier un processus (draft → in_review)
   */
  async verify (processId: number, data: WorkflowComment = {}): Promise<ProcessWorkflowResponse> {
    const response = await api.post(`/processes/${processId}/verify`, data)
    return response.data
  }

  /**
   * Valider un processus (in_review → active)
   */
  async validate (processId: number, data: WorkflowComment = {}): Promise<ProcessWorkflowResponse> {
    const response = await api.post(`/processes/${processId}/validate`, data)
    return response.data
  }

  /**
   * Rejeter un processus et retourner à draft
   */
  async reject (processId: number, data: RejectReason): Promise<ProcessWorkflowResponse> {
    const response = await api.post(`/processes/${processId}/reject`, data)
    return response.data
  }

  /**
   * Obtenir les statistiques des processus
   */
  async getStatistics (siteId?: number): Promise<any> {
    const params = siteId ? { site_id: siteId } : {}
    const response = await api.get('/processes/statistics', { params })
    return response.data.data
  }

  /**
   * Vérifier si l'utilisateur peut vérifier un processus
   */
  canVerify (process: any, user: any): boolean {
    // Super admin peut tout
    if (user?.user_type === 'super_admin') {
      return true
    }

    // Doit avoir la permission
    if (!this.hasPermission(user, 'processes.validate')) {
      return false
    }

    // Le processus doit être en draft
    return process?.status === 'draft'
  }

  /**
   * Vérifier si l'utilisateur peut valider un processus
   */
  canValidate (process: any, user: any): boolean {
    // Super admin peut tout
    if (user?.user_type === 'super_admin') {
      return true
    }

    // Doit avoir la permission
    if (!this.hasPermission(user, 'processes.validate')) {
      return false
    }

    // Le processus doit être en in_review
    return process?.status === 'in_review'
  }

  /**
   * Vérifier si l'utilisateur peut rejeter un processus
   */
  canReject (process: any, user: any): boolean {
    // Super admin peut tout
    if (user?.user_type === 'super_admin') {
      return true
    }

    // Doit avoir au moins une des permissions
    if (!this.hasPermission(user, 'processes.validate')
      && !this.hasPermission(user, 'processes.update')) {
      return false
    }

    // Peut rejeter si en in_review ou validated
    return process?.status === 'in_review' || process?.status === 'validated'
  }

  /**
   * Vérifier si l'utilisateur peut modifier un processus
   */
  canEdit (process: any, user: any): boolean {
    // Super admin peut tout
    if (user?.user_type === 'super_admin') {
      return true
    }

    // Permission générale update
    if (this.hasPermission(user, 'processes.update')) {
      return true
    }

    // Le pilot ou copilot peut modifier si le processus est en draft
    if (process?.status === 'draft'
      && (process?.pilot_id === user?.id || process?.copilot_id === user?.id)) {
      return true
    }

    return false
  }

  /**
   * Obtenir le label du statut
   */
  getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      draft: 'Brouillon',
      in_review: 'En révision',
      validated: 'Validé',
      active: 'Actif',
      obsolete: 'Obsolète',
    }
    return labels[status] || status
  }

  /**
   * Obtenir la couleur du statut
   */
  getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      draft: 'grey',
      in_review: 'orange',
      validated: 'blue',
      active: 'green',
      obsolete: 'red',
    }
    return colors[status] || 'grey'
  }

  /**
   * Obtenir l'icône du statut
   */
  getStatusIcon (status: string): string {
    const icons: Record<string, string> = {
      draft: 'mdi-file-document-outline',
      in_review: 'mdi-eye-check',
      validated: 'mdi-check-circle-outline',
      active: 'mdi-check-circle',
      obsolete: 'mdi-archive',
    }
    return icons[status] || 'mdi-circle-outline'
  }

  /**
   * Vérifier si l'utilisateur a une permission spécifique
   */
  private hasPermission (user: any, permission: string): boolean {
    if (!user || !user.permissions) {
      return false
    }
    const aliases = new Set(expandPermissionAliases(permission))

    // Si c'est un tableau de permissions Spatie
    if (Array.isArray(user.permissions)) {
      return user.permissions.some((p: any) => aliases.has(p?.name || p))
    }

    // Si c'est un objet avec des permissions
    if (typeof user.permissions === 'object') {
      return Object.values(user.permissions).some((p: any) =>
        (typeof p === 'string' && aliases.has(p))
        || (typeof p === 'object' && aliases.has(p.name)),
      )
    }

    return false
  }
}

export default new ProcessWorkflowService()
