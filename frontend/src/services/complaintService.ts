import api from '@/api/client'

export interface ComplaintFormData {
  site_id: number
  title: string
  description: string
  category?: string
  priority?: 'low' | 'medium' | 'high' | 'urgent'
  customer_name?: string
  customer_email?: string
  customer_phone?: string
  customer_address?: string
  client_name?: string
  client_email?: string
  client_phone?: string
  client_company?: string
  wants_email_response?: boolean
  wants_mail?: boolean
  expected_solution?: string
  analysis?: string
  immediate_response?: string
  assigned_to?: number
  status?: 'pending' | 'in_progress' | 'resolved' | 'closed'
  received_date?: string
  due_date?: string
  actions?: Array<{
    type: 'corrective' | 'preventive' | 'improvement'
    description: string
    responsible_id: number
    deadline: string
    process_id?: number
    title?: string
  }>
}

export interface Complaint {
  id: number
  reference: string
  user_id: number
  site_id: number
  title: string
  description: string
  category?: string
  priority?: 'low' | 'medium' | 'high' | 'urgent'
  status: 'pending' | 'in_progress' | 'resolved' | 'closed'
  customer_name?: string
  customer_email?: string
  customer_phone?: string
  customer_address?: string
  client_name?: string
  client_email?: string
  client_phone?: string
  client_company?: string
  wants_email_response?: boolean
  wants_mail?: boolean
  expected_solution?: string
  recommandations?: string
  analysis?: string
  immediate_response?: string
  response?: string
  assigned_to?: number
  received_date?: string
  due_date?: string
  closed_date?: string
  created_at: string
  updated_at: string
  user?: {
    id: number
    name: string
    email: string
  }
  site?: {
    id: number
    name: string
    location?: string
    city?: string
  }
  assignedUser?: {
    id: number
    name: string
  }
  actions?: Array<{
    id: number
    type: string
    title?: string
    description?: string
    deadline?: string
    responsible?: { id: number, name: string }
  }>
}

export interface ComplaintCategory {
  value: string
  label: string
}

class ComplaintService {
  private mapCategoryForUi (value?: string): string | undefined {
    if (!value) {
      return value
    }
    const normalized = value.toLowerCase()
    const map: Record<string, string> = {
      product_quality: 'product',
      product_defect: 'product',
      service_quality: 'service',
      delivery_delay: 'delivery',
      delivery_error: 'delivery',
      documentation: 'other',
      packaging: 'other',
      billing: 'billing',
      communication: 'other',
      other: 'other',
    }
    return map[normalized] || value
  }

  private normalizeRelation (relation: any): any {
    if (!relation) {
      return undefined
    }
    if (Array.isArray(relation)) {
      return relation.map(item => this.normalizeRelation(item))
    }
    if (Array.isArray(relation.data)) {
      return relation.data.map(item => this.normalizeRelation(item))
    }
    if (relation.data) {
      return this.normalizeRelation(relation.data)
    }
    if (relation.attributes) {
      const base: any = {
        id: Number(relation.id ?? relation.attributes?.id),
        ...relation.attributes,
      }
      if (relation.relationships) {
        const relationships = relation.relationships || {}
        base.site = this.normalizeRelation(relationships.site)
        base.process = this.normalizeRelation(relationships.process)
        base.responsible = this.normalizeRelation(relationships.responsible)
        base.initiator = this.normalizeRelation(relationships.initiator)
        if (base.responsible?.id && !base.responsible_id) {
          base.responsible_id = base.responsible.id
        }
        if (base.process?.id && !base.process_id) {
          base.process_id = base.process.id
        }
      }
      return base
    }
    return relation
  }

