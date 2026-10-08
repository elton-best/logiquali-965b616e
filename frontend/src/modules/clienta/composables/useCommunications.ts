import type { Communication, CommunicationFormData, CommunicationStats } from '../types/communication.types'
import { computed, ref } from 'vue'
import { communicationApi } from '@/api/communications'

export function useCommunications () {
  const communications = ref<Communication[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const stats = computed<CommunicationStats>(() => ({
    total: communications.value.length,
    planifiees: communications.value.filter(c => c.status === 'planifiee').length,
    realisees: communications.value.filter(c => c.status === 'realisee').length,
    enRetard: communications.value.filter(c => {
      if (!c.dateDebut) {
        return false
      }
      const today = new Date()
      const startDate = new Date(c.dateDebut)
      if (Number.isNaN(startDate.getTime())) {
        return false
      }
      return startDate < today && c.status === 'en_attente'
    }).length,
    annulees: communications.value.filter(c => c.status === 'annulee').length,
    tauxRealisation: communications.value.length > 0
      ? Math.round((communications.value.filter(c => c.status === 'realisee').length / communications.value.length) * 100)
      : 0,
    budgetTotal: communications.value.reduce((sum, c) => sum + (c.cout || 0), 0),
    budgetConsomme: communications.value
      .filter(c => c.status === 'realisee')
      .reduce((sum, c) => sum + (c.cout || 0), 0),
  }))

  const fetchCommunications = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      const payload = await communicationApi.getAll(params) as any
      const list = Array.isArray(payload)
        ? payload
        : (Array.isArray(payload?.data)
            ? payload.data
            : [])
      communications.value = list
    } catch (error_: any) {
      error.value = error_.message
      communications.value = []
    } finally {
      loading.value = false
    }
  }

  const createCommunication = async (data: CommunicationFormData) => {
    loading.value = true
    error.value = null
    try {
      const created = await communicationApi.create(data)
      communications.value.push(created)
      return created
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateCommunication = async (id: string, data: Partial<CommunicationFormData>) => {
    loading.value = true
    error.value = null
    try {
      const updated = await communicationApi.update(id, data)
      const index = communications.value.findIndex(c => c.id === id)
      if (index !== -1) {
        communications.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteCommunication = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      await communicationApi.delete(id)
      communications.value = communications.value.filter(c => c.id !== id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const completeCommunication = async (id: string, proofs: File[]) => {
    loading.value = true
    error.value = null
    try {
      const completed = await communicationApi.complete(id, proofs)
      const index = communications.value.findIndex(c => c.id === id)
      if (index !== -1) {
        communications.value[index] = completed
      }
      return completed
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const rescheduleCommunication = async (id: string, newDateDebut: string, newDateFin: string, comment?: string) => {
    loading.value = true
    error.value = null
    try {
      const updated = await communicationApi.reschedule(id, newDateDebut, newDateFin, comment)
      const index = communications.value.findIndex(c => c.id === id)
      if (index !== -1) {
        communications.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const cancelCommunication = async (id: string, reason: string) => {
    loading.value = true
    error.value = null
    try {
      const updated = await communicationApi.cancel(id, reason)
      const index = communications.value.findIndex(c => c.id === id)
      if (index !== -1) {
        communications.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const uploadProof = async (communicationId: string, file: File) => {
    loading.value = true
    error.value = null
    try {
      const proof = await communicationApi.uploadProof(communicationId, file)
      const communication = communications.value.find(c => c.id === communicationId)
      if (communication) {
        communication.proofs = communication.proofs || []
        communication.proofs.push(proof)
      }
      return proof
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteProof = async (communicationId: string, proofId: string) => {
    loading.value = true
    error.value = null
    try {
      await communicationApi.deleteProof(communicationId, proofId)
      const communication = communications.value.find(c => c.id === communicationId)
      if (communication) {
        communication.proofs = communication.proofs.filter(p => p.id !== proofId)
      }
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    communications,
    loading,
    error,
    stats,
    fetchCommunications,
    createCommunication,
    updateCommunication,
    deleteCommunication,
    completeCommunication,
    rescheduleCommunication,
    cancelCommunication,
    uploadProof,
    deleteProof,
  }
}
