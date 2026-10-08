/**
 * Normalisateurs de métadonnées documentaires
 * Unification des types et statuts legacy vers le système moderne
 */

const TYPE_MAP: Record<string, string> = {
  POL: 'policy',
  PRC: 'procedure',
  PRD: 'instruction',
  FOR: 'form',
  ENR: 'record',
  MAN: 'manual',
}

const TYPE_LABELS: Record<string, string> = {
  policy: 'Politique',
  procedure: 'Procédure',
  instruction: 'Instruction',
  form: 'Formulaire',
  record: 'Enregistrement',
  manual: 'Manuel',
  other: 'Autre',
}

const WORKFLOW_STATUS_LABELS: Record<string, string> = {
  draft: 'Brouillon',
  pending_verification: 'En vérification',
  pending_approval: 'En approbation',
  approved: 'Approuvé',
  rejected: 'Rejeté',
  obsolete: 'Obsolète',
}

const WORKFLOW_STATUS_COLORS: Record<string, string> = {
  draft: 'text-gray-600',
  pending_verification: 'text-blue-600',
  pending_approval: 'text-orange-600',
  approved: 'text-green-600',
  rejected: 'text-red-600',
  obsolete: 'text-gray-500',
}

/**
 * Normalise un type documentaire (legacy → moderne)
 */
export function normalizeDocumentType(raw: string | null | undefined): string {
  if (!raw) return 'other'
  const upper = raw.toUpperCase()
  return TYPE_MAP[upper] || raw.toLowerCase()
}

/**
 * Retourne le label humain d'un type de document
 */
export function getDocumentTypeLabel(type: string | null | undefined): string {
  const normalized = normalizeDocumentType(type)
  return TYPE_LABELS[normalized] || normalized
}

/**
 * Retourne le label humain d'un workflow_status
 */
export function getWorkflowStatusLabel(status: string | null | undefined): string {
  if (!status) return 'Inconnu'
  return WORKFLOW_STATUS_LABELS[status] || status
}

/**
 * Retourne la classe CSS de couleur pour un workflow_status
 */
export function getWorkflowStatusColor(status: string | null | undefined): string {
  if (!status) return 'text-gray-400'
  return WORKFLOW_STATUS_COLORS[status] || 'text-gray-600'
}
