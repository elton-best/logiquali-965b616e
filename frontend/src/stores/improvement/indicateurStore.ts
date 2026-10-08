import type { AddKpiValueDto, CreateKpiDto, Indicateur } from '@/types/improvement'
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { indicateurService } from '@/services/improvement/indicateurService'

export const useIndicateurStore = defineStore('indicateur', () => {
  const indicateurs = ref<Indicateur[]>([])
  const currentIndicateur = ref<Indicateur | null>(null)
  const loading = ref(false)
  const error = ref<string | null>(null)

  const withAlerts = computed(() =>
    indicateurs.value.filter(kpi => {
      const latest = kpi.historical_data?.at(-1)
      if (!latest) {
        return false
      }
      return (kpi.alert_above && latest.value > kpi.alert_above)
        || (kpi.alert_below && latest.value < kpi.alert_below)
    }),
  )

  const byAxe = computed(() => (axeId: number) =>
    indicateurs.value.filter(kpi => kpi.axes?.some(a => a.id === axeId)),
  )

  async function fetchAll (filters?: any) {
    loading.value = true
    try {
      const data = await indicateurService.getAll(filters)
      indicateurs.value = data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function fetchById (id: number) {
    loading.value = true
    try {
      const data = await indicateurService.getById(id)
      currentIndicateur.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur chargement'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function create (payload: CreateKpiDto) {
    loading.value = true
    try {
      const data = await indicateurService.create(payload)
      indicateurs.value.unshift(data)
      currentIndicateur.value = data
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur création'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function update (id: number, payload: Partial<Indicateur>) {
    loading.value = true
    try {
      const data = await indicateurService.update(id, payload)
      const index = indicateurs.value.findIndex(k => k.id === id)
      if (index !== -1) {
        indicateurs.value[index] = data
      }
      if (currentIndicateur.value?.id === id) {
        currentIndicateur.value = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur mise à jour'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function addValue (id: number, payload: AddKpiValueDto) {
    loading.value = true
    try {
      const data = await indicateurService.addValue(id, payload)
      const index = indicateurs.value.findIndex(k => k.id === id)
      if (index !== -1) {
        indicateurs.value[index] = data
      }
      if (currentIndicateur.value?.id === id) {
        currentIndicateur.value = data
      }
      return data
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur ajout valeur'
      throw error_
    } finally {
      loading.value = false
    }
  }

  async function getChartData (id: number) {
    try {
      return await indicateurService.getChartData(id)
    } catch (error_: any) {
      error.value = error_.response?.data?.message || 'Erreur graphique'
      throw error_
    }
  }

  return {
    indicateurs,
    currentIndicateur,
    loading,
    error,
    withAlerts,
    byAxe,
    fetchAll,
    fetchById,
    create,
    update,
    addValue,
    getChartData,
  }
})
