export function formatDate (date: string | null | undefined): string {
  if (!date) {
    return 'N/A'
  }

  try {
    const dateObj = new Date(date)
    return dateObj.toLocaleDateString('fr-FR', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
    })
  } catch {
    return 'N/A'
  }
}

export function formatStatus (status: string): string {
  const statuses: Record<string, string> = {
    non_demarre: 'Non démarré',
    en_cours: 'En cours',
    termine: 'Terminé',
    pending_verification: 'En attente',
    approved: 'Approuvé',
    rejected: 'Rejeté',
    draft: 'Brouillon',
  }

  return statuses[status] || status
}

export function formatTaskType (type: string): string {
  const types: Record<string, string> = {
    action: 'Action',
    audit: 'Audit',
    formation: 'Formation',
    communication: 'Communication',
    non_conformity: 'Non-conformité',
    objective: 'Objectif',
    risk: 'Risque',
    plan_action: 'Plan d\'action',
    maintenance_plan: 'Plan de maintenance',
    calibration_plan: 'Plan de calibrage',
    compliance_obligation_action: 'Action obligation de conformité',
    stakeholder_requirement_action: 'Action exigence partie prenante',
    process_risk_opportunity: 'Risque/Opportunité processus',
    operational_project_activity: 'Activité projet opérationnel',
    operational_project_task: 'Tâche projet opérationnel',
  }

  return types[type] || type
}

export function getTypeColor (type: string): string {
  const colors: Record<string, string> = {
    action: '#ef5350',
    audit: '#ff9800',
    formation: '#2196f3',
    communication: '#4caf50',
    non_conformity: '#f44336',
    objective: '#9c27b0',
    risk: '#e91e63',
    plan_action: '#ff5722',
    maintenance_plan: '#607d8b',
    calibration_plan: '#00bcd4',
    compliance_obligation_action: '#795548',
    stakeholder_requirement_action: '#a1887f',
    process_risk_opportunity: '#ffeb3b',
    operational_project_activity: '#8bc34a',
    operational_project_task: '#cddc39',
  }

  return colors[type] || '#2196f3'
}

export function getStatusColor (status: string): string {
  const colors: Record<string, string> = {
    non_demarre: '#9e9e9e',
    en_cours: '#ff9800',
    termine: '#4caf50',
    pending_verification: '#2196f3',
    approved: '#4caf50',
    rejected: '#f44336',
    draft: '#9e9e9e',
  }

  return colors[status] || '#2196f3'
}

export function getDaysUntilDeadline (deadline: string | null | undefined): number {
  if (!deadline) {
    return -1
  }

  try {
    const deadlineDate = new Date(deadline)
    const today = new Date()
    today.setHours(0, 0, 0, 0)
    deadlineDate.setHours(0, 0, 0, 0)

    const time = deadlineDate.getTime() - today.getTime()
    return Math.ceil(time / (1000 * 3600 * 24))
  } catch {
    return -1
  }
}

export function isOverdue (deadline: string | null | undefined): boolean {
  if (!deadline) {
    return false
  }
  return getDaysUntilDeadline(deadline) < 0
}

export function isPastDeadline (deadline: string | null | undefined): boolean {
  return isOverdue(deadline)
}

export function formatProgressRate (rate: number): string {
  return `${Math.round(rate)}%`
}

export function getProgressColor (rate: number): string {
  if (rate < 33) {
    return '#f44336'
  } // Red
  if (rate < 66) {
    return '#ff9800'
  } // Orange
  return '#4caf50' // Green
}
