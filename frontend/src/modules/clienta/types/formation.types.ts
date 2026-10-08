export type FormationFrequency
  = | 'ponctuelle'
    | 'annuelle'
    | 'semestrielle'
    | 'trimestrielle'
    | 'mensuelle'
    | 'biennale'
    | 'sur_demande'

export type FormationStatus
  = | 'planifiee'
    | 'en_attente'
    | 'realisee'
    | 'replanifiee'
    | 'annulee'

export type FormationTarget = string

export interface FormationProof {
  id: string
  filename: string
  url: string
  uploadedAt: string
  uploadedBy: string
}

export interface FormationHistory {
  id: string
  action: 'created' | 'updated' | 'completed' | 'rescheduled' | 'cancelled'
  date: string
  userId: string
  userName: string
  comment?: string
  previousDate?: string
  newDate?: string
}

export interface FormationAlert {
  id: string
  formationId: string
  type: 'J-30' | 'J-15' | 'J-7' | 'J-3' | 'J-1'
  date: string
  sent: boolean
  sentAt?: string
}

export interface FormationInternalEvaluation {
  id: string
  formationId: string
  evaluatorId?: string
  method: 'combinaison'
  scores?: {
    pedagogie?: number
    contenu?: number
    applicabilite?: number
    animation?: number
  }
  globalScore?: number
  strengths?: string[]
  improvements?: string[]
  comment?: string
  evaluatedAt?: string
  createdAt?: string
  updatedAt?: string
}

export interface Formation {
  id: string
  numero: number
  designation: string
  cibles: FormationTarget[]
  targetUserIds?: number[]
  targets?: Array<{ id: number, name: string, email?: string }>
  chronogramme?: boolean[]
  formateur: string
  formateurUserId?: number | null
  organizerUserId?: number | null
  processId?: number | null
  dateDebut?: string | null
  dateFin?: string | null
  periodMode?: 'standard' | 'custom'
  periodLabel?: string
  status: FormationStatus
  alertState?: 'active' | 'resolved' | 'expired'
  frequency: FormationFrequency
  observations?: string
  proofs: FormationProof[]
  history: FormationHistory[]
  alerts: FormationAlert[]
  internalEvaluation?: FormationInternalEvaluation
  createdAt: string
  updatedAt: string
  createdBy: string
  siteId?: string
  planYear?: number
}

export interface FormationFormData {
  designation: string
  targetUserIds: number[]
  formateur: string
  formateurUserId?: number | null
  organizerUserId?: number | null
  processId?: number | null
  dateDebut: string | null
  dateFin: string | null
  periodMode: 'standard' | 'custom'
  periodLabel?: string
  frequency: FormationFrequency
  observations?: string
  planYear?: number
}

export interface FormationFilters {
  status?: FormationStatus[]
  frequency?: FormationFrequency[]
  cibles?: FormationTarget[]
  year?: number | null
  search?: string
}

export interface FormationStats {
  total: number
  planifiees: number
  realisees: number
  enRetard: number
  annulees: number
  tauxRealisation: number
  budgetTotal: number
  budgetConsomme: number
}

export interface TrainingPlan {
  id: number
  enterpriseId: number
  siteId?: number
  year: number
  status: 'draft' | 'active' | 'closed'
  totalBudget: number
  spentAmount: number
  budgetEngaged: number
  budgetRealized: number
  budgetRemaining: number
  plannedFormations?: number
  stats: {
    totalFormations: number
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