  private normalizeComplaint (raw: any): Complaint {
    if (!raw) {
      return raw
    }
    if (raw.attributes) {
      const attrs = raw.attributes || {}
      const relationships = raw.relationships || {}
      return {
        id: Number(raw.id ?? attrs.id),
        ...attrs,
        category: this.mapCategoryForUi(attrs.category),
        reference: attrs.reference || attrs.ref || raw.reference || raw.ref || 'N/A',
        user: this.normalizeRelation(relationships.user) || attrs.user,
        site: this.normalizeRelation(relationships.site) || attrs.site,
        assignedUser: this.normalizeRelation(relationships.assigned_user || relationships.assignedUser) || attrs.assignedUser,
        actions: this.normalizeRelation(relationships.actions) || attrs.actions,
      }
    }
    return {
      ...raw,
      category: this.mapCategoryForUi(raw.category),
      reference: raw.reference || raw.ref || 'N/A',
    }
  }

  /**
   * Get all complaints for current user
   */
  async getComplaints (params?: {
    status?: string
    search?: string
    site_id?: number
    per_page?: number
    page?: number
    sort_by?: string
    sort_order?: 'asc' | 'desc'
  }): Promise<{ data: Complaint[], total: number, per_page: number, current_page: number }> {
    const { data } = await api.get('/complaints', { params })

    // Normalize data - ensure reference field is set
    const rawItems = Array.isArray(data.data) ? data.data : []
    const complaints = rawItems.map((c: any) => this.normalizeComplaint(c))

    return {
      data: complaints,
      total: data.meta?.total || data.total || 0,
      per_page: data.meta?.per_page || data.per_page || 10,
      current_page: data.meta?.current_page || data.current_page || 1,
    }
  }

  /**
   * Get single complaint
   */
  async getComplaint (id: number): Promise<Complaint> {
    const { data } = await api.get(`/complaints/${id}`)
    // Handle both data.data and direct data response
    const payload = data.data || data
    return this.normalizeComplaint(payload)
  }

  /**
   * Create new complaint
   */
  async createComplaint (complaintData: ComplaintFormData): Promise<Complaint> {
    const { data } = await api.post('/complaints', complaintData)
    const payload = data.data || data
    return this.normalizeComplaint(payload)
  }

  /**
   * Update complaint (only if status is pending for Client B)
   */
  async updateComplaint (id: number, complaintData: Partial<ComplaintFormData>): Promise<Complaint> {
    const { data } = await api.put(`/complaints/${id}`, complaintData)
    const payload = data.data || data
    return this.normalizeComplaint(payload)
  }

  /**
   * Delete complaint (only if status is pending for Client B)
   */
  async deleteComplaint (id: number): Promise<void> {
    await api.delete(`/complaints/${id}`)
  }

  /**
   * Add response to complaint (Client A only)
   */
  async addResponse (
    id: number,
    responseData: {
      response: string
      status?: 'pending' | 'in_progress' | 'resolved' | 'closed'
    },
  ): Promise<Complaint> {
    const { data } = await api.post(`/complaints/${id}/response`, responseData)
    return data.data
  }

  /**
   * Get complaint categories
   */
  async getCategories (): Promise<string[]> {
    try {
      const { data } = await api.get('/complaints-categories')
      return data.data || data || []
    } catch {
      // Return default categories if API fails
      return [
        'Service client',
        'Qualité produit',
        'Livraison',
        'Facturation',
        'Technique',
        'Autre',
      ]
    }
  }

  /**
   * Get status color for UI
   */
  getStatusColor (status: string): string {
    const colors: Record<string, string> = {
      pending: 'warning',
      in_progress: 'info',
      resolved: 'success',
      closed: 'grey',
    }
    return colors[status] || 'grey'
  }

  /**
   * Get status label in French
   */
  getStatusLabel (status: string): string {
    const labels: Record<string, string> = {
      pending: 'En attente',
      in_progress: 'En cours',
      resolved: 'Résolue',
      closed: 'Fermée',
    }
    return labels[status] || status
  }

  /**
   * Get status icon
   */
  getStatusIcon (status: string): string {
    const icons: Record<string, string> = {
      pending: 'mdi-clock-outline',
      in_progress: 'mdi-progress-clock',
      resolved: 'mdi-check-circle-outline',
      closed: 'mdi-archive-outline',
    }
    return icons[status] || 'mdi-help-circle-outline'
  }
}

export const complaintService = new ComplaintService()
export default complaintService
