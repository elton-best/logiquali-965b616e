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

export interface DocumentLink {
  id: number
  document_source_id: number
  document_cible_id: number
  type_relation: 'utilise' | 'reference' | 'remplace' | 'complete' | 'genere'
  description?: string
  created_at: string
  updated_at: string
  document_source?: { id: number, title?: string, code?: string }
  document_cible?: { id: number, title?: string, code?: string }
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
