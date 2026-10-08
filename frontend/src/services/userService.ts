import { apiClient } from '../api/client'

export interface Role {
  [key: string]: any
  id: number
  name: string
}

export interface User {
  [key: string]: any
  id: number
  first_name?: string
  last_name?: string
  name?: string
  email: string
  status?: string
  photo_path?: string
  photo_url?: string
  job_description_id?: string | number | null
  site_id?: string | number | null
  roles?: Role[]
}

export interface CreateUserRequest {
  [key: string]: any
  first_name?: string
  last_name?: string
  email: string
  password?: string
  role_ids?: Array<string | number>
}

export interface UpdateUserRequest extends Partial<CreateUserRequest> {}

export const userService = {
  async getAll (params?: Record<string, any>) {
    const response = await apiClient.get('/users', { params })
    return response.data.data
  },

  async getById (id: string | number) {
    const response = await apiClient.get(`/users/${id}`)
    return response.data.data
  },

  async getJobDescriptions () {
    const response = await apiClient.get('/job-descriptions')
    return response.data.data
  },

  async getUsersByJob (jobId: string | number) {
    const response = await apiClient.get(`/users?job_description_id=${jobId}`)
    return response.data.data
  },

  async getBySite (siteId: string | number) {
    const response = await apiClient.get('/users', { params: { site_id: siteId } })
    return response.data.data
  },

  async create (data: CreateUserRequest) {
    const response = await apiClient.post('/users', data)
    return response.data.data
  },

  async update (id: string | number, data: UpdateUserRequest) {
    const response = await apiClient.put(`/users/${id}`, data)
    return response.data.data
  },

  async toggleActive (id: string | number) {
    const response = await apiClient.post(`/users/${id}/toggle-active`)
    return response.data.data
  },

  async delete (id: string | number) {
    await apiClient.delete(`/users/${id}`)
  },

  async getRoles () {
    const response = await apiClient.get('/roles')
    return response.data.data
  },

  async getProfile () {
    const response = await apiClient.get('/profile')
    return response.data.data
  },

  async updateProfile (data: Partial<User>) {
    const response = await apiClient.put('/profile', data)
    return response.data.data
  },

  async uploadPhoto (id: string | number, file: File) {
    const formData = new FormData()
    formData.append('photo', file)
    const response = await apiClient.post(`/users/${id}/photo`, formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    })
    return response.data.data
  },
}

export default userService
