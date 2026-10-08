import apiClient from '@/api/client'

export interface CodificationElement {
  id?: number
  type: 'categorie' | 'localisation'
  code: string
  libelle: string
  description?: string
  actif: boolean
  created_at?: string
  updated_at?: string
}

export interface Equipement {
  id?: number
  code_complet: string
  categorie_id: number
  localisation_id: number
  nom_commun: string
  nom_commun_abrege: string
  indice: string
  annee_acquisition: number
  marque?: string
  modele?: string
  numero_serie?: string
  etat: 'tres_bon' | 'bon' | 'mauvais'
  valeur_acquisition?: number
  observations?: string
  necessite_maintenance: boolean
  frequence_maintenance_jours?: number
  actif: boolean
  categorie?: CodificationElement
  localisation?: CodificationElement
  created_at?: string
  updated_at?: string
}

export interface EquipementImportResult {
  success: boolean
  message: string
  summary: {
    total: number
    created: number
    updated: number
    failed: number
  }
  report: Array<{
    row: number
    status: 'created' | 'updated' | 'failed'
    message: string
    equipement_id?: number
    code_complet?: string
  }>
}

export interface Maintenance {
  id?: number
  equipement_id: number
  type: 'preventive' | 'corrective' | 'etalonnage'
  date_prevue: string
  date_realisee?: string
  statut: 'planifie' | 'en_cours' | 'realise' | 'reporte' | 'annule'
  description?: string
  responsable?: string
  observations?: string
  equipement?: Equipement
  suivis?: MaintenanceSuivi[]
  niveau_alerte?: string
  suivi_active?: boolean
  alert_state?: 'active' | 'resolved' | 'expired'
  created_at?: string
  updated_at?: string
}

export interface MaintenanceSuivi {
  id?: number
  maintenance_id: number
  user_id: number
  action: 'realise' | 'reporte'
  date_action: string
  nouvelle_date_prevue?: string
  commentaire?: string
  preuve_path?: string
  user?: any
  created_at?: string
  updated_at?: string
}

export interface MaintenanceImportResult {
  success: boolean
  message: string
  summary?: {
    total: number
    created: number
    failed: number
  }
  preview?: Array<{
    equipement: string
    type: string
    date_prevue: string
  }>
  errors?: string[]
  report?: Array<{
    row: number
    status: 'created' | 'failed'
    message: string
    maintenance_id?: number
    equipement?: string
  }>
}

class SupportService {
  // Codification
  async getCodifications (params?: { type?: string, actif?: boolean }) {
    const response = await apiClient.get('/codifications', { params })
    return response.data
  }

  async getCodification (id: number) {
    const response = await apiClient.get(`/codifications/${id}`)
    return response.data
  }

  async createCodification (data: Partial<CodificationElement>) {
    const response = await apiClient.post('/codifications', data)
    return response.data
  }

  async updateCodification (id: number, data: Partial<CodificationElement>) {
    const response = await apiClient.put(`/codifications/${id}`, data)
    return response.data
  }

  async deleteCodification (id: number) {
    const response = await apiClient.delete(`/codifications/${id}`)
    return response.data
  }

  // Équipements
  async getEquipements (params?: any) {
    const response = await apiClient.get('/equipements', { params })
    return response.data
  }

  async getEquipement (id: number) {
    const response = await apiClient.get(`/equipements/${id}`)
    return response.data
  }

  async createEquipement (data: Partial<Equipement>) {
    const response = await apiClient.post('/equipements', data)
    return response.data
  }

  async updateEquipement (id: number, data: Partial<Equipement>) {
    const response = await apiClient.put(`/equipements/${id}`, data)
    return response.data
  }

  async deleteEquipement (id: number) {
    const response = await apiClient.delete(`/equipements/${id}`)
    return response.data
  }

  async getProchainIndice (nomCommun: string) {
    const response = await apiClient.get('/equipements/prochain-indice', {
      params: { nom_commun: nomCommun },
    })
    return response.data
  }

  async importEquipements (formData: FormData): Promise<EquipementImportResult> {
    const response = await apiClient.post('/equipements/import-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  // Maintenances
  async getAlertesMaintenance () {
    const response = await apiClient.get('/maintenances/alertes')
    return response.data
  }

  async getMaintenances (params?: any) {
    const response = await apiClient.get('/maintenances', { params })
    return response.data
  }

  async getMaintenance (id: number) {
    const response = await apiClient.get(`/maintenances/${id}`)
    return response.data
  }

  async createMaintenance (data: Partial<Maintenance>) {
    const response = await apiClient.post('/maintenances', data)
    return response.data
  }

  async updateMaintenance (id: number, data: Partial<Maintenance>) {
    const response = await apiClient.put(`/maintenances/${id}`, data)
    return response.data
  }

  async deleteMaintenance (id: number) {
    const response = await apiClient.delete(`/maintenances/${id}`)
    return response.data
  }

  async suivreMaintenance (id: number, data: FormData) {
    const response = await apiClient.post(`/maintenances/${id}/suivre`, data, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  async importMaintenances (formData: FormData): Promise<MaintenanceImportResult> {
    const response = await apiClient.post('/maintenances/import-file', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data
  }

  async exportMaintenances () {
    const response = await apiClient.get('/maintenances/export', {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', `plan_maintenance_${new Date().toISOString().split('T')[0]}.xlsx`)
    document.body.append(link)
    link.click()
    link.remove()
  }

  async downloadMaintenanceTemplate () {
    const response = await apiClient.get('/maintenances/template', {
      responseType: 'blob',
    })
    const url = window.URL.createObjectURL(new Blob([response.data]))
    const link = document.createElement('a')
    link.href = url
    link.setAttribute('download', 'modele_import_maintenances.xlsx')
    document.body.append(link)
    link.click()
    link.remove()
  }
}

export default new SupportService()
