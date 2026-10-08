import type { Formation, FormationFormData, FormationStats } from '../types/formation.types'
import { computed, ref } from 'vue'
import { formationApi } from '@/api/formations'

export function useFormations () {
  const formations = ref<Formation[]>([])
  const loading = ref(false)
  const error = ref<string | null>(null)

  const stats = computed<FormationStats>(() => ({
    total: formations.value.length,
    planifiees: formations.value.filter(f => f.status === 'planifiee').length,
    realisees: formations.value.filter(f => f.status === 'realisee').length,
    enRetard: formations.value.filter(f => {
      if (!f.dateDebut && !f.dateFin) {
        return false
      }
      const today = new Date()
      const endDate = new Date(f.dateFin || f.dateDebut || '')
      if (Number.isNaN(endDate.getTime())) {
        return false
      }
      return endDate < today && ['planifiee', 'en_attente', 'replanifiee'].includes(f.status)
    }).length,
    annulees: formations.value.filter(f => f.status === 'annulee').length,
    tauxRealisation: formations.value.length > 0
      ? Math.round((formations.value.filter(f => f.status === 'realisee').length / formations.value.length) * 100)
      : 0,
    budgetTotal: 0,
    budgetConsomme: 0,
  }))

  const fetchFormations = async (params?: any) => {
    loading.value = true
    error.value = null
    try {
      formations.value = await formationApi.getAll(params)
    } catch (error_: any) {
      error.value = error_.message
    } finally {
      loading.value = false
    }
  }

  const createFormation = async (data: FormationFormData) => {
    loading.value = true
    error.value = null
    try {
      const created = await formationApi.create(data)
      formations.value.push(created)
      return created
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const updateFormation = async (id: string, data: Partial<FormationFormData>) => {
    loading.value = true
    error.value = null
    try {
      const updated = await formationApi.update(id, data)
      const index = formations.value.findIndex(f => f.id === id)
      if (index !== -1) {
        formations.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteFormation = async (id: string) => {
    loading.value = true
    error.value = null
    try {
      await formationApi.delete(id)
      formations.value = formations.value.filter(f => f.id !== id)
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const completeFormation = async (id: string, proofs: File[]) => {
    loading.value = true
    error.value = null
    try {
      const completed = await formationApi.complete(id, proofs)
      const index = formations.value.findIndex(f => f.id === id)
      if (index !== -1) {
        formations.value[index] = completed
      }
      return completed
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const rescheduleFormation = async (id: string, newDateDebut: string, newDateFin: string, comment?: string) => {
    loading.value = true
    error.value = null
    try {
      const updated = await formationApi.reschedule(id, newDateDebut, newDateFin, comment)
      const index = formations.value.findIndex(f => f.id === id)
      if (index !== -1) {
        formations.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const cancelFormation = async (id: string, reason: string) => {
    loading.value = true
    error.value = null
    try {
      const updated = await formationApi.cancel(id, reason)
      const index = formations.value.findIndex(f => f.id === id)
      if (index !== -1) {
        formations.value[index] = updated
      }
      return updated
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const uploadProof = async (formationId: string, file: File) => {
    loading.value = true
    error.value = null
    try {
      const proof = await formationApi.uploadProof(formationId, file)
      const formation = formations.value.find(f => f.id === formationId)
      if (formation) {
        formation.proofs.push(proof)
      }
      return proof
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  const deleteProof = async (formationId: string, proofId: string) => {
    loading.value = true
    error.value = null
    try {
      await formationApi.deleteProof(formationId, proofId)
      const formation = formations.value.find(f => f.id === formationId)
      if (formation) {
        formation.proofs = formation.proofs.filter(p => p.id !== proofId)
      }
    } catch (error_: any) {
      error.value = error_.message
      throw error_
    } finally {
      loading.value = false
    }
  }

  return {
    formations,
    loading,
    error,
    stats,
    fetchFormations,
    createFormation,
    updateFormation,
    deleteFormation,
    completeFormation,
    rescheduleFormation,
    cancelFormation,
    uploadProof,
    deleteProof,
  }
}
