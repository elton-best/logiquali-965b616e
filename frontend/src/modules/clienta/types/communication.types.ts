export type CommunicationType = 'communication' | 'sensibilisation'

export type CommunicationFrequency
  = | 'ponctuelle'
    | 'annuelle'
    | 'semestrielle'
    | 'trimestrielle'
    | 'mensuelle'
    | 'biennale'
    | 'sur_demande'

export type CommunicationStatus
  = | 'planifiee'
    | 'en_attente'
    | 'realisee'
    | 'replanifiee'
    | 'annulee'

export type CommunicationTarget
  = | 'Personnel'
    | 'Clients'
    | 'Fournisseurs'
    | 'Direction'
    | 'Parties intéressées'
    | 'Autre'

export type CommunicationMoyen
  = | 'Réunion'
    | 'Email'
    | 'Affichage'
    | 'Intranet/Site web'
    | 'Newsletter'
    | 'Formation'
    | 'Atelier'
    | 'Vidéo'
    | 'Autre'

export interface CommunicationProof {
  id: string
  filename: string
  url: string
  uploadedAt: string
  uploadedBy: string
}

export interface CommunicationHistory {
  id: string
  action: 'created' | 'updated' | 'completed' | 'rescheduled' | 'cancelled'
  date: string
  userId: string
  userName: string
  comment?: string
  previousDate?: string
  newDate?: string
}

export interface CommunicationAlert {
  id: string
  communicationId: string
  type: 'J-30' | 'J-15' | 'J-7' | 'J-3' | 'J-1'
  date: string
  sent: boolean
  sentAt?: string
}

export interface Communication {
  id: string
  numero: number
  type: CommunicationType
  designation: string
  cibles: CommunicationTarget[]
  moyens: CommunicationMoyen[]
  chronogramme: boolean[]
  responsable: string
  organizerUserId?: number | null
  responsibleUserId?: number | null
  participantUserIds?: number[]
  processId?: number | null
  cout?: number
  dateDebut?: string | null
  dateFin?: string | null
  periodMode?: 'standard' | 'custom'
  status: CommunicationStatus
  frequency: CommunicationFrequency
  observations?: string
  proofs: CommunicationProof[]
  history: CommunicationHistory[]
  alerts: CommunicationAlert[]
  createdAt: string
  updatedAt: string
  createdBy: string
  siteId?: string
  planYear?: number
}

export interface CommunicationFormData {
  type: CommunicationType
  designation: string
  cibles: CommunicationTarget[]
  moyens: CommunicationMoyen[]
  chronogramme: boolean[]
  responsable: string
  organizerUserId?: number | null
  responsibleUserId?: number | null
  participantUserIds?: number[]
  processId?: number | null
  cout?: number
  dateDebut: string | null
  dateFin: string | null
  periodMode: 'standard' | 'custom'
  frequency: CommunicationFrequency
  observations?: string
  planYear?: number
}

export interface CommunicationFilters {
  status?: CommunicationStatus[]
  type?: CommunicationType[]
  frequency?: CommunicationFrequency[]
  year?: number
  search?: string
}

export interface CommunicationStats {
  total: number
  planifiees: number
  realisees: number
  enRetard: number
  annulees: number
  tauxRealisation: number
  budgetTotal: number
  budgetConsomme: number
}

export interface CommunicationPlan {
  id: number
  enterpriseId: number
  siteId?: number
  year: number
  status: 'draft' | 'active' | 'closed'
  plannedBudget?: number
  plannedActions?: number
  spentAmount: number
  budgetEngaged: number
  budgetRealized: number
  budgetRemaining: number
  stats: {
    totalActions: number
    planifiees: number
    replanifiees: number
    enAttente: number
    realisees: number
    annulees: number
    tauxRealisation: number
  }
  createdAt?: string
  updatedAt?: string
}
