/**
 * Types unifiés pour le système documentaire
 *
 * Supporte à la fois le nouveau modèle Document et l'ancien DocumentInventory
 * pour assurer une transition en douceur.
 */

export interface Nomenclature {
  id: number
  enterprise_id?: number | null
  site_id?: number | null
  process_id?: number | null
  processus: string
  nom: string
  format: string
  format_structure?: Array<{
    type: string
    token?: string
    value?: string
    length?: number
  }> | null
  description?: string
  actif: boolean
  created_at: string
  updated_at: string
}

export interface DocumentTypeCatalog {
  id: number
  enterprise_id?: number | null
  site_id?: number | null
  name: string
  abbreviation: string
  description?: string | null
  is_active: boolean
  display_order: number
  created_at: string
  updated_at: string
}

/**
 * Type unifié pour les documents
 * Supporte les deux formats (Document et DocumentInventory)
 */
export interface UnifiedDocument {
  id: number
  site_id: number

  // Champs communs
  code: string
  processus?: string
  process_id?: number
  type: string
  version: string
  metadata?: any
  created_at: string
  updated_at: string
  deleted_at?: string | null

  // Nomenclature unifiée
  document_type_configuration_id?: number
  nomenclature_template_id?: number

  // Titre/Nom (unifié)
  title?: string
  nom?: string

  // Description
  description?: string

  // Fichier
  file_path?: string
  fichier?: string

  // État/Statut
  etat?: 'a_etablir' | 'en_cours' | 'termine'
  status?: 'draft' | 'pending_verification' | 'pending_approval' | 'approved' | 'rejected' | 'obsolete'
  statut?: 'brouillon' | 'en_revision' | 'valide' | 'obsolete'
  workflow_status?: string

  // Dates
  date_creation?: string
  date_revision?: string
  periodicite_revision?: number
  prochaine_revision?: string
  review_due_date?: string

  // Utilisateurs
  created_by?: number
  author_id?: number
  validated_by?: number
  approver_id?: number
  verifier_id?: number
  validated_at?: string
  approved_at?: string

  // Workflow
  needs_verification?: boolean
  rejection_reason?: string

  // Relations
  site?: any
  nomenclature?: Nomenclature
  nomenclatureTemplate?: any
  creator?: any
  author?: any
  validator?: any
  approver?: any
  verifier?: any
  versions?: DocumentVersion[]
  reviews?: DocumentReview[]
  liens_source?: DocumentLink[]
  liens_cible?: DocumentLink[]

  // Flags
  is_active?: boolean
  is_confidential?: boolean
}

/**
/**
 * Type moderne (nouveau modèle)
 */
export interface Document extends UnifiedDocument {
  title: string
  status: 'draft' | 'pending_verification' | 'pending_approval' | 'approved' | 'rejected' | 'obsolete'
  file_path?: string
  author_id: number
}

export interface DocumentVersion {
  id: number
  document_id: number
  version: string
  modifications?: string
  fichier?: string
  file_path?: string
  created_by: number
  created_at: string
  updated_at: string
  creator?: any
}

export interface DocumentLink {
  id: number
  document_source_id: number
  document_cible_id: number
  type_relation: 'utilise' | 'reference' | 'remplace' | 'complete' | 'genere'
  description?: string
  created_at: string
  updated_at: string
  document_source?: UnifiedDocument
  document_cible?: UnifiedDocument
}

export interface DocumentReview {
  id: number
  document_id: number
  date_prevue: string
  date_realisee?: string
  statut: 'planifiee' | 'en_cours' | 'terminee' | 'reportee'
  commentaire?: string
  responsable_id: number
  created_at: string
  updated_at: string
  responsable?: any
}

export interface DocumentStats {
  total: number
  par_type: Array<{ type: string, count: number }>
  par_etat: Array<{ etat: string, count: number }>
  par_statut: Array<{ statut: string, count: number }>
  a_reviser: number
}

/**
 * Helpers pour normaliser les documents
 */
export const DocumentHelpers = {
  /**
   * Obtenir le titre d'un document (unifié)
   */
  getTitle (doc: UnifiedDocument): string {
    return doc.title || doc.nom || ''
  },

  /**
   * Obtenir le fichier d'un document (unifié)
   */
  getFilePath (doc: UnifiedDocument): string | undefined {
    return doc.file_path || doc.fichier
  },

  /**
   * Obtenir l'auteur d'un document (unifié)
   */
  getAuthorId (doc: UnifiedDocument): number | undefined {
    return doc.author_id || doc.created_by
  },

  /**
   * Obtenir l'approbateur d'un document (unifié)
   */
  getApproverId (doc: UnifiedDocument): number | undefined {
    return doc.approver_id || doc.validated_by
  },

  /**
   * Obtenir le statut d'un document (unifié)
   */
  getStatus (doc: UnifiedDocument): string {
    if (doc.status) {
      return doc.status
    }
    if (doc.statut) {
      return {
        brouillon: 'draft',
        en_revision: 'pending_verification',
        valide: 'approved',
        obsolete: 'obsolete',
      }[doc.statut] || 'draft'
    }
    return 'draft'
  },

  /**
   * Obtenir le statut legacy (pour compatibilité)
   */
  getStatut (doc: UnifiedDocument): string {
    if (doc.statut) {
      return doc.statut
    }
    if (doc.status) {
      return {
        draft: 'brouillon',
        pending_verification: 'en_revision',
        pending_approval: 'en_revision',
        approved: 'valide',
        rejected: 'brouillon',
        obsolete: 'obsolete',
      }[doc.status] || 'brouillon'
    }
    return 'brouillon'
  },

  /**
   * Vérifier si un document est migré
   */
  isMigrated (doc: UnifiedDocument): boolean {
    return Boolean(doc.metadata?.migrated_from_inventory)
  },
}
