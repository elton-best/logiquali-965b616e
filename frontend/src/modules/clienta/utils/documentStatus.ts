export type DocumentWorkflowStatus =
  | 'draft'
  | 'pending_verification'
  | 'pending_approval'
  | 'awaiting_submitter_confirmation'
  | 'rejected'
  | 'approved'
  | 'obsolete'
  | 'archived'
  | string

type DocumentStatusInput = {
  status?: string | null
  workflow_status?: string | null
  version?: string | number | null
  metadata?: Record<string, any> | null
}

type StatusConfig = {
  label: string
  color: string
  icon: string
}

export function formatDocumentVersion (version?: string | number | null): string {
  const value = String(version || '1').trim()
  if (!value) return '1'
  return value.replace(/\.0+$/, '')
}

export function getBaseApprovedVersion (document?: DocumentStatusInput | null): string {
  return formatDocumentVersion(
    document?.metadata?.versioning?.base_approved_version
      || document?.metadata?.previous_approved_version
      || document?.version
      || '1',
  )
}

export function getDocumentWorkflowStatus (document?: DocumentStatusInput | null): string {
  return String(document?.workflow_status || document?.status || 'draft')
}

export function getDocumentBusinessStatusLabel (document?: DocumentStatusInput | null): string {
  const status = getDocumentWorkflowStatus(document)
  const version = formatDocumentVersion(document?.version)
  const baseVersion = getBaseApprovedVersion(document)

  if (status === 'approved') return `validé - version ${version}`
  if (status === 'pending_approval') return baseVersion ? `version ${baseVersion} - en attente de validation` : 'brouillon - en attente de validation'
  if (status === 'pending_verification') return baseVersion ? `version ${baseVersion} - en cours de vérification` : 'brouillon en cours de vérification'
  if (status === 'draft') return baseVersion && baseVersion !== version ? `version ${baseVersion} - brouillon` : 'brouillon en attente de vérification'
  if (status === 'awaiting_submitter_confirmation') return 'rejeté - décision soumissionnaire requise'
  if (status === 'rejected') return 'rejeté'
  if (status === 'obsolete') return 'obsolète'
  if (status === 'archived') return 'archivé'

  return status
}

export function getDocumentBusinessStatusConfig (document?: DocumentStatusInput | null): StatusConfig {
  const status = getDocumentWorkflowStatus(document)
  const label = getDocumentBusinessStatusLabel(document)

  const config: Record<string, Omit<StatusConfig, 'label'>> = {
    draft: { color: 'grey', icon: 'mdi-pencil-outline' },
    pending_verification: { color: 'warning', icon: 'mdi-eye-check-outline' },
    pending_approval: { color: 'info', icon: 'mdi-check-circle-outline' },
    awaiting_submitter_confirmation: { color: 'error', icon: 'mdi-alert-circle-outline' },
    rejected: { color: 'error', icon: 'mdi-close-circle-outline' },
    approved: { color: 'success', icon: 'mdi-check-decagram' },
    obsolete: { color: 'grey', icon: 'mdi-archive-outline' },
    archived: { color: 'grey', icon: 'mdi-archive-outline' },
  }

  return {
    label,
    ...(config[status] || { color: 'grey', icon: 'mdi-help-circle-outline' }),
  }
}
