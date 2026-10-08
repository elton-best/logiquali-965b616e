import api from '@/api/client'

export interface Site {
  id: number
  name: string
  code?: string
  address?: string
  is_active: boolean
}

export interface Process {
  id: number
  name: string
  code?: string
  description?: string
  is_active: boolean
}

export interface User {
  id: number
  name: string
  email: string
  user_type: string
  is_active: boolean
}

export interface Norm {
  id: number
  name: string
  code: string
  version?: string
  is_active: boolean
}

class ReferenceDataService {
  async getSites () {
    return api.get<{ data: Site[] }>('/sites')
  }

  async getProcesses () {
    return api.get<{ data: Process[] }>('/processes')
  }

  async getUsers () {
    return api.get<{ data: User[] }>('/users')
  }

  async getNorms () {
    return api.get<{ data: Norm[] }>('/norms')
  }

  async getAllReferenceData () {
    try {
      const [sitesRes, processesRes, usersRes, normsRes] = await Promise.all([
        this.getSites(),
        this.getProcesses(),
        this.getUsers(),
        this.getNorms(),
      ])

      return {
        sites: this.transformJsonApiData(sitesRes.data.data || []),
        processes: this.transformJsonApiData(processesRes.data.data || []),
        users: this.transformJsonApiData(usersRes.data.data || []),
        norms: this.transformJsonApiData(normsRes.data.data || []),
      }
    } catch (error) {
      console.error('Error loading reference data:', error)
      return {
        sites: [],
        processes: [],
        users: [],
        norms: [],
      }
    }
  }

  // Helper to transform JSON:API format to simple objects
  private transformJsonApiData (data: any[]): any[] {
    if (!Array.isArray(data)) {
      return []
    }

    return data.map(item => {
      // If already in simple format, return as-is
      if (!item.type || !item.attributes) {
        return item
      }

      // Transform JSON:API format {id, type, attributes} to {id, ...attributes}
      return {
        id: item.id,
        ...item.attributes,
      }
    })
  }
}

export default new ReferenceDataService()
