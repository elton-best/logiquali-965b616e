// Status Mappers - Unified color and label mapping for Client A module

/**
 * Status mapping for various entities (NC, Actions, Documents, etc.)
 */
export function getStatusColor (status: string): string {
  const colors: Record<string, string> = {
    // Non-conformities & Reclamations
    open: 'error',
    in_progress: 'warning',
    closed: 'success',
    resolved: 'success',
    cancelled: 'grey',

    // Documents
    draft: 'grey',
    in_review: 'info',
    validated: 'success',
    obsolete: 'warning',
    archived: 'grey-darken-2',

    // Actions
    pending: 'warning',
    in_action: 'info',
    completed: 'success',
    overdue: 'error',

    // Audits
    planned: 'info',
    ongoing: 'warning',
    done: 'success',

    // Processes
    active: 'success',
    inactive: 'grey',
    under_review: 'warning',

    // Default
    default: 'grey',
  }

  return colors[status] ?? 'grey'
}

export function getStatusLabel (status: string): string {
  const labels: Record<string, string> = {
    // Non-conformities & Reclamations
    open: 'Ouvert',
    in_progress: 'En cours',
    closed: 'Fermé',
    resolved: 'Résolu',
    cancelled: 'Annulé',

    // Documents
    draft: 'Brouillon',
    in_review: 'En revue',
    validated: 'Validé',
    obsolete: 'Obsolète',
    archived: 'Archivé',

    // Actions
    pending: 'En attente',
    in_action: 'En cours',
    completed: 'Terminé',
    overdue: 'En retard',

    // Audits
    planned: 'Planifié',
    ongoing: 'En cours',
    done: 'Terminé',

    // Processes
    active: 'Actif',
    inactive: 'Inactif',
    under_review: 'En révision',

    // Default
    default: 'Inconnu',
  }

  return labels[status] ?? 'Inconnu'
}

/**
 * Severity mapping (Risks, NC, etc.)
 */
export function getSeverityColor (severity: string): string {
  const colors: Record<string, string> = {
    critical: 'error',
    high: 'error',
    major: 'warning',
    medium: 'warning',
    minor: 'info',
    low: 'success',
    negligible: 'grey',
    default: 'grey',
  }

  return colors[severity] ?? 'grey'
}

export function getSeverityLabel (severity: string): string {
  const labels: Record<string, string> = {
    critical: 'Critique',
    high: 'Élevé',
    major: 'Majeur',
    medium: 'Moyen',
    minor: 'Mineur',
    low: 'Faible',
    negligible: 'Négligeable',
    default: 'Inconnu',
  }

  return labels[severity] ?? 'Inconnu'
}

/**
 * Priority mapping (Actions, Tasks)
 */
export function getPriorityColor (priority: string): string {
  const colors: Record<string, string> = {
    urgent: 'error',
    high: 'warning',
    normal: 'info',
    low: 'success',
    default: 'grey',
  }

  return colors[priority] ?? 'grey'
}

export function getPriorityLabel (priority: string): string {
  const labels: Record<string, string> = {
    urgent: 'Urgent',
    high: 'Haute',
    normal: 'Normale',
    low: 'Basse',
    default: 'Inconnue',
  }

  return labels[priority] ?? 'Inconnue'
}

/**
 * Risk level mapping (Criticality: Probability x Severity)
 */
export function getRiskLevelColor (level: number | string): string {
  if (typeof level === 'string') {
    const colors: Record<string, string> = {
      low: 'success',
      moderate: 'warning',
      high: 'error',
      critical: 'error',
    }
    return colors[level] || 'grey'
  }

  // Numeric level (1-25 scale)
  if (level >= 15) {
    return 'error'
  } // Critical
  if (level >= 9) {
    return 'warning'
  } // High
  if (level >= 4) {
    return 'info'
  } // Moderate
  return 'success' // Low
}

export function getRiskLevelLabel (level: number | string): string {
  if (typeof level === 'string') {
    const labels: Record<string, string> = {
      low: 'Faible',
      moderate: 'Modéré',
      high: 'Élevé',
      critical: 'Critique',
    }
    return labels[level] || 'Inconnu'
  }

  // Numeric level
  if (level >= 15) {
    return 'Critique'
  }
  if (level >= 9) {
    return 'Élevé'
  }
  if (level >= 4) {
    return 'Modéré'
  }
  return 'Faible'
}

/**
 * Type mapping (Documents)
 */
export function getDocumentTypeLabel (type: string): string {
  const labels: Record<string, string> = {
    policy: 'Politique (N1)',
    manual: 'Manuel (N2)',
    procedure: 'Procédure (N3)',
    instruction: 'Instruction (N4)',
    record: 'Enregistrement (N5)',
    form: 'Formulaire',
    template: 'Modèle',
    report: 'Rapport',
    default: 'Autre',
  }

  return labels[type] ?? 'Autre'
}

/**
 * Boolean mapping
 */
export function getBooleanLabel (value: boolean): string {
  return value ? 'Oui' : 'Non'
}

export function getBooleanColor (value: boolean): string {
  return value ? 'success' : 'grey'
}
