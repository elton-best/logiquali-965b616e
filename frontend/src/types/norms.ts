// Types pour la structure hiérarchique des normes ISO

export type NormNodeType
  = | 'chapter' // Niveau 1: Chapitre (ex: 7. Support)
    | 'subchapter' // Niveau 2: Sous-chapitre (ex: 7.2 Compétences)
    | 'paragraph' // Niveau 3: Paragraphe/Exigence
    | 'requirement' // Exigence (avec "doit")
    | 'recommendation' // Recommandation (avec "devrait")
    | 'point' // Niveau 4: Point à puces (a, b, c)
    | 'subpoint' // Niveau 5: Sous-point (i, ii, iii)
    | 'detail' // Niveau 6: Détail (1, 2, 3)
    | 'note' // Note (clarification non contraignante)
    | 'annex' // Annexe

export interface NormNode {
  id: string
  type: NormNodeType
  code: string // Ex: "7.2.1", "a)", "i.", "Note 1", "Annexe A"
  title: string // Titre du nœud
  content: string // Contenu/description
  isObligatory?: boolean // true pour "doit", false pour "devrait"
  children?: NormNode[] // Nœuds enfants
  parentId?: string // ID du parent
  order: number // Ordre d'affichage
  references?: string[] // Références croisées (ex: ["4.1", "8.3"])
  metadata?: {
    isNormative?: boolean // Pour les annexes
    createdAt?: string
    updatedAt?: string
  }
}

export interface NormStructure {
  id?: number
  code: string // Ex: "ISO 9001:2015"
  name: string // Ex: "Système de management de la qualité"
  version: string // Ex: "2015"
  domain?: string // quality|environment|security|food_safety|integrated|other
  description?: string
  publish?: boolean
  publicationDate?: string
  nodes: NormNode[] // Arbre hiérarchique complet
  created_at?: string
  updated_at?: string
}

export interface NormCreationPayload {
  code: string
  name: string
  version: string
  description?: string
  structure: NormNode[] // L'arbre complet
}

// Helper pour créer un nouveau nœud
export function createNormNode (
  type: NormNodeType,
  code: string,
  title: string,
  parentId?: string,
): NormNode {
  return {
    id: `node_${Date.now()}_${Math.random().toString(36).slice(2, 11)}`,
    type,
    code,
    title,
    content: '',
    children: [],
    parentId,
    order: 0,
    isObligatory: type === 'requirement',
  }
}

// Helper pour obtenir l'icône selon le type
export function getNormNodeIcon (type: NormNodeType): string {
  const icons: Record<NormNodeType, string> = {
    chapter: 'mdi-book-open-page-variant',
    subchapter: 'mdi-file-document-outline',
    paragraph: 'mdi-text-box-outline',
    requirement: 'mdi-alert-circle',
    recommendation: 'mdi-lightbulb-outline',
    point: 'mdi-circle-small',
    subpoint: 'mdi-minus',
    detail: 'mdi-numeric',
    note: 'mdi-note-text',
    annex: 'mdi-paperclip',
  }
  return icons[type] || 'mdi-file'
}

// Helper pour obtenir la couleur selon le type
export function getNormNodeColor (type: NormNodeType): string {
  const colors: Record<NormNodeType, string> = {
    chapter: 'primary',
    subchapter: 'info',
    paragraph: 'secondary',
    requirement: 'error',
    recommendation: 'warning',
    point: 'grey',
    subpoint: 'grey-darken-1',
    detail: 'grey-darken-2',
    note: 'blue-grey',
    annex: 'purple',
  }
  return colors[type] || 'grey'
}

// Helper pour obtenir le label selon le type
export function getNormNodeTypeLabel (type: NormNodeType): string {
  const labels: Record<NormNodeType, string> = {
    chapter: 'Chapitre',
    subchapter: 'Sous-chapitre',
    paragraph: 'Paragraphe',
    requirement: 'Exigence',
    recommendation: 'Recommandation',
    point: 'Point',
    subpoint: 'Sous-point',
    detail: 'Détail',
    note: 'Note',
    annex: 'Annexe',
  }
  return labels[type]
}

// Validation de la hiérarchie
export function validateHierarchy (node: NormNode): string[] {
  const errors: string[] = []

  // Un chapitre ne peut pas avoir d'annexe comme enfant direct
  if (node.type === 'chapter' && node.children) {
    const hasAnnex = node.children.some(child => child.type === 'annex')
    if (hasAnnex) {
      errors.push(`Le chapitre "${node.code}" ne peut pas contenir d'annexe`)
    }
  }

  // Une note ne peut pas avoir d'enfants
  if (node.type === 'note' && node.children && node.children.length > 0) {
    errors.push(`La note "${node.code}" ne peut pas avoir d'enfants`)
  }

  // Validation récursive
  if (node.children) {
    for (const child of node.children) {
      const childErrors = validateHierarchy(child)
      errors.push(...childErrors)
    }
  }

  return errors
}
